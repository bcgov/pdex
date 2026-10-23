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
        Schema::table('profile_form_fields', function (Blueprint $table) {
            $table->boolean('api_enabled')
                ->default(true)
                ->after('is_active')
                ->comment('Whether the field is exposed through the API (utils/student endpoint)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profile_form_fields', function (Blueprint $table) {
            $table->dropColumn('api_enabled');
        });
    }
};
