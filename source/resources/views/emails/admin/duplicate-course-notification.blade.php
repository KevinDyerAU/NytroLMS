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
    <title>Duplicate Course Assignment - Manual Review Required</title>
    <link
        href="https://fonts.googleapis.com/css?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700"
        rel="stylesheet" media="screen">
    <style>
        .hover-underline:hover {
            text-decoration: underline !important;
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
    <div style="display: none;">Duplicate Course Assignment Notification</div>
    <div role="article" aria-roledescription="email" aria-label="Duplicate Course Assignment Notification" lang="en">
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
                                            <h2
                                                style="font-weight: 600; font-size: 20px; margin-bottom: 20px; color: #d32f2f;">
                                                ⚠️ Duplicate Course Assignment Alert</h2>

                                            <p style="margin: 0 0 24px;">
                                                An attempt was made to assign a course to an existing student who has already been enrolled in this course.
                                            </p>

                                            <div
                                                style="background-color: #e3f2fd; padding: 20px; border-radius: 4px; margin: 24px 0;">
                                                <h3 style="margin: 0 0 16px; font-size: 16px; color: #333;">Student
                                                    Information:</h3>
                                                <p style="margin: 8px 0;"><strong>Student ID:</strong>
                                                    {{ $student->id }}</p>
                                                <p style="margin: 8px 0;"><strong>Name:</strong>
                                                    {{ $student->first_name }} {{ $student->last_name }}</p>
                                                <p style="margin: 8px 0;"><strong>Email:</strong>
                                                    {{ $student->email }}</p>
                                                <p style="margin: 8px 0;"><strong>Phone:</strong>
                                                    {{ $student->detail->phone ?? '-' }}</p>
                                                <p style="margin: 8px 0;"><strong>Account Created: </strong>&#8203;
                                                    {{ $student->created_at }}</p>
                                                <p style="margin: 8px 0;"><strong>Account Status:</strong>
                                                    {{ $student->is_active ? 'Active' : 'Inactive' }}</p>
                                            </div>

                                            <div
                                                style="background-color: #e3f2fd; padding: 20px; border-radius: 4px; margin: 24px 0;">
                                                <h3 style="margin: 0 0 16px; font-size: 16px; color: #333;">Course
                                                    Assignment Details:</h3>
                                                <p style="margin: 8px 0;"><strong>Course:</strong> {{ $course->title }}
                                                </p>
                                                <p style="margin: 8px 0;"><strong>Course ID:</strong> {{ $course->id }}
                                                </p>
                                                <p style="margin: 8px 0;"><strong>Purchase Order Number:</strong>
                                                    {{ $registrationData['purchase_order'] ?? '-' }}</p>
                                                <p style="margin: 8px 0;"><strong>Learning Schedule:</strong>
                                                    {{ $registrationData['schedule'] ?? '-' }}</p>
                                                <p style="margin: 8px 0;"><strong>Employment Service:</strong>
                                                    {{ $registrationData['employment_service'] ?? '-' }}</p>
                                                <p style="margin: 8px 0;"><strong>Restrict to Semester 1:</strong>
                                                    {{ isset($registrationData['allowed_to_next_course']) ? ($registrationData['allowed_to_next_course'] ? 'Yes' : 'No') : 'Not specified' }}
                                                </p>
                                                <p style="margin: 8px 0;"><strong>Registered By:</strong>
                                                    {{ $registeredBy->name }} ({{ $registeredBy->email }})</p>
                                                <p style="margin: 8px 0;"><strong>Assignment Date:</strong>
                                                    {{ now()->format('j F, Y g:i A') }}</p>
                                            </div>

                                            <p style="margin: 20px 0 16px; font-size: 12px; color: #999;">
                                                This is an automated message. Please do not reply to this email.
                                            </p>
                                        </td>
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
