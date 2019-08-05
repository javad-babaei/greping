<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    protected $table = 'artist';
    protected $hidden = [];

    protected $casts = [
        'id' => 'string',
    ];

    public $timestamps = false;
}
