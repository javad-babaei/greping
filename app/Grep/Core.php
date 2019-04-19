<?php 

namespace App\Grep\Core;

use App\Crawler\Contract\CrawlerContract;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class Core
{
	protected $url;
	protected $crawler;

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

	public function text()
	{
		return $this->dom->text();
	}

	public function image($attr = 'src')
	{
		return $this->dom->attr($attr);
	}

	public function link()
	{
		return $this->dom->href();
	}


}