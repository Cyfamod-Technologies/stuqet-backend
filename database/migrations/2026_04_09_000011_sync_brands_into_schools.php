<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('brands') || !Schema::hasTable('schools') || !Schema::hasTable('products')) {
            return;
        }

        if (!Schema::hasColumn('products', 'school_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('brand_id');
            });
        }

        $brands = DB::table('brands')
            ->select('id', 'name', 'slug', 'logo', 'meta_title', 'meta_description')
            ->orderBy('id')
            ->get();

        foreach ($brands as $brand) {
            if (blank($brand->name)) {
                continue;
            }

            $school = DB::table('schools')
                ->where('slug', $brand->slug)
                ->orWhere('name', $brand->name)
                ->first();

            if ($school) {
                DB::table('schools')
                    ->where('id', $school->id)
                    ->update([
                        'name' => $brand->name,
                        'slug' => $school->slug ?: $this->uniqueSchoolSlug($brand->slug ?: $brand->name),
                        'logo' => $brand->logo,
                        'meta_title' => $brand->meta_title,
                        'meta_description' => $brand->meta_description,
                        'is_active' => 1,
                        'updated_at' => now(),
                    ]);

                $schoolId = $school->id;
            } else {
                $schoolId = DB::table('schools')->insertGetId([
                    'name' => $brand->name,
                    'slug' => $this->uniqueSchoolSlug($brand->slug ?: $brand->name),
                    'logo' => $brand->logo,
                    'meta_title' => $brand->meta_title,
                    'meta_description' => $brand->meta_description,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('products')
                ->where('brand_id', $brand->id)
                ->update(['school_id' => $schoolId]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank: this migration copies legacy brand data into schools.
    }

    private function uniqueSchoolSlug(string $value): string
    {
        $baseSlug = Str::slug($value) ?: 'school';
        $slug = $baseSlug;
        $suffix = 1;

        while (DB::table('schools')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
};
