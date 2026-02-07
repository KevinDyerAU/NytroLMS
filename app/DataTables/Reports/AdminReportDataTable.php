<?php

namespace App\DataTables\Reports;

use App\Helpers\Helper;
use App\Models\AdminReport;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Services\CourseProgressService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AdminReportDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure
    {
        return function (AdminReport $report) {
            $student_status = $report->student_status;
            $course_status = $report->course_status;
            $enrolment = $report->getStudentEnrolment();

            $is_locked = $enrolment->is_locked ? 'Yes' : '';
            $is_chargeable = $enrolment->is_chargeable ? 'Yes' : '';
            // If initial enrollment (registered_on_create = 1), do not show re-registration date
            // If re-enrollment (registered_on_create = 0), show registration_date (not course_start_at)
            $registration_date = null;
            // Only show registration date for re-enrollments (registered_on_create = 0)
            if (!$enrolment->registered_on_create) {
                // If show_registration_date is true, use registration_date (fixes 0000 date error)
                if (!empty($enrolment->show_registration_date) && !empty($enrolment->registration_date)) {
                    try {
                        $parsedDate = Carbon::parse($enrolment->registration_date);
                        // Validate the parsed date is actually valid (not something like -0001)
                        if ($parsedDate->year > 1900 && $parsedDate->year < 2100) {
                            $registration_date = $parsedDate->format('d-m-Y');
                        }
                    } catch (\Exception $e) {
                        // Invalid date, leave as null
                        $registration_date = null;
                    }
                } elseif (!empty($enrolment->registration_date)) {
                    // Fallback for legacy records
                    try {
                        $parsedDate = Carbon::parse($enrolment->registration_date);
                        // Validate the parsed date is actually valid (not something like -0001)
                        if ($parsedDate->year > 1900 && $parsedDate->year < 2100) {
                            $registration_date = $parsedDate->format('d-m-Y');
                        }
                    } catch (\Exception $e) {
                        // Invalid date, leave as null
                        $registration_date = null;
                    }
                }
            }
            $deactivated_on = '';
            if (
                !empty($report->student) &&
                intval($report->student->is_active) === 0
            ) {
                $student_status = 'INACTIVE';
                $deactivated_on = Carbon::parse(
                    $report->student->getRawOriginal('updated_at')
                )
                    ->timezone(Helper::getTimeZone())
                    ->toDateString();
            } else {
                if (\Str::lower($student_status) === 'enrolled') {
                    $student_status = 'REGISTERED';
                    $course_status = 'NOT STARTED';
                } elseif (\Str::lower($student_status) === 'onboarded') {
                    $student_status = 'ACTIVE';
                } elseif (
                    empty($report->student->detail->onboard_at) ||
                    empty($report->student_details['last_logged_in'])
                ) {
                    $student_status = 'ENROLLED';
                } elseif ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (!empty($progress['current_course_progress'])) {
                        if ($progress['current_course_progress'] > 0) {
                            if (
                                !empty($report->student) &&
                                intval($report->student->is_active) === 1
                            ) {
                                $student_status = 'ACTIVE';
                            }
                        }
                    }
                }
            }

            [$expected, $course_status] = $this->courseStatus($report);

            $agreement_date = '';
            $agreement_dates = [];
            if (!empty($report->student_details['enrolment'])) {
                $enrolment = $report->student_details['enrolment'] ?? null;
                if (!empty($enrolment)) {
                    foreach ($enrolment as $item) {
                        if (
                            isset($item['enrolment_key']) &&
                            ($item['enrolment_key'] === 'onboard' || preg_match('/^onboard\d+$/', $item['enrolment_key'] ?? '')) &&
                            ($item['is_active'] ?? true) // Check if active (default to true for backwards compatibility)
                        ) {
                            if (
                                isset(
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ]
                                )
                            ) {
                                $date =
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ]['key'] ??
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ];
                                // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                                $carbonDate = is_numeric($date)
                                    ? \Carbon\Carbon::createFromTimestamp($date)
                                    : \Carbon\Carbon::parse($date);
                                $agreement_dates[] = $carbonDate->timezone(Helper::getTimeZone());
                            } elseif (
                                isset(
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ]
                                )
                            ) {
                                $date =
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ]['key'] ??
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ];
                                // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                                $carbonDate = is_numeric($date)
                                    ? \Carbon\Carbon::createFromTimestamp($date)
                                    : \Carbon\Carbon::parse($date);
                                $agreement_dates[] = $carbonDate->timezone(Helper::getTimeZone());
                            }
                        }
                    }
                }
            }
            // Get the most recent agreement date (for reenrollments)
            if (!empty($agreement_dates)) {
                $agreement_date = collect($agreement_dates)->max()->format('d-m-Y');
            }
            $progress = $report->student_course_progress;

            $course_details = $report->course_details;
            $deferred = '';
            if (!empty($course_details)) {
                if (
                    !empty($course_details['deferred']) &&
                    $course_details['deferred']
                ) {
                    if (
                        Carbon::parse(
                            $report->getRawOriginal('student_course_end_date')
                        )->greaterThan(Carbon::now())
                    ) {
                        $deferred = 'Yes';
                    }
                } else {
                    $deferred = 'No';
                }
            }
            $course_expiry = '';
            if (!empty($report->course_expiry)) {
                $course_expiry = Carbon::parse($report->course_expiry)->format(
                    'j F, Y'
                );
            }
            $exportCols = [
                'ID' => $report->id,
                'Student ID' => $report->student_id,
                'Student' => $report->student_details['name'] ?? '',
                'Student Email' => $report->student_details['email'] ?? '',
                'Student Phone' => $report->student_details['phone'] ?? '',
                'Purchase Order Number' =>
                    $report->student_details['purchase_order'] ?? '',
                'Employment Service' => $report->getEmploymentService(),
                'Preferred Language' =>
                    $report->student_details['preferred_language'] ?? '',
                'Created At' => $report->student_details['created_at'] ?? '',
                'Student Status' => \Str::title(
                    str_replace('_', ' ', $student_status)
                ),
                'Deactivated On' => $deactivated_on,
                'Last Active On' => $report->student_last_active ?? '',
                'Student Agreement Date' => $agreement_date,
                'Trainer' => $report->trainer_details['name'] ?? '',
                'Leader' => $report->leader_details['name'] ?? '',
                'Leader Email' => $report->leader_details['email'] ?? '',
                'Company Name' => $report->company_details['name'] ?? '',
                'Company Address' => $report->company_details['address'] ?? '',
                'Company Phone' => $report->company_details['number'] ?? '',
                'Company Email' => $report->company_details['email'] ?? '',
                'Course' => $report->course_details['title'] ?? '',
                'Course Status' => \Str::title(
                    str_replace('_', ' ', $course_status)
                ),
                'Course Completed On' => !empty($report->course_completed_at)
                    ? Carbon::parse($report->course_completed_at)->format(
                        'j F, Y'
                    )
                    : '',
                'Course Expiry' => $course_expiry,
                'Start Date' => $report->student_course_start_date ?? '',
                'End Date' => $report->student_course_end_date ?? '',
                'Deferred' => $deferred,
                'Semester 1 Only' => $report->allowed_to_next_course
                    ? 'No'
                    : 'Yes',
                'Current Progress' =>
                    ($progress['current_course_progress'] ?? 0) . '%',
                'Expected Progress' => $expected . '%',
                'Course Total Time' => isset($progress['hours_details'])
                    ? $progress['hours_details']['actual']['hours'] .
                    ' : ' .
                    $progress['hours_details']['actual']['minutes']
                    : '',
                'Total Time Spent' => isset($progress['hours_details'])
                    ? $progress['hours_details']['reported']['hours'] .
                    ' : ' .
                    $progress['hours_details']['reported']['minutes']
                    : '',
                'Time Spent (Last Week)' =>
                    isset($progress['hours_details']) &&
                    isset($progress['hours_details']['last_week'])
                    ? $progress['hours_details']['last_week']['hours'] .
                    ' : ' .
                    $progress['hours_details']['last_week']['minutes']
                    : '',
                'Total Assignments' => $progress['total_assignments'] ?? 0,
                'Satisfactory Assignments' =>
                    $progress['total_assignments_satisfactory'] ?? 0,
                'Not Satisfactory Assignments' =>
                    $progress['total_assignments_not_satisfactory'] ?? 0,
                'Pending Assignments' =>
                    $progress['total_assignments_remaining'] ?? 0,
                'Certificate Issued' =>
                    !empty(
                        $report->course_details['certificate']['cert_issued']
                    ) &&
                    $report->course_details['certificate']['cert_issued'] === 1
                    ? 'Yes'
                    : '',
                'Cert. Issued On' =>
                    $report->course_details['certificate']['cert_issued_on'] ??
                    '',
                'Re-registration Date' => $registration_date ?? '',
                'Generate Invoice' => $is_chargeable ?? '',
                'Course Locked' => $is_locked ?? '',
                'Course Version' =>
                    $report->course_details['version'] ??
                    ($report->course->version ?? ''),
                'Study Type' => $report->student_details['study_type'] ?? '',
            ];

            if (
                auth()
                    ->user()
                    ->cannot('view reports special columns')
            ) {
                unset($exportCols['Purchase Order Number']);
                unset($exportCols['Employment Service']);
                unset($exportCols['Leader Email']);
                unset($exportCols['Company Address']);
                unset($exportCols['Company Phone']);
                unset($exportCols['Company Email']);
            }

            return $exportCols;
        };
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('', '')
            ->addColumn('', '')
            ->editColumn('student_created_at', function (AdminReport $report) {
                return $report->student_details['created_at'] ?? '';
            })
            ->editColumn('student_details_agreement_date', function (AdminReport $report) {
                $agreement_date = '';
                $agreement_dates = [];
                if (empty($report->student_details['enrolment'])) {
                    return $agreement_date;
                }
                $enrolment = $report->student_details['enrolment'] ?? null;
                if (!empty($enrolment)) {
                    foreach ($enrolment as $item) {
                        if (
                            isset($item['enrolment_key']) &&
                            ($item['enrolment_key'] === 'onboard' || preg_match('/^onboard\d+$/', $item['enrolment_key'] ?? '')) &&
                            ($item['is_active'] ?? true) // Check if active (default to true for backwards compatibility)
                        ) {
                            if (
                                isset(
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ]
                                )
                            ) {
                                $date =
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ]['key'] ??
                                    $item['enrolment_value']['step-6'][
                                        'signed_on'
                                    ];
                                $agreement_dates[] = \Carbon\Carbon::parse($date)
                                    ->timezone(Helper::getTimeZone());
                            } elseif (
                                isset(
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ]
                                )
                            ) {
                                $date =
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ]['key'] ??
                                    $item['enrolment_value']['step-5'][
                                        'signed_on'
                                    ];
                                $agreement_dates[] = \Carbon\Carbon::parse($date)
                                    ->timezone(Helper::getTimeZone());
                            }
                        }
                    }
                }
                // Get the most recent agreement date (for reenrollments)
                if (!empty($agreement_dates)) {
                    $agreement_date = collect($agreement_dates)->max()->format('d-m-Y');
                }

                return $agreement_date;
            })
            ->editColumn('student_employment_service', function (AdminReport $report) {
                return $report->getEmploymentService();
            })
            ->editColumn('student_preferred_language', function (AdminReport $report) {
                if (
                    empty($report->student_id) ||
                    empty($report->student_details)
                ) {
                    return '';
                }

                return $report->student_details['preferred_language'] ?? '';
            })
            ->editColumn('student', function (AdminReport $report) {
                if (
                    empty($report->student_id) ||
                    empty($report->student_details)
                ) {
                    return '';
                }

                return '<a href="' .
                    route(
                        'account_manager.students.show',
                        $report->student_id
                    ) .
                    '">' .
                    $report->student_details['name'] .
                    '</a>';
            })
            ->editColumn('trainer', function (AdminReport $report) {
                if (
                    empty($report->trainer_id) ||
                    empty($report->trainer_details)
                ) {
                    return '';
                }

                return '<a href="' .
                    route(
                        'account_manager.trainers.show',
                        $report->trainer_id
                    ) .
                    '">' .
                    $report->trainer_details['name'] .
                    '</a>';
            })
            ->editColumn('leader', function (AdminReport $report) {
                if (
                    empty($report->leader_id) ||
                    empty($report->leader_details)
                ) {
                    return '';
                }

                return '<a href="' .
                    route('account_manager.leaders.show', $report->leader_id) .
                    '">' .
                    $report->leader_details['name'] .
                    '</a>';
            })
            ->editColumn('leader_details_email', function (AdminReport $report) {
                if (
                    empty($report->leader_id) ||
                    empty($report->leader_details)
                ) {
                    return '';
                }

                return $report->leader_details['email'];
            })
            ->addColumn('company', function (AdminReport $report) {
                return $report->company_id ?? '';
            })
            ->editColumn('company_details.name', function (AdminReport $report) {
                if (empty($report->company_id)) {
                    return '';
                }

                return '<a href="' .
                    route(
                        'account_manager.companies.show',
                        $report->company_id
                    ) .
                    '">' .
                    $report->company_details['name'] .
                    '</a>';
            })
            ->editColumn('company_details.email', function (AdminReport $report) {
                if (empty($report->company_id)) {
                    return '';
                }

                return $report->company_details['email'];
            })
            ->editColumn('company_details.address', function (AdminReport $report) {
                if (empty($report->company_id)) {
                    return '';
                }

                return $report->company_details['address'];
            })
            ->editColumn('company_details.number', function (AdminReport $report) {
                if (empty($report->company_id)) {
                    return '';
                }

                return $report->company_details['number'];
            })
            ->editColumn('student_status', function (AdminReport $report) {
                $student_status = $report->student_status;

                if (
                    !empty($report->student) &&
                    intval($report->student->is_active) === 0
                ) {
                    $student_status = 'INACTIVE';
                } else {
                    if (\Str::lower($student_status) === 'enrolled') {
                        $student_status = 'REGISTERED';
                        $course_status = 'NOT STARTED';
                    } elseif (\Str::lower($student_status) === 'onboarded') {
                        $student_status = 'ACTIVE';
                    } elseif (
                        empty($report->student->detail->onboard_at) ||
                        empty($report->student_details['last_logged_in'])
                    ) {
                        $student_status = 'ENROLLED';
                    } elseif ($report->student_course_progress) {
                        $progress = $report->student_course_progress;
                        if (!empty($progress['current_course_progress'])) {
                            if ($progress['current_course_progress'] > 0) {
                                if (
                                    !empty($report->student) &&
                                    intval($report->student->is_active) === 1
                                ) {
                                    $student_status = 'ACTIVE';
                                }
                            }
                        }
                    }
                }

                $color = config(
                    'constants.status.color.' . $student_status,
                    'primary'
                );

                return '<span class="text-' .
                    $color .
                    '">' .
                    \Str::title(str_replace('_', ' ', $student_status)) .
                    '</span>';
            })
            ->editColumn('deactivated_on', function (AdminReport $report) {
                $output = '';
                if (
                    !empty($report->student) &&
                    intval($report->student->is_active) === 0
                ) {
                    $output = Carbon::parse(
                        $report->student->getRawOriginal('updated_at')
                    )
                        ->timezone(Helper::getTimeZone())
                        ->toDateString();
                }

                return $output;
            })
            ->editColumn('course_details', function (AdminReport $report) {
                if (!empty($report->course_id)) {
                    if (
                        auth()
                            ->user()
                            ->isLeader()
                    ) {
                        return $report->course_details['title'] ?? '';
                    }
                    if (!empty($report->course_details)) {
                        return '<a href="' .
                            route('lms.courses.show', $report->course_id) .
                            '">' .
                            ($report->course_details['title'] ?? '') .
                            '</a>';
                    }

                    return '';
                }

                return '<span>No Course assigned.</span>';
            })
            ->editColumn('course_status', function (AdminReport $report) {
                [$expected, $status] = $this->courseStatus($report);
                //                dd(\Str::upper( str_replace( ' ', '_', $status ) ));
                $color = config('constants.status.color.' . $status, 'primary');

                return '<span class="text-' .
                    $color .
                    '">' .
                    \Str::title(str_replace('_', ' ', $status)) .
                    '</span>';
            })
            ->editColumn('course_expiry', function (AdminReport $report) {
                $course_expiry = '';
                if (!empty($report->course_expiry)) {
                    $course_expiry = Carbon::parse(
                        $report->course_expiry
                    )->format('j F, Y');
                }

                return $course_expiry;
            })
            ->editColumn('course_completed_at', function (AdminReport $report) {
                return !empty($report->course_completed_at)
                    ? Carbon::parse($report->course_completed_at)->format(
                        'j F, Y'
                    )
                    : '';
            })
            ->editColumn('student_course_allowed_to_next_course', function (AdminReport $report) {
                return $report->allowed_to_next_course ? 'No' : 'Yes';
            })
            ->editColumn('student_course_current_progress', function (AdminReport $report) {
                // $courseProgress = CourseProgress::select( 'percentage' )
                //                                 ->where( 'user_id', $report->student_id )
                //                                 ->where( 'course_id', $report->course_id )->first();
                //                Helper::debug($courseProgress,'dd');
                //                if ( !empty( $courseProgress ) ) {
                //                    return $this->getPercentage( $courseProgress->getRawOriginal( 'percentage' ), $report->course_id, $report->student ) . '%';
                //                }
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;

                    return ($progress['current_course_progress'] ?? 0) . '%';
                }

                return '';
            })
            ->editColumn('student_course_expected_progress', function (AdminReport $report) {
                $enrolment = $report->getStudentEnrolment();
                if (!empty($enrolment)) {
                    $startDate = \Carbon\Carbon::parse(
                        $enrolment->getRawOriginal('course_start_at')
                    );
                    $endDate = Carbon::parse(
                        $enrolment->getRawOriginal('course_ends_at')
                    );
                    $now = Carbon::now()->toDateTimeString();
                    $totals = $startDate->diff($endDate)->days;
                    $diff = $startDate->diff($now)->days;
                    $expected = 0;
                    if ($startDate->greaterThan($now)) {
                        $expected = 0;
                    } elseif ($endDate->lessThanOrEqualTo($now)) {
                        // COURSE END DATE ALREADY REACHED
                        $expected = 100;
                    } elseif (
                        $totals > 0 &&
                        $diff > 0 &&
                        $endDate->greaterThan($now)
                    ) {
                        $expectedVal = floatval($diff / $totals) * 100;
                        $expected =
                            $expectedVal <= 100
                            ? number_format($expectedVal, 2)
                            : 100;
                    }

                    //                    if(auth()->user()->id === 1) {
                    //                        dd($report, $enrolment, $endDate,$endDate->lessThanOrEqualTo( $now ),( $totals > 0 && $diff > 0 && $endDate->greaterThan( $now ) ),  $expected );
                    //                    }
                    return $expected . '%';
                }

                return '0%';
            })
            ->editColumn('student_course_total_time', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (isset($progress['hours_details'])) {
                        return $progress['hours_details']['actual']['hours'] .
                            ':' .
                            $progress['hours_details']['actual']['minutes'];
                    }
                }

                return '';
            })
            ->editColumn('student_course_time_spent', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (isset($progress['hours_details'])) {
                        return $progress['hours_details']['reported']['hours'] .
                            ':' .
                            $progress['hours_details']['reported']['minutes'];
                    }
                }

                return '';
            })
            ->editColumn('student_course_time_spent_last_week', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (
                        isset($progress['hours_details']) &&
                        isset($progress['hours_details']['last_week'])
                    ) {
                        return $progress['hours_details']['last_week'][
                            'hours'
                        ] .
                            ':' .
                            $progress['hours_details']['last_week']['minutes'];
                    }
                }

                return '';
            })
            ->editColumn('total_assignments', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (!empty($progress['total_assignments'])) {
                        return $progress['total_assignments'];
                    }

                    return 0;
                }

                return 0;
            })
            ->editColumn('total_assignments_satisfactory', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (!empty($progress['total_assignments_satisfactory'])) {
                        return $progress['total_assignments_satisfactory'];
                    }

                    return 0;
                }

                return 0;
            })
            ->editColumn('total_assignments_not_satisfactory', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (
                        !empty($progress['total_assignments_not_satisfactory'])
                    ) {
                        return $progress['total_assignments_not_satisfactory'];
                    }

                    return 0;
                }

                return 0;
            })
            ->editColumn('total_assignments_remaining', function (AdminReport $report) {
                if ($report->student_course_progress) {
                    $progress = $report->student_course_progress;
                    if (!empty($progress['total_assignments_remaining'])) {
                        return $progress['total_assignments_remaining'];
                    }

                    return 0;
                }

                return 0;
            })
            ->editColumn('deferred', function (AdminReport $report) {
                $course_details = $report->course_details;
                if (!empty($course_details)) {
                    if (
                        !empty($course_details['deferred']) &&
                        $course_details['deferred']
                    ) {
                        if (
                            Carbon::parse(
                                $report->getRawOriginal(
                                    'student_course_end_date'
                                )
                            )->greaterThan(Carbon::now())
                        ) {
                            return 'Yes';
                        }

                        return '';
                    }

                    return 'No';
                }

                return '';
            })
            ->editColumn('certificated_issued', function (AdminReport $report) {
                return !empty(
                    $report->course_details['certificate']['cert_issued']
                ) && $report->course_details['certificate']['cert_issued'] === 1
                    ? 'Yes'
                    : '';
            })
            ->editColumn('certificated_issued_on', function (AdminReport $report) {
                return $report->course_details['certificate'][
                    'cert_issued_on'
                ] ?? '';
            })
            ->editColumn('registration_date', function (AdminReport $report) {
                $enrolment = $report->getStudentEnrolment();
                if (empty($enrolment)) {
                    return '';
                }
                // If initial enrollment (registered_on_create = 1), do not show re-registration date
                if ($enrolment->registered_on_create) {
                    return '';
                }
                // If re-enrollment (registered_on_create = 0), show registration_date (not course_start_at)
                // If show_registration_date is true, use registration_date (fixes 0000 date error)
                if (!empty($enrolment->show_registration_date) && !empty($enrolment->registration_date)) {
                    try {
                        $parsedDate = Carbon::parse($enrolment->registration_date);
                        // Validate the parsed date is actually valid (not something like -0001)
                        if ($parsedDate->year > 1900 && $parsedDate->year < 2100) {
                            return $parsedDate->format('d-m-Y');
                        }
                    } catch (\Exception $e) {
                        // Invalid date, return empty string
                        return '';
                    }
                }
                // Fallback for legacy records
                if (!empty($enrolment->registration_date)) {
                    try {
                        $parsedDate = Carbon::parse($enrolment->registration_date);
                        // Validate the parsed date is actually valid (not something like -0001)
                        if ($parsedDate->year > 1900 && $parsedDate->year < 2100) {
                            return $parsedDate->format('d-m-Y');
                        }
                    } catch (\Exception $e) {
                        // Invalid date, return empty string
                        return '';
                    }
                }

                return '';
            })
            ->editColumn('is_chargeable', function (AdminReport $report) {
                $enrolment = $report->getStudentEnrolment();

                return $enrolment->is_chargeable ? 'Yes' : '';
            })
            ->editColumn('is_locked', function (AdminReport $report) {
                $enrolment = $report->getStudentEnrolment();

                return $enrolment->is_locked ? 'Yes' : '';
            })
            ->editColumn('study_type', function (AdminReport $report) {
                return $report->student_details['study_type'] ?? '';
            })
            ->filterColumn('student_course_start_date', function ($query, $keyword) {
                $searchVal = isset($keyword) ? json_decode($keyword) : '';
                if (!empty($searchVal) && isset($searchVal->start)) {
                    //                    dd(Carbon::parse($this->search->start), Carbon::parse($this->search->end));
                    return $query
                        ->whereDate(
                            'student_course_start_date',
                            '>=',
                            $searchVal->start
                        )
                        ->whereDate(
                            'student_course_start_date',
                            '<=',
                            $searchVal->end
                        );
                }

                return '';
            })
            ->filterColumn('company', function ($query, $company_id) {
                if ($company_id === '') {
                    return $query;
                }

                // Handle multiple company IDs (JSON array) or single ID
                $companyIds = json_decode($company_id, true);
                if (is_array($companyIds) && count($companyIds) > 0) {
                    // Cast to integers to ensure proper type matching
                    $companyIds = array_map('intval', $companyIds);
                    return $query->whereIn('company_id', $companyIds);
                }

                // Fallback to single company ID for backward compatibility
                return $query->where('company_id', intval($company_id));
            })
            ->filterColumn('course_status', function ($query, $keyword) {
                if ($keyword === '') {
                    return $query;
                }

                // 'ATTEMPTING','SUBMITTED','REVIEWING','RETURNED','SATISFACTORY','FAIL','OVERDUE'
                return $query->whereRaw('LOWER(course_status) LIKE %?%', [
                    trim(strtolower(urldecode($keyword))),
                ]);
            })
            ->filterColumn('registration_date', function ($query, $keyword) {
                if ($keyword === '') {
                    return $query;
                }
                $enrolment = $query->getModel()->getStudentEnrolment();
                if ($enrolment->registered_on_create) {
                    return $query->whereRaw(
                        'LOWER(student_course_enrolments.created_at) LIKE %?%',
                        [trim(strtolower(urldecode($keyword)))]
                    );
                }

                return $query->whereRaw(
                    'LOWER(student_course_enrolments.registration_date) LIKE %?%',
                    [trim(strtolower(urldecode($keyword)))]
                );
            })
            ->addColumn('action', 'adminreport.action')
            ->rawColumns([
                'student_created_at',
                'student_employment_service',
                'student_preferred_language',
                'student_details_agreement_date',
                'student',
                'trainer',
                'leader',
                'leader_details_email',
                'company_details.name',
                'company_details.email',
                'company_details.address',
                'company_details.number',
                'student_status',
                'course_details',
                'course_status',
                'course_expiry',
                'course_completed_at',
                'student_course_current_progress',
                'student_course_expected_progress',
                'student_course_total_time',
                'student_course_time_spent',
                'student_course_time_spent_last_week',
                'total_assignments',
                'total_assignments_satisfactory',
                'total_assignments_not_satisfactory',
                'total_assignments_remaining',
                'student_course_allowed_to_next_course',
                'deferred',
                'certificated_issued',
                'certificated_issued_on',
                'registration_date',
                'is_chargeable',
                'is_locked',
                'study_type',
            ]);
    }

    /**
     * Get query source of dataTable.
     *
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(AdminReport $model)
    {
        $return = $model
            ->newQuery()
            ->select('admin_reports.*')
            ->with(['student', 'student.detail', 'course'])
            ->where('admin_reports.student_details', '!=', '');

        // Debugging: Count total Semester 2 courses before filters
        //        $semester2Count = $return->clone()->where('admin_reports.is_main_course', 0)->count();
        //        \Log::info("Total Semester 2 courses (is_main_course = 0): {$semester2Count}");

        if (
            auth()
                ->user()
                ->isLeader()
        ) {
            $companies = auth()->user()->companies?->pluck('id');
            if (!empty($companies)) {
                $return = $return->whereIn('company_id', $companies);
            }
            $return = $return->where(
                'admin_reports.student_status',
                '!=',
                'DELIST'
            );
        }
        $return = $return->join('student_course_enrolments', function ($join) {
            $join
                ->on(
                    'admin_reports.course_id',
                    '=',
                    'student_course_enrolments.course_id'
                )
                ->on(
                    'admin_reports.student_id',
                    '=',
                    'student_course_enrolments.user_id'
                );
        });

        // Debugging: Count Semester 2 courses after join
        //        $semester2AfterJoin = $return->clone()->where('admin_reports.is_main_course', 0)->count();
        //        \Log::info("Semester 2 courses after join: {$semester2AfterJoin}");

        $return = $return->where(
            'student_course_enrolments.status',
            '!=',
            'DELIST'
        );
        $return = $return
            ->where(
                'admin_reports.course_id',
                '!=',
                config('constants.precourse_quiz_id', 0)
            )
            ->whereNotNull('admin_reports.course_id')
            ->whereNotNull('admin_reports.course_details');

        // Debugging: Count Semester 2 courses after status and null checks
        //        $semester2AfterFilters = $return->clone()->where('admin_reports.is_main_course', 0)->count();
        //        \Log::info("Semester 2 courses after status and null checks: {$semester2AfterFilters}");

        if ($this->company) {
            $return = $return->where(
                'admin_reports.company_id',
                $this->company
            );
        }

        if ($this->course_status) {
            $return = $return->where(
                'admin_reports.course_status',
                $this->course_status
            );
            $return = $return
                ->where('admin_reports.student_status', '!=', 'INACTIVE')
                ->where('admin_reports.student_status', '!=', 'DELIST');
        }

        if ($this->registration_date) {
            $return = $return->whereDate(
                'student_course_enrolments.registration_date',
                $this->registration_date
            );
        }

        // Debugging: Count Semester 2 courses after optional filters
        //        $semester2AfterOptional = $return->clone()->where('admin_reports.is_main_course', 0)->count();
        //        \Log::info("Semester 2 courses after optional filters: {$semester2AfterOptional}");

        $return = $return->where(function ($query) {
            return $query
                ->where(function ($qry) {
                    return $qry->where('admin_reports.is_main_course', 1);
                })
                ->orWhere(function ($qry) {
                    return $qry
                        ->where('admin_reports.is_main_course', 0)
                        ->where(function ($q) {
                            return $q
                                ->where(function ($q1) {
                                    return $q1->whereDate(
                                        'admin_reports.student_course_start_date',
                                        '<=',
                                        Carbon::today(
                                            Helper::getTimeZone()
                                        )->toDateString()
                                    );
                                })
                                ->orWhere(function ($q2) {
                                    return $q2
                                        ->whereDate(
                                            'admin_reports.student_course_start_date',
                                            '>',
                                            Carbon::today(
                                                Helper::getTimeZone()
                                            )->toDateString()
                                        )
                                        ->whereRaw(
                                            'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(admin_reports.course_details, \'$.is_chargeable\')), \'false\') = \'true\''
                                        );
                                });
                        });
                });
        });

        // Debugging: Count Semester 2 courses in final result
        //        $semester2Final = $return->clone()->where('admin_reports.is_main_course', 0)->count();
        //        \Log::info("Semester 2 courses in final result: {$semester2Final}");

        $return = $return->groupBy([
            'admin_reports.student_id',
            'admin_reports.course_id',
        ]);

        return $return;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $urlData = [];
        $url = url()->current();
        if ($this->request()->has('course_status')) {
            $urlData += [
                'course_status' => $this->request()->get('course_status'),
            ];
        }
        if ($this->request()->has('company')) {
            $urlData += ['company' => $this->request()->get('company')];
        }
        if ($this->request()->has('registration_date')) {
            $urlData += [
                'registration_date' => $this->request()->get(
                    'registration_date'
                ),
            ];
        }

        return $this->builder()
            ->setTableId('admin-report-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax($url, null, $urlData)
            ->parameters([
                'searchDelay' => 600,
                'order' => [
                    3, // here is the column number
                    'desc',
                ],
                //                'buttons' => ['csv'],
            ])
            ->buttons(
                Button::make('export')
                    ->text(
                        "<i class='font-small-4 me-50' data-lucide='share'></i>Export"
                    )
                    ->className(
                        'dt-button buttons-collection btn btn-outline-secondary dropdown-toggle me-2'
                    )
                    ->buttons([
                        Button::make('postCsv')
                            ->text("<i data-lucide='file-text'></i> CSV")
                            ->className('dropdown-item')
                            ->exportOptions(['columns' => ':visible']),
                        //                            Button::make('excel')
                        //                                ->text("<i data-lucide='file'></i>Excel")
                        //                                ->className('dropdown-item')
                        //                                ->exportOptions(['modifier' => ['selected' => null], 'columns' => ":visible"])
                    ])
                    ->authorized(
                        auth()
                            ->user()
                            ->can('download reports')
                    )
            );
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $canViewSpecialCol = auth()
            ->user()
            ->can('view reports special columns');

        return [
            Column::computed('', '')
                ->exportable(false)
                ->printable(false)
                ->responsivePriority(2)
                ->addClass('control'),
            Column::computed('', '')
                ->responsivePriority(3)
                ->addClass('dt-checkboxes-cell')
                ->exportable(false),
            Column::make('id'),
            Column::make('id')
                ->orderable(false)
                ->visible(false)
                ->exportable(false),
            //            Column::computed( 'action' )
            //                  ->exportable( FALSE )
            //                  ->printable( FALSE )
            //                  ->width( 60 )
            //                  ->addClass( 'text-center text-nowrap' ),
            Column::make('student_id')
                ->orderable(false)
                ->visible(false)
                ->exportable(true),
            Column::make('student')
                ->title('Student')
                ->data('student')
                ->name('student_details'),
            Column::make('student_details.email')
                ->title('Student Email')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('student_details.phone')
                ->title('Student Phone')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('student_details.purchase_order')
                ->title('Purchase Order Number')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('student_employment_service')
                ->title('Employment Service')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('student_created_at')
                ->title('Created At')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_status'),
            Column::make('deactivated_on')
                ->title('Deactivated On')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_last_active')->title('Last Active On'),
            Column::make('student_preferred_language')
                ->title('Preferred Language')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_details_agreement_date')
                ->title('Student Agreement Date')
                ->orderable(false)
                ->searchable(false),
            Column::make('trainer')
                ->title('Trainer')
                ->data('trainer')
                ->name('trainer_details')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol),
            Column::make('leader')
                ->title('Leader')
                ->data('leader')
                ->name('leader_details')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol),
            Column::make('leader_details_email')
                ->title('Leader Email')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('company')
                ->title('Company')
                ->orderable(false)
                ->searchable(true)
                ->visible(false)
                ->exportable(false),
            Column::make('company_details.name')
                ->title('Company Name')
                ->orderable(false)
                ->searchable(false),
            Column::make('company_details.address')
                ->title('Company Address')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('company_details.number')
                ->title('Company Phone')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('company_details.email')
                ->title('Company Email')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('course_details')->title('Course'),
            Column::make('course_status')->searchable(false),
            Column::make('course_completed_at')
                ->title('Course Completed On')
                ->searchable(false),
            Column::make('course_expiry')
                ->searchable(false)
                ->orderable(false),
            Column::make('deferred')
                ->title('Deferred')
                ->name('deferred')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_start_date')->title('Start Date'),
            Column::make('student_course_end_date')->title('End Date'),
            Column::make('student_course_allowed_to_next_course')
                ->title('Semester 1 Only')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_current_progress')
                ->title('Current Progress')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_expected_progress')
                ->title('Expected Progress')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_total_time')
                ->title('Course Total Time')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_time_spent')
                ->title('Total Time Spent')
                ->orderable(false)
                ->searchable(false),
            Column::make('student_course_time_spent_last_week')
                ->title('Time Spent (Last Week)')
                ->orderable(false)
                ->searchable(false),
            Column::make('total_assignments')
                ->title('Total Assignments')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('total_assignments_satisfactory')
                ->title('Satisfactory Assignments')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('total_assignments_not_satisfactory')
                ->title('Not Satisfactory Assignments')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('total_assignments_remaining')
                ->title('Pending Assignments')
                ->visible($canViewSpecialCol)
                ->exportable($canViewSpecialCol)
                ->orderable(false)
                ->searchable(false),
            Column::make('certificated_issued')
                ->title('Certificated Issued')
                ->orderable(false)
                ->searchable(false),
            Column::make('certificated_issued_on')
                ->title('Cert. Issued On')
                ->orderable(false)
                ->searchable(false),
            Column::make('registration_date')
                ->title('Re-registration Date')
                ->orderable(false)
                ->searchable(false),
            Column::make('is_chargeable')
                ->title('Generate Invoice')
                ->orderable(false)
                ->searchable(false),
            Column::make('is_locked')
                ->title('Course Locked')
                ->orderable(false)
                ->searchable(false),
            Column::make('course.version')
                ->title('Version')
                ->orderable(false)
                ->searchable(false),
            Column::make('study_type')
                ->title('Study Type')
                ->orderable(false)
                ->searchable(false),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return 'AdminReport_' . date('YmdHis');
    }

    public function courseStatus(AdminReport $report)
    {
        $expected = 0;
        $course_status = '';
        $enrolment = $report->getStudentEnrolment();

        if (!empty($enrolment)) {
            if ($enrolment->status === 'DELIST') {
                return [$expected, 'DELIST'];
            }

            $courseEndDate = $enrolment->getRawOriginal('course_ends_at');
            $courseStartDate = $enrolment->getRawOriginal('course_start_at');
            $startDate = Carbon::parse($courseStartDate);
            $endDate = Carbon::parse($courseEndDate);
            $now = Carbon::now(Helper::getTimeZone())->toDateTimeString();
            $totals = $startDate->diff($endDate)->days;
            $diff = $startDate->diff($now)->days;

            if ($startDate->greaterThan($now)) {
                $expected = 0;
            } elseif ($endDate->lessThanOrEqualTo($now)) {
                // COURSE END DATE ALREADY REACHED
                $expected = 100;
            } elseif ($totals > 0 && $diff > 0 && $endDate->greaterThan($now)) {
                $expectedVal = floatval($diff / $totals) * 100;
                $expected =
                    $expectedVal <= 100 ? number_format($expectedVal, 2) : 100;
            }
            if ($report->student_course_progress) {
                $progress = $report->student_course_progress;
                //                if(auth()->user()->id && $enrolment->course_id === 100082) {
                //                    dump( 'current_course_progress', $progress[ 'current_course_progress' ] );
                //                }
                if (
                    isset($progress['current_course_progress']) &&
                    $progress['current_course_progress'] >= 0
                ) {
                    $gap =
                        $expected >= $progress['current_course_progress']
                        ? $expected - $progress['current_course_progress']
                        : 0;
                    $course_status =
                        $gap <= 30 ? 'ON SCHEDULE' : 'BEHIND SCHEDULE';

                    if (
                        Carbon::parse($courseEndDate)->lessThan(
                            Carbon::today(Helper::getTimeZone())
                        )
                    ) {
                        $course_status = 'BEHIND SCHEDULE';
                    }

                    if ($progress['current_course_progress'] >= 100) {
                        if (
                            (!empty(
                                $progress['total_assignments_not_satisfactory']
                            ) &&
                                $progress[
                                    'total_assignments_not_satisfactory'
                                ] > 0) ||
                            (!empty($progress['total_assignments_remaining']) &&
                                $progress['total_assignments_remaining'] > 0)
                        ) {
                            $course_status = 'ON SCHEDULE';
                        } else {
                            $course_status = 'COMPLETED';
                        }
                    }
                }
            }

            if (
                \Str::lower($report->student_status) === 'enrolled' &&
                (!empty($report->student) &&
                    intval($report->student->is_active) === 1)
            ) {
                $course_status = 'NOT STARTED';
            }
        }

        //        if(auth()->user()->id && $enrolment->course_id === 100082) {
        //            dd( $expected, $course_status );
        //        }
        return [$expected, $course_status];
    }

    public function getPercentage($value, $course_id, $user): float
    {
        $percentage = json_decode($value, true);

        $isMainCourse =
            Course::mainCourseOnly()
                ->where('id', $course_id)
                ->count() > 0;
        $onboarded = $user->detail->onboard_at;

        $stats = DB::table('courses')
            ->leftJoin('lessons', 'courses.id', '=', 'lessons.course_id')
            ->leftJoin('topics', 'courses.id', '=', 'topics.course_id')
            ->leftJoin('quizzes', 'courses.id', '=', 'quizzes.course_id')
            ->select(
                'courses.id',
                DB::raw('COUNT(DISTINCT lessons.id) as lesson_count'),
                DB::raw('COUNT(DISTINCT topics.id) as topic_count'),
                DB::raw('COUNT(DISTINCT quizzes.id) as quiz_count'),
                DB::raw(
                    '(COUNT(DISTINCT lessons.id) + COUNT(DISTINCT topics.id) + COUNT(DISTINCT quizzes.id)) as total_count'
                )
            )
            ->where('courses.id', $course_id)
            ->groupBy('courses.id')
            ->first();
        $percentage['total'] = $stats->total_count;
        $percentage['processed'] = $stats->total_count; // Assume all items are processed for fallback
        $percentage['course_completed'] = false;
        $percentage['passed'] = 0;
        $percentage['empty'] = 0;
        //        $preCourseAssessment = QuizAttempt::select('status')
        //                                                      ->where('user_id', $this->user_id)
        //                                                      ->where('course_id', config( 'constants.precourse_quiz_id', 0 ))
        //                                                      ->first()?->status;
        //        dd($isMainCourse, $onboarded);
        $finalPercentage = floatval(
            CourseProgressService::calculatePercentage(
                $percentage,
                $user->id,
                $course_id
            )
        );
        //        dump($finalPercentage, $isMainCourse, $onboarded->toDayDateTimeString(),$this->course->toArray());
        //        if(auth()->user()->id === 1) {
        //            dump($finalPercentage);
        //        }
        //        Helper::debug($finalPercentage);
        if ($isMainCourse) {
            // $preCourseAssessment === 'SATISFACTORY'
            $finalPercentage =
                (empty($onboarded) ? 0.0 : 5.0) + $finalPercentage * 0.95;
        }

        //        Helper::debug([$this->course,$this->user->detail, $isMainCourse, $onboarded, $finalPercentage], 'dd');
        //        if(auth()->user()->id === 1) {
        //                    dd($this->course, $isMainCourse, $onboarded, $finalPercentage);
        //        }
        return number_format($finalPercentage, 2);
    }
}
