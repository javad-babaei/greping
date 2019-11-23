<?php

namespace App\Console\Commands;

use Illuminate\Console\Command      as Command;
use App\Console\FilesToTracksHelper as Helper;

class FilesToTracks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upload {--p|path=null} {--no-overwrite} {--debug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert track row per mp3 file';

    /**
     * The Helper to uploading.
     *
     * @var Helper
     */
    protected $helper;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct( Helper $helper )
    {
        parent::__construct();
        $this->helper = $helper;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path         = $this->option( 'path'         );
        $debug        = $this->option( 'debug'        );
        $no_overwrite = $this->option( 'no-overwrite' );

        $this->helper->handler( $path, ! $no_overwrite, $debug );
    }
}