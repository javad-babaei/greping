<?php
namespace App\Grep\Batch;

use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Core;

class Artist
{
	protected $core;
	protected $data;
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
		$this->data = $data;
		(
			new \App\Grep\Entity\Artist
		)->grep($data);
	}

	public function getTrackLink()
	{

		$list_url = "https://ahaang.com/query/profile-ajax-tab.php?tab=mp3&artist=" . urlencode(
			str_replace(' ', '-', $this->data['name'])
		);

		$core = new Core($list_url);
		$list = $core->filter('.profile_box_body a')->dom()->each(function(Crawler $node, $i){
			if(!$node->filter('.soon')->count()) {
				return $node->attr('href');
			}
		});

		return $list;
	}

}
