<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasFactory;

    protected $fillable = ['name', 'guard_name'];

    /**
     * Get the post that the comment belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
