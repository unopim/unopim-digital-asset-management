<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dam_asset_resource_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('related_field');
            $table->foreignId('dam_asset_id')->constrained('dam_assets');

            /**
             * product_id / category_id hard-link an asset to a PIM entity. On a
             * standalone DAM install the catalog packages are absent, so these
             * tables do not exist - skip the column and its foreign key rather
             * than fail the migration. Asset-to-entity mapping still works through
             * the type + related_field columns above.
             */
            if (Schema::hasTable('products')) {
                $table->unsignedInteger('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            }

            if (Schema::hasTable('categories')) {
                $table->unsignedInteger('category_id')->nullable();
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            }

            $table->index('dam_asset_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dam_asset_resource_mappings');
    }
};
