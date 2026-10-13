<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'University of Lagos',
            'Lagos State University',
            'Yaba College of Technology',
            'University of Ibadan',
            'Covenant University',
        ] as $name) {
            School::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
