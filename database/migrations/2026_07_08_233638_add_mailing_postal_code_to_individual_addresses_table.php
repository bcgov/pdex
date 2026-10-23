<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('individual_addresses', 'mailing_postal_code')) {
            Schema::table('individual_addresses', function (Blueprint $table) {
                $table->string('mailing_postal_code')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('individual_addresses', 'mailing_postal_code')) {
            Schema::table('individual_addresses', function (Blueprint $table) {
                $table->dropColumn('mailing_postal_code');
            });
        }
    }
};
