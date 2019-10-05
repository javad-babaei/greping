<?php
namespace App\Grep\Entity;

use App\Traits\PublishDate;

class Track extends Api
{
	use PublishDate;

	public function create($data)
	{
		return $this->client()->request('POST', 'Track', [
			'name' => $data['name'],
			'assignedUserName' => 'root',
			'assignedUserId' => 1
		]);
	}

	public function trackExists($data)
	{

		$res = $this->client()->request('GET', 'Track', [
			'where[0][type]' => 'contains',
			'where[0][attribute]' => 'translate',
			'where[0][value]' => $data['translate'],
			'where[1][type]' => 'contains',
			'where[1][attribute]' => 'artist',
			'where[1][value]' => $data['artist']
		])['total'];

        return $res;
	}
	public function findByName($name)
	{
		return $this->client()->request('GET', 'Track', [
			'where[0][type]' => 'contains',
			'where[0][attribute]' => 'name',
			'where[0][value]' => $name
		]);
	}

	public function grep($data)
	{
		$exists = $this->trackExists($data);
		if($exists) {
			return true;
		}
        dump($exists);
		// create entity
		$track = $this->create($data);
		$id = $track['id'];

		// downloaded
		$this->downloadFile($data['downloadUrl'], $id);
		$this->downloadFile($data['img'], $id, 'cover');
		// upload databeatsmusic
		// $base_url = "https://stream.app..ir";
		$base_url = "";
		$data['segmentlist'] = $base_url . "/track/segment/$id/track.m3u8";
		$data['stream'] = $base_url . "/track/stream/" . $id . '.aac';
		$data['trackUrl'] = $base_url . "/track/" . $id . '.mp3';
		$data['img'] = $base_url . "/cover/" . $id . '.jpg';
		$duration = $this->FFMpeg($id, $data);
		$data['duration'] = $duration;
		$data['publishedDate'] = $this->normalizeDate($data['published']);
		$data['likes'] = rand(200, 20000);
		$data['plays'] = rand(200, 20000);
		// update track
		$this->updateTrack($id, $data);
		// related with artist
		$this->relatedToArtist($data['artist'], $id);
	}

	public function updateTrack($id, $data)
	{
		if(isset($data['albumId'])) {
			$albumId = $data['albumId'];
			$this->client()->request('POST', "Album/$albumId/tracks", [
				'ids' => [
					$id
				]
			]);
		}
		return $this->client()->request('PUT', 'Track/' . $id, $data);
	}

	public function relatedToArtist($name, $id)
	{
		$name = trim($name, " ");
		$name = trim($name);
		$artist = $this->client()->request('GET', 'Artist', [
			'where[0][type]' => 'equals',
			'where[0][attribute]' => 'name',
			'where[0][value]' => $name
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
		//This is the file where we save the    information
		$filename = '/usr/share/nginx/music/repository/track/' . $id . '.mp3';
		if($type) {
			$filename = '/usr/share/nginx/music/repository/cover/' . $id . '.jpg';
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
		$filesource = '/usr/share/nginx/music/repository/track/' . $id . '.mp3';


		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);

		$format = new \FFMpeg\Format\Audio\Aac();
		$format->on('progress', function ($audio, $format, $percentage) {
		    echo "$percentage % transcoded" . PHP_EOL;
		});

		// need aac format ziped
		// $format->setAudioChannels(2)->setAudioKiloBitrate(256);

		$filename = '/usr/share/nginx/music/repository/track/stream/' . $id . '.aac';
		$audio->save($format, $filename);



		$ffmpeg = \FFMpeg\FFMpeg::create();
		$audio = $ffmpeg->open($filesource);
		$audio->filters()->addMetadata([
			"track" => $data['name'],
			"title" => $data['name'],
			'artist' => $data['artist'],
			'genre' => 'پاپ',
			"comment" => "Amazing iranian songs https://beatsmusic.ir",
			"description" => "api services from https://beatsmusic.ir",
			"lyrics" => $data['lyric']
		]);

		$ffprobe = \FFMpeg\FFProbe::create();
		return $ffprobe->format($filename)->get('duration');
	}

}
