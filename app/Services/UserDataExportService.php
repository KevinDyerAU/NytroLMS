<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UserDataExportService
{
    /**
     * Generate SQL queries to export user data.
     *
     * @return array Array of SQL queries with comments
     */
    public function generateUserDataQueries(int $userId): array
    {
        $queries = [];
        $summary = [];

        // Get user data
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return $queries;
        }

        // Get enrolled courses
        $enrolledCourses = DB::table('student_course_enrolments')
            ->where('user_id', $userId)
            ->pluck('course_id')
            ->toArray();

        // User basic information
        $queries[] = '-- User basic information';
        $queries[] = "INSERT INTO users (
            id, first_name, last_name, email, password, remember_token, 
            email_verified_at, userable_type, userable_id, is_active, is_archived,
            password_change_at, created_at, updated_at
        ) VALUES (
            {$user->id}, 
            '".addslashes($user->first_name)."', 
            '".addslashes($user->last_name)."', 
            '".addslashes($user->email)."', 
            '".addslashes($user->password)."', 
            ".($user->remember_token ? "'".addslashes($user->remember_token)."'" : 'NULL').', 
            '.($user->email_verified_at ? "'".$user->email_verified_at."'" : 'NULL').', 
            '.($user->userable_type ? "'".addslashes($user->userable_type)."'" : 'NULL').', 
            '.($user->userable_id ? $user->userable_id : 'NULL').', 
            '.($user->is_active ? 'TRUE' : 'FALSE').', 
            '.($user->is_archived ? 'TRUE' : 'FALSE').', 
            '.($user->password_change_at ? "'".$user->password_change_at."'" : 'NULL').", 
            '".$user->created_at."', 
            '".$user->updated_at."'
        ) ON DUPLICATE KEY UPDATE 
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            email = VALUES(email),
            password = VALUES(password),
            remember_token = VALUES(remember_token),
            email_verified_at = VALUES(email_verified_at),
            userable_type = VALUES(userable_type),
            userable_id = VALUES(userable_id),
            is_active = VALUES(is_active),
            is_archived = VALUES(is_archived),
            password_change_at = VALUES(password_change_at),
            updated_at = VALUES(updated_at);";
        $summary['users'] = 1;

        // User profile details
        $userDetails = DB::table('user_details')
            ->where('user_id', $userId)
            ->first();

        if ($userDetails) {
            // Ensure registered_by is always 0 if not set
            $registeredBy = isset($userDetails->registered_by) && $userDetails->registered_by !== null ?
                $userDetails->registered_by : 0;

            $queries[] = '-- User profile details';
            $queries[] = "INSERT INTO user_details (
                id, user_id, avatar, phone, address, language, country_id, timezone, 
                last_logged_in, onboard_at, status, preferred_language, position, 
                registered_by, purchase_order, first_enrollment, first_login, 
                preferred_name, signup_through_link, signup_links_id, created_at, updated_at
            ) VALUES (
                {$userDetails->id}, 
                {$userDetails->user_id}, 
                ".($userDetails->avatar ? "'".addslashes($userDetails->avatar)."'" : 'NULL').', 
                '.($userDetails->phone ? "'".addslashes($userDetails->phone)."'" : 'NULL').', 
                '.($userDetails->address ? "'".addslashes($userDetails->address)."'" : 'NULL').', 
                '.($userDetails->language ? "'".addslashes($userDetails->language)."'" : 'NULL').', 
                '.($userDetails->country_id ? $userDetails->country_id : 'NULL').', 
                '.($userDetails->timezone ? "'".addslashes($userDetails->timezone)."'" : 'NULL').', 
                '.($userDetails->last_logged_in ? "'".$userDetails->last_logged_in."'" : 'NULL').', 
                '.($userDetails->onboard_at ? "'".$userDetails->onboard_at."'" : 'NULL').', 
                '.($userDetails->status ? "'".addslashes($userDetails->status)."'" : 'NULL').', 
                '.($userDetails->preferred_language ? "'".addslashes($userDetails->preferred_language)."'" : 'NULL').', 
                '.($userDetails->position ? "'".addslashes($userDetails->position)."'" : 'NULL').", 
                {$registeredBy}, 
                ".($userDetails->purchase_order ? "'".addslashes($userDetails->purchase_order)."'" : 'NULL').', 
                '.($userDetails->first_enrollment ? "'".$userDetails->first_enrollment."'" : 'NULL').', 
                '.($userDetails->first_login ? "'".$userDetails->first_login."'" : 'NULL').', 
                '.($userDetails->preferred_name ? "'".addslashes($userDetails->preferred_name)."'" : 'NULL').', 
                '.($userDetails->signup_through_link ? 'TRUE' : 'FALSE').', 
                '.($userDetails->signup_links_id ? $userDetails->signup_links_id : 'NULL').', 
                '.($userDetails->created_at ? "'".$userDetails->created_at."'" : 'NULL').', 
                '.($userDetails->updated_at ? "'".$userDetails->updated_at."'" : 'NULL')."
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                avatar = VALUES(avatar),
                phone = VALUES(phone),
                address = VALUES(address),
                language = VALUES(language),
                country_id = VALUES(country_id),
                timezone = VALUES(timezone),
                last_logged_in = VALUES(last_logged_in),
                onboard_at = VALUES(onboard_at),
                status = VALUES(status),
                preferred_language = VALUES(preferred_language),
                position = VALUES(position),
                registered_by = {$registeredBy},
                purchase_order = VALUES(purchase_order),
                first_enrollment = VALUES(first_enrollment),
                first_login = VALUES(first_login),
                preferred_name = VALUES(preferred_name),
                signup_through_link = VALUES(signup_through_link),
                signup_links_id = VALUES(signup_links_id),
                created_at = VALUES(created_at),
                updated_at = VALUES(updated_at);";
            $summary['user_details'] = 1;
        }

        // User attachments
        $attachments = DB::table('user_has_attachables')
            ->where('user_id', $userId)
            ->get();

        foreach ($attachments as $attachment) {
            $queries[] = '-- User attachment';
            $queries[] = "INSERT INTO user_has_attachables (
                user_id, attachable_id, attachable_type
            ) VALUES (
                {$attachment->user_id}, 
                {$attachment->attachable_id}, 
                '{$attachment->attachable_type}'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                attachable_id = VALUES(attachable_id),
                attachable_type = VALUES(attachable_type);";
            $summary['attachments'] = ($summary['attachments'] ?? 0) + 1;
        }

        // Enrolments
        $enrolments = DB::table('enrolments')
            ->where('user_id', $userId)
            ->get();

        foreach ($enrolments as $enrolment) {
            $queries[] = '-- User enrolment';
            $queries[] = "INSERT INTO enrolments (
                id, user_id, enrolment_key, enrolment_value, created_at, updated_at
            ) VALUES (
                {$enrolment->id}, 
                {$enrolment->user_id}, 
                '".addslashes($enrolment->enrolment_key)."', 
                '".addslashes($enrolment->enrolment_value)."', 
                '".$enrolment->created_at."', 
                '".$enrolment->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                enrolment_key = VALUES(enrolment_key),
                enrolment_value = VALUES(enrolment_value),
                updated_at = VALUES(updated_at);";
            $summary['enrolments'] = ($summary['enrolments'] ?? 0) + 1;
        }

        // Student documents
        $documents = DB::table('student_documents')
            ->where('user_id', $userId)
            ->get();

        foreach ($documents as $document) {
            $queries[] = '-- Student document';
            $queries[] = "INSERT INTO student_documents (
                id, user_id, file_name, file_size, file_path, file_uuid, created_at, updated_at
            ) VALUES (
                {$document->id}, 
                {$document->user_id}, 
                '".addslashes($document->file_name)."', 
                {$document->file_size}, 
                '".addslashes($document->file_path)."', 
                '".$document->file_uuid."', 
                '".$document->created_at."', 
                '".$document->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                file_name = VALUES(file_name),
                file_size = VALUES(file_size),
                file_path = VALUES(file_path),
                file_uuid = VALUES(file_uuid),
                updated_at = VALUES(updated_at);";
            $summary['student_documents'] = ($summary['student_documents'] ?? 0) + 1;
        }

        // Student course enrolments
        $courseEnrolments = DB::table('student_course_enrolments')
            ->where('user_id', $userId)
            ->get();

        foreach ($courseEnrolments as $enrolment) {
            $queries[] = '-- Student course enrolment';
            $queries[] = "INSERT INTO student_course_enrolments (
                id, user_id, course_id, is_main_course, last_updated, student_course_stats_id,
                status, course_start_at, course_ends_at, deferred_details, cert_issued, cert_issued_on,
                cert_issued_by, cert_details, created_at, updated_at
            ) VALUES (
                {$enrolment->id}, 
                {$enrolment->user_id}, 
                {$enrolment->course_id}, 
                ".($enrolment->is_main_course ? 'TRUE' : 'FALSE').', 
                '.($enrolment->last_updated ? "'".$enrolment->last_updated."'" : 'NULL').', 
                '.($enrolment->student_course_stats_id ? $enrolment->student_course_stats_id : 'NULL').", 
                '".addslashes($enrolment->status)."', 
                ".($enrolment->course_start_at ? "'".$enrolment->course_start_at."'" : 'NULL').', 
                '.($enrolment->course_ends_at ? "'".$enrolment->course_ends_at."'" : 'NULL').', 
                '.($enrolment->deferred_details ? "'".addslashes($enrolment->deferred_details)."'" : 'NULL').', 
                '.($enrolment->cert_issued ? 'TRUE' : 'FALSE').', 
                '.($enrolment->cert_issued_on ? "'".$enrolment->cert_issued_on."'" : 'NULL').', 
                '.($enrolment->cert_issued_by ? $enrolment->cert_issued_by : 'NULL').', 
                '.($enrolment->cert_details ? "'".addslashes($enrolment->cert_details)."'" : 'NULL').", 
                '".$enrolment->created_at."', 
                '".$enrolment->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                course_id = VALUES(course_id),
                is_main_course = VALUES(is_main_course),
                last_updated = VALUES(last_updated),
                student_course_stats_id = VALUES(student_course_stats_id),
                status = VALUES(status),
                course_start_at = VALUES(course_start_at),
                course_ends_at = VALUES(course_ends_at),
                deferred_details = VALUES(deferred_details),
                cert_issued = VALUES(cert_issued),
                cert_issued_on = VALUES(cert_issued_on),
                cert_issued_by = VALUES(cert_issued_by),
                cert_details = VALUES(cert_details),
                updated_at = VALUES(updated_at);";
            $summary['student_course_enrolments'] = ($summary['student_course_enrolments'] ?? 0) + 1;
        }

        // Student course stats
        $courseStats = DB::table('student_course_stats')
            ->where('user_id', $userId)
            ->get();

        foreach ($courseStats as $stat) {
            $queries[] = '-- Student course stats';
            $queries[] = "INSERT INTO student_course_stats (
                id, user_id, course_id, next_course_id, pre_course_lesson_id,
                pre_course_attempt_id, pre_course_assisted, is_full_course_completed,
                can_issue_cert, created_at, updated_at
            ) VALUES (
                {$stat->id}, 
                {$stat->user_id}, 
                {$stat->course_id}, 
                ".($stat->next_course_id ? $stat->next_course_id : 'NULL').', 
                '.($stat->pre_course_lesson_id ? $stat->pre_course_lesson_id : 'NULL').', 
                '.($stat->pre_course_attempt_id ? $stat->pre_course_attempt_id : 'NULL').', 
                '.($stat->pre_course_assisted ? 'TRUE' : 'FALSE').', 
                '.($stat->is_full_course_completed ? 'TRUE' : 'FALSE').', 
                '.($stat->can_issue_cert ? 'TRUE' : 'FALSE').", 
                '".$stat->created_at."', 
                '".$stat->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                course_id = VALUES(course_id),
                next_course_id = VALUES(next_course_id),
                pre_course_lesson_id = VALUES(pre_course_lesson_id),
                pre_course_attempt_id = VALUES(pre_course_attempt_id),
                pre_course_assisted = VALUES(pre_course_assisted),
                is_full_course_completed = VALUES(is_full_course_completed),
                can_issue_cert = VALUES(can_issue_cert),
                updated_at = VALUES(updated_at);";
            $summary['student_course_stats'] = ($summary['student_course_stats'] ?? 0) + 1;
        }

        // Student activities
        $activities = DB::table('student_activities')
            ->where('user_id', $userId)
            ->get();

        foreach ($activities as $activity) {
            $queries[] = '-- Student activity';
            $queries[] = "INSERT INTO student_activities (
                id, user_id, activity_event, activity_on, activity_details,
                actionable_id, actionable_type, created_at, updated_at
            ) VALUES (
                {$activity->id}, 
                {$activity->user_id}, 
                '".addslashes($activity->activity_event)."', 
                '".$activity->activity_on."', 
                ".($activity->activity_details ? "'".addslashes($activity->activity_details)."'" : 'NULL').', 
                '.($activity->actionable_id ? $activity->actionable_id : 'NULL').', 
                '.($activity->actionable_type ? "'".addslashes($activity->actionable_type)."'" : 'NULL').", 
                '".$activity->created_at."', 
                '".$activity->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                activity_event = VALUES(activity_event),
                activity_on = VALUES(activity_on),
                activity_details = VALUES(activity_details),
                actionable_id = VALUES(actionable_id),
                actionable_type = VALUES(actionable_type),
                updated_at = VALUES(updated_at);";
            $summary['student_activities'] = ($summary['student_activities'] ?? 0) + 1;
        }

        // Student LMS attachables
        $lmsAttachables = DB::table('student_lms_attachables')
            ->where('student_id', $userId)
            ->get();

        foreach ($lmsAttachables as $attachable) {
            $queries[] = '-- Student LMS attachable';
            $queries[] = "INSERT INTO student_lms_attachables (
                id, student_id, event, description, properties, 
                causer_id, causer_type, attachable_id, attachable_type, 
                created_at, updated_at
            ) VALUES (
                {$attachable->id}, 
                {$attachable->student_id}, 
                '".addslashes($attachable->event)."', 
                ".($attachable->description ? "'".addslashes($attachable->description)."'" : 'NULL').', 
                '.($attachable->properties ? "'".addslashes($attachable->properties)."'" : 'NULL').', 
                '.($attachable->causer_id ? $attachable->causer_id : 'NULL').', 
                '.($attachable->causer_type ? "'".addslashes($attachable->causer_type)."'" : 'NULL').", 
                {$attachable->attachable_id}, 
                '".addslashes($attachable->attachable_type)."', 
                '".$attachable->created_at."', 
                '".$attachable->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                student_id = VALUES(student_id),
                event = VALUES(event),
                description = VALUES(description),
                properties = VALUES(properties),
                causer_id = VALUES(causer_id),
                causer_type = VALUES(causer_type),
                attachable_id = VALUES(attachable_id),
                attachable_type = VALUES(attachable_type),
                updated_at = VALUES(updated_at);";
            $summary['student_lms_attachables'] = ($summary['student_lms_attachables'] ?? 0) + 1;
        }

        // Notes
        $notes = DB::table('notes')
            ->where('user_id', $userId)
            ->get();

        foreach ($notes as $note) {
            $queries[] = '-- Note';
            $queries[] = "INSERT INTO notes (
                id, user_id, note, created_at, updated_at
            ) VALUES (
                {$note->id}, 
                {$note->user_id}, 
                '".addslashes($note->note)."', 
                '".$note->created_at."', 
                '".$note->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                note = VALUES(note),
                updated_at = VALUES(updated_at);";
            $summary['notes'] = ($summary['notes'] ?? 0) + 1;
        }

        // Lesson end dates
        $lessonEndDates = DB::table('lesson_end_dates')
            ->where('student_id', $userId)
            ->get();

        foreach ($lessonEndDates as $endDate) {
            $queries[] = '-- Lesson end date';
            $queries[] = "INSERT INTO lesson_end_dates (
                id, student_id, course_id, lesson_id, end_date, 
                competency_date, work_placement_date, checklist_date, 
                last_quiz_marked_date, created_at, updated_at
            ) VALUES (
                {$endDate->id}, 
                {$endDate->student_id}, 
                {$endDate->course_id}, 
                {$endDate->lesson_id}, 
                ".($endDate->end_date ? "'".$endDate->end_date."'" : 'NULL').', 
                '.($endDate->competency_date ? "'".$endDate->competency_date."'" : 'NULL').', 
                '.($endDate->work_placement_date ? "'".$endDate->work_placement_date."'" : 'NULL').', 
                '.($endDate->checklist_date ? "'".$endDate->checklist_date."'" : 'NULL').', 
                '.($endDate->last_quiz_marked_date ? "'".$endDate->last_quiz_marked_date."'" : 'NULL').", 
                '".$endDate->created_at."', 
                '".$endDate->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                student_id = VALUES(student_id),
                course_id = VALUES(course_id),
                lesson_id = VALUES(lesson_id),
                end_date = VALUES(end_date),
                competency_date = VALUES(competency_date),
                work_placement_date = VALUES(work_placement_date),
                checklist_date = VALUES(checklist_date),
                last_quiz_marked_date = VALUES(last_quiz_marked_date),
                updated_at = VALUES(updated_at);";
            $summary['lesson_end_dates'] = ($summary['lesson_end_dates'] ?? 0) + 1;
        }

        // Admin reports
        $adminReports = DB::table('admin_reports')
            ->where('student_id', $userId)
            ->get();

        foreach ($adminReports as $report) {
            $queries[] = '-- Admin report';
            $queries[] = "INSERT INTO admin_reports (
                id, student_id, course_id, trainer_id, leader_id, company_id,
                student_details, student_status, student_last_active,
                student_course_start_date, student_course_end_date, course_status,
                course_details, student_course_progress, trainer_details,
                leader_details, leader_last_active, company_details, created_at, updated_at
            ) VALUES (
                {$report->id}, 
                {$report->student_id}, 
                ".($report->course_id ? $report->course_id : 'NULL').', 
                '.($report->trainer_id ? $report->trainer_id : 'NULL').', 
                '.($report->leader_id ? $report->leader_id : 'NULL').', 
                '.($report->company_id ? $report->company_id : 'NULL').", 
                '".addslashes($report->student_details)."', 
                '".addslashes($report->student_status)."', 
                ".($report->student_last_active ? "'".$report->student_last_active."'" : 'NULL').', 
                '.($report->student_course_start_date ? "'".$report->student_course_start_date."'" : 'NULL').', 
                '.($report->student_course_end_date ? "'".$report->student_course_end_date."'" : 'NULL').', 
                '.($report->course_status ? "'".addslashes($report->course_status)."'" : 'NULL').', 
                '.($report->course_details ? "'".addslashes($report->course_details)."'" : 'NULL').', 
                '.($report->student_course_progress ? "'".addslashes($report->student_course_progress)."'" : 'NULL').", 
                '".addslashes($report->trainer_details)."', 
                '".addslashes($report->leader_details)."', 
                ".($report->leader_last_active ? "'".$report->leader_last_active."'" : 'NULL').', 
                '.($report->company_details ? "'".addslashes($report->company_details)."'" : 'NULL').", 
                '".$report->created_at."', 
                '".$report->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                student_id = VALUES(student_id),
                course_id = VALUES(course_id),
                trainer_id = VALUES(trainer_id),
                leader_id = VALUES(leader_id),
                company_id = VALUES(company_id),
                student_details = VALUES(student_details),
                student_status = VALUES(student_status),
                student_last_active = VALUES(student_last_active),
                student_course_start_date = VALUES(student_course_start_date),
                student_course_end_date = VALUES(student_course_end_date),
                course_status = VALUES(course_status),
                course_details = VALUES(course_details),
                student_course_progress = VALUES(student_course_progress),
                trainer_details = VALUES(trainer_details),
                leader_details = VALUES(leader_details),
                leader_last_active = VALUES(leader_last_active),
                company_details = VALUES(company_details),
                updated_at = VALUES(updated_at);";
            $summary['admin_reports'] = ($summary['admin_reports'] ?? 0) + 1;
        }

        // Evaluations
        $evaluations = DB::table('evaluations')
            ->where('student_id', $userId)
            ->get();

        foreach ($evaluations as $evaluation) {
            $queries[] = '-- Evaluation';
            $queries[] = "INSERT INTO evaluations (
                id, results, evaluable_id, evaluable_type, status, 
                email_sent_on, evaluator_id, student_id, created_at, updated_at
            ) VALUES (
                {$evaluation->id}, 
                '".addslashes($evaluation->results)."', 
                {$evaluation->evaluable_id}, 
                '".addslashes($evaluation->evaluable_type)."', 
                ".($evaluation->status ? "'".addslashes($evaluation->status)."'" : 'NULL').', 
                '.($evaluation->email_sent_on ? "'".addslashes($evaluation->email_sent_on)."'" : 'NULL').", 
                {$evaluation->evaluator_id}, 
                {$evaluation->student_id}, 
                '".$evaluation->created_at."', 
                '".$evaluation->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                results = VALUES(results),
                evaluable_id = VALUES(evaluable_id),
                evaluable_type = VALUES(evaluable_type),
                status = VALUES(status),
                email_sent_on = VALUES(email_sent_on),
                evaluator_id = VALUES(evaluator_id),
                student_id = VALUES(student_id),
                updated_at = VALUES(updated_at);";
            $summary['evaluations'] = ($summary['evaluations'] ?? 0) + 1;
        }

        // Courses
        $courses = DB::table('courses')
            ->whereIn('id', $enrolledCourses)
            ->get();

        foreach ($courses as $course) {
            $queries[] = '-- Course';
            $queries[] = "INSERT INTO courses (
                id, slug, title, course_type, course_length_days, visibility,
                status, revisions, published_at, category, created_at, updated_at
            ) VALUES (
                {$course->id}, 
                '".addslashes($course->slug)."', 
                '".addslashes($course->title)."', 
                '".addslashes($course->course_type)."', 
                ".($course->course_length_days ? $course->course_length_days : 'NULL').", 
                '".addslashes($course->visibility)."', 
                '".addslashes($course->status)."', 
                ".($course->revisions ? $course->revisions : 'NULL').', 
                '.($course->published_at ? "'".$course->published_at."'" : 'NULL').", 
                '".addslashes($course->category)."', 
                '".$course->created_at."', 
                '".$course->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                slug = VALUES(slug),
                title = VALUES(title),
                course_type = VALUES(course_type),
                course_length_days = VALUES(course_length_days),
                visibility = VALUES(visibility),
                status = VALUES(status),
                revisions = VALUES(revisions),
                published_at = VALUES(published_at),
                category = VALUES(category),
                updated_at = VALUES(updated_at);";
            $summary['courses'] = ($summary['courses'] ?? 0) + 1;
        }

        // Lessons
        $lessons = DB::table('lessons')
            ->whereIn('course_id', $enrolledCourses)
            ->get();

        foreach ($lessons as $lesson) {
            $queries[] = '-- Lesson';
            $queries[] = "INSERT INTO lessons (
                id, course_id, `order`, slug, title, has_topic, release_key, release_value, has_work_placement, created_at, updated_at
            ) VALUES (
                {$lesson->id}, 
                {$lesson->course_id}, 
                ".($lesson->order !== null ? $lesson->order : 'NULL').", 
                '".addslashes($lesson->slug)."', 
                '".addslashes($lesson->title)."', 
                ".($lesson->has_topic !== null ? (int) $lesson->has_topic : 'NULL').', 
                '.(isset($lesson->release_key) ? "'".addslashes($lesson->release_key)."'" : 'NULL').', 
                '.(isset($lesson->release_value) ? "'".addslashes($lesson->release_value)."'" : 'NULL').', 
                '.(isset($lesson->has_work_placement) ? (int) $lesson->has_work_placement : 'NULL').", 
                '".$lesson->created_at."', 
                '".$lesson->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                course_id = VALUES(course_id),
                `order` = VALUES(`order`),
                slug = VALUES(slug),
                title = VALUES(title),
                has_topic = VALUES(has_topic),
                release_key = VALUES(release_key),
                release_value = VALUES(release_value),
                has_work_placement = VALUES(has_work_placement),
                updated_at = VALUES(updated_at);";
            $summary['lessons'] = ($summary['lessons'] ?? 0) + 1;
        }

        // Topics
        $topics = DB::table('topics')
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->get();

        foreach ($topics as $topic) {
            $queries[] = '-- Topic';
            $queries[] = "INSERT INTO topics (
                id, lesson_id, `order`, slug, title, estimated_time, has_quiz, course_id, created_at, updated_at
            ) VALUES (
                {$topic->id}, 
                {$topic->lesson_id}, 
                ".($topic->order !== null ? $topic->order : 'NULL').", 
                '".addslashes($topic->slug)."', 
                '".addslashes($topic->title)."', 
                ".($topic->estimated_time !== null ? $topic->estimated_time : 'NULL').', 
                '.($topic->has_quiz !== null ? (int) $topic->has_quiz : 'NULL').', 
                '.(isset($topic->course_id) ? $topic->course_id : 'NULL').", 
                '".$topic->created_at."', 
                '".$topic->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                lesson_id = VALUES(lesson_id),
                `order` = VALUES(`order`),
                slug = VALUES(slug),
                title = VALUES(title),
                estimated_time = VALUES(estimated_time),
                has_quiz = VALUES(has_quiz),
                course_id = VALUES(course_id),
                updated_at = VALUES(updated_at);";
            $summary['topics'] = ($summary['topics'] ?? 0) + 1;
        }

        // Quizzes
        $quizzes = DB::table('quizzes')
            ->whereIn('topic_id', $topics->pluck('id'))
            ->get();

        foreach ($quizzes as $quiz) {
            $queries[] = '-- Quiz';
            $queries[] = "INSERT INTO quizzes (
                id, topic_id, `order`, slug, title, passing_percentage, estimated_time, allowed_attempts, course_id, lesson_id, has_checklist, created_at, updated_at
            ) VALUES (
                {$quiz->id}, 
                {$quiz->topic_id}, 
                ".($quiz->order !== null ? $quiz->order : 'NULL').", 
                '".addslashes($quiz->slug)."', 
                '".addslashes($quiz->title)."', 
                {$quiz->passing_percentage}, 
                ".($quiz->estimated_time !== null ? $quiz->estimated_time : 'NULL').', 
                '.($quiz->allowed_attempts !== null ? $quiz->allowed_attempts : 'NULL').', 
                '.(isset($quiz->course_id) ? $quiz->course_id : 'NULL').', 
                '.(isset($quiz->lesson_id) ? $quiz->lesson_id : 'NULL').', 
                '.(isset($quiz->has_checklist) ? (int) $quiz->has_checklist : 'NULL').", 
                '".$quiz->created_at."', 
                '".$quiz->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                topic_id = VALUES(topic_id),
                `order` = VALUES(`order`),
                slug = VALUES(slug),
                title = VALUES(title),
                passing_percentage = VALUES(passing_percentage),
                estimated_time = VALUES(estimated_time),
                allowed_attempts = VALUES(allowed_attempts),
                course_id = VALUES(course_id),
                lesson_id = VALUES(lesson_id),
                has_checklist = VALUES(has_checklist),
                updated_at = VALUES(updated_at);";
            $summary['quizzes'] = ($summary['quizzes'] ?? 0) + 1;
        }

        // Course progress
        $courseProgress = DB::table('course_progress')
            ->where('user_id', $userId)
            ->get();

        foreach ($courseProgress as $progress) {
            $queries[] = '-- Course progress';
            $queries[] = "INSERT INTO course_progress (
                id, user_id, course_id, percentage, details, created_at, updated_at
            ) VALUES (
                {$progress->id}, 
                {$progress->user_id}, 
                {$progress->course_id}, 
                '".addslashes($progress->percentage)."', 
                '".addslashes($progress->details)."', 
                '".$progress->created_at."', 
                '".$progress->updated_at."'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                course_id = VALUES(course_id),
                percentage = VALUES(percentage),
                details = VALUES(details),
                updated_at = VALUES(updated_at);";
            $summary['course_progress'] = ($summary['course_progress'] ?? 0) + 1;
        }

        // Course Feedback
        $courseFeedback = DB::table('feedbacks')
            ->where('attachable_type', 'App\\Models\\Course')
            ->whereIn('attachable_id', $courses->pluck('id'))
            ->get();

        foreach ($courseFeedback as $feedback) {
            $queries[] = '-- Course Feedback';
            $queries[] = "INSERT INTO feedbacks (
                id, body, attachable_id, attachable_type, user_id, owner_id, created_at, updated_at
            ) VALUES (
                {$feedback->id}, 
                '".addslashes($feedback->body)."', 
                {$feedback->attachable_id}, 
                '{$feedback->attachable_type}', 
                {$feedback->user_id}, 
                {$feedback->owner_id}, 
                '{$feedback->created_at}', 
                '{$feedback->updated_at}'
            ) ON DUPLICATE KEY UPDATE 
                body = VALUES(body),
                attachable_id = VALUES(attachable_id),
                attachable_type = VALUES(attachable_type),
                user_id = VALUES(user_id),
                owner_id = VALUES(owner_id),
                updated_at = VALUES(updated_at);";
            $summary['course_feedback'] = ($summary['course_feedback'] ?? 0) + 1;
        }

        // Quiz Attempts
        $quizAttempts = DB::table('quiz_attempts')
            ->where('user_id', $userId)
            ->get();

        foreach ($quizAttempts as $attempt) {
            $queries[] = '-- Quiz Attempt';
            $queries[] = "INSERT INTO quiz_attempts (
                id, user_id, course_id, lesson_id, topic_id, quiz_id, questions, submitted_answers, attempt, system_result, status, user_ip, submitted_at, is_valid_accessor, accessed_at, accessor_id, assisted, created_at, updated_at
            ) VALUES (
                {$attempt->id}, 
                {$attempt->user_id}, 
                {$attempt->course_id}, 
                {$attempt->lesson_id}, 
                {$attempt->topic_id}, 
                {$attempt->quiz_id}, 
                '".addslashes($attempt->questions)."', 
                '".addslashes($attempt->submitted_answers)."', 
                {$attempt->attempt}, 
                '{$attempt->system_result}', 
                '{$attempt->status}', 
                '{$attempt->user_ip}', 
                ".($attempt->submitted_at ? "'{$attempt->submitted_at}'" : 'NULL').', 
                '.($attempt->is_valid_accessor ? (int) $attempt->is_valid_accessor : 'NULL').', 
                '.($attempt->accessed_at ? "'{$attempt->accessed_at}'" : 'NULL').', 
                '.($attempt->accessor_id ? $attempt->accessor_id : 'NULL').', 
                '.($attempt->assisted ? (int) $attempt->assisted : 'NULL').", 
                '{$attempt->created_at}', 
                '{$attempt->updated_at}'
            ) ON DUPLICATE KEY UPDATE 
                user_id = VALUES(user_id),
                course_id = VALUES(course_id),
                lesson_id = VALUES(lesson_id),
                topic_id = VALUES(topic_id),
                quiz_id = VALUES(quiz_id),
                questions = VALUES(questions),
                submitted_answers = VALUES(submitted_answers),
                attempt = VALUES(attempt),
                system_result = VALUES(system_result),
                status = VALUES(status),
                user_ip = VALUES(user_ip),
                submitted_at = VALUES(submitted_at),
                is_valid_accessor = VALUES(is_valid_accessor),
                accessed_at = VALUES(accessed_at),
                accessor_id = VALUES(accessor_id),
                assisted = VALUES(assisted),
                updated_at = VALUES(updated_at);";
            $summary['quiz_attempts'] = ($summary['quiz_attempts'] ?? 0) + 1;
        }

        // Add summary to the beginning of queries
        $summaryText = "-- Export Summary:\n";
        foreach ($summary as $table => $count) {
            $summaryText .= "-- {$table}: {$count} records\n";
        }
        array_unshift($queries, $summaryText);

        return $queries;
    }

    /**
     * Generate a downloadable SQL file with user data.
     *
     * @return string SQL file content
     */
    public function generateUserDataSqlFile(int $userId): string
    {
        $queries = $this->generateUserDataQueries($userId);

        $content = "-- User Data Export\n";
        $content .= '-- Generated: '.date('Y-m-d H:i:s')."\n";
        $content .= "-- User ID: {$userId}\n\n";

        $content .= implode("\n\n", $queries);

        return $content;
    }

    public function exportUserData($userId)
    {
        $queries = $this->generateUserDataQueries($userId);
        $summary = $this->getSummary($queries);
        $sqlContent = implode("\n\n", $queries);

        return $sqlContent;
    }

    private function getSummary($queries)
    {
        $summary = [
            'users' => 0,
            'user_details' => 0,
            'attachments' => 0,
            'enrolments' => 0,
            'student_documents' => 0,
            'student_course_enrolments' => 0,
            'student_course_stats' => 0,
            'student_activities' => 0,
            'student_lms_attachables' => 0,
            'notes' => 0,
            'lesson_end_dates' => 0,
            'admin_reports' => 0,
            'evaluations' => 0,
            'courses' => 0,
            'lessons' => 0,
            'topics' => 0,
            'quizzes' => 0,
            'course_progress' => 0,
            'course_feedback' => 0,
            'quiz_attempts' => 0,
        ];

        foreach ($queries as $query) {
            if (strpos($query, '--') === 0) {
                $type = trim(substr($query, 2));
                if (isset($summary[$type])) {
                    $summary[$type]++;
                }
            }
        }

        return $summary;
    }
}
