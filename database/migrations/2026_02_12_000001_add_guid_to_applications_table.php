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
        Schema::table('applications', function (Blueprint $table) {
            // Add nullable guid column first
            $table->string('guid', 32)->nullable()->index()->after('id');
        });

        // Generate guids for existing records
        \DB::table('applications')->whereNull('guid')->update([
            'guid' => \DB::raw("substring(md5(random()::text), 1, 32)")
        ]);

        // Make the column not nullable and add unique constraint
        Schema::table('applications', function (Blueprint $table) {
            $table->string('guid', 32)->change();
            $table->unique('guid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['guid']);
            $table->dropIndex(['guid']);
            $table->dropColumn('guid');
        });
    }
};
