<?php

namespace App\Contracts;

interface SentimentAnalyzerInterface
{
    public function analyze(string $text): array;
}
