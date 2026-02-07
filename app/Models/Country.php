<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'flag' => 'array',
        'languages' => AsCollection::class,
        'calling_codes' => 'array',
        'currency' => 'array',
    ];

    public function scopeWithFlags($query)
    {
        return $query->where('flag', '<>', '"<span class=\"flag-icon flag-icon-\"><\/span>"');
    }
}
