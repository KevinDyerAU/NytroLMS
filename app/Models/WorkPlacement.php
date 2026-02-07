<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;

class WorkPlacement extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'company_id',
        'leader_id',
        'course_start_date',
        'course_end_date',
        'consultation_completed',
        'consultation_completed_on',
        'wp_commencement_date',
        'wp_end_date',
        'employer_name',
        'employer_email',
        'employer_phone',
        'employer_address',
        'employer_notes',
        'created_by',
        'field_changes',
    ];

    protected $casts = [
        'consultation_completed' => 'boolean',
        'course_start_date' => 'date',
        'course_end_date' => 'date',
        'consultation_completed_on' => 'date',
        'wp_commencement_date' => 'date',
        'wp_end_date' => 'date',
        'field_changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCourseEndDateAttribute($value)
    {
        return Helper::parseDate($value);
    }

    public function getCourseStartDateAttribute($value)
    {
        return Helper::parseDate($value);
    }

    public function getConsultationCompletedOnAttribute($value)
    {
        return Helper::parseDate($value);
    }

    public function getWpCommencementDateAttribute($value)
    {
        return Helper::parseDate($value);
    }

    public function getWpEndDateAttribute($value)
    {
        return Helper::parseDate($value);
    }
}
