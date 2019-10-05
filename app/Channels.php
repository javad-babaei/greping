<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Channels extends Model
{
    //
    protected $table = 'channel';
    protected $casts = [
        'id' => 'string'
    ];

    public $timestamps = false;

}
