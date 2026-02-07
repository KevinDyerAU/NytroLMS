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
    <div style="display: none;">Your Quiz Assessment Result:</div>
    <div role="article" aria-roledescription="email" aria-label="Details" lang="en">
        <table style="font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; width: 100%;" width="100%"
            cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td align="center"
                    style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;"
                    bgcolor="rgba(236, 239, 241, var(--bg-opacity))">
                    <table class="sm-w-full" style="font-family: 'Montserrat',Arial,sans-serif; width: 90%;"
                        width="90%" cellpadding="0" cellspacing="0" role="presentation">
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

                                            <div class="container"
                                                style="font-family: 'Montserrat',Arial,sans-serif; font-size: 14px;">
                                                <div class="header">
                                                    <h4 class="card-title"
                                                        style='font-weight: 500;font-size: 1.285rem;color: #596cb1 !important;margin: 0;'>
                                                        <small style='font-size: 0.857rem;'>Quiz:</small>
                                                        {{ $attempt->quiz->title }}
                                                    </h4>
                                                    <h6 class="text-secondary"
                                                        style='font-weight: 500;font-size: 1rem;color: #82868b !important;margin: 0;'>
                                                        <small style='font-size: 0.857rem;'>Topic/Lesson/Course:</small>
                                                        {{ $attempt->topic->title . ' / ' . $attempt->lesson->title . ' / ' . $attempt->course->title }}
                                                    </h6>
                                                </div>
                                                <ul class="list-group"
                                                    style='display: block;padding-left: 0;margin-bottom: 0;border-radius: 0.357rem;'>
                                                    @foreach ($questions as $question)
                                                        <li href="#" class="list-group-item"
                                                            style='width: 100%;clear: both;position: relative;display: block;padding: 0.75rem 1.25rem;color: #6e6b7b;background-color: #fff;border: 1px solid rgba(34, 41, 47, 0.125);line-height: 1.5;'>
                                                            <div class="d-flex w-100 justify-content-between"
                                                                style='display: flex !important;width: 100% !important;justify-content: space-between !important;'>
                                                                <h5 class="mb-1 text-primary"
                                                                    style='font-weight: 500;line-height: 1.2;font-size: 1.07rem;margin-bottom: 1rem !important;color: #596cb1 !important;'>
                                                                    <i data-lucide='target'></i>
                                                                    {{ '#' . ($loop->index + 1) . ': ' . $question['title'] }}
                                                                    @if ( $question['is_deleted'] )
                                                                        <span style='display: inline-block;padding: 0.25rem 0.5rem;font-size: 0.75rem;font-weight: 700;line-height: 1;color: #fff;background-color: #dc3545;border-radius: 0.25rem;margin-left: 0.5rem;'>Deleted</span>
                                                                    @endif
                                                                </h5>
                                                            </div>
                                                            <div class="card-text">
                                                                <div class='question' style='line-height: 1.5rem;'>
                                                                    {!! $question['content'] !!}</div>

                                                                @if ($question['answer_type'] === 'SCQ')
                                                                    <ul class='list-unstyled d-flex flex-column'
                                                                        style='padding-left: 0;list-style: none;display: flex !important;flex-direction: column !important;'>
                                                                        @foreach ($options[$question['id']]['scq'] as $k => $q)
                                                                            <li class='col-lg-6 col-12'
                                                                                style='flex: 0 0 auto;width: 50%;'>
                                                                                <p style='line-height: 1.5rem;'>
                                                                                    {{ $k . ': ' . $q }}
                                                                                </p>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @elseif($question['answer_type'] === 'MCQ')
                                                                    <ul class='list-unstyled d-flex flex-column'
                                                                        style='padding-left: 0;list-style: none;display: flex !important;flex-direction: column !important;'>
                                                                        @foreach ($options[$question['id']]['mcq'] as $k => $q)
                                                                            <li class='col-lg-6 col-12'
                                                                                style='flex: 0 0 auto;width: 50%;'>
                                                                                <p style='line-height: 1.5rem;'>
                                                                                    {{ $k . ': ' . $q }}
                                                                                </p>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @elseif($question['answer_type'] === 'SORT')
                                                                    <ul class='list-unstyled d-flex flex-column'>
                                                                        @foreach ($options[$question['id']]['sort'] as $k => $q)
                                                                            <li class='col-lg-6 col-12'>
                                                                                <p style='line-height: 1.5rem;'>
                                                                                    {{ $k . ': ' . $q }}
                                                                                </p>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @elseif($question['answer_type'] === 'MATRIX')
                                                                    <ul class='list-unstyled d-flex flex-column'>
                                                                        @foreach ($options[$question['id']]['matrix'] as $k => $q)
                                                                            <li class='col-lg-6 col-12'>
                                                                                <p style='line-height: 1.5rem;'>
                                                                                    {{ $k . ': ' . $q }}
                                                                                </p>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @endif
                                                                <div class='answer mb-1 mt-1'
                                                                    style='margin-top: 1rem !important;margin-bottom: 1rem !important;'>
                                                                    <h5 class="mb-1 text-primary"
                                                                        style='font-weight: 500;line-height: 1.2;margin-top: 0;font-size: 1.07rem;margin-bottom: 1rem !important;color: #596cb1 !important;'>
                                                                        <i data-lucide='pen-tool'></i> Answer:
                                                                    </h5>
                                                                    @if ($question['answer_type'] === 'FILE')
                                                                        <a href='{{ Storage::url($attempt->submitted_answers[$question['id']]) }}'
                                                                            target='_blank'
                                                                            class='btn btn-outline-secondary btn-sm'
                                                                            style='display: inline-block;line-height: 1;text-align: center;vertical-align: middle;padding: 0.486rem 1rem;font-size: 0.9rem;border-radius: 0.358rem;font-weight: 500;border: 1px solid #82868b !important;background-color: transparent;color: #82868b;'>View
                                                                            File</a>
                                                                    @else
                                                                        @if (is_array($attempt->submitted_answers[$question['id']]))
                                                                            @foreach ($attempt->submitted_answers[$question['id']] as $answer)
                                                                                {!! $loop->index + 1 . ': ' . $answer . '<br/>' !!}
                                                                            @endforeach
                                                                        @else
                                                                            <p style='line-height: 1.5rem;'>
                                                                                {!! $attempt->submitted_answers[$question['id']] !!}</p>
                                                                        @endif
                                                                    @endif
                                                                </div>

                                                                @if (
                                                                    !empty($evaluation) &&
                                                                        isset($evaluation->results[$question['id']]) &&
                                                                        !empty($evaluation->results[$question['id']]['status']))
                                                                    <div class='answer mb-1 mt-1'
                                                                        style='margin-top: 1rem !important;margin-bottom: 1rem !important;'>
                                                                        <h5 class="mb-1 text-primary"
                                                                            style='font-weight: 500;line-height: 1.2;margin-top: 0;font-size: 1.07rem;margin-bottom: 1rem !important;color: #596cb1 !important;'>
                                                                            <i data-lucide='pen-tool'></i> Feedback:
                                                                        </h5>
                                                                        <p style='line-height: 1.5rem;'>Marked as
                                                                            <strong>{{ $evaluation->results[$question['id']]['status'] }}</strong>
                                                                            @if (!empty($evaluation->results[$question['id']]['comment']))
                                                                                , with comments:
                                                                                <span>{!! $evaluation->results[$question['id']]['comment'] !!}</span>
                                                                            @endif
                                                                        </p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            <p style='line-height: 1.5rem;'> Overall feedback from the evaluator of
                                                this
                                                assessment :
                                                <strong>{!! $attempt->feedbacks()->orderBy('id', 'DESC')->first()?->body['message'] !!}</strong>
                                            </p>

                                            <table style="font-family: 'Montserrat',Arial,sans-serif; width: 100%;"
                                                width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                                <tr>
                                                    <td
                                                        style="font-family: 'Montserrat',Arial,sans-serif; padding-top: 32px; padding-bottom: 32px;">
                                                        <div
                                                            style="--bg-opacity: 1; background-color: #eceff1; background-color: rgba(236, 239, 241, var(--bg-opacity)); height: 1px; line-height: 1px;">
                                                            &zwnj;
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                            <p style="margin: 0 0 16px;">Thanks, <br>{{ env('APP_NAME') }}</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: 'Montserrat',Arial,sans-serif; height: 20px;"
                                            height="20"></td>
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
