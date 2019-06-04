<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grep\Batch\Album;
use App\Grep\Batch\Ahaang;


class AlbumAhangBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:album {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        // get artist
        $url = $this->argument('url');
        // get album link
        // create album

        // each track name
        // link track id
        $album = new Album($url);
        
        $album->add();
        dd($album);

        $track_links = $artist->getTrackLink();
        // $artist->getAlbum();

        foreach ($track_links as $track_link) {
            dump($track_link);

            if($track_link){
                (
                    new Ahaang($track_link)
                )->proccess();
            }
        }




        // get track


    }
}
