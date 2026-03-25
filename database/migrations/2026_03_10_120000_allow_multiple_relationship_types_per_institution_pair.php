<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institution_relationships', function (Blueprint $table) {
            $table->dropUnique('unique_institution_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution_relationships', function (Blueprint $table) {
            $table->unique(['institution_a_guid', 'institution_b_guid'], 'unique_institution_relationship');
        });
    }
};
