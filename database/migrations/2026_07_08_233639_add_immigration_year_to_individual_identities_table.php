<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('individual_identities', 'immigration_year')) {
            Schema::table('individual_identities', function (Blueprint $table) {
                $table->date('immigration_year')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('individual_identities', 'immigration_year')) {
            Schema::table('individual_identities', function (Blueprint $table) {
                $table->dropColumn('immigration_year');
            });
        }
    }
};
