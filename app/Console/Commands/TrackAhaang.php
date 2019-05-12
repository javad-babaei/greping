<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TrackAhaang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:ahaang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'greping from ahaang';

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
        $url = "";
        $grep = new \App\Grep\TrackAhaang($url);
        $grep->proccess();
    }
}
