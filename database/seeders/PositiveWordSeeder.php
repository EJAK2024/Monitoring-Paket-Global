<?php

namespace Database\Seeders;

use App\Models\PositiveWord;
use Illuminate\Database\Seeder;

class PositiveWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'growth', 'increase', 'profit', 'stable', 'improve',
            'gain', 'rise', 'boost', 'surplus', 'expansion',
            'recovery', 'strength', 'success', 'positive', 'efficient',
            'innovation', 'opportunity', 'breakthrough', 'progress', 'advance',
            'flourish', 'thrive', 'prosper', 'upturn', 'momentum',
        ];

        foreach ($words as $word) {
            PositiveWord::firstOrCreate(['word' => $word]);
        }
    }
}
