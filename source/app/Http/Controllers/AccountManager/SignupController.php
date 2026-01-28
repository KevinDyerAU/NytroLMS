<?php

namespace App\Http\Controllers\AccountManager;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\SignupLink;
use App\Models\StudentCourseEnrolment;
use App\Models\User;
use App\Models\UserDetail;
use App\Notifications\AnacondaAccountNotification;
use App\Notifications\NewAccountNotification;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use App\Services\StudentActivityService;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SignupController extends Controller
{
    public StudentActivityService $activityService;

    protected array $employment_service;

    protected array $schedule;

    public function __construct(StudentActivityService $activityService) {
        $this->employment_service = ['Workforce Australia', 'Inclusive Employment Australia (IEA)', 'Transition to Work (TTW)', 'Parent Pathways', 'Other'];
        $this->schedule = ['25 Hours', '15 Hours', '8 Hours', 'No Time Limit', 'Not Applicable'];
        $this->activityService = $activityService;
    }

    public function create(SignupLink $link, Request $request) {
        if (!$link->is_active) {
            abort(403, 'Invalid Link, Please contact admin. ');
        }

        if (\Auth::check()) {
            $pageConfigs = [
                'showMenu' => false,
                'layoutWidth' => 'full',
                'mainLayoutType' => 'horizontal',
                'footerType' => 'sticky',
                'blankPage' => false,
            ];

            return view('content.signup.error')->with([
                'message' => 'Already Registered.',
                'link' => [
                    'href' => '/',
                    'title' => 'Go back',
                ],
                'pageConfigs' => $pageConfigs,
                'data' => $link,
            ]);
        }

        $pageConfigs = ['showMenu' => false, 'layoutWidth' => 'full', 'footerType' => 'sticky'];
        $breadcrumbs = [
            ['link' => '/', 'name' => 'Home'],
            ['name' => 'Register Student'],
        ];

        return view(
            'content.signup.create',
            [
                'breadcrumbs' => [],
                'pageConfigs' => $pageConfigs,
                'data' => $link,
            ]
        );
    }

    public function store(SignupLink $link, Request $request) {
        $validated = $request->validate([
            'first_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'last_name' => ['required', 'max:255', 'regex:/^[A-Za-z0-9\s]+/'],
            'email' => 'required|unique:users',
            'phone' => ['required', 'regex:/^[\+0-9]+/'],
            'timezone' => 'required|exists:timezones,name',
            'password' => 'required|confirmed|min:6',
        ],
            [
            'phone.regex' => 'A valid phone is required',
        ]
        );

        $student = User::create(
            [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->first_name . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]
        );
        $student->detail()->save(
            new UserDetail(
                [
            'purchase_order' => 'N/A',
            'phone' => $request->phone ?? '',
            'address' => '',
            'preferred_language' => '',
            'language' => 'en',
            'signup_links_id' => $link->key,
            'signup_through_link' => true,
            'timezone' => $request->timezone,
        ]
            )
        );
        $student->assignRole('Student');

        $student->enrolments()->save((new Enrolment([
            'enrolment_key' => 'basic',
            'enrolment_value' => collect([
                'schedule' => 'Not Applicable',
                'employment_service' => 'Other',
            ]),
        ])));

        $student->leaders()->sync($link->leader_id);
        $student->companies()->sync($link->company_id);

        $this->assign_course_on_create($link, $student);

        $newCourse = $link->course;

        try {
            if (!empty($newCourse) && \Str::lower($newCourse->category) === 'anaconda') {
                $student->notify(new AnacondaAccountNotification('Student', $request->password));
            } else {
                $student->notify(new NewAccountNotification('Student', $request->password));
            }

            event(new Registered($student));

            return redirect('/login')
                ->with('success', 'You have successfully registered.')
                ->with('info', 'Email with details sent at: ' . $request->email);
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            Log::error('Email failed to send during student signup: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'email' => $request->email,
                'exception' => $e
            ]);

            return redirect('/login')
                ->with('error', 'Account created successfully, but failed to send welcome email. Please contact support to resend your credentials.')
                ->with('warning', 'Error: Unable to connect to email server.');
        } catch (\Exception $e) {
            Log::error('Unexpected error sending email during student signup: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'email' => $request->email,
                'exception' => $e
            ]);

            return redirect('/login')
                ->with('error', 'Account created successfully, but failed to send welcome email. Please contact support.')
                ->with('warning', 'Email Server Error: ' . $e->getMessage());
        }
    }

    protected function assign_course_on_create(SignupLink $link, User $student) {
        $course = Course::find($link->course_id);
        $course_start_at = Carbon::today(Helper::getTimeZone())->format('Y-m-d');
        $course_ends_at = Carbon::today(Helper::getTimeZone())->addDays($course->course_length_days)->format('Y-m-d');
        $timeNow = '00:00:00';

        // Check if the course title contains "Semester 2"
        $isSemester2 = stripos(Str::lower($course->title), 'semester 2') !== false;
        $isMainCourse = !$isSemester2;

        $data = [
            'user_id' => $student->id,
            'course_id' => intval($course->id),
            'allowed_to_next_course' => 1,
            'course_start_at' => $course_start_at . ' ' . $timeNow,
            'course_ends_at' => $course_ends_at . ' ' . $timeNow,
            'status' => 'ENROLLED',
            'version' => $course->version,
            'is_chargeable' => $link->is_chargeable,
            'registration_date' => null, // Initial enrollment - do not set registration_date
            'registered_by' => $student->id,
            'registered_on_create' => 1,
            'show_registration_date' => 0, // Initial enrollment - do not show registration date
            'is_semester_2' => $isSemester2,
            'is_main_course' => $isMainCourse,
        ];
        $record1 = StudentCourseEnrolment::updateOrCreate(['user_id' => $student->id, 'course_id' => $data['course_id']], $data);
        $student->detail()->update(['status' => 'ENROLLED']);

        CourseProgressService::initProgressSession($student->id, $course->id, $record1);
        CourseProgressService::updateStudentCourseStats($record1, $isMainCourse);

        $adminReportService = new AdminReportService($student->id, $course->id);
        $adminReportService->update($adminReportService->prepareData($student, $course), $record1);
        $record2 = [];
        //        dd($course);
        if ($course->auto_register_next_course === 1) {
            //            dump('here');
            $record2 = $this->enrolNextCourse($course, $course_ends_at, $student, $link);
        }

        return [$record1, $record2];
    }


    // comment what this does
    // The assign_course_on_create function enrolls a new student into the specified course when they sign up.
    // It calculates the course start and end dates, sets up the enrollment data, and marks if the course is a main course or semester 2.
    // The function updates student info, initializes their course progress, and updates reports.
    // If the course is set to auto-register the next course, it enrolls the student into that as well.
    // Returns an array: [main course enrollment record, next course enrollment record if any].

    protected function enrolNextCourse(Course $course, $first_course_end_date, User $student, SignupLink $link) {
        $next_course = Course::find(intval($course->next_course));
        //        dump($next_course);
        if (empty($next_course)) {
            return false;
        }
        $timeNow = '00:00:00';

        $next_course_start_date = Carbon::parse(filter_var($first_course_end_date, FILTER_SANITIZE_NUMBER_INT) . ' ' . $timeNow)
            ->addDays(intval($course->next_course_after_days));
        //        dd($first_course_end_date, $next_course_start_date);
        $next_course_end_date = $next_course_start_date->clone()->addDays($next_course->course_length_days);
        // Log::info( [ filter_var( $first_course_end_date, FILTER_SANITIZE_NUMBER_INT ), Carbon::parse( filter_var( $first_course_end_date, FILTER_SANITIZE_NUMBER_INT ) . ' ' . $timeNow )->toDateTime(), $next_course_start_date->toDateTime(), $next_course->course_length_days, $next_course_end_date->toDateTime() ] );
        $next_course_data = [
            'user_id' => $student->id,
            'course_id' => intval($next_course->id),
            'allowed_to_next_course' => 0,
            'course_start_at' => $next_course_start_date->toDateTime(),
            'course_ends_at' => $next_course_end_date->toDateTime(),
            'status' => 'ENROLLED',
            'is_chargeable' => $link->is_chargeable,
            'registration_date' => null, // Initial enrollment - do not set registration_date
            'registered_by' => $student->id,
            'registered_on_create' => 1,
            'show_registration_date' => 0, // Initial enrollment - do not show registration date
            'is_semester_2' => 1,
            'is_main_course' => 0,
        ];
        //        dump($next_course_data);
        $record = StudentCourseEnrolment::updateOrCreate(['user_id' => intval($student->id), 'course_id' => intval($next_course->id)], $next_course_data);

        CourseProgressService::initProgressSession($student->id, $next_course->id, $record);
        CourseProgressService::updateStudentCourseStats($record, 0);

        $adminReportService = new AdminReportService($student->id, $next_course->id);
        $adminReportService->update($adminReportService->prepareData($student, $next_course), $record);

        return $record;
    }
}
