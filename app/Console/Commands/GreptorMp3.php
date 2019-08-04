<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Greptor as model;

class GreptorMp3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:mp3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'grep one track';

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
        $request = Model::where('done', 0)->where('type', 'track')->first();

        if(!$request) {
            return false;
        }

        try {

            $grep = new \App\Grep\TrackAhaang($request->link);
            $grep->proccess();
            $request->done = true;
            $request->save();

        } catch (Exception $e) {

            $message = "Error Processing Request";
            $request->description = $message . $e->getMessage();
            $request->done = true;
            $request->save();

            throw new Exception($message, 1);
        }
        
    }
}
