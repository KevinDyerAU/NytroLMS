<?php

namespace App\Models;

use App\Helpers\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $casts = [
        'modified_by' => 'array',
    ];

    protected $fillable = ['name', 'email', 'address', 'number', 'created_by', 'modified_by', 'poc_user_id'];

    public function getNameAttribute($value)
    {
        return urldecode($value);
    }

    //    public function setNameAttribute( $value )
    //    {
    //        $this->attributes[ 'name' ] = urlencode( urldecode( $value ) );
    //    }

    public function users()
    {
        return $this->morphToMany(User::class, 'attachable', 'user_has_attachables');
    }

    public function leaders()
    {
        return $this->users()->onlyLeaders();
    }

    public function students()
    {
        return $this->morphToMany(User::class, 'attachable', 'user_has_attachables')
            ->whereHas('roles', function ($query) {
                $query->where('roles.name', '=', 'Student');
            });
    }

    public function scopeExcludeSpecialNames($query)
    {
        return $query->where(function ($q) {
            $q->where('name', 'not like', '01%')
                ->where('name', 'not like', '02%');
        });
    }

    public function filterStudents()
    {
    }

    public function pocUser()
    {
        return $this->hasOne(User::class, 'id', 'poc_user_id');
    }

    public function bmUser()
    {
        return $this->hasOne(User::class, 'id', 'bm_user_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timezone(Helper::getTimeZone())->format('j F, Y g:i A');
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param mixed $value
     * @param string|null $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)->firstOrFail();
    }
}
