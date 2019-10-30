<?php
namespace App\Grep;

use Symfony\Component\DomCrawler\Crawler;

class TrackNextOne
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
		$lyric = $this->grep()->text('.lyrics');
		$name = $this->grep()->filter('.center strong')->eq(1)->text();
		$artist_name = $this->grep()->filter('.center strong')->eq(0)->text();
		$trackUrl = $this->grep()->filter('.lnkdl a')->eq(0)->href();
		$downloadUrl = $this->grep()->filter('.lnkdl a')->eq(0)->href();
		$translate = $this->grep()->filter('.center p a')->eq(3)->text();
		$exploded = explode(' - ', $translate);
		if(count($exploded)) {
			$translate = $exploded[0];
		}

		return [
			'description' => $lyric,
			'downloadUrl' => $downloadUrl,
			'stream' => '',
			'trackUrl' => $trackUrl,
			'img' => $this->grep()->img('.center img'),
			'lyric' => $this->grep()->text('.lyrics'),
			'translate' => $translate,
			'name' => $this->grep()->filter('.center strong')->eq(1)->text(),
			'published' => $this->grep()->text('.pstop text'),
			'artist' =>  $this->grep()->filter('.center strong')->eq(0)->text()
		];
	}

	public function proccess()
	{
		$data = $this->featchDom();
		dd($data);
		return (new \App\Grep\Entity\perTrack())->grep($data);
	}
}

