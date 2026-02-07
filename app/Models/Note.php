<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pin_log' => 'array',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }
}
