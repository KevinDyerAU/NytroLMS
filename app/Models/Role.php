<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends \Spatie\Permission\Models\Role
{
    use HasFactory;

    protected $fillable = ['name', 'guard_name'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    public function scopeNotSelf($query)
    {
        return $query->where('id', '!=', auth()->user()->id);
    }

    public function scopeEditRecord($query, $record_id)
    {
        return $query->where('id', '>', $record_id);
    }

    public function scopeNotRoot($query)
    {
        return $query->where('name', '!=', 'Root');
    }

    public function scopeNotRole($query, $roles)
    {
        return $query->whereNotIn('name', $roles);
    }
}
