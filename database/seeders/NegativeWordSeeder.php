<?php

namespace Database\Seeders;

use App\Models\NegativeWord;
use Illuminate\Database\Seeder;

class NegativeWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'war', 'crisis', 'inflation', 'delay', 'disaster',
            'decline', 'decrease', 'loss', 'deficit', 'recession',
            'collapse', 'sanction', 'conflict', 'instability', 'corruption',
            'default', 'downturn', 'slowdown', 'stagnation', 'volatility',
            'tariff', 'ban', 'strike', 'flood', 'drought',
        ];

        foreach ($words as $word) {
            NegativeWord::firstOrCreate(['word' => $word]);
        }
    }
}
