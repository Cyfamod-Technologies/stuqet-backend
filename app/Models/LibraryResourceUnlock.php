<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryResourceUnlock extends Model
{
    protected $fillable = [
        'library_resource_id',
        'user_id',
        'amount',
        'unlocked_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'unlocked_at' => 'datetime',
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
