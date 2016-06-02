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
        $guzzlehttp = new \GuzzleHttp\Client(["base_uri" => "https://trackobot.com/profile/history.json"]);
        $datareaper = \DB::getMongoDB();
        $batch = new \MongoUpdateBatch($datareaper->games, ["ordered" => false]);
        $cards = new \MongoUpdateBatch($datareaper->cards, ["ordered" => false]);
        $decks = array_fill_keys(['Druid', 'Hunter', 'Mage', 'Priest', 'Paladin', 'Rogue', 'Shaman', 'Warlock', 'Warrior'], []);
        $deckscsv = fopen('decks.csv', 'w');
        fputcsv($deckscsv, ['name', 'class', 'archetype', 'card']);
        $training = fopen('training.csv', 'w');
        foreach (Deck::all()->sortBy('class') as $deck)
        {
            $decks[$deck->class][$deck->id] = $deck->cards;
            foreach($deck->cards as $card)
                fputcsv($deckscsv, [$deck->_id, $deck->class, $deck->archetype, $card]);
        }
        foreach ($datareaper->games->find(['mode' => 'ranked', 'added' => ['$gte' => new \MongoDate($this->from ? strtotime($this->from) : 0), '$lte' => new \MongoDate($this->to ? strtotime($this->to) : time())]])->sort(['_id' => -1]) as $game)
        {
            $herocards = $opponentcards = [];
            foreach ($game['card_history'] as $card)
            {
                $card['player'] == "me" ? $herocards[] = $card['card']['name'] : $opponentcards[] = $card['card']['name'];
                $cards->add(['q' => ['_id' => $card['card']['id']], 'u' => ['$set' => $card['card']], 'upsert' => true]);
            }
            $herodeckscore = array_map(function ($value) use ($herocards)
            {
                return count(array_intersect($value, $herocards))/count($value);
            }, $decks[$game['hero']]);
            arsort($herodeckscore);
            $opponentdeckscore = array_map(function ($value) use ($opponentcards)
            {
                return count(array_intersect($value, $opponentcards))/count($value);
            }, $decks[$game['opponent']]);
            arsort($opponentdeckscore);
            $batch->add(['q' => ['_id' => $game['_id']], 'u' => ['$set' => ['hero_deck' => $herodeck = reset($herodeckscore) ? key($herodeckscore) : null, 'opponent_deck' => $opponentdeck = reset($opponentdeckscore) ? key($opponentdeckscore) : null]]]);
            fputcsv($training, array_merge([$opponentdeck, $game['id'], 'opponent', $game['player'], (int)$game['rank'], $game['opponent']], $opponentcards));
        }
        $batch->execute();
        $cards->execute();
    }
}
