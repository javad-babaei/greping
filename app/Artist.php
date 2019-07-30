<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $tables = 'artist';
    protected $hidden = [];

    protected $casts = [
        'id' => 'string',
    ];
}
