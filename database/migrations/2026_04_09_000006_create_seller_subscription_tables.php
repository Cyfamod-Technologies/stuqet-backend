<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('seller_packages')) {
            Schema::create('seller_packages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->double('amount', 11, 2)->default(0);
                $table->integer('product_upload_limit')->default(0);
                $table->integer('preorder_product_upload_limit')->default(0);
                $table->string('logo')->nullable();
                $table->integer('duration')->default(0);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }

        if (!Schema::hasTable('seller_package_translations')) {
            Schema::create('seller_package_translations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('seller_package_id');
                $table->string('name', 50);
                $table->string('lang', 100);
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('seller_package_id');
                $table->index('lang');
            });
        }

        if (!Schema::hasTable('seller_package_payments')) {
            Schema::create('seller_package_payments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('seller_package_id');
                $table->double('amount', 20, 2);
                $table->string('payment_method')->nullable();
                $table->longText('payment_details')->nullable();
                $table->tinyInteger('approval');
                $table->tinyInteger('offline_payment')->comment("1=offline payment\n2=online payment");
                $table->string('reciept', 150)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

                $table->index('user_id');
                $table->index('seller_package_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_package_payments');
        Schema::dropIfExists('seller_package_translations');
        Schema::dropIfExists('seller_packages');
    }
};
