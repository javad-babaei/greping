<?php 
namespace App\Grep\Entity;

class Track extends Api
{
	public function create($data)
	{	
		return $this->client()->request('POST', 'Track', [
			'name' => $data['name'],
			'assignedUserName' => 'root',
			'assignedUserId' => 1
		]);
	}

	public function grep($data)
	{
		// create entity
		$track = $this->create($data);
		$id = $track['id'];
		// downloaded
		$base_url = "stream.app.beatsmusic.ir";
		$stream = $base_url . "\/track\/stream\/" . $id;
		$trackUrl = $base_url . "\/track\/" . $id;
		$img = $base_url . "\/cover\/" . $id;

		$this->downloadFile($data['downloadUrl'], $id);
		$this->downloadFile($data['img'], $id, 'cover');
		$this->hls($id);	

		// releated
		// upload data
	}

	public function relatedToArtist($Artist, $id)
	{
		# code...
	}

	public function downloadFile($link, $id, $type = null)
	{
		set_time_limit(0);
		//This is the file where we save the    information
		$filename = '/home/apps/music/repository/track/' . $id . '.mp3';
		if($type) {
			$filename = '/home/apps/music/repository/cover/' . $id . '.jpg';
		}
		
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

	public function hls($id)
	{
		$filesource = '/home/apps/music/repository/track/' . $id . '.mp3';

		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);

		$format = new \FFMpeg\Format\Audio\Flac();
		$format->on('progress', function ($audio, $format, $percentage) {
		    echo "$percentage % transcoded";
		});

		$format->setAudioChannels(2)->setAudioKiloBitrate(320);
		
		$filename = '/home/apps/music/repository/track/stream/' . $id . '.flac';
		$audio->save($format, $filename);
	}

}