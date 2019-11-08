<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grep\AutoUpdateNewTrack;

class AutoUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this command auto download new tracks';

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
        $url = "https://ahaang.com/category/single/";

        $bot = new AutoUpdateNewTrack;
        $bot->setUrl($url);
        $bot->getNewTrackLinks();
        $bot->download();
    }
}
