<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AbsenceReason;

class AbsenceReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['name' => 'Sick', 'code' => 'S', 'category' => 'medical', 'is_excused' => true, 'display_order' => 1],
            ['name' => 'Doctor Appointment', 'code' => 'DR', 'category' => 'medical', 'is_excused' => true, 'display_order' => 2],
            ['name' => 'Family Simcha', 'code' => 'FS', 'category' => 'family', 'is_excused' => true, 'display_order' => 3],
            ['name' => 'Family Emergency', 'code' => 'FE', 'category' => 'family', 'is_excused' => true, 'display_order' => 4],
            ['name' => 'Shiva/Avel', 'code' => 'SH', 'category' => 'religious', 'is_excused' => true, 'display_order' => 5],
            ['name' => 'Yom Tov Travel', 'code' => 'YT', 'category' => 'religious', 'is_excused' => true, 'display_order' => 6],
            ['name' => 'School Trip/Event', 'code' => 'ST', 'category' => 'school', 'is_excused' => true, 'display_order' => 7],
            ['name' => 'Dentist/Orthodontist', 'code' => 'DT', 'category' => 'medical', 'is_excused' => true, 'display_order' => 8],
            ['name' => 'Mental Health Day', 'code' => 'MH', 'category' => 'medical', 'is_excused' => true, 'display_order' => 9],
            ['name' => 'Transportation Issue', 'code' => 'TR', 'category' => 'other', 'is_excused' => false, 'display_order' => 10],
            ['name' => 'Overslept', 'code' => 'OS', 'category' => 'other', 'is_excused' => false, 'display_order' => 11],
            ['name' => 'Unexcused', 'code' => 'U', 'category' => 'other', 'is_excused' => false, 'display_order' => 12],
            ['name' => 'Unknown/Other', 'code' => 'O', 'category' => 'other', 'is_excused' => false, 'display_order' => 13],
        ];

        foreach ($reasons as $reason) {
            AbsenceReason::firstOrCreate(
                ['code' => $reason['code']],
                $reason
            );
        }
    }
}
