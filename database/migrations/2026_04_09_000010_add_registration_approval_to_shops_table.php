<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shops') || Schema::hasColumn('shops', 'registration_approval')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->tinyInteger('registration_approval')->default(0);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shops') || !Schema::hasColumn('shops', 'registration_approval')) {
            return;
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('registration_approval');
        });
    }
};
