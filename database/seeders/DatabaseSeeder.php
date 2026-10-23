<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RoleSeeder::class,
            CountriesSeeder::class,
            
            ApplicationSeeder::class,
            InstitutionSeeder::class,
            InstitutionSiteSeeder::class,
            ProfileFormFieldSeeder::class,
             
        ]);

        // User::factory(10)->create();

        // Note: Test user creation commented out since we use Keycloak SSO
        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
