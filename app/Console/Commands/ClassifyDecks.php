<?php

namespace DataReaper\Console\Commands;

use Illuminate\Console\Command;

class ClassifyDecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'decks:classify {from?} {to?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Classify Decks';

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
        dispatch(new \DataReaper\Jobs\ClassifyDecks($this->argument('from'), $this->argument('to')));
        $this->line(memory_get_peak_usage(true)/1024/1024);
    }
}
