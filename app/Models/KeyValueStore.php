<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyValueStore extends Model
{
    protected $table = 'key_value_store';

    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
