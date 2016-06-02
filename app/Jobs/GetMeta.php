<?php

namespace DataReaper\Jobs;

use DataReaper\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GetMeta extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $from;
    protected $to;
    protected $rank;
    protected $legend;

    /**
     * Create a new job instance.
     *
     * @param  $from
     * @param  $to
     * @param  $rank
     * @param  $legend
     * @return void
     */
    public function __construct($from = null, $to = null, $rank = null, $legend = null)
    {
        $this->from = $from;
        $this->to = $to;
        $this->rank = $rank;
        $this->legend = $legend;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $datareaper = \DB::getMongoDB();
        $match = ['mode' => 'ranked', 'rank' => null, 'legend' => ['$ne' => null], 'added' => ['$gte' => new \MongoDate($this->from ? strtotime($this->from) : 0), '$lte' => new \MongoDate($this-> to ? strtotime($this->to) : time())]];
        $group = ['_id' => '$opponent_deck', 'class' => '$opponent', 'count' => ['$sum' => 1]];
        $sort = ['class' => 1];
        $count = $datareaper->games->count($match);
        foreach ($datareaper->games->aggregate(['$match' => $match], ['$group' => $group], ['$sort' => $sort])['result'] as $deck)
            $decks[$deck['class']][$deck['_id']] = round($class['count'] / $count * 100) . '%';
    }
}
