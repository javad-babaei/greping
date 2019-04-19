<?php 

namespace App\Grep;

use App\Crawler\Contract\CrawlerContract;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class Core
{
	protected $url;
	protected $dom;

	public function __construct($url)
	{
		$client = new Client();
		$this->url = $url;
		$this->grep = $client->request('GET', $this->url);
	}

	public function dom()
	{
		return $this->dom;
	}

	public function filter($filter)
	{
		$this->dom = $this->grep->filter($filter);
		return $this;
	}

	public function first()
	{
		$this->dom = $this->dom->first()
		return $this;
	}

	public function eq($eq = 0)
	{
		$this->dom = $this->dom->eq($eq)
		return $this;
	}

	public function text($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->text();
		}
		return $this->dom->text();
	}

	public function image($filter = null, $attr = 'src')
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->attr($attr);
		}
		return $this->dom->attr($attr);
	}

	public function link($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->link();
		}
		return $this->dom->link();
	}

	public function href($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->href();
		}
		return $this->dom->href();
	}


}