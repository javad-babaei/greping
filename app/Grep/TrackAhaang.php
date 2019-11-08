<?php 
namespace App\Grep;

use Symfony\Component\DomCrawler\Crawler;

class TrackAhaang
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
		$lyric = $this->grep()->text('.lyric_box');
		$name_node = $this->grep()->filter('ul li.icon-audiotrack');
		if($name_node->count()){
			$name = $name_node->eq(0)->text();
			$name = str_replace('عنوان اثر', '', $name);
		}else {
			$name = $this->grep()->filter('.single_text strong')->eq(1)->text();	
		}
		
		// $artist_name = $this->grep()->filter('.single_text strong')->eq(0)->text();
		$node = $this->grep()->filter('ul li.icon-person a')->count();
		if($node){
			$artist_name = $this->grep()->filter('ul li.icon-person a')->eq(0)->text();
		} else {
			$artist_name = 'غیره';
		}

		$trackUrl = $this->grep()->filter('.single_track')->eq(1)->href();
		$downloadUrl = $this->grep()->link('.single_track_320');
		$translate = $this->grep()->text('.single_cover h2');		
		$exploded = explode(' - ', $translate);
		$cover = $this->grep()->img('.single_pic img', 'src') ?? $this->grep()->img('.single_pic img', 'data-src');

		
		if(count($exploded) > 1) {
			$translate = $exploded[1];
		} else {
			$translate = $exploded[0];
		}
		
		return [
			'description' => $lyric,
			'downloadUrl' => $downloadUrl,
			'stream' => '',
			'trackUrl' => $trackUrl,
			'img' => $cover,
			'lyric' => $lyric,
			'translate' => $translate,
			'name' => $name,
			'published' => $this->grep()->text('.icon-calendar'),
			'artist' => $artist_name
		];
	}

	public function proccess()
	{
		$soon = $this->grep()->filter('.soon')->count();
		if($soon) return false;
		$data = $this->featchDom();
		return (new \App\Grep\Entity\Track())->grep($data);
	}

}