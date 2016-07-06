<?php

namespace DataReaper\Jobs;

use DataReaper\Deck;
use DataReaper\TrackobotAccount;
use DataReaper\Game;
use DataReaper\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class TagGames extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $trackobot_account;
    protected $from;
    protected $to;

    /**
     * Create a new job instance.
     *
     * @param  $from
     * @param  $to
     * @param  TrackobotAccount  $trackobot_account
     * @return void
     */
    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $datareaper = \DB::getMongoDB();
        $batch = new \MongoUpdateBatch($datareaper->games, ["ordered" => false]);
        $sets = iterator_to_array($datareaper->sets->find());
        $decks = array_fill_keys(['Druid', 'Hunter', 'Mage', 'Priest', 'Paladin', 'Rogue', 'Shaman', 'Warlock', 'Warrior'], []);
        $deckscsv = fopen('decks.csv', 'w');
        fputcsv($deckscsv, ['name', 'class', 'archetype', 'card']);
        $training = fopen('training.csv', 'w');
        foreach($datareaper->cards->find() as $card)
            $cards[$card['name']] = $card;
        foreach (Deck::all()->sortBy('class') as $deck)
        {
            $decks[$deck->class][$deck->id] = $deck->cards;
            foreach($deck->cards as $card)
                fputcsv($deckscsv, [$deck->_id, $deck->class, $deck->archetype, $card]);
        }
        foreach ($datareaper->games->find(['mode' => 'ranked', 'format' => 'Standard', 'added' => ['$gte' => new \MongoDate($this->from ? strtotime($this->from) : 0), '$lte' => new \MongoDate($this->to ? strtotime($this->to) : time())]])->sort(['_id' => -1])->timeout(120000) as $game)
        {
            $herocards = $opponentcards = [];
            $previousturn = 1;
            foreach ($game['card_history'] as $i => $card)
            {
                if ($card['turn'] < $previousturn)
                {
                    for ($j = $i + 1; $j; $j--)
                        array_shift($game['card_history']);
                    $herocards = $opponentcards = [];
                    $datareaper->games->update(['_id' => $game['_id']], ['$set' => ['card_history' => $game['card_history'], 'format' => $game['format'] = 'Standard']]);
                }
                for(; $previousturn < $card['turn']; $previousturn++)
                    $herocards[] = $opponentcards[] = "End Turn";
                $card['player'] == "me" ? $herocards[] = $card['card']['name'] : $opponentcards[] = $card['card']['name'];
                if(isset($cards[$card['card']['name']]['cardSet']) && $sets[$cards[$card['card']['name']]['cardSet']]['format'] == 'Wild')
                    $datareaper->games->update(['_id' => $game['_id']], ['$set' => ['format' => $game['format'] = 'Wild']]);
            }
            $deckscore = function ($cards, $decks)
            {
                return array_map(function ($deck) use ($cards)
                {
                    return count(array_intersect($cards, $deck)) / count($deck);
                }, $decks);
            };
            $herodeckscore = $deckscore($herocards, $decks[$game['hero']]);
            arsort($herodeckscore);
            $opponentdeckscore = $deckscore($opponentcards, $decks[$game['opponent']]);
            arsort($opponentdeckscore);
            $batch->add(['q' => ['_id' => $game['_id']], 'u' => ['$set' => ['hero_deck' => $herodeck = reset($herodeckscore) ? key($herodeckscore) : null, 'opponent_deck' => $opponentdeck = reset($opponentdeckscore) ? key($opponentdeckscore) : null]]]);
            if($game['format'] == 'Standard')
            {
                fputcsv($training, array_merge([$herodeck, $game['id'], 'hero', $game['player'], $game['rank'], $game['added']->toDateTime()->format(DATE_W3C), $game['hero'], '["' . implode('","', $herocards) . '"]']));
                fputcsv($training, array_merge([$opponentdeck, $game['id'], 'opponent', $game['player'], $game['rank'], $game['added']->toDateTime()->format(DATE_W3C), $game['opponent'], '["' . implode('","', $opponentcards) . '"]']));
            }
        }
        $batch->execute();
    }
}
