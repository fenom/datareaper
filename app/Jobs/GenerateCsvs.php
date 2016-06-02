<?php

namespace DataReaper\Jobs;

use DataReaper\Deck;
use DataReaper\TrackobotAccount;
use DataReaper\Game;
use DataReaper\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateCsvs extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $from;
    protected $to;

    /**
     * Create a new job instance.
     *
     * @param  $from
     * @param  $to
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
        $gamescsv = fopen('games.csv', 'w');
        $cardscsv = fopen('cards.csv', 'w');
        $newestcsv = fopen('newest.csv', 'w');
        fputcsv($gamescsv, $gamesheader = ['player', 'id', 'mode', 'hero', 'hero_deck', 'opponent', 'opponent_deck', 'coin', 'result', 'duration', 'rank', 'legend', 'added']);
        fputcsv($cardscsv, array_merge($gamesheader, ['turn' ,'hero_card', 'opponent_card']));
        fputcsv($newestcsv, ['player', 'username', 'id', 'mode', 'hero', 'hero_deck', 'opponent', 'opponent_deck', 'coin', 'result', 'duration', 'added']);
        foreach ($datareaper->games->find(['mode' => 'ranked', 'added' => ['$gte' => new \MongoDate($this->from ? strtotime($this->from) : 0), '$lte' => new \MongoDate($this->to ? strtotime($this->to) : time())]])->sort(['id' => -1]) as $game)
        {
            fputcsv($gamescsv, $data = [$game['player'], $game['id'], $game['mode'], $game['hero'], $game['hero_deck'], $game['opponent'], $game['opponent_deck'], $game['coin'], $game['result'], $game['duration'], $game['rank'], $game['legend'], $game['added']->toDateTime()->format(DATE_W3C)]);
            $herocards = $opponentcards = [];
            foreach ($game['card_history'] as $card)
            {
                $card['player'] == "me" ? $herocards[] = $card['card']['name'] : $opponentcards[] = $card['card']['name'];
                fputcsv($cardscsv, array_merge($data, [$card['turn'], $card['player'] == 'me' ? $card['card']['name'] : null, $card['player'] == 'opponent' ? $card['card']['name'] : null]));
            }
        }
        foreach (TrackobotAccount::all() as $account)
        {
            if($newest = $datareaper->games->find(['player' => $account->id], ['card_history' => 0])->sort(['_id' => -1])->next())
                fputcsv($newestcsv, $data = [$newest['player'], $newest['username'], $newest['id'], $newest['mode'], $newest['hero'], $newest['hero_deck'], $newest['opponent'], $newest['opponent_deck'], $newest['coin'], $newest['result'], $newest['duration'], $newest['added']->toDateTime()->format(DATE_W3C)]);
        }
    }
}
