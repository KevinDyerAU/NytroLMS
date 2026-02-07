<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Enrolment extends Pivot
{
    protected $table = 'enrolments';

    protected $fillable = ['user_id', 'enrolment_key', 'enrolment_value', 'is_active'];

    protected $casts = [
        'enrolment_value' => AsCollection::class,
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopePrepare($query) {
        return $query->pluck('enrolment_value', 'enrolment_key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getBasicAttribute($value)
    {
        return json_decode(trim(stripslashes($value), '"'), true);
    }

    public function getOnboardAttribute($value) {
        return json_decode(trim(stripslashes($value), '"'), true);
    }
}
