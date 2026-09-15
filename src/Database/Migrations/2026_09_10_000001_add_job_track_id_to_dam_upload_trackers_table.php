<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dam_upload_trackers', function (Blueprint $table) {
            $table->unsignedInteger('job_track_id')->nullable()->after('id');
            $table->foreign('job_track_id')->references('id')->on('job_track')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dam_upload_trackers', function (Blueprint $table) {
            $table->dropForeign(['job_track_id']);
            $table->dropColumn('job_track_id');
        });
    }
};
