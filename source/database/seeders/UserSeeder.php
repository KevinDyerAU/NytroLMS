<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\StudentCourseEnrolment;
use App\Models\Timezone;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\AdminReportService;
use App\Services\CourseProgressService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //        Schema::disableForeignKeyConstraints();
        //        User::truncate();
        //        UserDetail::truncate();
        //        Schema::enableForeignKeyConstraints();
        $rootUser = User::create([
            'first_name' => 'Mohsin',
            'last_name' => 'A.',
            'username' => 'mohsin.adeel',
            'email' => 'mohsin@inceptionsol.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Mohsin@123'),
        ]);
        $rootUser->assignRole('Root');
        $rootCountry = Country::where('code', 'PK')->first();
        $rootUser->userProfile()->save(new UserDetail([
            'country_id' => $rootCountry->id,
            'language' => 'en',
            'timezone' => Timezone::where('name', '=', 'Asia/Karachi')->first()->name,
        ]));

        $adminUser = User::create([
            'first_name' => 'Luke',
            'last_name' => 'C.',
            'username' => 'lukec',
            'email' => 'luke@keyinstitute.com.au',
            'email_verified_at' => now(),
            'password' => Hash::make('Luke@123'),
        ]);
        $adminUser->assignRole('Admin');
        $adminCountry = Country::where('code', 'AU')->first();
        $adminUser->userProfile()->save(new UserDetail([
            'country_id' => $adminCountry->id,
            'language' => 'en',
            'timezone' => Timezone::where('name', '=', 'Australia/Melbourne')->first()->name,
        ]));

        $studentUser = User::create([
            'first_name' => 'Test',
            'last_name' => 'Student',
            'username' => 'tests',
            'email' => 'mohsin.adeel@yahoo.com',
            'email_verified_at' => now(),
            'password_change_at' => now(),
            'password' => Hash::make('Mohsin@123'),
        ]);
        $studentUser->assignRole('Student');
        $studentCountry = Country::where('code', 'PK')->first();
        $studentUser->userProfile()->save(new UserDetail([
            'country_id' => $studentCountry->id,
            'language' => 'en',
            'timezone' => Timezone::where('name', '=', 'Australia/Melbourne')->first()->name,
            'status' => 'ONBOARDED',
        ]));
        $studentUser->enrolments()->save(
            (new Enrolment([
                'enrolment_key' => 'basic',
                'enrolment_value' => new Collection([
                    'schedule' => '25 Hours',
                    'employment_service' => 'Workforce Australia',
                ]),
            ]))
        );

        $courseId = config('constants.precourse_quiz_id', 99999);
        $course = Course::find($courseId);
        if (!empty($course)) {
            $student = $studentUser;
            $data = [
                'user_id' => $student->id,
                'course_id' => intval($course->id),
                'allowed_to_next_course' => false,
                'course_start_at' => '2022-01-01 00:00:00',
                'course_ends_at' => '2022-12-31 00:00:00',
                'status' => 'ENROLLED',
            ];
            $record = StudentCourseEnrolment::updateOrCreate(['user_id' => $student->id, 'course_id' => $data['course_id']], $data);
            $student->detail()->update(['status' => 'ENROLLED']);

            CourseProgressService::initProgressSession($student->id, $course->id, $record);
            CourseProgressService::updateStudentCourseStats($record, 0);

            $adminReportService = new AdminReportService($student->id, $course->id);
            $adminReportService->update($adminReportService->prepareData($student, $course), $record);
        }
        //        $studentUser->enrolments()->save((new Enrolment([
        //            'enrolment_key' => 'onboard',
        //            'enrolment_value' => json_decode('{"step-1":{"title":"Mr","gender":"male","dob":"1985-04-03","home_phone":"121346","mobile":"3123","emergency_contact_name":"No One","relationship_to_you":"NILL","emergency_contact_number":"00","residence_address":"Faraday Street, Carlton VIC, Australia","postal_address":"Faraday Street, Carlton VIC, Australia","country":"Australia","language":"English","language_other":null,"torres_island":"No","has_disability":"no","disabilities":"","need_assistance":"","industry1":"Mining","industry2":"Clerical and administrative workers","industry2_other":null,"employment":"Self employed \u2013 employing others"},"step-2":{"school_level":"Completed Year 12","secondary_level":"No","higher_degree":"International","advanced_diploma":"N\/A","diploma":"N\/A","certificate4":"N\/A","certificate3":"N\/A","certificate2":"N\/A","certificate1":"N\/A","certificate_any":"N\/A","certificate_any_details":""},"step-3":{"organization_name":null,"your_position":null,"supervisor_name":null,"street_address":null,"suburb_locality":null,"state_territory":null,"postcode":null,"telephone":null,"fax":null,"email":null,"website":null},"step-4":{"study_reason":"To start my own business","usi_number":"123156","nominate_usi":"Email","document1_type":"","document2_type":""},"step-5":{"agreement":"agree","signed_on":1648473230}}', true)
        //        ]))
        //        );
    }
}
