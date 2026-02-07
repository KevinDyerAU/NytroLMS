<?php

namespace App\Services;

use App\Models\StudentCourseEnrolment;
use App\Models\User;

class StudentSyncService
{
    public function __construct()
    {
        //
    }

    public function syncProfile(StudentCourseEnrolment $enrolment): void
    {
        $student = User::find($enrolment->user_id);
        if (!$student) {
            return;
        }
        //        if( $student->id === 13 ) {
        //            //throw exception
        //            throw new \Exception('Student ID 13 is not allowed to be processed.');
        //        }
        $isMainCourse = $enrolment->course?->is_main_course
            || !\Str::contains(\Str::lower($enrolment->course?->title), 'emester 2');

        CourseProgressService::initProgressSession($student->id, $enrolment->course_id, $enrolment);
        CourseProgressService::updateStudentCourseStats($enrolment, $isMainCourse);
        CourseProgressService::setupExpiryDate($enrolment);

        // update course progress on admin_reports
        $adminReportService = new AdminReportService($student->id, $enrolment->course_id);
        $adminReportService->update($adminReportService->prepareData($student, $enrolment->course), $enrolment);

        $enrolment->touch();
    }
}
