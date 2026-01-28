<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($isEmailError) && $isEmailError ? __('Email Server Error') : __('Server Error') }}</title>
    <style>
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-container {
            max-width: 600px;
            padding: 2rem;
            text-align: center;
        }
        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #e53e3e;
            margin: 0;
        }
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin: 1rem 0;
        }
        .error-message {
            font-size: 1rem;
            color: #4a5568;
            margin: 1rem 0;
            line-height: 1.6;
        }
        .error-details {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 0.25rem;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        .error-details strong {
            color: #856404;
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        .error-details p {
            margin: 0;
            color: #856404;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.75rem 1.5rem;
            background-color: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 0.25rem;
            transition: background-color 0.2s;
            font-size: 0.95rem;
        }
        .back-link:hover {
            background-color: #3182ce;
        }
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a202c;
            }
            .error-code {
                color: #fc8181;
            }
            .error-title {
                color: #e2e8f0;
            }
            .error-message {
                color: #cbd5e0;
            }
            .error-details {
                background-color: #2d3748;
                border-color: #4a5568;
            }
            .error-details strong {
                color: #fbd38d;
            }
            .error-details p {
                color: #e2e8f0;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-code">500</h1>

        @if(isset($isEmailError) && $isEmailError)
            <h2 class="error-title">Email Server Error</h2>
            <p class="error-message">
                Failed to send email. The system was unable to communicate with the email server.
            </p>

            <div class="error-details">
                <strong>Error Details:</strong>
                <p>{{ $errorDetails ?? 'Failed to send email. Please check the email server configuration and try again.' }}</p>
            </div>
        @else
            <h2 class="error-title">Server Error</h2>
            <p class="error-message">
                Something went wrong on our end. Please try again later.
            </p>
        @endif

        <a href="{{ url()->previous() }}" class="back-link">Go Back</a>
    </div>
</body>
</html>
