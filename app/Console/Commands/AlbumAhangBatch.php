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
        $album = new Album($url);
        $album->getAlbumByLink();
        $album->getTrackLinks();
        $album->getTrackNameAndAttachToAlbum();
    }
}
