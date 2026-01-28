<?php

namespace App\Models;

use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanOns\Laraberg\Models\Gutenbergable;

class Topic extends Model
{
    use Gutenbergable;
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::addGlobalScope(
            'nonzero',
            function (Builder $builder) {
                if (env('APP_URL') !== 'http://localhost') {
                    $builder->where($builder->qualifyColumn('id'), '!=', 0);
                }
            }
        );
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

    public function studentActivity()
    {
        return $this->morphOne(StudentActivity::class, 'actionable');
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function quizzes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('order', 'ASC');
    }

    public function featuredImage()
    {
        return $this->images()->where('type', 'FEATURED')->first();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function courseProgressDetails()
    {
        if (empty(auth()->user()->id) || empty($this->course)) {
            return false;
        }

        $progress = CourseProgressService::getProgress(auth()->user()->id, $this->course->id);
        if (empty($progress)) {
            return false;
        }

        return $progress->details->toArray();
    }

    public function isComplete($courseProgress = null)
    {
        if (empty($courseProgress)) {
            $courseProgress = $this->courseProgressDetails();
        }

        if (empty($courseProgress) || empty($courseProgress['lessons'])) {
            return false;
        }
        foreach ($courseProgress['lessons']['list'] as $lesson) {
            if (isset($lesson['topics']['list'][$this->id])) {
                if ($lesson['topics']['list'][$this->id]['quizzes']['count'] > 0) {
                    if ($lesson['topics']['list'][$this->id]['completed'] || !empty($lesson['topics']['list'][$this->id]['marked_at'])) {
                        return true;
                    }
                } else {
                    if (!empty($lesson['topics']['list'][$this->id]['marked_at'])) {
                        return true;
                    }
                }

                break;
            }
        }

        return false;
    }

    public function isSubmitted($courseProgress = null)
    {
        if (empty($courseProgress)) {
            $courseProgress = $this->courseProgressDetails();
        }

        if (empty($courseProgress) || empty($courseProgress['lessons'])) {
            return false;
        }
        foreach ($courseProgress['lessons']['list'] as $lesson) {
            if (isset($lesson['topics']['list'][$this->id])) {
                if ($lesson['topics']['list'][$this->id]['submitted'] || $lesson['topics']['list'][$this->id]['attempted']) {
                    return true;
                }

                break;
            }
        }

        return false;
    }

    public function isAllowed()
    {
        return true;
    }

    public function hasQuizzes()
    {
        return Quiz::where('topic_id', $this->id)->count();
    }

    public function hasContent()
    {
        return !empty($this->lb_raw_content);
    }
}
