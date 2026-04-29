<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    protected $fillable = ['type', 'title'];

    protected $casts = [
        'title' => 'array',
    ];
}
