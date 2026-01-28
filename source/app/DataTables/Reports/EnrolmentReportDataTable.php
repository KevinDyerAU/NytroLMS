<?php

namespace App\DataTables\Reports;

use App\Models\Enrolment;
use App\Models\User;
use Carbon\Carbon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EnrolmentReportDataTable extends DataTable
{
    protected bool $fastExcel = true;

    public function fastExcelCallback(): \Closure {
        return function (Enrolment $report) {
            $user = User::where('id', $report->user_id)->first();
            // Fix: decode onboard if it's a string
            $onboard = $report->onboard;
            if (is_string($onboard)) {
                $onboard = json_decode($onboard, true);
            }
            if (!empty($user)) {
                $onboard_step2_additional_qualification = '';
                if (
                    !empty($onboard) &&
                    isset($onboard['step-2']) &&
                    isset($onboard['step-2']['additional_qualification']) &&
                    strtolower(
                        $onboard['step-2']['additional_qualification']
                    ) == 'yes'
                ) {
                    if (!empty($onboard['step-2']['higher_degree'])) {
                        $onboard_step2_additional_qualification .= ", Bachelor Degree or Higher Degree Level: {$onboard['step-2']['higher_degree']}";
                    }
                    if (!empty($onboard['step-2']['advanced_diploma'])) {
                        $onboard_step2_additional_qualification .= ", Advanced Diploma or Associate Degree Level: {$onboard['step-2']['advanced_diploma']}";
                    }
                    if (!empty($onboard['step-2']['diploma'])) {
                        $onboard_step2_additional_qualification .= ", Diploma Level: {$onboard['step-2']['diploma']}";
                    }
                    if (!empty($onboard['step-2']['certificate1'])) {
                        $onboard_step2_additional_qualification .= ", Certificate I: {$onboard['step-2']['certificate1']}";
                    }
                    if (!empty($onboard['step-2']['certificate2'])) {
                        $onboard_step2_additional_qualification .= ", Certificate II: {$onboard['step-2']['certificate2']}";
                    }
                    if (!empty($onboard['step-2']['certificate3'])) {
                        $onboard_step2_additional_qualification .= ", Certificate III: {$onboard['step-2']['certificate3']}";
                    }
                    if (!empty($onboard['step-2']['certificate4'])) {
                        $onboard_step2_additional_qualification .= ", Certificate IV: {$onboard['step-2']['certificate4']}";
                    }
                    if (!empty($onboard['step-2']['certificate_any'])) {
                        $onboard_step2_additional_qualification .= ", Miscellaneous Education: {$onboard['step-2']['certificate_any']}";
                    }
                }
                $isValidStep1 =
                    !empty($onboard) &&
                    isset($onboard['step-1']) &&
                    !empty($onboard['step-1']);
                $isValidStep2 =
                    !empty($onboard) &&
                    isset($onboard['step-2']) &&
                    !empty($onboard['step-2']);
                $isValidStep3 =
                    !empty($onboard) &&
                    isset($onboard['step-3']) &&
                    !empty($onboard['step-3']);
                $isValidStep4 =
                    !empty($onboard) &&
                    isset($onboard['step-4']) &&
                    !empty($onboard['step-4']);
                $isValidStep5 =
                    !empty($onboard) &&
                    isset($onboard['step-5']) &&
                    !empty($onboard['step-5']);
                $gender =
                    $isValidStep1 && isset($onboard['step-1']['gender'])
                    ? \Str::title($onboard['step-1']['gender'])
                    : '';
                if (
                    $isValidStep1 &&
                    isset($onboard['step-1']['gender']) &&
                    $onboard['step-1']['gender'] === 'other'
                ) {
                    $gender = 'Indeterminate/Intersex/Unspecified';
                }

                return [
                    'ID' => $report->id,
                    'Student ID' => $user->id,
                    'Title' =>
                        $isValidStep1 && isset($onboard['step-1']['title'])
                        ? $onboard['step-1']['title']
                        : '',
                    'Student' => $user->name,
                    'Gender' => $gender,
                    'Date of Birth' =>
                        $isValidStep1 && isset($onboard['step-1']['dob'])
                        ? trim(
                            Carbon::parse(
                                $onboard['step-1']['dob']
                            )->format('d/m/Y')
                        )
                        : '',
                    'USI' =>
                        $isValidStep4 && isset($onboard['step-4']['usi_number'])
                        ? $onboard['step-4']['usi_number'] ?? ''
                        : '',
                    'Student Email' => $user->email,
                    'Student Mobile' =>
                        $isValidStep1 && isset($onboard['step-1']['mobile'])
                        ? $onboard['step-1']['mobile'] ?? ''
                        : '',
                    'Student Phone' =>
                        $isValidStep1 && isset($onboard['step-1']['home_phone'])
                        ? $onboard['step-1']['home_phone'] ?? ''
                        : '',
                    'Preferred contact method' =>
                        $isValidStep4 &&
                        isset($onboard['step-4']['nominate_usi'])
                        ? $onboard['step-4']['nominate_usi'] ?? ''
                        : '',
                    'Emergency contact person' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['emergency_contact_name'])
                        ? $onboard['step-1']['emergency_contact_name'] ?? ''
                        : '',
                    'Emergency contact relationship' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['relationship_to_you'])
                        ? $onboard['step-1']['relationship_to_you'] ?? ''
                        : '',
                    'Emergency contact number' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['emergency_contact_number'])
                        ? $onboard['step-1']['emergency_contact_number'] ??
                        ''
                        : '',
                    'Residential Address' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['residence_address'])
                        ? (!empty($onboard['step-1']['residence_address'])
                            ? $onboard['step-1']['residence_address']
                            : '')
                        : '',
                    'Residential Postcode' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['residence_address_postcode'])
                        ? $onboard['step-1'][
                            'residence_address_postcode'
                        ] ?? ''
                        : '',
                    'Postal Address' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['postal_address']) &&
                        isset($onboard['step-1']['residence_address']) &&
                        $onboard['step-1']['postal_address'] !==
                        $onboard['step-1']['residence_address']
                        ? $onboard['step-1']['postal_address'] ?? ''
                        : '',
                    'Postal Postcode' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['postal_address_postcode'])
                        ? $onboard['step-1']['postal_address_postcode'] ??
                        ''
                        : '',
                    'Country' =>
                        $isValidStep1 && isset($onboard['step-1']['country'])
                        ? $onboard['step-1']['country'] ?? ''
                        : '',
                    'Birthplace' =>
                        $isValidStep1 && isset($onboard['step-1']['birthplace'])
                        ? $onboard['step-1']['birthplace'] ?? ''
                        : '',
                    //                    'Language' => ( $isValidStep1 && ( !empty( $onboard[ 'step-1' ][ 'language_other' ] ?? null ) ? $onboard[ 'step-1' ][ 'language_other' ] : ( $onboard[ 'step-1' ][ 'language' ] ?? "" ) ) ),
                    'Language' => $isValidStep1
                        ? (!empty(
                        $report['onboard']['step-1']['language_other']
                    )
                        ? $report['onboard']['step-1']['language_other']
                        : $report['onboard']['step-1']['language'] ?? '')
                        : '',
                    'English Proficiency' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['english_proficiency'])
                        ? $onboard['step-1']['english_proficiency'] ?? ''
                        : '',
                    'Indigenous Status' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['torres_island'])
                        ? $onboard['step-1']['torres_island'] ?? ''
                        : '',
                    'Has Disability' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['has_disability'])
                        ? $onboard['step-1']['has_disability'] ?? ''
                        : '',
                    'Disabilities' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['disabilities'])
                        ? $onboard['step-1']['disabilities'] ?? ''
                        : '',
                    'Industry1' =>
                        $isValidStep1 && isset($onboard['step-1']['industry1'])
                        ? $onboard['step-1']['industry1'] ?? ''
                        : '',
                    'Industry2' =>
                        $isValidStep1 &&
                        isset($onboard['step-1']['industry2_other']) &&
                        !empty($onboard['step-1']['industry2_other'])
                        ? $onboard['step-1']['industry2_other']
                        : ($isValidStep1 &&
                            isset($onboard['step-1']['industry2'])
                            ? $onboard['step-1']['industry2'] ?? ''
                            : ''),
                    'Employment' =>
                        $isValidStep1 && isset($onboard['step-1']['employment'])
                        ? $onboard['step-1']['employment'] ?? ''
                        : '',
                    'Study Reason' =>
                        $isValidStep4 &&
                        isset($onboard['step-4']['study_reason'])
                        ? $onboard['step-4']['study_reason'] ?? ''
                        : '',
                    'School Level' =>
                        $isValidStep2 &&
                        isset($onboard['step-2']['school_level'])
                        ? $onboard['step-2']['school_level'] ?? ''
                        : '',
                    'Secondary Level' =>
                        $isValidStep2 &&
                        isset($onboard['step-2']['secondary_level'])
                        ? $onboard['step-2']['secondary_level'] ?? ''
                        : '',
                    'Additional Qualification' => $onboard_step2_additional_qualification
                        ? substr($onboard_step2_additional_qualification, 1)
                        : '',
                    'Organization Name' =>
                        $isValidStep3 &&
                        isset($onboard['step-3']['organization_name'])
                        ? $onboard['step-3']['organization_name'] ?? ''
                        : '',
                    'Your Position' =>
                        $isValidStep3 &&
                        isset($onboard['step-3']['your_position'])
                        ? $onboard['step-3']['your_position'] ?? ''
                        : '',
                    'Supervisor Name' =>
                        $isValidStep3 &&
                        isset($onboard['step-3']['supervisor_name'])
                        ? $onboard['step-3']['supervisor_name'] ?? ''
                        : '',
                    'Organization Address' =>
                        $isValidStep3 &&
                        isset($onboard['step-3']['street_address'])
                        ? $onboard['step-3']['street_address'] ?? ''
                        : '',
                    'Organization Postcode' =>
                        $isValidStep3 && isset($onboard['step-3']['postcode'])
                        ? $onboard['step-3']['postcode'] ?? ''
                        : '',
                    'Organization Phone' =>
                        $isValidStep3 && isset($onboard['step-3']['telephone'])
                        ? $onboard['step-3']['telephone'] ?? ''
                        : '',
                    'Organization Email' =>
                        $isValidStep3 && isset($onboard['step-3']['email'])
                        ? $onboard['step-3']['email'] ?? ''
                        : '',
                    'Organization Website' =>
                        $isValidStep3 && isset($onboard['step-3']['website'])
                        ? $onboard['step-3']['website'] ?? ''
                        : '',
                    'Signed On' => (function () use ($onboard) {
                        if (!empty($onboard)) {
                            // Check step-6 first (new format)
                            if (isset($onboard['step-6']['signed_on'])) {
                                $signedOn = $onboard['step-6']['signed_on'];
                                if (!empty($signedOn)) {
                                    // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                                    $carbonDate = is_numeric($signedOn)
                                        ? Carbon::createFromTimestamp($signedOn)
                                        : Carbon::parse($signedOn);
                                    return $carbonDate->format('d/m/Y');
                                }
                            }
                            // Fallback to step-5 for old data (backwards compatibility)
                            if (isset($onboard['step-5']['signed_on'])) {
                                $signedOn = $onboard['step-5']['signed_on'];
                                if (!empty($signedOn)) {
                                    // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                                    $carbonDate = is_numeric($signedOn)
                                        ? Carbon::createFromTimestamp($signedOn)
                                        : Carbon::parse($signedOn);
                                    return $carbonDate->format('d/m/Y');
                                }
                            }
                        }

                        return '';
                    })(),
                    'Re-enrolment Changes' => $this->getEnrolmentChangesForExport($report),
                ];
            }
        };
    }

    /**
     * Get enrolment changes for export (without HTML)
     */
    private function getEnrolmentChangesForExport(Enrolment $report): string {
        $userId = $report->user_id;

        // Get current active enrolment
        $currentEnrolment = \App\Models\Enrolment::where('user_id', $userId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', true)
            ->first();

        if (!$currentEnrolment) {
            return '';
        }

        // Get previous inactive enrolment (most recent)
        $previousEnrolment = \App\Models\Enrolment::where('user_id', $userId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', false)
            ->where('id', '!=', $currentEnrolment->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // If no previous enrolment, this is the first enrolment
        if (!$previousEnrolment) {
            return '';
        }

        // Compare enrolments
        $oldData = $previousEnrolment->enrolment_value->toArray();
        $newData = $currentEnrolment->enrolment_value->toArray();

        $changedFields = $this->detectEnrolmentChanges($oldData, $newData);

        if (empty($changedFields)) {
            return 'No changes';
        }

        $count = count($changedFields);
        return $count . ' changes made' . ($count !== 1 ? 's' : '');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query) {
        return datatables()
            ->eloquent($query)
            ->addColumn('', '')
            ->addColumn('', '')
            ->editColumn('title', function (Enrolment $report) {
                $user = User::where('id', $report->user_id)->first();
                if (empty($user)) {
                    return '';
                }
                $onboard = $report->onboard;
                if (is_string($onboard)) {
                    $onboard = json_decode($onboard, true);
                }

                return !empty($onboard) && isset($onboard['step-1']['title'])
                    ? $onboard['step-1']['title']
                    : '';
            })
            ->editColumn('student', function (Enrolment $report) {
                $user = User::where('id', $report->user_id)->first();
                if (empty($user)) {
                    return '';
                }

                return '<a href="' .
                    route('account_manager.students.show', $user->id) .
                    '">' .
                    $user->name .
                    '</a>';
            })
            //            ->editColumn( 'basic_schedule', function ( Enrolment $report ) {
            //                return $report->basic[ 'schedule' ];
            //            } )
            //            ->editColumn( 'basic_employment_service', function ( Enrolment $report ) {
            //                return $report->basic[ 'employment_service' ];
            //            } )
            ->editColumn('onboard_step1_gender', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    if ($report['onboard']['step-1']['gender'] === 'other') {
                        return 'Indeterminate/Intersex/Unspecified';
                    } else {
                        return \Str::title(
                            $report['onboard']['step-1']['gender']
                        );
                    }
                }

                return '';
            })
            ->editColumn('onboard_step1_dob', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return trim(
                        Carbon::parse(
                            $report['onboard']['step-1']['dob']
                        )->format('d/m/Y')
                    );
                }

                return '';
            })
            ->editColumn('onboard_step4_usi_number', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-4'])
                ) {
                    return $report['onboard']['step-4']['usi_number'];
                }

                return '';
            })
            ->editColumn('onboard_step1_mobile', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['mobile'];
                }

                return '';
            })
            ->editColumn('onboard_step1_home_phone', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['home_phone'];
                }

                return '';
            })
            ->editColumn('onboard_step4_nominate_usi', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-4'])
                ) {
                    return $report['onboard']['step-4']['nominate_usi'];
                }

                return '';
            })
            ->editColumn('onboard_step1_emergency_contact_name', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1'][
                        'emergency_contact_name'
                    ];
                }

                return '';
            })
            ->editColumn('onboard_step1_relationship_to_you', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['relationship_to_you'];
                }

                return '';
            })
            ->editColumn('onboard_step1_emergency_contact_number', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1'][
                        'emergency_contact_number'
                    ];
                }

                return '';
            })
            ->editColumn('onboard_step1_residence_address', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty($report['onboard']['step-1']['residence_address'])
                ) {
                    return $report['onboard']['step-1']['residence_address'];
                }

                return '';
            })
            ->editColumn('onboard_step1_residence_address_postcode', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty(
                    $report['onboard']['step-1'][
                        'residence_address_postcode'
                    ]
                )
                ) {
                    return $report['onboard']['step-1'][
                        'residence_address_postcode'
                    ];
                }

                return '';
            })
            ->editColumn('onboard_step1_postal_address', function (Enrolment $report) {
                if (
                    !empty(
                    $report->onboard && !empty($report['onboard']['step-1'])
                ) &&
                    !empty($report['onboard']['step-1']['postal_address']) &&
                    $report['onboard']['step-1']['postal_address'] !==
                    $report['onboard']['step-1']['residence_address']
                ) {
                    return $report['onboard']['step-1']['postal_address'];
                }

                return '';
            })
            ->editColumn('onboard_step1_postal_address_postcode', function (Enrolment $report) {
                if (
                    !empty(
                    $report->onboard && !empty($report['onboard']['step-1'])
                ) &&
                    !empty(
                    $report['onboard']['step-1']['postal_address_postcode']
                ) &&
                    $report['onboard']['step-1']['postal_address'] !==
                    $report['onboard']['step-1']['residence_address']
                ) {
                    return $report['onboard']['step-1'][
                        'postal_address_postcode'
                    ];
                }

                return '';
            })
            ->editColumn('onboard_step1_country', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['country'];
                }

                return '';
            })
            ->editColumn('onboard_step1_birthplace', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    isset($report['onboard']['step-1']['birthplace'])
                ) {
                    return $report['onboard']['step-1']['birthplace'];
                }

                return '';
            })
            ->editColumn('onboard_step1_language', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty($report['onboard']['step-1']['language_other'])
                ) {
                    return $report['onboard']['step-1']['language_other'];
                } elseif (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty($report['onboard']['step-1']['language'])
                ) {
                    return $report['onboard']['step-1']['language'];
                }

                return '';
            })
            ->editColumn('onboard_step1_english_proficiency', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty($report['onboard']['step-1']['english_proficiency'])
                ) {
                    return $report['onboard']['step-1']['english_proficiency'];
                }

                return '';
            })
            ->editColumn('onboard_step1_torres_island', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['torres_island'];
                }

                return '';
            })
            ->editColumn('onboard_step1_has_disability', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['has_disability'];
                }

                return '';
            })
            ->editColumn('onboard_step1_disabilities', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['disabilities'];
                }

                return '';
            })
            ->editColumn('onboard_step1_industry1', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['industry1'];
                }

                return '';
            })
            ->editColumn('onboard_step1_industry2', function (Enrolment $report) {
                //                if ( !empty( $report->onboard ) && !empty( $report[ 'onboard' ][ 'step-1' ] ) && !empty( $report[ 'onboard' ][ 'step-1' ][ 'industry2_other' ] ) ) {
                //                    return $report[ 'onboard' ][ 'step-1' ][ 'industry2_other' ];
                //                } else
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1']) &&
                    !empty($report['onboard']['step-1']['industry2'])
                ) {
                    return $report['onboard']['step-1']['industry2'];
                }

                return '';
            })
            ->editColumn('onboard_step1_employment', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-1'])
                ) {
                    return $report['onboard']['step-1']['employment'];
                }

                return '';
            })
            ->editColumn('onboard_step4_study_reason', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-4'])
                ) {
                    return $report['onboard']['step-4']['study_reason'];
                }

                return '';
            })
            ->editColumn('onboard_step2_school_level', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-2'])
                ) {
                    return $report['onboard']['step-2']['school_level'];
                }

                return '';
            })
            ->editColumn('onboard_step2_secondary_level', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-2'])
                ) {
                    return $report['onboard']['step-2']['secondary_level'];
                }

                return '';
            })
            ->editColumn('onboard_step2_additional_qualification', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-2']) &&
                    isset(
                    $report['onboard']['step-2']['additional_qualification']
                ) &&
                    strtolower(
                        $report['onboard']['step-2']['additional_qualification']
                    ) == 'yes'
                ) {
                    $output = '';
                    if (!empty($report['onboard']['step-2']['higher_degree'])) {
                        $output .= "<li>Bachelor Degree or Higher Degree Level: {$report['onboard']['step-2']['higher_degree']}</li>";
                    }
                    if (
                        !empty($report['onboard']['step-2']['advanced_diploma'])
                    ) {
                        $output .= "<li>Advanced Diploma or Associate Degree Level: {$report['onboard']['step-2']['advanced_diploma']}</li>";
                    }
                    if (!empty($report['onboard']['step-2']['diploma'])) {
                        $output .= "<li>Diploma Level: {$report['onboard']['step-2']['diploma']}</li>";
                    }
                    if (!empty($report['onboard']['step-2']['certificate1'])) {
                        $output .= "<li>Certificate I: {$report['onboard']['step-2']['certificate1']}</li>";
                    }
                    if (!empty($report['onboard']['step-2']['certificate2'])) {
                        $output .= "<li>Certificate II: {$report['onboard']['step-2']['certificate2']}</li>";
                    }
                    if (!empty($report['onboard']['step-2']['certificate3'])) {
                        $output .= "<li>Certificate III: {$report['onboard']['step-2']['certificate3']}</li>";
                    }
                    if (!empty($report['onboard']['step-2']['certificate4'])) {
                        $output .= "<li>Certificate IV: {$report['onboard']['step-2']['certificate4']}</li>";
                    }
                    if (
                        !empty($report['onboard']['step-2']['certificate_any'])
                    ) {
                        $output .= "<li>Miscellaneous Education: {$report['onboard']['step-2']['certificate_any']}</li>";
                    }

                    return !empty($output) ? '<ul>' . $output . '</ul>' : 'N/A';
                }

                return 'No';
            })
            ->editColumn('onboard_step3_organization_name', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['organization_name'];
                }

                return '';
            })
            ->editColumn('onboard_step3_your_position', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['your_position'];
                }

                return '';
            })
            ->editColumn('onboard_step3_supervisor_name', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['supervisor_name'];
                }

                return '';
            })
            ->editColumn('onboard_step3_address', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['street_address'];
                }

                return '';
            })
            ->editColumn('onboard_step3_postcode', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['postcode'];
                }

                return '';
            })
            ->editColumn('onboard_step3_telephone', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['telephone'];
                }

                return '';
            })
            ->editColumn('onboard_step3_email', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['email'];
                }

                return '';
            })
            ->editColumn('onboard_step3_website', function (Enrolment $report) {
                if (
                    !empty($report->onboard) &&
                    !empty($report['onboard']['step-3'])
                ) {
                    return $report['onboard']['step-3']['website'];
                }

                return '';
            })
            ->editColumn('onboard_step5_signed_on', function (Enrolment $report) {
                if (!empty($report->onboard)) {
                    // Check step-6 first (new format after June 2025)
                    if (!empty($report['onboard']['step-6'])) {
                        $signedOn = $report['onboard']['step-6']['signed_on'] ?? '';
                        if (!empty($signedOn)) {
                            // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                            $carbonDate = is_numeric($signedOn)
                                ? \Carbon\Carbon::createFromTimestamp($signedOn)
                                : \Carbon\Carbon::parse($signedOn);
                            return $carbonDate->format('d/m/Y');
                        }
                    }
                    // Fallback to step-5 for old data (backwards compatibility)
                    if (!empty($report['onboard']['step-5'])) {
                        $signedOn = $report['onboard']['step-5']['signed_on'] ?? '';
                        if (!empty($signedOn)) {
                            // Handle both epoch timestamp (legacy) and ISO 8601 date string (new format)
                            $carbonDate = is_numeric($signedOn)
                                ? \Carbon\Carbon::createFromTimestamp($signedOn)
                                : \Carbon\Carbon::parse($signedOn);
                            return $carbonDate->format('d/m/Y');
                        }
                    }
                }

                return '';
            })
            ->filterColumn('student', function ($query, $keyword) {
                if (empty($keyword)) {
                    return $query;
                }
                // Search for student name in the users table (case-insensitive)
                // Use whereHas to avoid interfering with the GROUP BY clause
                // Search in first_name, last_name, or concatenated full name
                return $query->whereHas('user', function ($q) use ($keyword) {
                    $searchTerm = '%' . strtolower($keyword) . '%';
                    $q->where(function ($subQuery) use ($searchTerm) {
                        $subQuery->whereRaw('LOWER(users.first_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(users.last_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(CONCAT(users.first_name, " ", users.last_name)) LIKE ?', [$searchTerm]);
                    });
                });
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (empty($keyword)) {
                    return $query;
                }
                // Search for name in the users table (case-insensitive)
                // Use whereHas to avoid interfering with the GROUP BY clause
                // This handles both 'name' and 'users.name' column references
                // Search in first_name, last_name, or concatenated full name
                return $query->whereHas('user', function ($q) use ($keyword) {
                    $searchTerm = '%' . strtolower($keyword) . '%';
                    $q->where(function ($subQuery) use ($searchTerm) {
                        $subQuery->whereRaw('LOWER(users.first_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(users.last_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(CONCAT(users.first_name, " ", users.last_name)) LIKE ?', [$searchTerm]);
                    });
                });
            })
            ->filterColumn('users.name', function ($query, $keyword) {
                if (empty($keyword)) {
                    return $query;
                }
                // Search for name in the users table (case-insensitive)
                // Handle explicit 'users.name' column reference
                // Search in first_name, last_name, or concatenated full name
                return $query->whereHas('user', function ($q) use ($keyword) {
                    $searchTerm = '%' . strtolower($keyword) . '%';
                    $q->where(function ($subQuery) use ($searchTerm) {
                        $subQuery->whereRaw('LOWER(users.first_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(users.last_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(CONCAT(users.first_name, " ", users.last_name)) LIKE ?', [$searchTerm]);
                    });
                });
            })
            ->filterColumn('enrolments.name', function ($query, $keyword) {
                if (empty($keyword)) {
                    return $query;
                }
                // Search for name in the users table (case-insensitive)
                // Handle 'enrolments.name' column reference (name doesn't exist in enrolments, search users instead)
                // Search in first_name, last_name, or concatenated full name
                return $query->whereHas('user', function ($q) use ($keyword) {
                    $searchTerm = '%' . strtolower($keyword) . '%';
                    $q->where(function ($subQuery) use ($searchTerm) {
                        $subQuery->whereRaw('LOWER(users.first_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(users.last_name) LIKE ?', [$searchTerm])
                                 ->orWhereRaw('LOWER(CONCAT(users.first_name, " ", users.last_name)) LIKE ?', [$searchTerm]);
                    });
                });
            })
            ->filterColumn('user.email', function ($query, $keyword) {
                if (empty($keyword)) {
                    return $query;
                }
                // Search for email in the users table (case-insensitive)
                return $query->whereHas('user', function ($q) use ($keyword) {
                    $q->whereRaw('LOWER(users.email) LIKE ?', ['%' . strtolower($keyword) . '%']);
                });
            })
            ->filterColumn('onboard_step1_gender', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_dob', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step4_usi_number', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_mobile', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_home_phone', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step4_nominate_usi', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_emergency_contact_name', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_relationship_to_you', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_emergency_contact_number', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_residence_address', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_residence_address_postcode', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_postal_address', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_postal_address_postcode', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_country', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_birthplace', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_language', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_english_proficiency', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_torres_island', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_has_disability', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_disabilities', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_industry1', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_industry2', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step1_employment', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step4_study_reason', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step2_school_level', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step2_secondary_level', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step2_additional_qualification', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_organization_name', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_your_position', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_supervisor_name', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_address', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_postcode', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_telephone', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_email', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step3_website', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('onboard_step5_signed_on', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            ->filterColumn('enrolments.onboard_step2_secondary_level', function ($query, $keyword) {
                // This column is computed from JSON, not a real database column
                return $query;
            })
            //            ->filterColumn( 'student_course_start_date', function ( $query, $keyword ) {
            //                $searchVal = isset( $keyword ) ? json_decode( $keyword ) : '';
            //                if ( !empty( $searchVal ) && isset( $searchVal->start ) ) {
            // //                    dd(Carbon::parse($this->search->start), Carbon::parse($this->search->end));
            //                    return $query->whereDate( 'created_at', '>=', $searchVal->start )
            //                                 ->whereDate( 'created_at', '<=', $searchVal->end );
            //                }
            //            } )
            ->addColumn('enrolment_changes', function (Enrolment $report) {
                return $this->getEnrolmentChanges($report);
            })
            ->addColumn('action', 'enrolmentreport.action')
            ->rawColumns([
                'student',
                'basic_schedule',
                'basic_employment_service',
                'onboard_gender',
                'onboard_step2_additional_qualification',
                'onboard_step1_residence_address_postcode',
                'onboard_step1_postal_address_postcode',
                'onboard_step1_english_proficiency',
                'enrolment_changes',
            ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\Enrolment $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(Enrolment $model) {
        // select user_id , GROUP_CONCAT( `enrolment_value`) as enrolment_value , GROUP_CONCAT(`enrolment_key`) as enrolment_key  from `enrolments` group by `user_id`
        return $model
            ->newQuery()
            ->with(['user'])
            ->selectRaw(
                "enrolments.id, enrolments.user_id, MAX(CASE WHEN (enrolments.enrolment_key = 'onboard' OR enrolments.enrolment_key REGEXP '^onboard[0-9]+$') AND enrolments.is_active = 1 THEN enrolments.enrolment_value END) as onboard"
            )
            ->where('enrolments.enrolment_value', '!=', '[]')
            ->groupBy('enrolments.user_id')
            ->havingRaw('onboard IS NOT NULL');
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html() {
        return $this->builder()
            ->setTableId('enrolment-report-table')
            ->addTableClass(['table-responsive', 'display', 'nowrap'])
            ->responsive(false)
            ->columns($this->getColumns())
            ->minifiedAjax()
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
    protected function getColumns() {
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
            Column::make('id')
                ->searchable(false)
                ->exportable(false),
            Column::make('id')
                ->orderable(false)
                ->searchable(false)
                ->visible(false)
                ->exportable(false),
            //            Column::computed( 'action' )
            //                  ->exportable( FALSE )
            //                  ->printable( FALSE )
            //                  ->width( 60 )
            //                  ->addClass( 'text-center text-nowrap' ),
            Column::make('user_id')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true)
                ->visible(false),
            Column::make('title')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true)
                ->title('Title'),
            Column::make('student')
                ->orderable(false)
                ->exportable(true)
                ->title('Student'),
            Column::make('onboard_step1_gender')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true)
                ->title('Gender'),
            Column::make('onboard_step1_dob')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true)
                ->title('Date of Birth'),
            Column::make('onboard_step4_usi_number')
                ->title('USI')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('user.email')
                ->title('Student Email')
                ->orderable(false)
                ->exportable(true),
            Column::make('onboard_step1_mobile')
                ->title('Student Mobile')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_home_phone')
                ->title('Student Phone')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step4_nominate_usi')
                ->title('Preferred contact method')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_emergency_contact_name')
                ->title('Emergency contact person')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_relationship_to_you')
                ->title('Emergency contact relationship')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_emergency_contact_number')
                ->title('Emergency contact number')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_residence_address')
                ->title('Residential Address')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_residence_address_postcode')
                ->title('Residential Postcode')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_postal_address')
                ->title('Postal Address')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_postal_address_postcode')
                ->title('Postal Postcode')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_country')
                ->title('Country')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_birthplace')
                ->title('Birthplace')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_language')
                ->title('Language')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_english_proficiency')
                ->title('English Proficiency')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_torres_island')
                ->title('Indigenous Status')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_has_disability')
                ->title('Has Disability')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_disabilities')
                ->title('Disabilities')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_industry1')
                ->title('Industry1')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_industry2')
                ->title('Industry2')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step1_employment')
                ->title('Employment')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step4_study_reason')
                ->title('Study Reason')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step2_school_level')
                ->title('School Level')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step2_secondary_level')
                ->title('Secondary Level')
                ->orderable(false)
                ->exportable(true),
            Column::make('onboard_step2_additional_qualification')
                ->title('Additional Qualification')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_organization_name')
                ->title('Organization Name')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_your_position')
                ->title('Your Position')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_supervisor_name')
                ->title('Supervisor Name')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_address')
                ->title('Organization Address')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_postcode')
                ->title('Organization Postcode')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_telephone')
                ->title('Organization Phone')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_email')
                ->title('Organization Email')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step3_website')
                ->title('Organization Website')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
            Column::make('onboard_step5_signed_on')
                ->title('Agreement Date')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true)
                ->edit(function ($value, $row) {
                    if (is_numeric($value) && $value > 1000000000) {
                        return \Carbon\Carbon::createFromTimestamp(
                            $value
                        )->format('d/m/Y');
                    }
                    if (!empty($value)) {
                        try {
                            return \Carbon\Carbon::parse($value)->format(
                                'd/m/Y'
                            );
                        }
                        catch (\Exception $e) {
                            return $value;
                        }
                    }

                    return '';
                }),
            Column::make('enrolment_changes')
                ->title('Re-enrolment Changes')
                ->orderable(false)
                ->searchable(false)
                ->exportable(true),
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string {
        return 'EnrolmentReport_' . date('YmdHis');
    }

    /**
     * Get enrolment changes for re-enrolments
     */
    private function getEnrolmentChanges(Enrolment $report): string {
        $userId = $report->user_id;

        // Get current active enrolment
        $currentEnrolment = \App\Models\Enrolment::where('user_id', $userId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', true)
            ->first();

        if (!$currentEnrolment) {
            return '';
        }

        // Get previous inactive enrolment (most recent)
        $previousEnrolment = \App\Models\Enrolment::where('user_id', $userId)
            ->where(function($query) {
                $query->where('enrolment_key', 'onboard')
                      ->orWhereRaw("enrolment_key REGEXP '^onboard[0-9]+$'");
            })
            ->where('is_active', false)
            ->where('id', '!=', $currentEnrolment->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // If no previous enrolment, this is the first enrolment
        if (!$previousEnrolment) {
            return '';
        }

        // Compare enrolments
        $oldData = $previousEnrolment->enrolment_value->toArray();
        $newData = $currentEnrolment->enrolment_value->toArray();

        $changedFields = $this->detectEnrolmentChanges($oldData, $newData);

        if (empty($changedFields)) {
            return 'No changes';
        }

        $count = count($changedFields);
        return $count . ' change' . ($count !== 1 ? 's' : '');
    }

    /**
     * Detect changes between old and new enrolment data
     */
    private function detectEnrolmentChanges(array $oldData, array $newData): array {
        $changedFields = [];
        $fieldLabels = $this->getFieldLabels();

        // Get all step keys from both old and new data
        $allStepKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        // Compare each step
        foreach ($allStepKeys as $stepKey) {
            $oldStepData = $oldData[$stepKey] ?? [];
            $newStepData = $newData[$stepKey] ?? [];

            if (!is_array($oldStepData) || !is_array($newStepData)) {
                continue;
            }

            // Get all field keys from both old and new step data
            $allFieldKeys = array_unique(array_merge(array_keys($oldStepData), array_keys($newStepData)));

            // Compare each field in the step
            foreach ($allFieldKeys as $fieldKey) {
                // Skip document IDs as they're handled separately
                // Skip signed_on as it always changes between re-enrolments
                // Skip other procedural fields
                if (in_array($fieldKey, ['document1', 'document2', 'signed_on', 'agreement', 'completed_at', 'quiz_completed', 'ptr_excluded'])) {
                    continue;
                }

                $oldValue = $oldStepData[$fieldKey] ?? null;
                $newValue = $newStepData[$fieldKey] ?? null;

                // Check if field was added (didn't exist in old data)
                if (!isset($oldStepData[$fieldKey]) && isset($newStepData[$fieldKey])) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                    continue;
                }

                // Check if field was removed (existed in old but not in new)
                if (isset($oldStepData[$fieldKey]) && !isset($newStepData[$fieldKey])) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                    continue;
                }

                // Compare values (handle arrays and strings)
                if (is_array($oldValue) && is_array($newValue)) {
                    if (json_encode($oldValue) !== json_encode($newValue)) {
                        $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                        if (!in_array($fieldLabel, $changedFields)) {
                            $changedFields[] = $fieldLabel;
                        }
                    }
                } elseif ($oldValue !== $newValue) {
                    $fieldLabel = $fieldLabels[$stepKey][$fieldKey] ?? ucfirst(str_replace('_', ' ', $fieldKey));
                    if (!in_array($fieldLabel, $changedFields)) {
                        $changedFields[] = $fieldLabel;
                    }
                }
            }
        }

        return $changedFields;
    }

    /**
     * Get field labels for display
     */
    private function getFieldLabels(): array {
        return [
            'step-1' => [
                'title' => 'Title',
                'gender' => 'Gender',
                'dob' => 'Date of Birth',
                'home_phone' => 'Home Phone',
                'mobile' => 'Mobile',
                'birthplace' => 'Birthplace',
                'emergency_contact_name' => 'Emergency Contact Name',
                'relationship_to_you' => 'Relationship',
                'emergency_contact_number' => 'Emergency Contact Number',
                'residence_address' => 'Residence Address',
                'residence_address_postcode' => 'Residence Postcode',
                'postal_address' => 'Postal Address',
                'postal_address_postcode' => 'Postal Postcode',
                'country' => 'Country',
                'language' => 'Language',
                'language_other' => 'Other Language',
                'english_proficiency' => 'English Proficiency',
                'torres_island' => 'Torres Strait Islander',
                'has_disability' => 'Has Disability',
                'disabilities' => 'Disabilities',
                'need_assistance' => 'Needs Assistance',
                'industry1' => 'Industry 1',
                'industry2' => 'Industry 2',
                'employment' => 'Employment Status',
            ],
            'step-2' => [
                'school_level' => 'School Level',
                'secondary_level' => 'Secondary Level',
                'additional_qualification' => 'Additional Qualification',
                'higher_degree' => 'Higher Degree',
                'advanced_diploma' => 'Advanced Diploma',
                'diploma' => 'Diploma',
                'certificate4' => 'Certificate IV',
                'certificate3' => 'Certificate III',
                'certificate2' => 'Certificate II',
                'certificate1' => 'Certificate I',
                'certificate_any' => 'Other Certificate',
                'certificate_any_details' => 'Certificate Details',
            ],
            'step-3' => [
                'organization_name' => 'Organization Name',
                'your_position' => 'Position',
                'supervisor_name' => 'Supervisor Name',
                'street_address' => 'Street Address',
                'postcode' => 'Postcode',
                'telephone' => 'Telephone',
                'email' => 'Email',
                'website' => 'Website',
            ],
            'step-4' => [
                'usi_number' => 'USI Number',
                'nominate_usi' => 'Nominate USI',
                'study_reason' => 'Study Reason',
            ],
            'step-5' => [
                'ptr_excluded' => 'PTR Excluded',
                'quiz_completed' => 'PTR Quiz Completed',
                'completed_at' => 'PTR Completed At',
            ],
            'step-6' => [
                'agreement' => 'Agreement',
                'signed_on' => 'Signed On',
            ],
        ];
    }
}
