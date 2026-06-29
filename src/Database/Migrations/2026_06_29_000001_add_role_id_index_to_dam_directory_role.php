<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The hot permission lookup filters dam_directory_role by role_id, but the
     * table's primary key leads with directory_id, so that filter cannot use it.
     * Add a (role_id, directory_id) index to cover the role-scoped query.
     */
    public function up(): void
    {
        Schema::table('dam_directory_role', function (Blueprint $table) {
            $table->index(['role_id', 'directory_id'], 'dam_directory_role_role_id_directory_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('dam_directory_role', function (Blueprint $table) {
            $table->dropIndex('dam_directory_role_role_id_directory_id_index');
        });
    }
};
