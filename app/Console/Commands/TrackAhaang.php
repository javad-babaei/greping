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
        $url = "https://ahaang.com/%D8%A2%D9%87%D9%86%DA%AF-%D9%86%D8%A7%D8%B5%D8%B1-%D8%B2%DB%8C%D9%86%D8%B9%D9%84%DB%8C-%D9%81%D9%82%D8%B7-%D8%A8%D8%A7%D8%B4/";
        $grep = new \App\Grep\TrackAhaang($url);
        $grep->proccess();
    }
}
