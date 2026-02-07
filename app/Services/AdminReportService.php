<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Models\AdminReport;
use App\Models\Company;
use App\Models\Course;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AdminReportService
{
    public $user_id;

    public $course_id;

    public function __construct($user_id, $course_id)
    {
        $this->user_id = intval($user_id);
        $this->course_id = intval($course_id);
    }

    /**
     * Get caller information for debugging.
     *
     * @return string
     */
    private function getCallerInfo(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // Skip the current method and get the caller
        for ($i = 1; $i < count($trace); $i++) {
            if (isset($trace[$i]['class']) && $trace[$i]['class'] !== self::class) {
                $class = $trace[$i]['class'];
                $method = $trace[$i]['function'] ?? 'unknown';
                $file = basename($trace[$i]['file'] ?? 'unknown');
                $line = $trace[$i]['line'] ?? 'unknown';

                return "{$class}::{$method}() in {$file}:{$line}";
            }
        }

        return 'unknown caller';
    }

    private static function getDefaultData(User $student): array
    {
        $leader = $student->leaders()->first();
        $trainer = $student->trainers()->first();
        $company = $student->companies()->first();

        $data = [
            'student_id' => $student->id,
            'student_details' => array_merge(['name' => $student->name, 'email' => $student->email, 'study_type' => $student->study_type], $student->detail->toArray(), ['enrolment' => !empty($student->enrolments) ? $student->enrolments->toArray() : []]),
            'student_status' => $student->detail->status,
            'student_last_active' => $student->detail->getRawOriginal('last_logged_in'),
            'leader_id' => $leader?->id,
            'leader_details' => (!empty($leader) && $leader->count() > 0) ? array_merge(['name' => $leader->name, 'email' => $leader->email], $leader->user->detail->toArray()) : [],
            'leader_last_active' => $leader?->user->detail->getRawOriginal('last_logged_in'),
            'trainer_id' => $trainer?->id,
            'trainer_details' => (!empty($trainer) && $trainer->count() > 0) ? array_merge(['name' => $trainer->name, 'email' => $trainer->email], $trainer->user->detail->toArray()) : [],
            'company_id' => $company?->id,
            'company_details' => $company?->toArray(),
        ];

        return $data;
    }

    public function get()
    {
        return AdminReport::where('student_id', $this->user_id)
            ->where('course_id', $this->course_id)->firstOrFail();
    }

    public static function countStudentWithCourse($student_id)
    {
        return AdminReport::where('student_id', $student_id)->count();
    }

    public function save($data)
    {
        return AdminReport::create($data);
    }

    public function update($data, $enrolment = null)
    {
        // Early return for PTR/LLN courses - they don't have normal enrollments
        if (intval($this->course_id) === intval(config('lln.course_id')) || intval($this->course_id) === intval(config('ptr.course_id')) || intval($this->course_id) === 0) {
            return;
        }

        // Get caller information for debugging
        $caller = $this->getCallerInfo();

        if (!empty($this->course_id) && !empty($this->user_id)) {
            unset($data['student_id']);
            unset($data['course_id']);

            AdminReport::updateOrCreate(
                ['student_id' => $this->user_id, 'course_id' => $this->course_id],
                $data
            );
        } else {
            AdminReport::where('student_id', $this->user_id)
                ->update($data);
        }

        $adminReport = AdminReport::where('student_id', $this->user_id)
            ->where('course_id', $this->course_id)
            ->first();

        if (empty($adminReport)) {
            \Log::error("AdminReportService -> AdminReport not found for user_id: {$this->user_id} and course_id: {$this->course_id}");

            return;
        }

        if (empty($enrolment)) {
            $enrolment = StudentCourseEnrolment::where('user_id', $this->user_id)
                ->where('course_id', $this->course_id)
                ->first();
        }

        if (empty($enrolment)) {
            \Log::error('AdminReportService -> StudentCourseEnrolment not found for user_id: ' . $this->user_id . ' and course_id: ' . $this->course_id . '. Caller: ' . $caller);
        }

        if (!empty($enrolment) && empty($enrolment->admin_reports_id) && !empty($adminReport->id)) {
            $enrolment->admin_reports_id = $adminReport->id;
            $enrolment->save();
        }

        return $adminReport;
    }

    public static function softDelete($student_id, $unassignedCourses)
    {
        return AdminReport::where('student_id', $student_id)->whereNotIn('course_id', $unassignedCourses)->update(['course_status' => 'DELIST']);
    }

    public function prepareData(User $student, ?Course $course = null)
    {
        $leader = $student->leaders()->first();
        $trainer = $student->trainers()->first();
        $company = $student->companies()->first();

        $data = [
            'student_id' => $student->id,
            'student_details' => array_merge(['name' => $student->name, 'email' => $student->email, 'study_type' => $student->study_type], $student->detail->toArray(), ['enrolment' => !empty($student->enrolments) ? $student->enrolments->toArray() : []]),
            'student_status' => $student->detail->fresh()->status,
            'student_last_active' => !empty($student->detail) ? $student->detail->getRawOriginal('last_logged_in') : null,
            'leader_id' => $leader?->id,
            'leader_details' => (!empty($leader) && $leader->count() > 0) ? array_merge(['name' => $leader->name, 'email' => $leader->email], $leader->user->detail->toArray()) : [],
            'leader_last_active' => $leader?->user->detail->getRawOriginal('last_logged_in'),
            'trainer_id' => $trainer?->id,
            'trainer_details' => (!empty($trainer) && $trainer->count() > 0) ? array_merge(['name' => $trainer->name, 'email' => $trainer->email], $trainer->user->detail->toArray()) : [],
            'company_id' => $company?->id,
            'company_details' => $company?->toArray(),
        ];

        if (empty($course)) {
            return $data;
        }

        $enrolment = StudentCourseEnrolment::where('user_id', $this->user_id)->where('course_id', $this->course_id)->first();
        $courseProgress = CourseProgressService::getProgress($this->user_id, $this->course_id);
        //        $isComplete = !empty( $courseProgress ) ? $courseProgress->details[ 'completed' ] : FALSE;
        //        dd(!empty( $enrolment ) );
        if (!empty($enrolment)) {
            $data['course_details'] = [
                'title' => $course->title,
                'course_length' => $course->course_length_days,
                'enrolment_id' => $enrolment->id,
                'deferred' => $enrolment->getRawOriginal('deferred'),
                'is_chargeable' => $enrolment->is_chargeable,
                'registration_date' => $enrolment->registration_date,
                'registered_by' => $enrolment->registered_by,
                'registered_on_create' => $enrolment->registered_on_create,
                'show_registration_date' => $enrolment->show_registration_date,
                'is_locked' => $enrolment->is_locked,
                'is_main_course' => $enrolment->is_main_course,
                'is_semester_2' => $enrolment->is_semester_2,
                'certificate' => empty($enrolment->cert_issued) ? [] : [
                    'cert_issued' => 1,
                    'cert_issued_on' => $enrolment->cert_issued_on,
                    'cert_issued_by' => [
                        'id' => $enrolment->getRawOriginal('cert_issued_by'),
                        'name' => $enrolment->cert_issued_by,
                    ],
                    'cert_details' => $enrolment->cert_details,
                ],
                //                'deferred_details' => $enrolment->getRawOriginal( 'deferred_details' ),
            ];
            //            dd($data['course_details'], $enrolment->toArray());
            $data['student_status'] = $data['student_status'] === 'ENROLLED' ? $enrolment->status : $data['student_status'];
            $data['student_course_start_date'] = $enrolment->getRawOriginal('course_start_at');
            $data['student_course_end_date'] = $enrolment->getRawOriginal('course_ends_at');
            $data['allowed_to_next_course'] = $enrolment->getRawOriginal('allowed_to_next_course');
            $data['course_completed_at'] = !empty($enrolment->course_completed_at) ? $enrolment->getRawOriginal('course_completed_at') : null;

            $data['student_course_progress'] = CourseProgressService::getCourseStats($this->user_id, $this->course_id);
            $data['course_status'] = $this->getCourseStatus($courseProgress, $enrolment);
            $data['course_expiry'] = CourseProgressService::calculateCourseExpiry($enrolment);

            if (!empty($courseProgress)) {
                $this->getCourseData($courseProgress, $data);
            }
        } else {
            $data['course_status'] = 'DELIST';
        }
        $data['is_main_course'] = (Str::contains(Str::lower($course->title), 'semester 2')) ? 0 : 1;

        //        dd(['AdminReportService' => $data]);
        return $data;
    }

    public static function updateStudent(User $student, $data)
    {
        if (self::countStudentWithCourse($student->id) > 0) {
            return AdminReport::where('student_id', $student->id)->update(
                $data
            );
        }
    }

    public static function updateStudentWithoutRelation(User $student)
    {
        $data = [
            'student_details' => array_merge(['name' => $student->name, 'email' => $student->email, 'study_type' => $student->study_type], $student->detail->toArray(), ['enrolment' => !empty($student->enrolments) ? $student->enrolments->toArray() : []]),
            'student_status' => $student->detail->status,
            'student_last_active' => !empty($student->detail) ? $student->detail->getRawOriginal('last_logged_in') : null,
        ];
        self::updateStudent($student, $data);
    }

    public static function updateStudentWithRelation(User $student)
    {
        $data = self::getDefaultData($student);
        //        dd($student, $data);

        self::updateStudent($student, $data);
    }

    public static function updateLeader(User $leader)
    {
        return AdminReport::where('leader_id', $leader->id)->update(
            [
            'leader_details' => array_merge(['name' => $leader->name, 'email' => $leader->email], $leader->detail->toArray()),
            'leader_last_active' => $leader->detail->getRawOriginal('last_logged_in'),
        ]
        );
    }

    public static function updateCompany(Company $company)
    {
        return AdminReport::where('company_id', $company->id)->update(
            [
            'company_details' => $company->toArray(),
        ]
        );
    }

    public static function updateTrainer(User $trainer)
    {
        return AdminReport::where('trainer_id', $trainer->id)->update(
            [
            'trainer_details' => array_merge(['name' => $trainer->name, 'email' => $trainer->email], $trainer->detail->toArray()),
        ]
        );
    }

    public function updateCourse(Course $course1)
    {
        $enrolment = StudentCourseEnrolment::where('user_id', $this->user_id)->where('course_id', $this->course_id)->first();
        $courseProgress = CourseProgressService::getProgress($this->user_id, $course1->id);
        $course = $enrolment->course;
        if (!empty($enrolment) && !empty($course)) {
            return $this->update(
                [
                'course_details' => [
                    'title' => $course->title,
                    'course_length' => $course->course_length_days,
                    'enrolment_id' => $enrolment->id,
                    'deferred' => $enrolment->getRawOriginal('deferred'),
                    'certificate' => empty($enrolment->cert_issued) ? [] : [
                        'cert_issued' => 1,
                        'cert_issued_on' => $enrolment->cert_issued_on,
                        'cert_issued_by' => [
                            'id' => $enrolment->getRawOriginal('cert_issued_by'),
                            'name' => $enrolment->cert_issued_by,
                        ],
                        'cert_details' => $enrolment->cert_details,
                    ],
                    //                    'deferred_details' => $enrolment->getRawOriginal( 'deferred_details' ),
                ],
                'course_status' => $this->getCourseStatus($courseProgress, $enrolment),
                'is_main_course' => (!empty($course) && Str::contains(Str::lower($course->title), 'semester 2')) ? 0 : 1,
                'student_course_start_date' => $enrolment->getRawOriginal('course_start_at'),
                'student_course_end_date' => $enrolment->getRawOriginal('course_ends_at'),
                'allowed_to_next_course' => $enrolment->getRawOriginal('allowed_to_next_course'),
            ]
            );
        }

        return false;
    }

    public function updateCourseProgress()
    {
        if (auth()->check() && auth()->user()?->isStudent()) {
            $student = auth()->user();
        } else {
            $student = User::find($this->user_id);
            if (!empty($student) && !$student->isStudent()) {
                return false;
            }
        }
        //        logger("updating progress for student", [$student->toArray()]);
        //        logger("updating progress for", ['student' => $this->user_id, 'course' => $this->course_id]);
        $courseProgress = CourseProgressService::getProgress($this->user_id, $this->course_id);
        $data = [];
        if ($courseProgress) {
            $this->getCourseData($courseProgress, $data);

            return $this->update($data);
        }

        return false;
    }

    public function updateProgress($resetStudent = true)
    {
        if (auth()->user()->isStudent()) {
            $student = auth()->user();
        } else {
            $student = User::find($this->user_id);
            if (!empty($student) && !$student->isStudent()) {
                return false;
            }
        }
        $courseProgress = CourseProgressService::getProgress($this->user_id, $this->course_id);
        $data = [];
        if ($courseProgress) {
            if ($resetStudent && auth()->user()->isStudent()) {
                $data = self::getDefaultData($student);
            }
            if (!empty($student->detail)) {
                $data['student_status'] = $student->detail->status;
                $data['student_last_active'] = $student->detail->getRawOriginal('last_logged_in');

                $this->getCourseData($courseProgress, $data);

                return $this->update($data);
            }
            \Log::warning('User/Student details missing', $student);

            return false;
        }

        return false;
    }

    public function getCourseStatus($courseProgress, $enrolment)
    {
        if (!empty($courseProgress) && !empty($courseProgress->details)) {
            if (!empty($enrolment) && $enrolment->status === 'DELIST') {
                return 'DELIST';
            }

            // Recalculate current course progress from fresh details for accurate status check
            $totalCounts = CourseProgressService::getTotalCounts($this->user_id, $courseProgress->details);
            $current_course_progress = CourseProgressService::calculatePercentage($totalCounts, $this->user_id, $this->course_id);

            // IF CP IS 100% but still have returned and pending assessments => ON SCHEDULE
            // if CP 100%, pending = 0,returned = 0 => COMPLETED
            if ((!empty($courseProgress->details['completed']) || $current_course_progress === 100.00)) {
                $quizzesCount = CourseProgressService::getTotalQuizzes($courseProgress->details, $this->user_id);
                if ($quizzesCount['remaining'] > 0 || $quizzesCount['failed'] > 0) {
                    return 'ON SCHEDULE';
                }

                return 'COMPLETED';
            }

            if (!empty($enrolment) && Carbon::parse($enrolment->getRawOriginal('course_ends_at'))->greaterThan(Carbon::today(Helper::getTimeZone()))) {
                return 'BEHIND SCHEDULE';
            }

            // Use the already calculated values from above
            $expected_course_progress = CourseProgressService::expectedPercentage($this->user_id, $this->course_id, $totalCounts);
            $gap = ($expected_course_progress >= $current_course_progress) ?
                ($expected_course_progress - $current_course_progress) : 0;

            // ON SCHEDULE => GAP < 30, BEHIND SCHEDULE > 30 and end date passed
            return ($gap <= 30) ? 'ON SCHEDULE' : 'BEHIND SCHEDULE';
        }

        return;
    }

    public function dateString($value): string
    {
        return Carbon::parse($value)->toDateString();
    }

    public static function setStudentActive(User $student)
    {
        self::setStudentStatus($student, 'ACTIVE');
    }

    public static function setStudentStatus(User $student, $status)
    {
        $student->detail->status = \Str::upper($status);
        $student->detail->save();

        self::updateStudentWithoutRelation($student);
    }

    public function getCourseData($courseProgress, array &$data): void
    {
        // Skip StudentCourseEnrolment lookup for PTR/LLN courses as they don't have normal enrollments
        $enrolment = null;
        if (intval($this->course_id) !== intval(config('lln.course_id')) && intval($this->course_id) !== intval(config('ptr.course_id')) && intval($this->course_id) !== 0) {
            $enrolment = StudentCourseEnrolment::where('user_id', $this->user_id)->where('course_id', $this->course_id)->first();
        }
        $course = Course::find($this->course_id);
        $quizzesCount = CourseProgressService::getTotalQuizzes($courseProgress->details, $this->user_id);
        $hoursReported = CourseProgressService::hoursReported($courseProgress->details, $this->user_id);

        // Recalculate current course progress from fresh details instead of using stored percentage
        $totalCounts = CourseProgressService::getTotalCounts($this->user_id, $courseProgress->details);
        $current_course_progress = CourseProgressService::calculatePercentage($totalCounts, $this->user_id, $this->course_id);

        // Use the recalculated totalCounts for expected percentage calculation
        $expectedPercentage = CourseProgressService::expectedPercentage($this->user_id, $this->course_id, $totalCounts);

        $data['student_course_progress'] = [
            'id' => $courseProgress->id,
            'percentage' => $current_course_progress,
            'details' => $courseProgress->details,
            'current_course_progress' => $current_course_progress,
            'expected_course_progress' => $expectedPercentage,
            'total_assignments' => $quizzesCount['total'],
            'total_assignments_remaining' => $quizzesCount['remaining'],
            'total_assignments_submitted' => $quizzesCount['submitted'],
            'total_assignments_satisfactory' => $quizzesCount['passed'],
            'total_assignments_not_satisfactory' => (!empty($quizzesCount['failed']) && $quizzesCount['failed'] > 0) ? $quizzesCount['failed'] : 0,
            'actual_reported' => (isset($hoursReported['actual']) ? $hoursReported['actual']['total'] : 0.00),
            'hours_reported' => (isset($hoursReported['reported']) ? $hoursReported['reported']['total'] : 0.00),
            'hours_reported_last_week' => ((isset($hoursReported['last_week'])) ? $hoursReported['last_week']['total'] : 0.00),
            'hours_details' => $hoursReported,
        ];
        if (!empty($course)) {
            $data['is_main_course'] = (Str::contains(Str::lower($course->title), 'semester 2')) ? 0 : 1;
            $data['course_details'] = [
                'title' => $course->title,
                'course_length' => $course->course_length_days,
            ];
        }
        if (!empty($enrolment)) {
            $data['course_status'] = $this->getCourseStatus($courseProgress, $enrolment);
            if (!empty($data['course_details'])) {
                $data['course_details']['enrolment_id'] = $enrolment->id;
                $data['course_details']['deferred'] = $enrolment->getRawOriginal('deferred');
                $data['course_details']['certificate'] = empty($enrolment->cert_issued) ? [] : [
                    'cert_issued' => 1,
                    'cert_issued_on' => $enrolment->cert_issued_on,
                    'cert_issued_by' => [
                        'id' => $enrolment->getRawOriginal('cert_issued_by'),
                        'name' => $enrolment->cert_issued_by,
                    ],
                    'cert_details' => $enrolment->cert_details,
                ];
                $data['course_details']['is_chargeable'] = $enrolment->is_chargeable;
                $data['course_details']['registration_date'] = $enrolment->registration_date;
                $data['course_details']['registered_by'] = $enrolment->registered_by;
                $data['course_details']['registered_on_create'] = $enrolment->registered_on_create;
                $data['course_details']['is_main_course'] = $enrolment->is_main_course;
                $data['course_details']['is_semester_2'] = $enrolment->is_semester_2;
            }
            //            $data['course_details']['deferred_details'] = $enrolment->getRawOriginal( 'deferred_details' );
            $data['student_course_start_date'] = $enrolment->getRawOriginal('course_start_at');
            $data['student_course_end_date'] = $enrolment->getRawOriginal('course_ends_at');
            $data['allowed_to_next_course'] = $enrolment->getRawOriginal('allowed_to_next_course');
        }
        //        \Log::info('Course Data to Update: ', [$this->user_id, $data['student_course_start_date']??0, $data['student_course_end_date']??0]);
    }

    public function updateStudentDetails($student_id)
    {
        self::updateStudentWithoutRelation(User::find($student_id));
    }
}
