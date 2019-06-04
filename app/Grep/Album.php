<?php 
namespace App\Grep;

use Symfony\Component\DomCrawler\Crawler;

class Album 
{
	protected $core;
	public function __construct($url)
	{
		$this->core = new Core($url);
	}

	public function grep()
	{
		return $this->core;
	}

	public function featchDom()
	{	
		$file_url =  $this->grep()->img('.profile_pic_main img', 'data-src');

		return [
			'name' => $this->grep()->text('.profile_dtls strong'),
			'translate' => $this->grep()->text('.profile_dtls h2'),
			'simplename' => str_replace(
				" ", "-",$this->grep()->text('.profile_dtls h2')
			),
			'cover' => $file_url,
		];
	}

	public function add()
	{
		$data = $this->featchDom();
		dd($data);
		(
			new \App\Grep\Entity\Artist
		)->grep($data);
	}

}