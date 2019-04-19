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
        $base_url = "https://ahaang.com/acat/pop/page/";

        for ($i=1; $i <= 29; $i++) { 
            $grep = new App\Grep\Artist($base_url + 1);
            $grep->add();
        }
        
    }
}
