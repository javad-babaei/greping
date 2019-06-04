<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Grep\Castbox\Crawller;

class Castbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grep:castbox {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $crawller = new Crawller($url);
        $crawller->add();
    }
}
