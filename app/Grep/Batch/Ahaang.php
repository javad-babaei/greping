<?php
namespace App\Grep\Batch;

use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Core;

class Ahaang
{
	protected $core;
	public $album;
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
		$count = $this->grep()->filter('.single_text strong')->count();
		if($count > 2) {
			$name = $this->grep()->filter('.single_text strong')->eq(1)->text();
			$artist_name = $this->grep()->filter('.single_text strong')->eq(0)->text();
		} elseif ($count == 2) {
			$name = $this->grep()->filter('.single_text strong')->eq(0)->text();
			$artist_name = $this->grep()->filter('.single_text strong')->eq(1)->text();
		} else {
			$count = $this->grep()->filter('.single_text b')->count();

			if($count >= 2) {
				$name = $this->grep()->filter('.single_text b')->eq(0)->text();
				$artist_name = $this->grep()->filter('.single_text b')->eq(1)->text();
			} else {
				$name = $this->grep()->filter('#breadcrumbs a')->eq(1)->text();
				$artist_name = $this->grep()->filter('#breadcrumbs span')->eq(2)->text();
				$name = str_replace("دانلود آهنگ " . $artist_name,'' , $name);
			}
		}

		$artist_name = $this->grep()->filter('.single_text strong')->eq(1)->text();


		$single_track_count = $this->grep()->filter('.single_track')->count();
		if($single_track_count == 1) {
			return false;
		}
		$trackUrl = $this->grep()->filter('.single_track')->eq(1)->href();
		$downloadUrl = $this->grep()->link('.single_track_320');
		if($downloadUrl == null) {
			$downloadUrl = $trackUrl;
		}
		$translate = $this->grep()->text('.single_cover h2');
		$exploded = explode(' - ', $translate);
		if(count($exploded) > 1) {
			$translate = $exploded[1];
		}

		return [
			'description' => $lyric,
			'downloadUrl' => $downloadUrl,
			'stream' => '',
			'trackUrl' => $trackUrl,
            'img' => $this->grep()->img('.single_pic img', 'data-src') ?? $this->grep()->img('.single_pic img'),
			'lyric' => $lyric,
			'translate' => $translate,
			'name' => $name,
			'published' => $this->grep()->text('.icon-calendar'),
			'artist' => $artist_name
		];
	}

	public function proccess()
	{
		$data = $this->featchDom();
		if($data){
			if($this->album){
				$data['albumId'] = $this->album['id'];
				$data['albumName'] = $this->album['name'];
			}
			return (new \App\Grep\Entity\Track())->grep($data);
		}
	}

}
