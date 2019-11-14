<?php 
namespace App\Grep\Entity;

use SnapyCloud\PhpApi\Client\SnapyClient;

class Api
{

	protected $client;

	public function __construct()
	{
		$this->client = new SnapyClient("https://api.ahangap.com");
		$this->client->setApiKey('872d14e8b3fdd69b8b31fa2866af18a2');
		$this->client->setSecretKey('1908a40926c27e383dd17b669aa0050c');
	}

	public function client()
	{
		return $this->client;
	}
}
