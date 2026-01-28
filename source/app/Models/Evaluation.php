<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    protected $guarded = [];

    protected $casts = [
        'results' => AsCollection::class,
        'emailed_sent_on' => AsCollection::class,
        'updated_at' => 'datetime',
    ];

    public function evaluable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function evaluator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function scopeLatestEvaluationOf($query, int $attempt_id)
    {
        return $query->where('evaluable_id', $attempt_id)->latest();
    }

    public function getUpdatedAtAttribute($value): string
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function isComplete(): bool
    {
        return $this->status !== null;
    }

    // Fetch the latest complete evaluation
    public static function getLatestComplete($evaluableType, $evaluableId)
    {
        return self::where('evaluable_type', $evaluableType)
            ->where('evaluable_id', $evaluableId)
            ->whereNotNull('status') // Only complete entries
            ->latest()
            ->first();
    }
}
