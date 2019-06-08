<?php
namespace App\Grep\Batch;

use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Core;
use App\Grep\Batch\Ahaang;
use App\Grep\Entity\Album as Api;
use App\Grep\Entity\Track as TrackApi;

class Album
{
	protected $core;
	protected $data;
	protected $album_list;
	public function __construct($url)
	{
		$this->core = new Core($url);
		$this->entity = new Api;
	}

	public function core($url)
	{
		return new Core($url);	
	}

	public function createAlbum($data)
	{
		
	}

	public function getTrackNameAndAttachToAlbum()
	{
		foreach ($this->tracks as $tracks) {
				// create album
				$album = $this->entity->grep($tracks);
				foreach ($tracks['tracks'] as $track) {
					 $class = new Ahaang($track);
					 $class->album = $album;
					 $class->proccess();
				}
		}
	}

	public function getTrackLinks()
	{
		foreach ($this->album_list as $key => $item) {
			$data[$key] = $this->getTrackDom($item);
		}

		$this->tracks = $data;
	}

	public function getTrackDom($item)
	{
		$core = new Core($item);
		$list = $core->filter('.track a.divStrong')->dom()->each(function(Crawler $node, $i){
			if(!$node->filter('.soon')->count()) {
				return $node->attr('href');
			}
		});


		$file_url =  $core->img('.single_pic img', 'data-src');
		
		return [
			'artistName' => $core->text('ul .icon-person a'),
			'name' => $core->filter('.single_text p strong')->dom->eq(2)->text(),
			'cover' => $file_url,
			'tracks' => $list
		];

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

	public function featchAlbumDom()
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


	public function getAlbumByLink()
	{
		$data = $this->featchDom();
		$this->data = $data;

		$list_url = "https://ahaang.com/query/profile-ajax-tab.php?tab=album&artist=" . urlencode(
			str_replace(' ', '-', $this->data['name'])
		);

		$core = new Core($list_url);
		$list = $core->filter('.profile_box_body a')->dom()->each(function(Crawler $node, $i){
			if(!$node->filter('.soon')->count()) {
				return $node->attr('href');
			}
		});

		$this->album_list =  $list;
	}

}
