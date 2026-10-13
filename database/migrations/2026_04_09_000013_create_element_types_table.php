<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('element_types')) {
            Schema::create('element_types', function (Blueprint $table) {
                $table->id();
                $table->integer('element_id');
                $table->string('name', 100);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        $defaults = [
            ['id' => 1, 'element_id' => 1, 'name' => 'Header 1', 'is_default' => 0],
            ['id' => 2, 'element_id' => 1, 'name' => 'Header 2', 'is_default' => 0],
            ['id' => 3, 'element_id' => 1, 'name' => 'Header 3', 'is_default' => 0],
            ['id' => 4, 'element_id' => 1, 'name' => 'Header 4', 'is_default' => 0],
            ['id' => 5, 'element_id' => 1, 'name' => 'Header 5', 'is_default' => 0],
            ['id' => 6, 'element_id' => 1, 'name' => 'Header 6', 'is_default' => 0],
        ];

        foreach ($defaults as $row) {
            if (!DB::table('element_types')->where('id', $row['id'])->exists()) {
                DB::table('element_types')->insert($row + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('element_types');
    }
};
