<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('individual_addresses', 'use_different_mailing_address')) {
            Schema::table('individual_addresses', function (Blueprint $table) {
                $table->boolean('use_different_mailing_address')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('individual_addresses', 'use_different_mailing_address')) {
            Schema::table('individual_addresses', function (Blueprint $table) {
                $table->dropColumn('use_different_mailing_address');
            });
        }
    }
};
