<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Report::query()->whereNull('activity_id')->exists()) {
            return;
        }

        // Create 3 yearly reports
        Report::factory()->count(3)->generateYearly()->create();

        // Report related to activites are generated from within the Activity Seeder
    }
}
