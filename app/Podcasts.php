<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Podcasts extends Model
{
    //
    protected $table = 'episode';
    protected $casts = [
        'id' => 'string'
    ];

    public $timestamps = false;
}
