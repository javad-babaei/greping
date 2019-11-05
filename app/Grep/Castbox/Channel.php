<?php 
namespace App\Grep\Castbox;

use App\Grep\Entity\Api;

class Channel extends Api
{
	public function create($data)
	{
		return $this->client()->request('POST', 'Channel', [
			'name' => $data['name'],
			'assignedUserName' => 'root',
			'assignedUserId' => 1
		]);
	}

	public function checkIfNotExists($data)
	{
		return $this->client()->request('GET', 'Channel', [
			'where[0][type]' => 'contains',
			'where[0][attribute]' => 'name',
			'where[0][value]' => $data['name']
		]);
	}

	public function proccess($data)
	{
		// check not exitst
		$exists = $this->checkIfNotExists($data);
		if($exists['total']) {
			return $exists['list'][0];
		}
		// create
		$channel = $this->create($data);
		$id = $channel['id'];
		// download
		$base_url = "/podcast/cover/$id.jpg";
		$this->downloadFile($data['feedcover'], $id);

		$data['feedcover'] = $base_url;
		$this->updateChannel($data, $id);
		return $channel;
	}

	public function updateChannel($data, $id)
	{
		return $this->client()->request('PUT', 'Channel/' . $id, $data);
	}

	public function downloadFile($link, $id)
	{
		set_time_limit(0);
		//This is the file where we save the    information
		$filename = '/home/app/repository/podcast/cover/' . $id . '.jpg';
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
}