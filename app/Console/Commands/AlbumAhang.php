<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grep\Album\AlbumAhang as Crawller;
use App\Grep\Batch\Ahaang;


class AlbumAhang extends Command
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
    protected $description = '';

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

        $url = $this->argument('url');
        $album = new Crawller($url);
        $album->add();
        die;
        $album->getTrackLinks();
        $album->getTrackNameAndAttachToAlbum();
    }
}
