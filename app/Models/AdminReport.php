<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

class AdminReport extends Model
{
    protected $guarded = [];

    protected $casts = [
        'student_details' => AsCollection::class,
        'course_details' => AsCollection::class,
        'trainer_details' => AsCollection::class,
        'leader_details' => AsCollection::class,
        'company_details' => AsCollection::class,
        'student_course_progress' => AsCollection::class,
        'student_course_start_date' => 'date',
        'student_course_end_date' => 'date',
        'student_last_active' => 'datetime',
        'leader_last_active' => 'datetime',
        'course_completed_at' => 'datetime',
        'course_expiry' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope('excludeLlnAndPtrCourses', function ($query) {
            $llnCourseId = config('lln.course_id');
            $ptrCourseId = config('ptr.course_id');
            $excludeIds = array_filter([$llnCourseId, $ptrCourseId]);
            if (!empty($excludeIds)) {
                $query->whereNotIn($query->qualifyColumn('course_id'), $excludeIds);
            }
        });
    }

    public function setCourseCompletedAtAttribute($value)
    {
        $this->attributes['course_completed_at'] = !empty($value) ? $value : null;
    }

    public function setCourseExpiryAttribute($value)
    {
        $this->attributes['course_expiry'] = !empty($value) ? $value : null;
    }

    public function enrolmentDetails()
    {
        return !empty($this->student_details['enrolment']) ? collect($this->student_details['enrolment'])->pluck('enrolment_value', 'enrolment_key') : '';
    }

    public function getEmploymentService()
    {
        return !empty($this->enrolmentDetails()) ? $this->enrolmentDetails()['basic']['employment_service'] : '';
    }

    public function student()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function studentEnrolment()
    {
        return $this->belongsTo(StudentCourseEnrolment::class, 'student_id', 'user_id')
            ->where('course_id', $this->course_id);
    }

    public function getStudentEnrolment()
    {
        return $this->studentEnrolment()->first();
    }

    public function courseProgress()
    {
        return $this->belongsTo(CourseProgress::class, 'student_id', 'user_id')
            ->where('course_id', $this->course_id);
    }

    public function getStudentLastActiveAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getLeaderLastActiveAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getStudentCourseStartDateAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getStudentCourseEndDateAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getCreatedAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function deListCourses($student_id, $registeredCoursesIds)
    {
        AdminReport::where(function ($query) use ($student_id, $registeredCoursesIds) {
            $query->where('student_id', $student_id)
                ->whereNotIn('course_id', $registeredCoursesIds);
        })->update(['course_status' => 'DELIST']);
    }
}
