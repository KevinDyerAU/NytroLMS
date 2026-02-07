<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCourseEnrolment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'deferred_details' => 'array',
        'cert_details' => 'array',
        'registration_date' => 'date',
        'registered_by' => 'integer',
        'registered_on_create' => 'boolean',
        'is_chargeable' => 'boolean',
        'course_completed_at' => 'datetime',
        'course_expiry' => 'date',
    ];

    // In source/app/Models/StudentCourseEnrolment.php

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

    public function setLastUpdatedAttribute($value)
    {
        $this->attributes['last_updated'] = !empty($value) ? $value : null;
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function competencies()
    {
        return Competency::where('course_id', $this->course_id)
            ->where('user_id', $this->user_id)
            ->get() ?? null;
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class, 'course_id', 'course_id');
    }

    public function enrolmentStats(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StudentCourseStats::class, 'student_course_stats_id');
    }

    public function adminReport()
    {
        return $this->belongsTo(AdminReport::class, 'admin_reports_id', 'id');
    }

    public function progress()
    {
        return $this->belongsTo(CourseProgress::class, 'course_progress_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'DELIST');
    }

    public function getCourseStartAtAttribute($value)
    {
        if (empty($value)) {
            return false;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getCourseEndsAtAttribute($value)
    {
        if (empty($value)) {
            return false;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function transformDate($attribute)
    {
        $input = $this->attributes[$attribute];
        if (Carbon::parse($input)->greaterThan(Carbon::parse('30-08-2023'))) {
            return Carbon::parse($input)->format('j F, Y');
        } else {
            return Carbon::parse($input)->timezone(Helper::getTimeZone())->format('j F, Y');
        }
    }

    public function getCertIssuedOnAttribute($value)
    {
        if (empty($value)) {
            return false;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getCertIssuedByAttribute($value)
    {
        if (empty($value)) {
            return false;
        }
        $user = User::find($value);

        return $user->name;
    }
}
