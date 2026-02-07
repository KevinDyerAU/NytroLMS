<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Swift_TransportException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });
        //        $this->reportable(function (Throwable $exception, $request) {
        // //
        //        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson()) {   // add Accept: application/json in request
            return $this->handleApiException($request, $exception);
        }
        $response = $this->handleException($request, $exception);
        if (is_array($response) && !empty($response['code'])) {
            return redirect()->back()->with($response);
        }

        if ($response instanceof Response) {
            return $response;
        }

        //        if($exception['code'] === 1062){
        //            $response = response( $exception, 409 );
        //            return Router::toResponse( $request, $exception );
        //        }
        // dd($exception);
        return parent::render($request, $exception);
    }

    private function handleException($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);

        // Handle 419 CSRF token mismatch - redirect to login
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }

        // Check if it's an HTTP exception with 419 status code
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $exception->getStatusCode() === 419) {
            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }

        if ($exception instanceof \Illuminate\Database\QueryException) {
            $errorCode = $exception->errorInfo[1];
            session()->flash('error', urldecode($exception->getPrevious()->getMessage()));

            return ['message' => $exception->getMessage(), 'code' => $errorCode ?? null];
        }

        // Handle 419 CSRF token mismatch errors - redirect to login
        if ($exception instanceof HttpException && $exception->getStatusCode() === 419) {
            Log::error("419 Error - Log in again", ['exception' => $exception]);
            return redirect()->route('login')->withErrors([
                'email' => 'Your session has expired (419 error). Please log in again to resume'
            ]);
        }

        if ($this->isEmailException($exception)) {
            $errorMessage = $this->getEmailErrorMessage($exception);
            session()->flash('error', $errorMessage);
            return response()->view('errors.500', [
                'isEmailError' => true,
                'message' => 'Email Server Error',
                'errorDetails' => $errorMessage
            ], 500);
        }

        return $exception;
    }

    private function handleApiException($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);
        //        dump($exception);
        if ($exception instanceof HttpResponseException) {
            $exception = $exception->getResponse();
            //            dd('HttpResponseException');
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            $exception = $this->unauthenticated($request, $exception);
            //            dd('AuthenticationException');
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            $exception = $this->convertValidationExceptionToResponse($exception, $request);
            //            dd('ValidationException');
        }

        if ($exception instanceof \Illuminate\Database\QueryException) {
            $error_code = $exception->errorInfo[1];
            if ($error_code == 1062) {
                session()->flash('error', $exception->getMessage());
            }
            $exception = $exception->getMessage();
            //            dd($exception, $error_code);
        }

        //        dd('unhandled');
        return ($exception instanceof JsonResponse) ? $exception : $this->customApiResponse($exception);
    }

    private function customApiResponse(Throwable $exception)
    {
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        $response = [];

        if ($this->isEmailException($exception)) {
            $response['message'] = $this->getEmailErrorMessage($exception);
        } else {
            $response['message'] = ($statusCode == 500) ? 'Whoops, looks like something went wrong' :
                (!empty($exception->getPrevious()) ? $exception->getPrevious()->getMessage() :
                    $exception->getMessage());
        }

        if (config('app.debug')) {
            $response['error'] = $exception->errorInfo ?? $exception->getCode();
            $response['trace'] = $exception->getTrace();
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
        }
        $response['code'] = intval($statusCode) + 300;
        $response['status'] = 'error';
        $response['success'] = false;

        return response()->json($response, $statusCode);
    }

    private function isEmailException(Throwable $exception): bool
    {
        if ($exception instanceof TransportException || $exception instanceof Swift_TransportException) {
            return true;
        }

        $previous = $exception->getPrevious();
        if ($previous && ($previous instanceof TransportException || $previous instanceof Swift_TransportException)) {
            return true;
        }

        $message = strtolower($exception->getMessage());
        $emailKeywords = ['mail', 'smtp', 'transport', 'connection refused', 'could not connect', 'failed to authenticate'];

        foreach ($emailKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function getEmailErrorMessage(Throwable $exception): string
    {
        $message = $exception->getMessage();
        $lowerMessage = strtolower($message);

        // Check for specific error patterns and provide helpful messages
        if (str_contains($lowerMessage, 'connection refused') || str_contains($lowerMessage, 'could not connect')) {
            return 'Failed to send email: Unable to connect to the email server. Please advise Admin';
        }

        if (str_contains($lowerMessage, 'failed to authenticate') || str_contains($lowerMessage, 'authentication failed') || str_contains($lowerMessage, 'invalid credentials')) {
            return 'Failed to send email: Authentication failed. Please advise Admin';
        }

        if (str_contains($lowerMessage, 'tls') || str_contains($lowerMessage, 'ssl') || str_contains($lowerMessage, 'encryption')) {
            return 'Failed to send email: TLS/SSL connection error. Please advise Admin';
        }

        if (str_contains($lowerMessage, 'timeout') || str_contains($lowerMessage, 'timed out')) {
            return 'Failed to send email: Connection timeout. The email server is not responding. Please advise Admin';
        }

        if (str_contains($lowerMessage, 'address') && (str_contains($lowerMessage, 'invalid') || str_contains($lowerMessage, 'not found'))) {
            return 'Failed to send email: Invalid email address or recipient not found. Please verify the recipient email address.';
        }

        // Default message for other email errors
        return 'Failed to send email: ' . $message . '. Please check the email server configuration.';
    }
}
