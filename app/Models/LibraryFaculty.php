<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryFaculty extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'is_active',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function departments()
    {
        return $this->hasMany(LibraryDepartment::class);
    }
}
