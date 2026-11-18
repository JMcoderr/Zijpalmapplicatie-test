<?php

namespace Database\Seeders;

use App\Models\Charity\CharityAdmin;
use App\Models\Charity\Event;
use App\Models\Charity\EventCost;
use App\Models\Charity\Member;
use Illuminate\Database\Seeder;

class CharitySeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            1 => ['title' => '30e verjaardag', 'amount' => 25],
            2 => ['title' => '40e verjaardag', 'amount' => 25],
            3 => ['title' => '50e verjaardag', 'amount' => 25],
            4 => ['title' => '60e verjaardag', 'amount' => 25],
            5 => ['title' => '65e verjaardag', 'amount' => 25],
            6 => ['title' => '12,5-jarig ambtsjubileum', 'amount' => 25],
            7 => ['title' => '25-jarig ambtsjubileum', 'amount' => 25],
            8 => ['title' => '40-jarig ambtsjubileum', 'amount' => 40],
            9 => ['title' => 'Geboorte', 'amount' => 25],
            10 => ['title' => 'Huwelijk of geregistreerd partnerschap', 'amount' => 40],
            11 => ['title' => 'Pensioen', 'amount' => 25],
            12 => ['title' => 'Vertrek bij de gemeente', 'amount' => 25],
            13 => ['title' => '12,5- of 25-jarig huwelijk', 'amount' => 25],
            14 => ['title' => '40-jarig huwelijk', 'amount' => 40],
            15 => ['title' => 'Langdurige ziekte', 'amount' => 25],
            16 => ['title' => 'Ziekenhuisopname', 'amount' => 25],
        ];

        foreach ($events as $id => $data) {
            $event = Event::query()->find($id)
                ?? Event::query()->where('title', $data['title'])->first();

            if (! $event) {
                $event = new Event(['title' => $data['title']]);
                $event->id = $id;
                $event->save();
            }

            EventCost::updateOrCreate(
                [
                    'event_id' => $event->id,
                    'start_date' => '2020-01-01 00:00:00',
                ],
                [
                    'amount' => $data['amount'],
                    'end_date' => null,
                ],
            );
        }

        $adminEmail = strtolower(config('mail.bestuur.address') ?: 'admin@example.test');
        CharityAdmin::firstOrCreate(['employee' => $adminEmail], ['super' => true]);

        Member::firstOrCreate(
            ['id' => 999999],
            [
                'email' => 'demo.member@example.test',
                'full_name' => 'Voorbeeld Deelnemer',
                'name' => 'Voorbeeld Deelnemer',
                'dob' => '1996-01-01',
                'years_of_service' => '2018-01-01',
            ],
        );
    }
}
