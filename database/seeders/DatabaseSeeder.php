<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusLine;
use App\Models\Vertices;
use App\Models\Edges;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(BusLineSeeder::class);
    }
}
