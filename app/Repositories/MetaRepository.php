<?php

namespace DataReaper\Repositories;

use DataReaper\User;

class MetaRepository
{
    /**
     * The query.
     *
     * @var array
     */
    protected $query = ['added' => [0], 'time' => ['00:00:00', '23:59:59'], 'rank' => [0], 'region' => ['Americas', 'Europe'], 'format' => ['Standard'], 'mode' => ['ranked']];

    /**
     * Get meta.
     *
     * @param  array  $query
     * @return array
     */
    public function get(array $query)
    {
        $datareaper = \DB::getMongoDB();
        $query = array_filter($query) + $this->query;
        $query = array_map(function($value) {return (array) $value;}, $query);
        $query['time'][0] = strtotime($query['time'][0]);
        $query['time'][0] = idate('H', $query['time'][0]) * 60 * 60 + idate('i', $query['time'][0]) * 60 + idate('s', $query['time'][0]);
        $query['time'][1] = strtotime($query['time'][1]);
        $query['time'][1] = idate('H', $query['time'][1]) * 60 * 60 + idate('i', $query['time'][1]) * 60 + idate('s', $query['time'][1]);
        sort($query['added']);
        sort($query['rank']);
        $match = ['mode' => ['$in' => $query['mode']],
                  'region' => ['$in' => $query['region']],
                  'format' => ['$in' => $query['format']],
                  'rank' => ['$gte' => $query['rank'][0] = (int) $query['rank'][0], '$lte' => $query['rank'][1] = isset($query['rank'][1]) ? (int) $query['rank'][1] : 25],
                  //'legend' => !$query['rank'][0] ? ['$gte' => $legend[0]] + (isset($legend[1]) ? ['$lte' => $legend[1]] : []) : null,
                  'added' => ['$gte' => new \MongoDate($query['added'][0] = $query['added'][0] ? max(strtotime($query['added'][0]), strtotime('2016-05-01 PDT')) : time() - 60 * 60 * 24 - 30 * 60),
                              '$lte' => new \MongoDate($query['added'][1] = isset($query['added'][1]) ? strtotime($query['added'][1]) : time() - 30 * 60)],
                  ($query['time'][0] > $query['time'][1] ? '$or' : '$and') => [['time' => ['$gte' => $query['time'][0]]], ['time' => ['$lte' => $query['time'][1]]]]
                 ];
        $group = ['_id' => ['class' => '$opponent', 'archetype' => '$opponent_deck'], 'count' => ['$sum' => 1]];
        $sort = ['_id.class' => 1, 'count' => -1];
        $count = $datareaper->games->count($match);
        $decks = [];
        foreach ($datareaper->games->aggregate(['$match' => $match], ['$group' => $group], ['$sort' => $sort])['result'] as $deck)
            $decks[$deck['_id']['class']][strstr($deck['_id']['archetype'], ' ' . $deck['_id']['class'], true) ?: 'Other'] = $deck['count'] / $count;
        return ['decks' => $decks, 'count' => $count, 'query' => $query];
    }
}