<?php

return [
    'base_url' => 'https://api.worldbank.org/v2',

    'timeout' => (int) env('WORLDBANK_TIMEOUT', 10),

    'cache_ttl' => (int) env('WORLDBANK_CACHE_TTL', 3600),

    'indicators' => [
        'gdp' => 'NY.GDP.MKTP.CD',
        'inflation' => 'FP.CPI.TOTL.ZG',
        'population' => 'SP.POP.TOTL',
        'exports' => 'NE.EXP.GNFS.CD',
        'imports' => 'NE.IMP.GNFS.CD',
    ],

    'normalize' => ['gdp', 'exports', 'imports'],
];
