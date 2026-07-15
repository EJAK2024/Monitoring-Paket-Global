<?php

namespace App\Services;

use App\Contracts\SentimentAnalyzerInterface;
use App\Models\NegativeWord;
use App\Models\PositiveWord;
use Illuminate\Support\Facades\Cache;

class SentimentService implements SentimentAnalyzerInterface
{
    public function analyze(string $text): array
    {
        $words = array_map('strtolower', str_word_count($text, 1));
        $positiveWords = Cache::remember('sentiment.positive_words', 3600, function () {
            return PositiveWord::pluck('word')->map(fn ($w) => strtolower($w))->toArray();
        });
        $negativeWords = Cache::remember('sentiment.negative_words', 3600, function () {
            return NegativeWord::where('category', 'negative')->pluck('word')->map(fn ($w) => strtolower($w))->toArray();
        });
        $crisisWords = Cache::remember('sentiment.crisis_words', 3600, function () {
            return NegativeWord::where('category', 'crisis')->pluck('word')->map(fn ($w) => strtolower($w))->toArray();
        });

        $positiveScore = 0;
        $negativeScore = 0;
        $crisisScore = 0;

        foreach ($words as $word) {
            if (in_array($word, $positiveWords)) {
                $positiveScore++;
            }
            if (in_array($word, $negativeWords)) {
                $negativeScore++;
            }
            if (in_array($word, $crisisWords)) {
                $crisisScore++;
            }
        }

        $total = $positiveScore + $negativeScore + $crisisScore;

        if ($total === 0) {
            return [
                'sentiment' => 'neutral',
                'positive' => 0,
                'negative' => 0,
                'crisis' => 0,
                'neutral' => 100,
            ];
        }

        if ($crisisScore > 0) {
            $sentiment = 'crisis';
        } elseif ($positiveScore > $negativeScore) {
            $sentiment = 'positive';
        } elseif ($negativeScore > $positiveScore) {
            $sentiment = 'negative';
        } else {
            $sentiment = 'neutral';
        }

        $positivePercent = round($positiveScore / $total * 100);
        $negativePercent = round(($negativeScore + $crisisScore) / $total * 100);
        $crisisPercent = round($crisisScore / $total * 100);
        $neutralPercent = max(0, 100 - $positivePercent - $negativePercent);

        return [
            'sentiment' => $sentiment,
            'positive' => $positivePercent,
            'negative' => $negativePercent,
            'crisis' => $crisisPercent,
            'neutral' => $neutralPercent,
        ];
    }
}
