<?php

namespace App\Services;

use App\Models\ProfileFormField;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the physical database schema in sync with the student profile form
 * field definitions (profile_form_fields).
 *
 * Every profile form field maps 1:1 to a column on one of the individual
 * tables, decided by the field's tab:
 *   general    -> individuals
 *   address    -> individual_addresses
 *   employment -> individual_employments
 *   identity   -> individual_identities
 *
 * When a field is created whose column does not yet exist, this service
 * generates a migration file for that column and runs it.
 */
class ProfileFieldSchemaSync
{
    /**
     * Map a form field tab to its backing table.
     */
    public const TAB_TABLE_MAP = [
        'general' => 'individuals',
        'address' => 'individual_addresses',
        'employment' => 'individual_employments',
        'identity' => 'individual_identities',
    ];

    /**
     * Ensure the column for the given field exists on its table.
     *
     * Generates and runs a migration when the column is missing.
     *
     * @return array{status:string, table:?string, column:?string, migration:?string, message:?string}
     */
    public function syncField(ProfileFormField $field): array
    {
        $table = self::TAB_TABLE_MAP[$field->tab] ?? null;
        $column = $field->field_id;

        if (! $table) {
            return [
                'status' => 'skipped',
                'table' => null,
                'column' => $column,
                'migration' => null,
                'message' => "Unknown tab '{$field->tab}'. Expected one of: " . implode(', ', array_keys(self::TAB_TABLE_MAP)),
            ];
        }

        if (! Schema::hasTable($table)) {
            return [
                'status' => 'skipped',
                'table' => $table,
                'column' => $column,
                'migration' => null,
                'message' => "Table '{$table}' does not exist.",
            ];
        }

        if (Schema::hasColumn($table, $column)) {
            return [
                'status' => 'exists',
                'table' => $table,
                'column' => $column,
                'migration' => null,
                'message' => "Column '{$column}' already exists on '{$table}'.",
            ];
        }

        $migrationPath = $this->writeMigration($table, $column, $field);

        Artisan::call('migrate', ['--force' => true]);

        return [
            'status' => 'created',
            'table' => $table,
            'column' => $column,
            'migration' => $migrationPath,
            'message' => "Added column '{$column}' to '{$table}'.",
        ];
    }

    /**
     * Write a migration file that adds the column to the table.
     *
     * @return string The absolute path of the generated migration file.
     */
    private function writeMigration(string $table, string $column, ProfileFormField $field): string
    {
        $definition = $this->columnDefinition($field);
        $rollback = "\$table->dropColumn('{$column}');";

        $stub = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('{$table}', '{$column}')) {
            Schema::table('{$table}', function (Blueprint \$table) {
                {$definition}
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('{$table}', '{$column}')) {
            Schema::table('{$table}', function (Blueprint \$table) {
                {$rollback}
            });
        }
    }
};

PHP;

        $fileName = $this->uniqueMigrationName("add_{$column}_to_{$table}_table");
        $path = database_path('migrations/' . $fileName);

        File::put($path, $stub);

        return $path;
    }

    /**
     * Build the Blueprint column definition line for a field.
     */
    private function columnDefinition(ProfileFormField $field): string
    {
        $column = $field->field_id;

        // multi-select fields store an array of values as JSON.
        if ($field->multi_select) {
            return "\$table->json('{$column}')->nullable();";
        }

        return match ($field->type) {
            'textarea' => "\$table->text('{$column}')->nullable();",
            'number' => "\$table->integer('{$column}')->nullable();",
            'date' => "\$table->date('{$column}')->nullable();",
            'checkbox' => "\$table->boolean('{$column}')->default(false);",
            default => "\$table->string('{$column}')->nullable();",
        };
    }

    /**
     * Produce a unique, timestamped migration file name.
     */
    private function uniqueMigrationName(string $descriptor): string
    {
        $base = now()->format('Y_m_d_His');
        $candidate = "{$base}_{$descriptor}.php";
        $counter = 1;

        while (File::exists(database_path('migrations/' . $candidate))) {
            $candidate = "{$base}_{$descriptor}_{$counter}.php";
            $counter++;
        }

        return $candidate;
    }
}
