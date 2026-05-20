<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_shares', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->enum('share_type', ['asset', 'directory']);
            $table->unsignedBigInteger('target_id');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['share_type', 'target_id']);
            $table->index('expires_at');

            $table->foreign('created_by')
                ->references('id')->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_shares');
    }
};
