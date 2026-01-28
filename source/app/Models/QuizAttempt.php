<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class QuizAttempt extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'questions' => AsCollection::class,
        'submitted_answers' => AsCollection::class,
        'updated_at' => 'datetime',
    ];

    public function evaluation(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Evaluation::class, 'evaluable');
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'attachable');
    }

    public function quiz(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function topic(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function lesson(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeLatestPassed($query)
    {
        return $query->whereIn('id', function ($query) {
            return $query->select(DB::raw('max(id)'))
                ->from('quiz_attempts')
                ->whereIn('system_result', ['COMPLETED', 'EVALUATED', 'MARKED'])
                ->whereIn('status', ['SATISFACTORY', 'COMPLETED'])
                ->whereNull('deleted_at')
                ->groupBy(['quiz_id', 'user_id']);
        });
    }

    public function scopeLatestAttempt($query)
    {
        return $query->whereIn('id', function ($query) {
            return $query->select(DB::raw('max(id)'))
                ->from('quiz_attempts')
                ->groupBy(['quiz_id', 'user_id']);
        });
    }

    public function scopeLatestAttemptSubmittedOnly($query)
    {
        return $query->whereIn('id', function ($query) {
            return $query->select(DB::raw('max(id)'))
                ->from('quiz_attempts')
                ->where('system_result', '!=', 'INPROGRESS')
                ->whereNull('deleted_at')
                ->groupBy(['quiz_id', 'user_id']);
        });
    }

    public function scopeLatestThreeAttempts($query)
    {
        return $query->whereIn('id', function ($query) {
            return $query->select('id')
                ->from('quiz_attempts')
                ->where('system_result', '!=', 'INPROGRESS')
                ->whereNull('deleted_at')
                ->groupBy(['quiz_id', 'user_id']);
        });
    }

    public function scopeOnlyPending($query)
    {
        return $query->where('system_result', '!=', 'INPROGRESS')
            ->where(function ($query) {
                $query->where('status', 'SUBMITTED')->orWhere('status', 'REVIEWING');
            });
    }

    public function scopeRelatedTrainer($query)
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->whereHas('trainers', function (Builder $query) {
                $query->where('id', '=', auth()->user()->id);
            });
        });
    }

    public function scopeRelatedLeader($query)
    {
        return $query->whereHas('user', function (Builder $query) {
            $query->whereHas('leaders', function (Builder $query) {
                $query->where('id', '=', auth()->user()->id);
            });
        });
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getSubmittedAtAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    /**
     * Check if the quiz attempt is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'SATISFACTORY' &&
               $this->system_result === 'MARKED' &&
               !empty($this->submitted_at);
    }

    /**
     * Check if the quiz attempt is submitted.
     */
    public function isSubmitted(): bool
    {
        return !empty($this->submitted_at);
    }
}
