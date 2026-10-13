<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'listing_type')) {
                $table->string('listing_type', 20)->nullable()->after('brand_id');
            }

            if (!Schema::hasColumn('products', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('listing_type');
                $table->index('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'school_id')) {
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            }

            if (Schema::hasColumn('products', 'listing_type')) {
                $table->dropColumn('listing_type');
            }
        });
    }
};
