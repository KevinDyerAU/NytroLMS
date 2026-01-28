<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'required',
        'slug',
        'title',
        'content',
        'answer_type',
        'options',
        'estimated_time',
        'correct_answer',
        'quiz_id',
        'table_structure',
        'is_deleted',
        'deleted_at',
    ];

    protected $casts = [
        'options' => AsCollection::class,
        'table_structure' => 'array',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function quiz(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function isTableQuestion(): bool
    {
        return !is_null($this->table_structure);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Automatically filter out deleted questions by default
        static::addGlobalScope('notDeleted', function ($query) {
            $query->where('is_deleted', false);
        });
    }

    /**
     * Scope to include deleted questions
     */
    public function scopeWithDeleted($query)
    {
        return $query->withoutGlobalScope('notDeleted');
    }

    /**
     * Scope to only get deleted questions
     */
    public function scopeOnlyDeleted($query)
    {
        return $query->withoutGlobalScope('notDeleted')->where('is_deleted', true);
    }

    /**
     * Soft delete the question
     */
    public function softDelete(): bool
    {
        $this->is_deleted = true;
        $this->deleted_at = now();
        return $this->save();
    }

    /**
     * Restore a soft deleted question
     */
    public function restore(): bool
    {
        $this->is_deleted = false;
        $this->deleted_at = null;
        return $this->save();
    }
}
