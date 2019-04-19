<?php 
namespace App\Grep;

class Artist
{
	protected $grep;

	public function __cunstruct($url)
	{
		$this->grep = new Core($url);
	}

	public function grep()
	{
		return $this->grep;
	}

	public function featchDom()
	{		
		'name' => $this->grep()->text('.profile_dtls strong'),
		'translate' => $this->grep()->text('.profile_dtls h2'),
		'img' => $this->grep()->img('.profile_pic_main img'),
	}

}