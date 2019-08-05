<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Albums extends Model
{
	protected $table = 'album';
	public $timestamp = false;
	protected $hidden = [];
	protected $casts = [
		'id' => 'string'
	];
}
