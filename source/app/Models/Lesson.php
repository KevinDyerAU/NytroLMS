<?php

namespace App\Models;

use App\Helpers\Helper;
use App\Services\CourseProgressService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanOns\Laraberg\Models\Gutenbergable;

class Lesson extends Model
{
    use Gutenbergable;
    use HasFactory;

    protected $appends = ['release_schedule'];

    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'release_at',
        'unlocked_at',
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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topics(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('order', 'ASC');
    }

    public function quiz()
    {
        return $this->hasOneThrough(Quiz::class, Topic::class);
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
        // dump($progress->details->toArray());
        if (empty($progress) || empty($progress->details)) {
            return false;
        }

        return $progress->details->toArray();
    }

    public function courseProgressDetailsForStudent(int $student_id)
    {
        if (empty($student_id) || empty($this->course)) {
            return false;
        }

        $progress = CourseProgressService::getProgress($student_id, $this->course->id);
        if (empty($progress) || empty($progress->details)) {
            return false;
        }

        return $progress->details->toArray();
    }

    public function isComplete($progress = null)
    {
        if (empty($progress)) {
            $progress = $this->courseProgressDetails();
        }

        // dd([
        //     'lesson' => $progress['lessons']['list'][$this->id],
        //     'topics' => $progress['lessons']['list'][$this->id]['topics'],
        //     'completed' => $progress['lessons']['list'][$this->id]['completed'],
        //     'marked_at' => $progress['lessons']['list'][$this->id]['marked_at'],
        //     'passed' => $progress['lessons']['list'][$this->id]['topics']['passed'],
        //     'count' => $progress['lessons']['list'][$this->id]['topics']['count'],
        // ]);

        if (empty($progress) || empty($progress['lessons']) || empty($progress['lessons']['list'][$this->id])) {
            return false;
        }
        $countTopics = $progress['lessons']['list'][$this->id]['topics']['count'];

        if (empty($countTopics)) {
            return false;
        }
        //        dump($progress[ 'lessons' ][ 'list' ][ $this->id ]);
        //        if($this->id === 100040 && auth()->user()->id == 3309){
        //            dump('check completed', $progress[ 'lessons' ][ 'list' ][ $this->id ], $countTopics);
        //        }
        if ($countTopics > 0) {
            if (!empty($progress['lessons']['list'][$this->id]['completed']) || !empty($progress['lessons']['list'][$this->id]['marked_at'])) {
                return true;
            }
        } else {
            if (!empty($progress['lessons']['list'][$this->id]['marked_at'])) {
                return true;
            }
        }
        if ($progress['lessons']['list'][$this->id]['topics']['passed'] >= $progress['lessons']['list'][$this->id]['topics']['count'] && $progress['lessons']['list'][$this->id]['topics']['count'] > 0) {
            return true;
        }

        return false;
    }

    public function isCompleteForStudent(int $student_id, $progress = null)
    {
        if (empty($progress)) {
            $progress = $this->courseProgressDetailsForStudent($student_id);
        }
        if (empty($progress) || empty($progress['lessons']) || empty($progress['lessons']['list'][$this->id])) {
            return false;
        }
        $lessonProgress = $progress['lessons']['list'][$this->id];
        $countTopics = $lessonProgress['topics']['count'];
        // dump([$lessonProgress, $countTopics]);
        // Consider lesson complete if 'completed' is true, 'marked' is true, or 'marked_at' is not empty
        if (!empty($lessonProgress['completed']) || !empty($lessonProgress['marked']) || !empty($lessonProgress['marked_at'])) {
            return true;
        }

        // If there are topics, check if all topics are passed
        if ($countTopics > 0) {
            if ($lessonProgress['topics']['passed'] >= $countTopics) {
                return true;
            }
        }

        return false;
    }

    public function isAttempted($progress = null)
    {
        if (empty($progress)) {
            $progress = $this->courseProgressDetails();
        }
        if (empty($progress) || empty($progress['lessons']) || empty($progress['lessons']['list'][$this->id])) {
            return false;
        }

        $countTopics = CourseProgressService::getCountTopics($progress['lessons']['list'][$this->id]);

        if (empty($countTopics)) {
            return false;
        }

        if (empty($countTopics['empty'])) {
            $countTopics['empty'] = 0;
        }

        if ($countTopics['count'] === 0) {
            return false;
        }

        if (($countTopics['count'] === $countTopics['empty']) || $countTopics['count'] === $countTopics['attempted']) {
            return true;
        }

        if (($progress['lessons']['list'][$this->id]['topics']['attempted'] === $progress['lessons']['list'][$this->id]['topics']['count'])) {
            return true;
        }

        return false;
    }

