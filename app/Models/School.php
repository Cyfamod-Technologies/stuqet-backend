<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use PreventDemoModeChanges;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
