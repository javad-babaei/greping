<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grep\Batch\Artist;
use App\Grep\Batch\Ahaang;
use App\Greptor as model;

class GreptorArtistWithTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto-grep:artist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'grep one artist with track';

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
        $request = Model::where('done', 0)->where('type', 'artist')->first();

        if(!$request) {
            return false;
        }

        


        try {

            $artist = new Artist($request->link);
            $artist->add();
            $track_links = $artist->getTrackLink();

            foreach ($track_links as $track_link) {
                dump($track_link);

                if($track_link){
                    (
                        new Ahaang($track_link)
                    )->proccess();
                }
            }

            $request->done = true;
            $request->save();

        } catch (Exception $e) {

            $message = "Error Processing Request";
            $request->description = $message . $e->getMessage();
            $request->done = true;
            $request->save();

            throw new Exception($message, 1);
        }
        
    }
}