    public function isSubmitted($progress = null)
    {
        if (empty($progress)) {
            $progress = $this->courseProgressDetails();
        }
        if (empty($progress) || empty($progress['lessons']) || empty($progress['lessons']['list'][$this->id])) {
            return false;
        }

        $countTopics = CourseProgressService::getCountTopics($progress['lessons']['list'][$this->id]);

        //        if($this->id === 100040 && auth()->user()->id == 3309){
        //            dump('check submitted', $progress[ 'lessons' ][ 'list' ][ $this->id ], $countTopics);
        //        }
        if (empty($countTopics)) {
            return false;
        }

        if (empty($countTopics['empty'])) {
            $countTopics['empty'] = 0;
        }

        if ($countTopics['count'] === 0) {
            return false;
        }

        if (($countTopics['count'] === $countTopics['empty']) || $countTopics['count'] === $countTopics['submitted'] || $countTopics['count'] === $countTopics['attempted']) {
            return true;
        }

        if (($countTopics['count'] > $countTopics['empty']) && (($countTopics['passed'] + $countTopics['attempted']) === $countTopics['count'])) {
            return true;
        }

        if (($progress['lessons']['list'][$this->id]['topics']['submitted'] === $progress['lessons']['list'][$this->id]['topics']['count'])) {
            return true;
        }

        return false;
    }

    public function addDays(\Carbon\Carbon $date, $number_of_days)
    {
        return $date->addDays($number_of_days);
    }

    /**
     * @return bool
     */
    public function isAllowed(?\Carbon\Carbon $course_start_date = null)
    {
        if ($this->release_key === 'IMMEDIATE') {
            return true;
        }
        if ($this->release_key === 'XDAYS') {
            if (!empty($course_start_date)) {
                $number_of_days = intval($this->release_value);
                $new_date = $course_start_date->copy()->addDays($number_of_days)->shiftTimezone(Helper::getTimeZone());

                return $new_date->lessThanOrEqualTo(Carbon::now(Helper::getTimeZone()));
            }

            return false;
        }

        return $this->release_schedule ? $this->release_schedule->shiftTimezone(Helper::getTimeZone())->lessThanOrEqualTo(Carbon::now(Helper::getTimeZone())) : false;
    }

    public function getReleaseScheduleAttribute()
    {
        $release_key = $this->release_key;
        $release_value = $this->release_value;

        if ($release_key === 'IMMEDIATE') {
            return;
        }
        if ($release_key === 'XDAYS') {
            return intval($release_value);
        }

        return Carbon::parse($release_value);
    }

    public function unlocks()
    {
        return $this->hasMany(LessonUnlock::class);
    }

    public function unlocksForStudent(int $student_id)
    {
        return $this->unlocks()->where('user_id', $student_id)->first();
    }

    /**
     * Get the release plan for a lesson.
     * *
     * * @param \Illuminate\Support\Carbon|null $course_start_date
     * * @return string|null
     */
    public function releasePlan($course_start_date = null, ?int $studentId = null, ?int $courseId = null)
    {
        // Check if lesson is unlocked for student
        if ($studentId !== null && $courseId !== null) {
            if (LessonUnlock::isUnlockedForUser($this->id, $studentId, $courseId)) {
                // Return null for unlocked lessons to treat them as IMMEDIATE
                return null;
            }
        }
        $release_key = $this->release_key;
        $release_value = $this->release_value;

        if ($release_key === 'IMMEDIATE') {
            return null;
        }
        if ($release_key === 'XDAYS') {
            if (!empty($course_start_date)) {
                $number_of_days = intval($this->release_value);
                $new_date = $course_start_date->copy()->addDays($number_of_days);

                return $new_date->timezone(Helper::getTimeZone())->toDateString();
            }

            return Carbon::now(Helper::getTimeZone())->toDateString();
        }

        return Carbon::parse($release_value)->timezone(Helper::getTimeZone())->toDateString();
    }

    public function hasTopics()
    {
        return Topic::where('lesson_id', $this->id)->count();
    }

    public function hasContent()
    {
        return !empty($this->lb_raw_content);
    }

    public function student_attachable()
    {
        $this->morphToMany(StudentLMSAttachables::class, 'attachable', 'student_lms_attachables');
    }
}
