<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'last_logged_in' => 'datetime',
        'onboard_at' => 'datetime',
    ];

    private $language = ['en' => 'English', 'ar' => 'Arabic', 'vi' => 'Vietnamese', 'zh-cn' => 'Chinese (Simplified)', 'zh-tw' => 'Chinese (Traditional)', 'tr' => 'Turkish'];

    /**
     * User info relation to user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getLanguageAttribute($value)
    {
        return $this->language[$value] ?? '';
    }

    public function getLastLoggedInAttribute($value)
    {
        if (empty($value)) {
            return;
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function getFirstLoginAttribute($value)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }

    public function getFirstEnrollmentAttribute($value)
    {
        if (empty($value) || $value === '0000-00-00 00:00:00') {
            return '';
        }

        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y');
    }
}
