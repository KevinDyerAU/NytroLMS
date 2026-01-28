<?php

namespace App\Models;

use App\Services\CourseProgressService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use VanOns\Laraberg\Models\Gutenbergable;

class Quiz extends Model
{
    use Gutenbergable;
    use HasFactory;

    protected $guarded = [];

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

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function topic(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function questions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function featuredImage()
    {
        return $this->images()->where('type', 'FEATURED')->first();
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function isAllowed()
    {
        // Check different conditions if user allowed to attempt
        $lastAttempt = $this->lastAttempt();
        if (empty($lastAttempt)) {
            //            dd( 'empty last attempt' );
            return true;
        }
        //        dd($lastAttempt, $lastAttempt->attempt, ($this->allowed_attempts ?? 99), $lastAttempt->attempt >= $this->allowed_attempts, $lastAttempt->attempt >= ($this->allowed_attempts ?? 99));
        if ($lastAttempt->attempt >= ($this->allowed_attempts ?? 999)) {
            //            dd( 'no more attempts' );
            return false;
        }
        if ($this->isAlreadySubmitted()) {
            //            dump('is already submitted');
            if (in_array($lastAttempt->status, ['RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'])) {
                //                dd( 'returned' );
                return true;
            }
            if (in_array($lastAttempt->status, ['SUBMITTED', 'REVIEWING', 'SATISFACTORY'])) {
                //                dd( 'already submitted' );
                return false;
            }

            if ($lastAttempt->system_result === 'INPROGRESS' && $lastAttempt->attempt === 1) {
                //                dd( 'in progress' );
                return true;
            }

            //            dump( 'is not allowed - cleaning now' );
            return $this->cleanAttemptResult();
        }
        $this->cleanAttemptResult();

        //        dd( 'is allowed' );
        return true;
    }

    /**
     * @return bool
     */
    public function cleanAttemptResult($userId = null)
    {
        $user_id = empty($userId) ? auth()->user()->id : intval($userId);
        $lastAttempt = $this->lastAttempt($user_id);
        $attempts = $this->attempts()
            ->where('user_id', $user_id)
            ->withTrashed()
            ->orderBy('id', 'DESC')
            ->get()->toArray();
        //        dump($user_id, $attempts, $lastAttempt->toArray());
        if (count($attempts) > 1) {
            if ($lastAttempt->system_result === 'INPROGRESS') {
                $i = 0;
                foreach ($attempts as $attempt) {
                    $i++;
                    if (in_array($attempt['status'], ['RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'])) {
                        return true;
                    }
                    if ($attempt['attempt'] > 0) {
                        $lastAttemptID = $attempt['id'];
                        //                        dump($attempt[ "system_result" ], $attempt[ "deleted_at" ], $i, $attempts[ $i ][ "status" ]);
                        if ($attempt['system_result'] === 'INPROGRESS'
                            && in_array($attempts[$i]['status'] ?? '', ['SATISFACTORY', 'ATTEMPTING'])
                            && empty($attempt['deleted_at'])) {
                            //                            dd($lastAttemptID, $attempts[ $i - 1 ][ "status" ], $attempts[$i]['status']);
                            \Log::notice("soft delete invalid/duplicate attempt {$lastAttemptID} for quiz {$this->id} user {$user_id}", ['attempts' => $lastAttempt->toArray()]);
                            QuizAttempt::where('id', $lastAttemptID)->delete();
                        }
                    }
                    //                    dd($attempt, $attempts[0], $i);
                }

                return false;
            } elseif (in_array($attempts[0]['status'], ['RETURNED', 'FAIL', 'OVERDUE', 'NOT SATISFACTORY'])) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'attachable');
    }

    public function isAlreadySubmitted()
    {
        $count = $this->attempts()
            ->where('user_id', auth()->user()->id)
            ->whereIn('system_result', ['COMPLETED', 'EVALUATED', 'MARKED'])
            ->count();

        return $count > 0;
    }

    public function lastAttempt($userId = null)
    {
        $user_id = empty($userId) ? auth()->user()->id : intval($userId);

        return $this->attempts()->where('user_id', $user_id)->orderBy('id', 'DESC')->first();
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

    public function isEvaluated()
    {
    }

    public function isComplete($courseProgress = null)
    {
        if (empty($courseProgress)) {
            $courseProgress = $this->courseProgressDetails();
        }

        if (empty($courseProgress) || empty($courseProgress['lessons'])) {
            return false;
        }

        return (bool) $courseProgress['lessons']['list'][$this->lesson->id]['topics']['list'][$this->topic->id]['quizzes']['list'][$this->id]['passed'];
    }

    public function isFailed($courseProgress = null)
    {
        if (empty($courseProgress)) {
            $courseProgress = $this->courseProgressDetails();
        }

        if (empty($courseProgress) || empty($courseProgress['lessons'])) {
            return false;
        }

        return (bool) $courseProgress['lessons']['list'][$this->lesson->id]['topics']['list'][$this->topic->id]['quizzes']['list'][$this->id]['passed'];
    }

    public function isSubmitted($courseProgress = null)
    {
        if (empty($courseProgress)) {
            $courseProgress = $this->courseProgressDetails();
        }

        if (empty($courseProgress) || empty($courseProgress['lessons'])) {
            return false;
        }

        return (bool) $courseProgress['lessons']['list'][$this->lesson->id]['topics']['list'][$this->topic->id]['quizzes']['list'][$this->id]['submitted'];
    }

    public function hasChecklist()
    {
        return $this->has_checklist > 0;
    }

    public function attachables()
    {
        return $this->morphMany(StudentLMSAttachables::class, 'attachable');
    }

    public function attachedChecklists()
    {
        return $this->attachables()->forEvent('CHECKLIST');
    }

    public function attachedChecklistsFor($student_id)
    {
        return $this->attachedChecklists()->where('student_id', $student_id);
    }
}
