<?php

use Illuminate\Foundation\Inspiring;


Artisan::command('update:artist', function(){

	$artist = \App\Artist::where([
		'deleted' => 0,
	])->get();

	foreach ($artist as $item) {
		$cover = str_replace("https://stream.app.beatsmusic.ir", "", $item->cover);
		$item->cover = $cover;
		$item->save();
        echo "Done. " .$item->id;
	}
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');


Artisan::command('update:date', function(){
    $tracks = \DB::table('track')->where('published_date', null)->get();

    foreach($tracks as $track) {
		if($track->deleted && $track->published_date == null){

		$date = str_replace('تاریخ انتشار  ', "", $track->published);
		$date = explode(' ', $date);


		$farsi_chars = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی', 'بهمن', 'اسفند'];
		$latin_chars = [1,2,3,4,5,6,7,8,9, 10, 11, 12];
		$month = str_replace($farsi_chars, $latin_chars, trim($date[1]));

		$farsi_chars = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
		$latin_chars = ['0', '1','2','3','4','5','6','7','8','9'];
		$day = str_replace($farsi_chars, $latin_chars, $date[0]);
		$year = str_replace($farsi_chars, $latin_chars, $date[2]);

		$date = new \jDateTime(true, true, 'Asia/Tehran');
		$time = $date->mktime(0,0,0,$month, $day,$year);
		$date = gmdate("Y-m-d", $time);

		$track_update = \DB::table('track')->where('id', $track->id);
		$track_update->update(['published_date' => $date]);
		echo $track->id . " updated \t\r\n";
		}
    }

});


Artisan::command('update:link', function(){
	$tracks = \DB::table('track')->where('deleted', 0)->get();
	foreach($tracks as $track) {
		$stream = str_replace("https://stream.app.beatsmusic.ir", "", $track->stream);
		$segment = str_replace("https://stream.app.beatsmusic.ir", "", $track->segmentlist);
		$img = str_replace("https://stream.app.beatsmusic.ir", "", $track->img);
		$track_url = str_replace("https://stream.app.beatsmusic.ir", "", $track->track_url);
		$download_url = str_replace("http://dl.ahaang.com", "", $track->download_url);

		$track_update = \DB::table('track')->where('id', $track->id);
		$track_update->update(['stream' => $stream, 'segmentlist' => $segment, 'img' => $img, 'track_url' => $track_url, 'download_url' => $download_url]);
		echo $track->id . " updated \t\r\n";
	}
});







