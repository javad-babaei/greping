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
		$dom = $this->grep()->filter('.A_link')->dom()->click();
		return $this->grep()->filter('a.ctrlItem')->dom()->each(function(Crawler $node, $i){
				return $node->attr('href');
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

		$url = "https://everest.castbox.fm/data/episode_list/v2?cid=1324117&skip=0&limit=100&ascending=1&web=1";
		$data = file_get_contents($url);
		$data = json_decode($data, true);
		$episode = $data['data']['episode_list'];
		
		foreach ($episode as $item) {
			$item['channel'] = $channel['name'];
			$item['channel_id'] = $channel['id'];
			(new Episode)->proccess($item);
		}
	}


}