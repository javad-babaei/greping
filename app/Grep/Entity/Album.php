<?php 
namespace App\Grep\Entity;

use App\Traits\PublishDate;

class Album extends Api
{
	use PublishDate;
	public function create($data)
	{
		return $this->client()->request('POST', 'Album', [
			'name' => $data['name'],
			'assignedUserName' => 'root',
			'assignedUserId' => 1
		]);
	}

	public function grep($data)
	{
		// create
		$album = $this->create($data);
		$id = $album['id'];
		// download
		$base_url = "/cover/$id.jpg";
		$this->downloadFile($data['cover'], $id);
		unset($data['tracks']);


		$data['cover'] = $base_url;
		$data['published'] = $this->normalizeDate($data['publishedDate']);
		$data['likes'] = rand(200, 20000);
		$data['plays'] = rand(200, 20000);
		return $this->updateAlbum($data, $id);
	}

	public function updateAlbum($data, $id)
	{
		return $this->client()->request('PUT', 'Album/' . $id, $data);
	}

	public function downloadFile($link, $id)
	{
		set_time_limit(0);
		//This is the file where we save the    information
		$filename = '/usr/share/nginx/music/repository/cover/' . $id . '.jpg';
		// file_put_contents($filename , fopen($link, 'r'));
		
		$fp = fopen ( $filename , 'w+');
		//Here is the file we are downloading, replace spaces with %20
		$ch = curl_init(str_replace(" ","%20",$link));
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		// write curl response to file
		curl_setopt($ch, CURLOPT_FILE, $fp); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		// get curl response
		curl_exec($ch); 
		curl_close($ch);
		fclose($fp);
	}

	public function attachTrack($album_id, $track_id)
	{
		
	}
}