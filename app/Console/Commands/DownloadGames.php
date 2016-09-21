<?php

namespace DataReaper\Console\Commands;

use Illuminate\Console\Command;

class DownloadGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:download {trackobot_account?} {--forks=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download Games';

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
        dispatch(new \DataReaper\Jobs\DownloadGames($this->option('forks')));
        $this->line(memory_get_peak_usage(true)/1024/1024);
    }
}
