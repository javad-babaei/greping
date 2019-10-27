<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tracks extends Model
{
    protected $table = 'album';
	public $timestamps = false;
	protected $hidden = [];
	protected $casts = [
		'id' => 'string'
	];
}
