<?php

namespace Database\Seeders;

use App\Models\NegativeWord;
use Illuminate\Database\Seeder;

class NegativeWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            ['word' => 'decline', 'category' => 'negative'],
            ['word' => 'decrease', 'category' => 'negative'],
            ['word' => 'loss', 'category' => 'negative'],
            ['word' => 'deficit', 'category' => 'negative'],
            ['word' => 'delay', 'category' => 'negative'],
            ['word' => 'corruption', 'category' => 'negative'],
            ['word' => 'instability', 'category' => 'negative'],
            ['word' => 'volatility', 'category' => 'negative'],
            ['word' => 'downturn', 'category' => 'negative'],
            ['word' => 'slowdown', 'category' => 'negative'],
            ['word' => 'stagnation', 'category' => 'negative'],
            ['word' => 'tariff', 'category' => 'negative'],
            ['word' => 'strike', 'category' => 'negative'],
            ['word' => 'flood', 'category' => 'negative'],
            ['word' => 'drought', 'category' => 'negative'],
            ['word' => 'inflation', 'category' => 'negative'],
            ['word' => 'war', 'category' => 'crisis'],
            ['word' => 'crisis', 'category' => 'crisis'],
            ['word' => 'disaster', 'category' => 'crisis'],
            ['word' => 'collapse', 'category' => 'crisis'],
            ['word' => 'sanction', 'category' => 'crisis'],
            ['word' => 'conflict', 'category' => 'crisis'],
            ['word' => 'recession', 'category' => 'crisis'],
            ['word' => 'default', 'category' => 'crisis'],
            ['word' => 'embargo', 'category' => 'crisis'],
            ['word' => 'bankruptcy', 'category' => 'crisis'],
            ['word' => 'contagion', 'category' => 'crisis'],
            ['word' => 'lockdown', 'category' => 'crisis'],
        ];

        foreach ($words as $item) {
            NegativeWord::firstOrCreate(
                ['word' => $item['word']],
                ['category' => $item['category']]
            );
        }
    }
}
