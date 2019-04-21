<?php 
namespace App\Grep\Entity;

class Artist extends Api
{
	public function create($data)
	{
		$data['assignedUserId'] = "5cba4dbe24ca9ace4";
		$data['assignedUserName'] = "grep";
		$data['createdById'] = "5cba4dbe24ca9ace4";
		$data['createdByName'] = "grep";
		$data['file'] = 'data:image/jpg;base64,' . base64_encode($data['file']);
		$attach = $this->attach($data);
		$data['imgId'] = $attach['id'];
		$data['imgName'] = $attach['name'];
		$this->client()->request('POST', 'Artist', $data);
	}

	public function attach($data)
	{
		$data['field'] = "img";
		$data['name'] = $data['filename'];
		$data['relatedType'] = "Artist";
		$data['role'] = "Attachment";
		return $this->client()->request('POST', 'Attachment', $data);
	}
}