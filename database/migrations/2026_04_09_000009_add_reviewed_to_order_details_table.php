<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_details')) {
            return;
        }

        $hasRefundDays = Schema::hasColumn('order_details', 'refund_days');
        $hasReviewed = Schema::hasColumn('order_details', 'reviewed');

        if ($hasRefundDays && $hasReviewed) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('order_details', 'refund_days')) {
                $table->integer('refund_days')->default(0)->after('delivery_status');
            }

            if (!Schema::hasColumn('order_details', 'reviewed')) {
                $table->tinyInteger('reviewed')->default(0)->after('refund_days');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('order_details') || !Schema::hasColumn('order_details', 'reviewed')) {
            return;
        }

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn('reviewed');
        });
    }
};
