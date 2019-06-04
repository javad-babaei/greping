<?php 

namespace App\Grep\Castbox;

use App\Grep\Core;
use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Castbox\Episode;

class Crawller extends Core
{
	protected $core;
	protected $data;
	public function __construct($url)
	{
		$this->core = new Core($url);
	}

	public function newCore($url)
	{
		return new Core($url);
	}

	public function grep()
	{
		return $this->core;
	}


	public function featchChannel()
	{	
		return [
			'name' => $this->grep()->text('.ch_feed_info h1'),
			'description' => $this->grep()->text('.des-con div'),
			'author' => str_replace(
				"Author:", "", $this->grep()->text('.author')
			),
			'feedcover' => $this->grep()->img('.coverImgContainer img'),
		];
	}

	public function featchAllEpisode()
	{
		return $this->grep()->filter('.trackListCon_list .episodeRow a')->dom()->each(function(Crawler $node, $i){
				return "https://castbox.fm" . $node->attr('href');
		});
	}

	public function featchEpisode($url)
	{
		$grep = $this->newCore($url);

		return [
			'name' => $grep->text('.trackinfo-titleBox h1'),
			'description' => $grep->text('.trackinfo-des'),
			'trackcover' => $grep->img('.coverImgContainer img'),
			'downloadUrl' => $grep->filter('source')->first()->dom()->attr('src'),
			'update' => $grep->filter('.trackinfo-con-des')->first()->text(),
		];
	}

	public function add()
	{
		
		$entity = new Channel();
		$channel = $entity->proccess(
			$this->featchChannel()
		);

		//
		$episode = $this->featchAllEpisode();

		foreach ($episode as $item) {
			$data = $this->featchEpisode($item);
			$data['channel'] = $channel['name'];
			(new Episode)->proccess($data);
		}


		dd($this->data);
	}


}