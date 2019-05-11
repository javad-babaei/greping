<?php 

namespace App\Grep;

use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class Core
{
	public $url;
	public $dom;
	public $grep;

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
		$this->dom = $this->dom->first();
		return $this;
	}

	public function eq($eq = 0)
	{
		$this->dom = $this->dom->eq($eq);
		return $this;
	}

	public function text($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->text();
		}
		return $this->dom->text();
	}

	public function img($filter = null, $attr = 'src')
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->attr($attr);
		}
		return $this->dom->attr($attr);
	}

	public function each($value='')
	{
		# code...
	}

	public function link($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->attr('href');
		}
		return $this->dom->attr('href');
	}

	public function href($filter = null)
	{
		if($filter) {
			return $this->grep->filter($filter)->first()->attr('href');
		}
		return $this->dom->attr('href');
	}


}