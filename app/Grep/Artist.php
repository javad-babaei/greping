<?php 
namespace App\Grep;

use Symfony\Component\DomCrawler\Crawler;

class Artist
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
		$filename = basename($file_url);
		$binary = file_get_contents($file_url);
		$size = strlen($binary);
		$type = getimagesizefromstring($binary)['mime'];

		return [
			'name' => $this->grep()->text('.profile_dtls strong'),
			'translate' => $this->grep()->text('.profile_dtls h2'),
			'simplename' => str_replace(
				" ", "-",$this->grep()->text('.profile_dtls h2')
			),
			'filename' => $filename,
			'file' => $binary,
			'size' => $size,
			'type' => $type
		];
	}

	public function add()
	{
		$data = $this->featchDom();
		$result = (new \App\Grep\Entity\Artist())->create($data);
	}

	public function proccess()
	{
		$this->grep()->filter('.post_body a.post_img')->dom()->each(function (Crawler $node, $i){
			$url = $node->attr('href');
			$artist = new \App\Grep\Artist($url);
			$artist->add();
		});
	}

}