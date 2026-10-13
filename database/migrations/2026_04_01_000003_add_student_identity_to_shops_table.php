<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'student_status')) {
                $table->string('student_status', 20)->nullable()->after('business_category');
            }

            if (!Schema::hasColumn('shops', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('student_status');
                $table->index('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'school_id')) {
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            }

            if (Schema::hasColumn('shops', 'student_status')) {
                $table->dropColumn('student_status');
            }
        });
    }
};
