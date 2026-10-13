<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_faculties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->unique(['school_id', 'name']);
        });

        Schema::create('library_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_faculty_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('library_faculty_id')->references('id')->on('library_faculties')->cascadeOnDelete();
            $table->unique(['library_faculty_id', 'name']);
        });

        Schema::create('library_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_department_id');
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('library_department_id')->references('id')->on('library_departments')->cascadeOnDelete();
            $table->unique(['library_department_id', 'name']);
        });

        Schema::create('user_library_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('library_faculty_id')->nullable();
            $table->unsignedBigInteger('library_department_id')->nullable();
            $table->unsignedBigInteger('library_level_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('library_faculty_id')->references('id')->on('library_faculties')->nullOnDelete();
            $table->foreign('library_department_id')->references('id')->on('library_departments')->nullOnDelete();
            $table->foreign('library_level_id')->references('id')->on('library_levels')->nullOnDelete();
        });

        Schema::create('library_resources', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('library_faculty_id');
            $table->unsignedBigInteger('library_department_id');
            $table->unsignedBigInteger('library_level_id');
            $table->string('resource_type');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('course_code')->index();
            $table->text('description')->nullable();
            $table->enum('access_type', ['free', 'paid'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('file_original_name');
            $table->string('file_path');
            $table->string('file_extension', 12);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('page_count')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->text('preview_page_paths')->nullable();
            $table->string('preview_pdf_path')->nullable();
            $table->unsignedTinyInteger('preview_page_limit')->default(3);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('unlock_count')->default(0);
            $table->unsignedInteger('upvote_count')->default(0);
            $table->unsignedInteger('downvote_count')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('library_faculty_id')->references('id')->on('library_faculties')->cascadeOnDelete();
            $table->foreign('library_department_id')->references('id')->on('library_departments')->cascadeOnDelete();
            $table->foreign('library_level_id')->references('id')->on('library_levels')->cascadeOnDelete();
            $table->index(['school_id', 'library_faculty_id', 'library_department_id', 'library_level_id'], 'library_taxonomy_index');
        });

        Schema::create('library_resource_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_resource_id');
            $table->unsignedInteger('user_id');
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->foreign('library_resource_id')->references('id')->on('library_resources')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['library_resource_id', 'user_id']);
        });

        Schema::create('library_resource_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_resource_id');
            $table->unsignedInteger('user_id');
            $table->tinyInteger('vote');
            $table->timestamps();

            $table->foreign('library_resource_id')->references('id')->on('library_resources')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['library_resource_id', 'user_id']);
        });

        Schema::create('library_resource_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('library_resource_id');
            $table->unsignedInteger('user_id');
            $table->string('reason');
            $table->text('details')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->foreign('library_resource_id')->references('id')->on('library_resources')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_resource_reports');
        Schema::dropIfExists('library_resource_votes');
        Schema::dropIfExists('library_resource_unlocks');
        Schema::dropIfExists('library_resources');
        Schema::dropIfExists('user_library_profiles');
        Schema::dropIfExists('library_levels');
        Schema::dropIfExists('library_departments');
        Schema::dropIfExists('library_faculties');
    }
};
