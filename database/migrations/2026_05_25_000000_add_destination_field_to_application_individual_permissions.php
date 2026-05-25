<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_individual_permissions', function (Blueprint $table) {
            $table->string('destination_field')->nullable()->after('column_name')
                ->comment('The field name used as the key in the JWT token sent to the destination application. Defaults to column_name if not set.');
        });
    }

    public function down(): void
    {
        Schema::table('application_individual_permissions', function (Blueprint $table) {
            $table->dropColumn('destination_field');
        });
    }
};
