<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <style>
        td, th, div, p, a, h1, h2, h3, h4, h5, h6 {
            font-family: "Segoe UI", sans-serif;
            mso-line-height-rule: exactly;
        }
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
                <table class="sm-w-full" style="font-family: 'Montserrat',Arial,sans-serif; width: 600px;" width="600"
                       cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="" style="padding: 24px 0 0; text-align: left;background-color: #FFFFFF">
                            <a href="{{ env('APP_URL') }}" style="">
                                <img src="{{ asset('images/anaconda/logo-symbol.png') }}" width="20%" alt="ANACONDA"
                                     style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;width: 20%;">
                                <img src="{{ asset('images/anaconda/logo-text.png') }}" width="75%" alt="ANACONDA"
                                     style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;width: 75%;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <img src="{{ asset('images/anaconda/banner.png') }}" width="100%" alt="Anaconda Academy"
                                 style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;width: 100%;">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <img src="{{ asset('images/anaconda/slogan.png') }}" width="100%" alt="INSPIRE.EQUIP.ENABLE"
                                 style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;width: 100%;">
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
                                        <p style="font-weight: 700; font-size: 20px; margin-top: 0; --text-opacity: 1;  color: orange; text-align: center;">
                                            Hi {{ \Str::title($notifiable->name) }},</p>
                                        <p style="font-weight: 700; font-size: 20px; margin-top: 0; --text-opacity: 1;  color: orange; text-align: center;">
                                            <strong>Welcome to Anaconda Academy</strong> and congratulations on your
                                            enrolment!</p>
                                        <p style="margin: 0 0 24px;">
                                            You can now access the Anaconda Academy Learning Portal.
                                        </p>
                                        <p style="margin: 0 0 24px;">To login and get started with your training, please
                                            follow
                                            the login link below using your details: </p>
                                        <table style="font-family: 'Montserrat',Arial,sans-serif;" cellpadding="0"
                                               cellspacing="0" role="presentation">
                                            <tr>
                                                <td>Username:</td>
                                                <td>&nbsp;</td>
                                                <td>{{ $notifiable->username }}</td>
                                            </tr>
                                            <tr>
                                                <td>Password:</td>
                                                <td>&nbsp;</td>
                                                <td>{{ $password }}</td>
                                            </tr>
                                        </table>
                                        <table style="font-family: 'Montserrat',Arial,sans-serif;" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr><td style="font-family: 'Montserrat',Arial,sans-serif; padding-top: 12px; padding-bottom: 12px;"></td></tr>
                                        </table>
                                        @if(!empty($notifiable->courseEnrolments))
                                            @foreach($notifiable->courseEnrolments as $courseEnrolments)
                                                @if(!( Str::contains(Str::lower($courseEnrolments->course->title), ['semester 2', 'semester2', 'semester-2'])))
                                                <table style="font-family: 'Montserrat',Arial,sans-serif;" cellpadding="0" cellspacing="0" role="presentation">
                                                    <tr>
                                                        <td>Course Name: </td>
                                                        <td>&nbsp;</td>
                                                        <td><strong>{{ $courseEnrolments->course->title }}</strong></td>
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
                                            <a href="{{ env('APP_URL') }}"
                                               style="cursor: pointer;font-weight: 700;border-color: #ff9f43 !important;background-color: #ff9f43 !important;color: #fff !important;text-align: center;vertical-align: middle;padding: 0.786rem 1.5rem;font-size: 1rem;border-radius: 0.358rem;"
                                               title="{{ env('APP_NAME') }}">LOGIN</a>
                                        </p>

                                        <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;"
                                               width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td style="font-family: 'Montserrat',Arial,sans-serif; padding-top: 32px; padding-bottom: 32px;">
                                                    <div
                                                        style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); height: 1px; line-height: 1px;">
                                                        &zwnj;
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin: 24px 0;"><strong>Support</strong></p>
                                        <p style="margin: 24px 0;">
                                            Contact by phone on 0480 433 497,
                                            or via email at <a href="anacondaacademy@anaconda.com.au">anacondaacademy@anaconda.com.au</a>
                                            - we are here to help.
                                        </p>
                                        <p style="margin: 0 0 16px;">Thanks, <br>Anaconda Academy Admin</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 32px; background-color: #fff;text-align: center">
                                        <img src="{{ asset('images/anaconda/stamp.png') }}" width="155" alt="DLS"
                                             style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
                                        <img
                                            src="{{ config('settings.site.site_logo', asset('images/logo/logo.png')) }}"
                                            width="155" alt="{{ env('APP_NAME') }}"
                                            style="border: 0; max-width: 100%; line-height: 100%; vertical-align: middle;">
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
