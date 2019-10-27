<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tracks extends Model
{
    protected $table = 'track';
	public $timestamps = false;
	protected $hidden = [];
	protected $casts = [
		'id' => 'string'
	];
}
