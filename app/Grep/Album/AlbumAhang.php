<?php 
namespace App\Grep\Album;

use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Core;
use App\Grep\Batch\Ahaang;

class AlbumAhang 
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
		$file_url =  $this->grep()->img('.single_pic img', 'data-src');
		$name = str_replace(
			["اثر رنگ بارون", "عنوان اثر"], ["", ""]
			, $this->grep()->text('ul .icon-audiotrack'));

		$tracks = $this->grep()->filter('.track a.divStrong')->dom()->each(function(Crawler $node, $i){
			if(!$node->filter('.soon')->count()) {
				return $node->attr('href');
			}
		});

		return [
			'name' => $name,
			'translate' => $this->grep()->text('.single_cover h2'),
			'published' => $this->grep()->text('.icon-calendar'),
			'artistName' => $this->grep()->text('.single_text p b'),
			'cover' => $file_url,
			'tracks' => $tracks
		];
	}

	public function add()
	{
		$data = $this->featchDom();
		$album = (new \App\Grep\Entity\Album)->grep($data);

		foreach ($data['tracks'] as $track_link) {
            dump($track_link);
            if($track_link){
                $class = (new Ahaang($track_link));
                $class->album = $album;
                $class->proccess();
            }
        }
	}

	public function getTrackLinkByAlbum()
	{

		$core = new Core($list_url);
		$list = $core->filter('.profile_box_body a')->dom()->each(function(Crawler $node, $i){
			if(!$node->filter('.soon')->count()) {
				return $node->attr('href');
			}
		});
		dd($list);
		return $list;
	}

}