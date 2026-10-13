<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryLevel extends Model
{
    protected $fillable = [
        'library_department_id',
        'name',
        'sort_order',
        'is_active',
    ];

    public function department()
    {
        return $this->belongsTo(LibraryDepartment::class, 'library_department_id');
    }
}
