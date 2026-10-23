<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $identityIndexes = [
        'individual_identities_indigenous_status_index' => ['indigenous_status'],
        'individual_identities_is_visible_minority_index' => ['is_visible_minority'],
        'individual_identities_individual_id_indigenous_status_index' => ['individual_id', 'indigenous_status'],
        'individual_identities_individual_id_is_visible_minority_index' => ['individual_id', 'is_visible_minority'],
    ];

    private array $profileAnswerOptions = [
        ['value' => 'yes', 'label' => 'Yes'],
        ['value' => 'no', 'label' => 'No'],
        ['value' => 'unknown', 'label' => 'Prefer not to answer'],
    ];

    private array $profileAnswerFields = [
        'disability_status',
        'indigenous_status',
        'is_visible_minority',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->changeBooleanColumnToNullableString('individuals', 'disability_status');

        // These two columns are indexed in the original table migration,
        // so drop their indexes before replacing the columns and restore them after.
        $this->dropIdentityIndexes();
        $this->changeBooleanColumnToNullableString('individual_identities', 'indigenous_status');
        $this->changeBooleanColumnToNullableString('individual_identities', 'is_visible_minority');
        $this->createIdentityIndexes();

        $this->updateProfileFormFieldsToSelects();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->restoreProfileFormFieldsToCheckboxes();

        $this->dropIdentityIndexes();
        $this->changeNullableStringColumnToBoolean('individual_identities', 'is_visible_minority');
        $this->changeNullableStringColumnToBoolean('individual_identities', 'indigenous_status');
        $this->createIdentityIndexes();

        $this->changeNullableStringColumnToBoolean('individuals', 'disability_status');
    }

    private function changeBooleanColumnToNullableString(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        $temporaryColumn = "{$column}_string_answer";

        if (! Schema::hasColumn($table, $temporaryColumn)) {
            Schema::table($table, function (Blueprint $table) use ($temporaryColumn) {
                $table->string($temporaryColumn)->nullable();
            });
        }
        // For historical true checkbox values, we choose to convert them to yes.
        // For historical false checkbox values, we choose to convert them to null.
        DB::table($table)
            ->where($column, true)
            ->update([$temporaryColumn => 'yes']);

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });

        // Rename the temporary column to the original column name.
        Schema::table($table, function (Blueprint $table) use ($temporaryColumn, $column) {
            $table->renameColumn($temporaryColumn, $column);
        });
    }

    private function changeNullableStringColumnToBoolean(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        // Rollback will lose the new answer detail because the old schema only supports booleans.
        // Only yes can be migrated as true, everything else will be false.
        $temporaryColumn = "{$column}_boolean_answer";

        if (! Schema::hasColumn($table, $temporaryColumn)) {
            Schema::table($table, function (Blueprint $table) use ($temporaryColumn) {
                $table->boolean($temporaryColumn)->default(false);
            });
        }

        DB::table($table)
            ->where($column, 'yes')
            ->update([$temporaryColumn => true]);

        Schema::table($table, function (Blueprint $table) use ($column) {
            $table->dropColumn($column);
        });

        Schema::table($table, function (Blueprint $table) use ($temporaryColumn, $column) {
            $table->renameColumn($temporaryColumn, $column);
        });
    }

    private function dropIdentityIndexes(): void
    {
        foreach (array_keys($this->identityIndexes) as $indexName) {
            Schema::table('individual_identities', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    private function createIdentityIndexes(): void
    {
        foreach ($this->identityIndexes as $indexName => $columns) {
            Schema::table('individual_identities', function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    private function updateProfileFormFieldsToSelects(): void
    {
        foreach ($this->profileAnswerFields as $fieldId) {
            $field = $this->profileFormField($fieldId);

            if (! $field) {
                continue;
            }

            DB::table('profile_form_fields')
                ->where('id', $field->id)
                ->update([
                    'type' => 'select',
                    'updated_at' => now(),
                ]);

            // Replace checkbox options so admin Form Fields shows:
            // Yes, No, Prefer not to answer.
            $this->replaceProfileFormFieldOptions($field->id, $this->profileAnswerOptions);
        }
    }

    private function restoreProfileFormFieldsToCheckboxes(): void
    {
        foreach ($this->profileAnswerFields as $fieldId) {
            $field = $this->profileFormField($fieldId);

            if (! $field) {
                continue;
            }

            DB::table('profile_form_fields')
                ->where('id', $field->id)
                ->update([
                    'type' => 'checkbox',
                    'updated_at' => now(),
                ]);

            $this->replaceProfileFormFieldOptions($field->id, []);
        }
    }

    private function profileFormField(string $fieldId): ?object
    {
        return DB::table('profile_form_fields')
            ->where('profile_type', 'student')
            ->where('field_id', $fieldId)
            ->first();
    }

    private function replaceProfileFormFieldOptions(int $profileFormFieldId, array $options): void
    {
        DB::table('profile_form_field_options')
            ->where('profile_form_field_id', $profileFormFieldId)
            ->delete();

        foreach ($options as $sortOrder => $option) {
            DB::table('profile_form_field_options')->insert([
                'profile_form_field_id' => $profileFormFieldId,
                'value' => $option['value'],
                'label' => $option['label'],
                'is_default' => false,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
