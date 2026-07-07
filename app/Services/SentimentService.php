<?php

namespace App\Services;

use App\Models\NegativeWord;
use App\Models\PositiveWord;

class SentimentService
{
    public function analyze(string $text): array
    {
        $words = array_map('strtolower', str_word_count($text, 1));
        $positiveWords = PositiveWord::pluck('word')->map(fn ($w) => strtolower($w))->toArray();
        $negativeWords = NegativeWord::pluck('word')->map(fn ($w) => strtolower($w))->toArray();

        $positiveScore = 0;
        $negativeScore = 0;

        foreach ($words as $word) {
            if (in_array($word, $positiveWords)) {
                $positiveScore++;
            }
            if (in_array($word, $negativeWords)) {
                $negativeScore++;
            }
        }

        $total = $positiveScore + $negativeScore;

        if ($total === 0) {
            return [
                'sentiment' => 'neutral',
                'positive' => 0,
                'negative' => 0,
                'neutral' => 100,
            ];
        }

        $sentiment = $positiveScore > $negativeScore ? 'positive' : 'negative';
        if ($positiveScore === $negativeScore) {
            $sentiment = 'neutral';
        }

        return [
            'sentiment' => $sentiment,
            'positive' => round($positiveScore / $total * 100),
            'negative' => round($negativeScore / $total * 100),
            'neutral' => 0,
        ];
    }
}
