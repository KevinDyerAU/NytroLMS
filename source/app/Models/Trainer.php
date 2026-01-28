<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trainer extends Model
{
    use HasFactory;

    protected $table = 'users';

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function students()
    {
        return $this->morphToMany(Student::class, 'attachable', 'user_has_attachables');
    }

    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
