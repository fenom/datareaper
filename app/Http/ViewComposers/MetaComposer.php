<?php

namespace DataReaper\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Http\Request;
//use DataReaper\Repositories\UserRepository;

class MetaComposer
{
    /**
     * The request implementation.
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new profile composer.
     *
     * @param  Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        // Dependencies automatically resolved by service container...
        $this->request = $request;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $days = $this->request->days ?: 1;
        $datareaper = \DB::getMongoDB();
        $match = ['mode' => 'ranked', 'rank' => null, 'legend' => ['$ne' => null], 'added' => ['$gte' => new \MongoDate(strtotime("-$days days"))]];
        $group = ['_id' => '$opponent', 'count' => ['$sum' => 1]];
        $sort = ['_id' => 1];
        $count = $datareaper->games->count($match);
        $classes = ['Druid' => 'Druid - 0%', 'Hunter' => 'Hunter - 0%', 'Mage' => 'Mage - 0%', 'Paladin' => 'Paladin - 0%', 'Priest' => 'Priest - 0%', 'Rogue' => 'Rogue - 0%', 'Shaman' => 'Shaman - 0%', 'Warlock' => 'Warlock - 0%', 'Warrior' => 'Warrior - 0%'];
        foreach ($datareaper->games->aggregate(['$match' => $match], ['$group' => $group], ['$sort' => $sort])['result'] as $class)
            $classes[$class['_id']] = $class['_id'] . ' - ' . round($class['count'] / $count * 100) . '%';
        $view->with('days', $days);
        $view->with('classes', collect($classes));
    }
}
