<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $guarded = [];

    protected $casts = [
        'body' => AsCollection::class,
        'updated_at' => 'datetime',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }
}
