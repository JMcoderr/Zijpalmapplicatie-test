<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Report;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Mail::fake();

        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (blank($adminEmail) || blank($adminPassword)) {
            throw new \RuntimeException('Set a unique ADMIN_EMAIL and ADMIN_PASSWORD before seeding.');
        }

        $admin = User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'firstName' => 'Example',
                'lastName' => 'Administrator',
                'password' => Hash::make($adminPassword),
                'type' => UserType::System,
            ],
        );
        $admin->forceFill(['is_admin' => true])->save();

        $this->call([
            ContentSeeder::class,
            ProductSeeder::class,
        ]);

        if (app()->environment('local')) {
            if (User::count() <= 4) {
                User::factory(100)->create();
            }

            if (Product::count() <= 1) {
                Product::factory(10)->create();
            }

            $this->call([
                ActivitySeeder::class,
                ApplicationSeeder::class,
                ReportSeeder::class,
                CharitySeeder::class,
                TournamentSeeder::class,
            ]);
        }


    }
}
