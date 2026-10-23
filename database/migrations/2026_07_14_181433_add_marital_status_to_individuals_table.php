<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('individuals', 'marital_status')) {
            Schema::table('individuals', function (Blueprint $table) {
                $table->string('marital_status')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('individuals', 'marital_status')) {
            Schema::table('individuals', function (Blueprint $table) {
                $table->dropColumn('marital_status');
            });
        }
    }
};
