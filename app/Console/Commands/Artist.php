<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Artist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:artist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'greping artist';

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
        $artist = new \App\Grep\Artist($url);
        $artist->add();
    }
}
