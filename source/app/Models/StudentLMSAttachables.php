<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StudentLMSAttachables extends Model
{
    protected $table = 'student_lms_attachables';

    public $guarded = [];

    protected $casts = [
        'properties' => 'collection',
    ];

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCausedBy(Builder $query, Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeForAttachable(Builder $query, string $model, int $id): Builder
    {
        return $query->where('attachable_type', $model)
            ->where('attachable_id', $id);
    }
}
