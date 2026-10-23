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
        Schema::create('profile_form_fields', function (Blueprint $table) {
            $table->id();

            // Which profile form this field belongs to (student, and later institution)
            $table->string('profile_type')->default('student')->comment('Owning profile form: student, institution, etc.');

            // Structure
            $table->string('tab')->comment('Step/tab the field belongs to: general, address, employment, identity');
            $table->string('section')->nullable()->comment('Section heading within the tab');

            // Field definition
            $table->string('field_id')->comment('The v-model key / field identifier used by the form');
            $table->string('label')->comment('Human readable label shown in the form');
            $table->string('type')->default('text')->comment('Input type: text, email, tel, date, number, select, checkbox, textarea, radio, autocomplete');
            $table->boolean('required')->default(false)->comment('Whether the field is required');
            $table->string('placeholder')->nullable()->comment('Placeholder text for the field');
            $table->boolean('multi_select')->default(false)->comment('For select/checkbox fields: allow multiple selections');
            $table->string('help_text')->nullable()->comment('Optional small hint shown near the field');

            // Ordering / visibility
            $table->integer('sort_order')->default(0)->comment('Display order within the section');
            $table->boolean('is_active')->default(true)->comment('Whether the field is currently used in the form');

            $table->timestamps();

            $table->unique(['profile_type', 'tab', 'field_id']);
            $table->index(['profile_type', 'tab']);
            $table->index('sort_order');
        });

        Schema::create('profile_form_field_options', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('profile_form_field_id')->comment('Reference to profile_form_fields table');

            $table->string('value')->comment('Value submitted when this option is selected');
            $table->string('label')->comment('Text displayed for this option');
            $table->boolean('is_default')->default(false)->comment('Whether this option is selected by default');
            $table->integer('sort_order')->default(0)->comment('Display order of the option');

            $table->timestamps();

            $table->foreign('profile_form_field_id')
                ->references('id')
                ->on('profile_form_fields')
                ->onDelete('cascade');

            $table->index('profile_form_field_id');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_form_field_options');
        Schema::dropIfExists('profile_form_fields');
    }
};
