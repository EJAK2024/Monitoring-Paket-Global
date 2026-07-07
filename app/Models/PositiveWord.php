<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PositiveWord extends Model
{
    protected $table = 'positive_words';

    protected $fillable = ['word'];
}
