<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NegativeWord extends Model
{
    protected $table = 'negative_words';

    protected $fillable = ['word'];
}
