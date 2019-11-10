<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UploadTrackMoveTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:uploadtracktotrack';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'upload track to track';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $list = \DB::table('upload_track')->where('deleted', 0)->get();

        $path = "/home/app/music/repository/";
        $upload_path = "/home/app/music/api/data/upload/";

        $data = [];

        foreach ($list as $track) {
            $mp3_id = $track['fileId'];
            $cover_id = $track['coverId'];

            $mp3_file = $upload_path . $mp3_id;
            $cover_file = $upload_path . $cover_id;

            $filesource = $path . "track/" . $mp3_id . ".mp3";
            $coversource = $path . "cover/" . $cover_id . ".jpg";

            file_put_contents($filesource, $mp3_file);
            file_put_contents($coversource, $cover_id);

            $ffprobe = \FFMpeg\FFProbe::create();
            @$duration =  @$ffprobe->format($filesource)->get('duration');
            $duration ?? 0;

            $data['stream'] = "/track/stream/" . $mp3_id . ".aac";
            $data['trackUrl'] = "/track/" . $mp3_id . ".mp3";
            $data['img'] = "/cover/" . $cover_id . ".jpg";
            $data['segmentlist'] = "/track/segment/" . $mp3_id . "/track.m3u8";
            $data['name'] = $track['name'];
            $data['artist'] = $track['artistName'];
            $data['description'] = $track['description'];
            $data['lyric'] = $track['description'];
            $data['genres'] = $track['genres'];
            $data['publishedDate'] = $track['publishedDate'];
            $data['duration'] = $duration;

            $api = new App\Grep\Entity\Api;
            $newTrack = $api->request('POST', 'track', $data);

            dd($newTrack);
        }
    }
}
