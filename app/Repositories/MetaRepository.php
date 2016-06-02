<?php

namespace DataReaper\Repositories;

use DataReaper\User;

class MetaRepository
{
    /**
     * Get meta.
     *
     * @param  $added[0]
     * @param  $added[1]
     * @param  $rank
     * @param  $legend
     * @return Collection
     */
    public function get($added = [0], $rank = [1, 25], $legend = [1], $username = [])
    {
        $datareaper = \DB::getMongoDB();
        $match = ['mode' => 'ranked', 'added' => ['$gte' => new \MongoDate($added[0] ? strtotime($added[0]) : 0), '$lte' => new \MongoDate($added[1] ? strtotime($added[1]) : time())]];
        $group = ['_id' => ['class' => '$opponent', 'archetype' => '$opponent_deck'], 'count' => ['$sum' => 1]];
        $sort = ['_id.class' => 1, '_id.archetype' => 1];
        $count = $datareaper->games->count($match);
        $decks = [];
        foreach ($datareaper->games->aggregate(['$match' => $match], ['$group' => $group], ['$sort' => $sort])['result'] as $deck)
            $decks[$deck['_id']['class']][$deck['_id']['archetype']] = $deck['count'] / $count;
        return collect($decks + ['count' => $count]);
    }
}