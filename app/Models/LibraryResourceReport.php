<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryResourceReport extends Model
{
    protected $fillable = [
        'library_resource_id',
        'user_id',
        'reason',
        'details',
        'status',
    ];

    public function resource()
    {
        return $this->belongsTo(LibraryResource::class, 'library_resource_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
