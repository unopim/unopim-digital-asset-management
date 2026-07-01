<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded asset that still needs background finalisation
 * (metadata extraction, cover-art, thumbnail). The DAM analogue of a
 * DataTransfer `job_track_batch`. Retry re-queues rows whose state is `failed`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_tracker_id')
                ->constrained('dam_upload_trackers')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('asset_id')->nullable()->index();
            $table->string('state')->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_upload_batches');
    }
};
