<?php

namespace App\Console\Commands;

use App\Models\ProfileFormField;
use App\Services\ProfileFieldSchemaSync;
use Illuminate\Console\Command;

class SyncProfileFieldColumns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile-field:sync
                            {--field= : Sync a single ProfileFormField by id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and run migrations so every student profile form field has a matching schema column';

    public function handle(ProfileFieldSchemaSync $sync): int
    {
        $query = ProfileFormField::query()->where('profile_type', 'student');

        if ($fieldId = $this->option('field')) {
            $query->where('id', $fieldId);
        }

        $fields = $query->get();

        if ($fields->isEmpty()) {
            $this->warn('No matching student profile form fields found.');

            return self::SUCCESS;
        }

        foreach ($fields as $field) {
            $result = $sync->syncField($field);

            $line = "[{$result['status']}] {$field->field_id}: {$result['message']}";

            match ($result['status']) {
                'created' => $this->info($line),
                'exists' => $this->line($line),
                default => $this->warn($line),
            };
        }

        return self::SUCCESS;
    }
}
