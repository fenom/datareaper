<?php

namespace DataReaper\Repositories;

use DataReaper\User;

class MetaRepository
{
    /**
     * Get meta.
     *
     * @param  array  $added
     * @param  array  $rank
     * @param  array  $legend
     * @return array
     */
    public function get(array $added = [0], array $rank = [0], array $legend = [1], array $username = [])
    {
        $datareaper = \DB::getMongoDB();
        $added or $added = [0];
        $rank or $rank = [0];
        sort($added);
        sort($rank);
        $match = ['mode' => 'ranked',
                  'format' => 'Standard',
                  'rank' => ['$gte' => (int) $rank[0], '$lte' => isset($rank[1]) ? (int) $rank[1] : 25],
                  //'legend' => !$rank[0] ? ['$gte' => $legend[0]] + (isset($legend[1]) ? ['$lte' => $legend[1]] : []) : null,
                  'added' => ['$gte' => new \MongoDate($added[0] ? max(strtotime($added[0]), strtotime('2016-05-01 PDT')) : time() - 60 * 60 * 24 - 20 * 60), '$lte' => new \MongoDate(isset($added[1]) ? strtotime($added[1]) : time() - 20 * 60)]
                 ];
        $group = ['_id' => ['class' => '$opponent', 'archetype' => '$opponent_deck'], 'count' => ['$sum' => 1]];
        $sort = ['_id.class' => 1, 'count' => -1];
        $count = $datareaper->games->count($match);
        $decks = [];
        foreach ($datareaper->games->aggregate(['$match' => $match], ['$group' => $group], ['$sort' => $sort])['result'] as $deck)
            $decks[$deck['_id']['class']][strstr($deck['_id']['archetype'], ' ' . $deck['_id']['class'], true) ?: 'Other'] = $deck['count'] / $count;
        return ['decks' => $decks, 'count' => $count, 'query' => $match];
    }
}