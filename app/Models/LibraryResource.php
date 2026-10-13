<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryResource extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'library_faculty_id',
        'library_department_id',
        'library_level_id',
        'resource_type',
        'title',
        'slug',
        'course_code',
        'description',
        'access_type',
        'price',
        'file_original_name',
        'file_path',
        'file_extension',
        'mime_type',
        'file_size',
        'page_count',
        'cover_image_path',
        'preview_page_paths',
        'preview_pdf_path',
        'preview_page_limit',
        'download_count',
        'unlock_count',
        'upvote_count',
        'downvote_count',
        'is_flagged',
        'is_published',
    ];

    protected $casts = [
        'preview_page_paths' => 'array',
        'price' => 'decimal:2',
        'is_flagged' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

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

    public function unlocks()
    {
        return $this->hasMany(LibraryResourceUnlock::class);
    }

    public function votes()
    {
        return $this->hasMany(LibraryResourceVote::class);
    }

    public function reports()
    {
        return $this->hasMany(LibraryResourceReport::class);
    }
}
