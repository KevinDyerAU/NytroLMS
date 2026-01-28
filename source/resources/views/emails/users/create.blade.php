<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--[if mso]>
    <xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml>
    <style>
        td,th,div,p,a,h1,h2,h3,h4,h5,h6 {font-family: "Segoe UI", sans-serif; mso-line-height-rule: exactly;}
    </style>
    <![endif]-->
    <title>Welcome to {{ env('APP_NAME') }}</title>
    <link
        href="https://fonts.googleapis.com/css?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700"
        rel="stylesheet" media="screen">
    <style>
        .hover-underline:hover {
            text-decoration: underline !important;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @keyframes ping {

            75%,
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        @keyframes pulse {
            50% {
                opacity: .5;
            }
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
            }

            50% {
                transform: none;
                animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
            }
        }

        @media (max-width: 600px) {
            .sm-px-24 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .sm-py-32 {
                padding-top: 32px !important;
                padding-bottom: 32px !important;
            }

            .sm-w-full {
                width: 100% !important;
            }
        }
    </style>
</head>

<body
    style="margin: 0; padding: 0; width: 100%; word-break: break-word; -webkit-font-smoothing: antialiased; --bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity));">
    <div style="display: none;">Welcome to {{ env('APP_NAME') }} as <strong>{{ $role }}</strong></div>
    <div role="article" aria-roledescription="email" aria-label="Welcome" lang="en">
        <table style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; width: 100%;" width="100%"
            cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center"
                    style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;"
                    bgcolor="rgba(236, 239, 241, var(--bg-opacity))">
                    <table class="sm-w-full" style="font-family: 'Montserrat',Arial,sans-serif; width: 600px;"
                        width="600" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                            <td class="sm-py-32 sm-px-24"
                                style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; padding: 48px; text-align: center;"
                                align="center">
                                <a href="{{ env('APP_URL') }}">
                                    <img src="{{ config('settings.site.site_logo', asset('images/logo/logo.png')) }}"
                                        width="155" alt="{{ env('APP_NAME') }}"
                                        style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" class="sm-px-24" style="font-family: 'Montserrat',Arial,sans-serif;">
                                <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;" width="100%"
                                    cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td class="sm-px-24"
                                            style="--bg-opacity: 1; background-color: #ffffff; background-color: rgba(255, 255, 255, var(--bg-opacity)); border-radius: 4px; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 14px; line-height: 24px; padding: 48px; text-align: left; --text-opacity: 1; color: #626262; color: rgba(98, 98, 98, var(--text-opacity));"
                                            bgcolor="rgba(255, 255, 255, var(--bg-opacity))" align="left">
                                            <p
                                                style="font-weight: 700; font-size: 20px; margin-top: 0; --text-opacity: 1; color: #ff5850; color: rgba(255, 88, 80, var(--text-opacity));">
                                                <span style="color:#000000;">Hi
                                                </span>{{ \Str::title($notifiable->name) }},
                                            </p>
                                            <p
                                                style="font-weight: 700; font-size: 20px; margin-top: 0; --text-opacity: 1; color: #ff5850; color: rgba(255, 88, 80, var(--text-opacity));">
                                                <strong>Welcome to {{ env('APP_NAME') }}</strong>
                                            </p>
                                            <p style="margin: 0 0 24px;">
                                                You can now access
                                                <span style="font-weight: 600;">{{ env('APP_NAME') }}</span> as
                                                {{ $role }}.
                                            </p>
                                            <p style="margin: 0 0 24px;">To access and get started with your training,
                                                please follow this link:
                                                <a href="{{ env('APP_URL') }}">{{ env('APP_URL') }}</a> and click the
                                                login button located in the top right corner of the page.
                                            </p>

                                            <table style="font-family: 'Montserrat',Arial,sans-serif;" cellpadding="0"
                                                cellspacing="0" role="presentation">
                                                <tr>
                                                    <td
                                                        style="font-family: 'Montserrat',Arial,sans-serif; padding-top: 12px; padding-bottom: 12px;">
                                                    </td>
                                                </tr>
                                            </table>
                                            @if (!empty($notifiable->courseEnrolments))
                                                @foreach ($notifiable->courseEnrolments as $courseEnrolments)
                                                    @if (!Str::contains(Str::lower($courseEnrolments->course->title), ['semester 2', 'semester2', 'semester-2']))
                                                        <table style="font-family: 'Montserrat',Arial,sans-serif;"
                                                            cellpadding="0" cellspacing="0" role="presentation">
                                                            <tr>
                                                                <td>Course Name: </td>
                                                                <td>&nbsp;</td>
                                                                <td><strong>{{ $courseEnrolments->course->title }}</strong>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Start Date: </td>
                                                                <td>&nbsp;</td>
                                                                <td>{{ $courseEnrolments->course_start_at }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td>End Date: </td>
                                                                <td>&nbsp;</td>
                                                                <td>{{ $courseEnrolments->course_ends_at }}</td>
                                                            </tr>
                                                        </table>
                                                    @endif
                                                @endforeach
                                            @endif
                                            <p style="margin: 24px 0;">
                                                Login here:
                                                <a href="{{ env('APP_URL') }}"
                                                    title="{{ env('APP_NAME') }}">{{ env('APP_NAME') }}</a>
                                            </p>
                                            <p>
                                                Your login details are listed below:
                                            </p>
                                            <table style="font-family: 'Montserrat',Arial,sans-serif;" cellpadding="0"
                                                cellspacing="0" role="presentation">
                                                <tr>
                                                    <td>Username: </td>
                                                    <td>&nbsp;</td>
                                                    <td>{{ $notifiable->username }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Password: </td>
                                                    <td>&nbsp;</td>
                                                    <td>{{ $password }}</td>
                                                </tr>
                                            </table>

                                            <p style="margin: 24px 0;">
                                                <strong>Note:</strong> This is one time password, you must set new
                                                password to use the system.
                                            </p>

                                            <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;"
                                                width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td
                                                        style="font-family: 'Montserrat',Arial,sans-serif; padding-top: 32px; padding-bottom: 32px;">
                                                        <div
                                                            style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); height: 1px; line-height: 1px;">
                                                            &zwnj;</div>
                                                    </td>
                                                </tr>
                                            </table>

                                            <p style="margin: 24px 0;"><strong>Support?</strong></p>
                                            <p style="margin: 24px 0;">
                                                If you require assistance, please feel free to contact our team on
                                                {{ config('settings.site.institute_phone', '1300 471 660') }},
                                                Monday to Friday from 9am - 5pm (AEST). You can also email us at <a
                                                    href="mailto:{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}">{{ config('settings.site.institute_email', 'admin@keyinstitute.com.au') }}</a>.
                                            </p>
                                            <p style="margin: 0 0 16px;">Thanks, <br>Admin Team</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: 'Montserrat',Arial,sans-serif; height: 20px;"
                                            height="20">
                                            <p>Please note this is an automated email, please do not reply </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td
                                            style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 12px; padding-left: 48px; padding-right: 48px; --text-opacity: 1; color: #eceff1; color: rgba(236, 239, 241, var(--text-opacity));">

                                            <p
                                                style="--text-opacity: 1; color: #263238; color: rgba(38, 50, 56, var(--text-opacity));">
                                                Use of our service and website is subject to our
                                                <a href="{{ route('usage-terms') }}" class="hover-underline"
                                                    style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">Terms
                                                    of Use</a> and
                                                <a href="{{ url('content/privacy_policy') }}" class="hover-underline"
                                                    style="--text-opacity: 1; color: #7367f0; color: rgba(115, 103, 240, var(--text-opacity)); text-decoration: none;">Privacy
                                                    Policy</a>.
                                            </p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: 'Montserrat',Arial,sans-serif; height: 16px;"
                                            height="16"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
