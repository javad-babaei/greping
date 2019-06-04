<?php
namespace App\Grep\Entity;

class Episode extends Api
{
	public function create($data)
	{
		return $this->client()->request('POST', 'Episode', [
			'name' => $data['name'],
			'assignedUserName' => 'root',
			'assignedUserId' => 1
		]);
	}

	public function trackExists($data)
	{
		return $this->client()->request('GET', 'Episode', [
			'where[0][type]' => 'startsWith',
			'where[0][attribute]' => 'name',
			'where[0][value]' => $data['name']
		])['total'];
	}

	public function grep($data)
	{
		$exists = $this->trackExists($data);
		if($exists) {
			return true;
		}
        dump("file is new " .  $exists);
		// create entity
		$track = $this->create($data);
		$id = $track['id'];

		// downloaded
		$this->downloadFile($data['stream'], $id);
		$this->downloadFile($data['trackcover'], $id, 'cover');
		// upload data
		$base_url = "https://podcast.app.beatsmusic.ir";
		$data['hls'] = $base_url . "/podcast/segment/$id/episode.m3u8";
		$data['stream'] = $base_url . "/podcast/stream/" . $id . '.aac';
		$data['trackUrl'] = $base_url . "/podcast/" . $id . '.mp3';
		$data['trackcover'] = $base_url . "/podcast/" . $id . '.jpg';
		$duration = $this->FFMpeg($id, $data);
		$data['duration'] = $duration;
		// update track
		$this->updateEpisode($id, $data);
		// related with artist
		$this->relatedToChannel($data['channel'], $id);
	}

	public function updateEpisode($id, $data)
	{
		return $this->client()->request('PUT', 'Track/' . $id, $data);
	}

	public function relatedToChannel($name, $id)
	{
		$name = trim($name, " ");
		$name = trim($name);
		$chaneel = $this->client()->request('GET', 'Channel', [
			'where[0][type]' => 'equals',
			'where[0][attribute]' => 'name',
			'where[0][value]' => $name
		]);

		if($chaneel['total']) {
			$this->client()->request('POST', "track/$id/channels", [
				'ids' => [
					$chaneel['list'][0]['id']
				]
			]);
		}

	}

	public function downloadFile($link, $id, $type = null)
	{
		//This is the file where we save the    information
		$filename = '/home/apps/music/repository/track/' . $id . '.mp3';
		if($type) {
			$filename = '/home/apps/music/repository/cover/' . $id . '.jpg';
			// disabled this part download some track incorrct
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
			return true;
		}

		file_put_contents($filename , fopen(str_replace(" ","%20",$link), 'r'));
		return true;
	}

	public function FFMpeg($id, $data = null)
	{
		$filesource = '/home/apps/music/repository/episode/' . $id . '.mp3';


		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);

		$format = new \FFMpeg\Format\Audio\Aac();
		$format->on('progress', function ($audio, $format, $percentage) {
		    echo "$percentage % transcoded" . PHP_EOL;
		});

		// need aac format ziped
		// $format->setAudioChannels(2)->setAudioKiloBitrate(256);

		$filename = '/home/apps/music/repository/episode/stream/' . $id . '.aac';
		$audio->save($format, $filename);



		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);
		$audio->filters()->addMetadata([
			"track" => $data['name'],
			"title" => $data['name'],
			'artist' => $data['authore'],
			'genre' => 'podcast',
			"comment" => "Amazing iranian songs https://beatsmusic.ir",
			"description" => "api services from https://beatsmusic.ir",
			"lyrics" => $data['lyric']
		]);

		$ffprobe = \FFMpeg\FFProbe::create();
		return $ffprobe->format($filename)->get('duration');
	}

}
