<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    protected $fillable = ['type', 'external_id', 'title'];

    protected $casts = [
        'title' => 'array',
    ];
}
