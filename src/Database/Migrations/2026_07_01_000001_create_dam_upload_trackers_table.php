<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tracks a single background asset-upload session (state-driven pause/resume/cancel). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_upload_trackers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('directory_id')->nullable()->index();
            $table->string('state')->default('pending')->index();
            $table->unsignedInteger('total_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_upload_trackers');
    }
};
