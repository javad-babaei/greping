<?php 
namespace App\Grep\Entity;

use SnapyCloud\PhpApi\Client\SnapyClient;

class Api
{

	protected $client;

	public function __construct()
	{
		$this->client = new SnapyClient("https://service.app.beatsmusic.ir");
		$this->client->setApiKey('96545aa95a374e7fe0d8fec6fa442214');
		$this->client->setSecretKey('b138e118fa9035dfc13a79b1b9c01e72');
	}

	public function client()
	{
		return $this->client;
	}


}