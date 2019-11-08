<?php 

namespace App\Grep;

use App\Grep\Crawller;

class AutoUpdateNewTrack
{
	protected $url;
	protected $findLinks;

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function crawl()
	{
		return new Crawller($this->url);
	}
	public function getNewTrackLinks()
	{
		$links = $this->crawl()->filter('.posts .post .post_body a.post_img');

		$this->findLinks = $links->dom()->each(function ($node) {
    		return $node->attr('href');
		});
	}

	public function download()
	{
		foreach ($this->findLinks as $key => $link) {
				$grep = new \App\Grep\TrackAhaang($link);
            	$grep->proccess();
		}
	}

}