<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PerTrack extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:nex1music {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'greping from nex1music';

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
        $grep = new \App\Grep\TrackNextOne($url);
        $grep->proccess();

    }
}
