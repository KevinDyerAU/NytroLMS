<?php

namespace App\Models;

use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanOns\Laraberg\Models\Gutenbergable;

class Course extends Model
{
    use Gutenbergable;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'restricted_roles' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('nonzero', function (Builder $builder) {
            if (env('APP_URL') !== 'http://localhost') {
                $builder->where($builder->qualifyColumn('id'), '!=', 0);
            }
        });
    }

    public function setSlugAttribute($value)
    {
        $slug = \Str::slug($value);
        //        $slug = substr($slugA, 0, ((strlen($slugA) > 245) ? 245 : strlen($slugA)));

        $oldSlug = \Str::slug($this->attributes['title']);
        //        $oldSlug = substr($slugB, 0, ((strlen($slugB) > 245) ? 245 : strlen($slugB)));

        if ($slug !== $oldSlug) {
            if ($this->where('slug', $slug)->count() > 0) {
                $slug = $this->incrementSlug($slug);
            }
        }
        $this->attributes['slug'] = $slug;
    }

    public function incrementSlug($slug)
    {
        $original = $slug;
        $count = 2;
        $slug = "{$original}-".$count++;
        while (static::whereSlug($slug)->exists()) {
            $slug = "{$original}-".$count++;
        }

        return $slug;
    }

    public function scopeNotRestricted($query)
    {
        return $query->whereRaw("JSON_SEARCH(restricted_roles,'one', '".auth()->user()->roles()->first()->id."') IS NULL");
    }

    public function scopeMainCourseOnly($query)
    {
        return $query->whereRaw('title NOT LIKE "%Semester 2%"');
    }

    public function scopeAccessible($query)
    {
        return $query->where('status', 'PUBLISHED')->where('visibility', 'PUBLIC');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    public function scopePrivate($query)
    {
        return $query->where('status', 'PRIVATE');
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'PUBLIC');
    }

    public function scopeDraft($query)
    {
        return $query->where('visibility', 'DRAFT');
    }

    public function lessons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order', 'ASC');
    }

    public function featuredImage()
    {
        return $this->images()->where('type', 'FEATURED')->first();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function progress()
    {
        return $this->hasOne(CourseProgress::class);
    }

    public function enrolments()
    {
        return $this->hasMany(StudentCourseEnrolment::class, 'course_id');
    }

    public function userEnrolments($userId = null)
    {
        $query = $this->hasMany(StudentCourseEnrolment::class, 'course_id');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    public function isComplete(): bool
    {
        $progress = CourseProgressService::getProgress(auth()->user()->id, $this->course->id);
        if (empty($progress)) {
            return false;
        }
        $courseProgress = $progress->details->toArray();

        if ($courseProgress['completed']) {
            return true;
        }

        return false;
    }

    public function isSubmitted(): bool
    {
        $progress = CourseProgressService::getProgress(auth()->user()->id, $this->course->id);
        if (empty($progress)) {
            return false;
        }
        $courseProgress = $progress->details->toArray();

        $totalQuizzes = CourseProgressService::getTotalQuizzes($courseProgress);
        if ($totalQuizzes['total'] !== 0 && ($totalQuizzes['total'] === $totalQuizzes['submitted']) || ($totalQuizzes['total'] === $totalQuizzes['attempted'])) {
            return true;
        }

        return false;
    }
}
