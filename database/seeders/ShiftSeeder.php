<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        Shift::updateOrCreate(
            ['code' => 'MATUTINO'],
            ['name' => 'Matutino'],
        );

        Shift::updateOrCreate(
            ['code' => 'VESPERTINO'],
            ['name' => 'Vespertino'],
        );
    }
}
