<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use App\Grep\Batch\Artist;
use App\Grep\Batch\Ahaang;


class AllArtistBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-grep {url}';

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

        $artists = (new \App\Grep\Core($url))->filter('.artist_posts a.post_link')->dom()->each(function(Crawler $node, $i){
            if(!$node->filter('.soon')->count()) {
                return $node->attr('href');
            }
        });

        $artists = array_reverse($artists);

        $artistCount = 0;
        foreach ($artists as $url) {
            dump($url);
            $artist = new Artist($url);
            $artist->add();
            dump($artistCount++ . " artist success ... ");

            $track_links = $artist->getTrackLink();
            // $artist->getAlbum();


            $count = 0;

            foreach ($track_links as $track_link) {
                //dump($track_link);
                dump($artistCount - 1 . " -> " . $count++ . " start track ... ");

                if($track_link){
                    (
                        new Ahaang($track_link)
                    )->proccess();
                }
            }

        }


    }
}
