<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'users';

    public function user(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function trainer(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Trainer::class, 'attachable', 'user_has_attachables');
    }

    public function leader(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Leader::class, 'attachable', 'user_has_attachables');
    }

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
