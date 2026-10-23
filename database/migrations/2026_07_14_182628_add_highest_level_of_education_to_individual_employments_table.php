<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('individual_employments', 'highest_level_of_education')) {
            Schema::table('individual_employments', function (Blueprint $table) {
                $table->string('highest_level_of_education')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('individual_employments', 'highest_level_of_education')) {
            Schema::table('individual_employments', function (Blueprint $table) {
                $table->dropColumn('highest_level_of_education');
            });
        }
    }
};
