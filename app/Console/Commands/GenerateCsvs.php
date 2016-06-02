<?php

namespace DataReaper\Console\Commands;

use Illuminate\Console\Command;

class GenerateCsvs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csvs:generate {from?} {to?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Csvs';

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
        dispatch(new \DataReaper\Jobs\GenerateCsvs($this->argument('from'), $this->argument('to')));
    }
}
