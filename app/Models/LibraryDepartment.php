<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryDepartment extends Model
{
    protected $fillable = [
        'library_faculty_id',
        'name',
        'slug',
        'is_active',
    ];

    public function faculty()
    {
        return $this->belongsTo(LibraryFaculty::class, 'library_faculty_id');
    }

    public function levels()
    {
        return $this->hasMany(LibraryLevel::class);
    }
}
