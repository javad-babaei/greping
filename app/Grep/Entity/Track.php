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
		$this->downloadFile($data['downloadUrl'], $id);
		$this->downloadFile($data['img'], $id, 'cover');
		// upload data
		$base_url = "https://stream.app.beatsmusic.ir";
		$data['segmentlist'] = $base_url . "/track/hls/" . $id . '.m3u8';
		$data['stream'] = $base_url . "/track/stream/" . $id . '.aac';
		$data['trackUrl'] = $base_url . "/track/stream/" . $id . '.mp3';
		$data['img'] = $base_url . "/cover/" . $id . '.jpg';
		$duration = $this->FFMpeg($id, $data);
		$data['duration'] = $duration;
		// update track
		$this->updateTrack($id, $data);
		// related with artist
		$this->relatedToArtist($data['artist'], $id);
	}

	public function updateTrack($id, $data)
	{
		return $this->client()->request('PUT', 'Track/' . $id, $data);
	}

	public function relatedToArtist($name, $id)
	{
		$artist = $this->client()->request('GET', 'Artist', [
			'where[0][type]' => 'equals',
			'where[0][attribute]' => 'name',
			// 'where[0][value]' => $name
			'where[0][value]' => 'عماد'
		]);

		if($artist['total']) {
			$this->client()->request('POST', "track/$id/artists", [
				'ids' => [
					$artist['list'][0]['id']
				]
			]);
		}

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

	public function FFMpeg($id, $data = null)
	{
		$filesource = '/home/apps/music/repository/track/' . $id . '.mp3';

		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);

		$format = new \FFMpeg\Format\Audio\Aac();
		$format->on('progress', function ($audio, $format, $percentage) {
		    echo "$percentage % transcoded";
		});

		$format->setAudioChannels(2)->setAudioKiloBitrate(256);
		
		$filename = '/home/apps/music/repository/track/stream/' . $id . '.aac';
		// $audio->filters()->addMetadata([
		// 	"title" => $data['name'],
		// 	"Artist Name" => $data['artist'],
		// 	'comment' => 'https://beatsmusic.ir'
		// ]);
		$audio->save($format, $filename);

		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filename);
		$audio->filters()->addMetadata([
			"title" => $data['name'],
			"comment" => "https://beatsmusic.ir"
		]);

		$ffprobe = \FFMpeg\FFProbe::create();
		return $ffprobe->format($filename)->get('duration'); 
	}

}