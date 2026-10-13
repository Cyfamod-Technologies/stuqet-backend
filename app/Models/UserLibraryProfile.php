<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLibraryProfile extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'library_faculty_id',
        'library_department_id',
        'library_level_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function faculty()
    {
        return $this->belongsTo(LibraryFaculty::class, 'library_faculty_id');
    }

    public function department()
    {
        return $this->belongsTo(LibraryDepartment::class, 'library_department_id');
    }

    public function level()
    {
        return $this->belongsTo(LibraryLevel::class, 'library_level_id');
    }
}
