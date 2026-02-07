<?php

namespace App\Http\Controllers\Auth;

use App\Events\Authenticated;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    use ThrottlesLogins;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Login username to be used by the controller.
     *
     * @var string
     */
    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest')->except('logout');

        $this->username = $this->findUsername();
    }

    /**
     * Get the login username to be used by the controller.
     */
    public function findUsername(): string {
        $login = request()->input('login');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    public function username() {
        return $this->username;
    }

    // Login
    public function showLoginForm(Request $request) {
        $pageConfigs = [
            'bodyClass' => 'bg-full-screen-image',
            'blankPage' => true,
        ];

        // Check if user was redirected due to session timeout
        $timeoutMessage = null;
        if ($request->has('timeout') && $request->get('timeout') == '1') {
            $timeoutMessage =
                'Your session has expired due to inactivity. Please log in again.';
        }

        return view('/auth/login', [
            'pageConfigs' => $pageConfigs,
            'timeoutMessage' => $timeoutMessage,
        ]);
    }

    protected function authenticated(Request $request, User $user) {
        $request->session()->put('loggedIn_user', $user);

        // Set last_activity immediately after login to prevent CSRF token issues
        $request->session()->put('last_activity', now()->timestamp);

        if (
            empty($user->password_change_at) &&
            empty($user->detail->last_logged_in)
        ) {
            return redirect(route('profile.password', $user));
        }
        event(new Authenticated($user));

        if ($user->hasRole('Student')) {
            Auth::logoutOtherDevices($request->password);

            /*if(empty($user->password_change_at) && !empty($user->email_verified_at)){
                $status = Password::sendResetLink(
                    ['email' => $user->email]
                );
                Auth::logout();

                return $request->wantsJson()
                    ? new JsonResponse([], 204)
                    :  ($status === Password::RESET_LINK_SENT
                        ? redirect(route('login'))->with(['status' =>  __($status)])
                        : redirect(route('login'))->withErrors(['email' => __($status)]));
            }*/

            if (!empty($user->detail->onboard_at)) {
                $request->session()->put('student_id', $user->id);

                return redirect(route('frontend.dashboard'));
            } else {
                return redirect(route('frontend.onboard.create', ['step' => 1, 'resumed' => 1]));
            }
        }

        if ($user->hasRole('Leader')) {
            if (empty($user->detail->onboard_at)) {
                return redirect(route('account_manager.leaders.onboard'));
            }
        }

        return redirect($this->redirectTo);
    }

    protected function validateLogin(Request $request) {
        $this->validate(
            $request,
            [
                $this->username() => Rule::exists('users')->where(function ($query) use ($request) {
                    return $query
                        ->where($this->username(), $request->{$this->username})
                        ->where('is_active', 1);
                }),

                //                'exists:users,' . $this->username() . ',is_active,1',
                'password' => 'required|string',
            ],
            [
                $this->username() .
                '.exists' => 'These credentials do not match our records.',
            ]
        );
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request) {
        $this->guard()->logout();

        // Explicitly clear last_activity to prevent stale data
        $request->session()->forget('last_activity');

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Check if logout was due to timeout
        $timeout = $request->input('timeout', 0);

        if ($request->wantsJson()) {
            return new JsonResponse([], 204);
        }

        // Redirect to login with timeout parameter if applicable
        if ($timeout == '1') {
            return redirect()->route('login', ['timeout' => 1]);
        }

        return redirect('/');
    }
}
