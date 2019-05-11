<?php 
namespace App\Grep\Entity;

class Artist extends Api
{
	public function create($data)
	{
		return $this->client()->request('POST', 'Artist', [
			'name' => $data['name']
		]);
	}

	public function grep($data)
	{
		// create entity
		$track = $this->create($data);
		// downloaded
		$base_url = "stream.app.beatsmusic.ir";
		$stream = $base_url . "\/track\/stream\/" . $track->id;
		$trackUrl = $base_url . "\/track\/" . $track->id;

		$this->downloadFile($data['downloadUrl'], $id);
		$this->hls($id);		

		// releated
		// upload data
	}

	public function relatedToArtist($Artist, $id)
	{
		# code...
	}

	public function downloadFile($link, $id)
	{
		set_time_limit(0);
		//This is the file where we save the    information
		$filename = '/home/apps/music/repository/track/' . $id . '.mp3';
		$fp = fopen ( $filename , 'w+');
		//Here is the file we are downloading, replace spaces with %20
		$ch = curl_init(str_replace(" ","%20",$data));
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		// write curl response to file
		curl_setopt($ch, CURLOPT_FILE, $fp); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		// get curl response
		curl_exec($ch); 
		curl_close($ch);
		fclose($fp);
		return $file;
	}

	public function hls($id)
	{
		$filesource = '/home/apps/music/repository/track/' . $id . '.mp3';

		$ffmpeg = FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);

		$format = new FFMpeg\Format\Audio\Flac();
		$format->on('progress', function ($audio, $format, $percentage) {
		    echo "$percentage % transcoded";
		});

		$format->setAudioChannels(2)->setAudioKiloBitrate(320);
		
		$filename = '/home/apps/music/repository/track/stream/' . $id . '.flac';
		$audio->save($format, $filename);
	}

}