<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories') || Schema::hasColumn('categories', 'hot_category')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->enum('hot_category', ['0', '1'])->default('0')->after('featured');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('categories') || !Schema::hasColumn('categories', 'hot_category')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('hot_category');
        });
    }
};
