<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TrackConvertToAAC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'convert:track';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'find tracks, was\'nt aac file, and converted to aac';

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
        $tracks = App\Tracks::where('deleted', 0)->all();

        foreach ($tracks as $value) {
            $r = $this->getExists($value);
            if($r) {
                exec(
                    "ffmpeg -i /usr/share/nginx/music/repository/track/" . $value->id . ".mp3" .
                    "-vn -ac 2 -acodec aac /usr/share/nginx/music/repository/track/" . $id . ".aac" 
                );
                echo $value->id . " done.../r/n";
            } else {
                echo $value->id . " exists.../r/n";
            }
        }

    }

    public function getExists($file)
    {
        return file_exists("/usr/share/nginx/music/repository/track/stream/" . $id . ".aac" );
    }
}
