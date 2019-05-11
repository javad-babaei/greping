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
        if($url){
            $artist = new \App\Grep\Artist($url);
            $artist->add();
            return true;
        }

        die;


        $base_url = "https://ahaang.com/acat/pop/page/";

        for ($i=1; $i <= 29; $i++) { 
            $base_url .= 1 . "/";
            if($i == 1){
                $base_url = "https://ahaang.com/acat/pop/";
            }
            $grep = new \App\Grep\Artist("https://ahaang.com/acat/pop/");
            $grep->proccess();
            die;
        }
        
    }
}
