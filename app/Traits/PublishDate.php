<?php 

namespace App\Traits;

trait PublishDate 
{
	public function normalizeDate($bad_date)
	{
		$better_date = str_replace('تاریخ انتشار  ', "", $bad_date);
		$split_date = explode(' ', $better_date);


		$farsi_chars = [
			'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی', 'بهمن', 'اسفند'
		];
		$latin_chars = [1,2,3,4,5,6,7,8,9, 10, 11, 12];


		$month = str_replace(
			$farsi_chars, $latin_chars, trim($split_date[1])
		);


		$farsi_chars = [
			'۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'
		];
		$latin_chars = [
			'0', '1','2','3','4','5','6','7','8','9'
		];
		$day = str_replace(
			$farsi_chars, $latin_chars, $split_date[0]
		);
		$year = str_replace(
			$farsi_chars, $latin_chars, $split_date[2]
		);



		$date = new \jDateTime(true, true, 'Asia/Tehran');
		$time = $date->mktime(0,0,0,$month, $day,$year);
		
		return gmdate("Y-m-d", $time);		
	}

}