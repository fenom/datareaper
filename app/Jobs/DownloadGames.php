<?php

namespace DataReaper\Jobs;

use DataReaper\TrackobotAccount;
use DataReaper\Game;
use DataReaper\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DownloadGames extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $trackobot_accounts;
    protected $forks;
    protected $sleep;
    protected $verbose;

    /**
     * Create a new job instance.
     *
     * @param  TrackobotAccount  $trackobot_accounts
     * @return void
     */
    public function __construct($forks = 0, $sleep = 4, $verbose = false, $trackobot_accounts = null)
    {
        $this->trackobot_accounts = $trackobot_accounts;
        $this->forks = $forks;
        $this->sleep = $sleep;
        $this->verbose = $verbose;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $guzzlehttp = new \GuzzleHttp\Client(['base_uri' => 'https://trackobot.com/profile/history.json']);
        $datareaper = \DB::getMongoDB();
        foreach($datareaper->cards->find() as $card)
            $cards[$card['cardId']] = $card;
        $batch = new \MongoUpdateBatch($datareaper->games, ['ordered' => false]);
        $thread = 0;
        for ($i = 0; $i < $this->forks; ++$i)
            pcntl_fork() and $thread += pow(2, $i);
        $trackobot_accounts = $this->trackobot_accounts ? TrackobotAccount::whereIn('username', $this->trackobot_accounts)->get() : TrackobotAccount::all();
        foreach($trackobot_accounts->sortBy('updated_at') as $account)
        {
            if($thread != abs(substr($account->username, -4) % pow(2, $this->forks)))
                continue;
            $last = Game::whereUsername($account->username)->orderBy('id', 'desc')->first() ?: (object)["id" => 0];
            $history = (object)['meta' => (object)['next_page' => 1]];
            do
            {
                try
                {
                    $history = $guzzlehttp->get('', ['query'=>['username' => $account->username, 'token' => $account->token, 'page' => $history->meta->next_page], 'http_errors' => false])->getBody();
                }
                catch(\Exception $e)
                {
                    echo "$e\n";
                }
                if ($history == 'Unauthorized')
                {
                    //print_r($history);
                    echo "$account\n";
                    \Log::info($account);
                    $account->delete();
                    //$datareaper->trackobot_accounts->remove(['username' => $account->username]);
                    break;
                }
                $history = json_decode($history);
                if (!isset($history->history))
                    break;
                //print_r($history->meta);
                foreach ($history->history as $game)
                {
                    if ($game->id <= $last->id)
                        break;
                    isset($game->legend) and $game->rank = 0;
                    $game->added = new \MongoDate($added = strtotime($game->added));
                    $game->mode != 'arena' and $game->format = 'Standard';
                    $game->region = $account->region;
                    $game->player = $account->_id;
                    $game->username = $account->username;
                    $game->token = $account->token;
                    $game->original_hero_deck = $game->hero_deck;
                    $game->original_opponent_deck = $game->opponent_deck;
                    $game->time = idate('H', $added) * 60 * 60 + idate('i', $added) * 60 + idate('s', $added);
                    $game->hero_deck = $game->opponent_deck = null;
                    /*$game->hero_cards = $game->opponent_cards = [];
                    $previousturn = 1;
                    foreach ($game->card_history as $i => $card)
                    {
                        if ($card->turn < $previousturn)
                        {
                            for ($j = $i + 1; $j; $j--)
                                array_shift($game->card_history);
                            $game->hero_cards = $game->opponent_cards = [];
                            //$datareaper->games->update(['_id' => $game['_id']], ['$set' => ['card_history' => $game['card_history'], 'format' => $game['format'] = 'Standard']]);
                        }
                        //for(; $previousturn < $card->turn; $previousturn++)
                            //$herocards[] = $opponentcards[] = 'End Turn';
                        $card->player == 'me' ? $game->hero_cards[] = $card->card : $game->opponent_cards[] = $card->card;
                        if(isset($cards[$card->card->name]['cardSet']) && $sets[$cards[$card->card->name]['cardSet']]['format'] == 'Wild')
                            $game->format = 'Wild';
                            //$datareaper->games->update(['_id' => $game['_id']], ['$set' => ['format' => $game['format'] = 'Wild']]);
                    }*/
                    
                    $game->hero_cards = array_filter($game->card_history, function ($card) use ($cards) {
                        return isset($cards[$card->card->id]) && $card->player == 'me';
                    });
                    $game->opponent_cards = array_filter($game->card_history, function ($card) use ($cards) {
                        return isset($cards[$card->card->id]) && $card->player == 'opponent';
                    });
                    $cmp = function ($a, $b) {
                        if($a->card->mana < $b->card->mana)
                            return -1;
                        if($a->card->mana > $b->card->mana)
                            return 1;
                        if($a->card->name < $b->card->name)
                            return -1;
                        if($a->card->name > $b->card->name)
                            return 1;
                        return 0;
                    };
                    usort($game->hero_cards, $cmp);
                    usort($game->opponent_cards, $cmp);
                    $cardname = function ($card) {
                        return $card->card->name;
                    };
                    $game->hero_cards = array_map($cardname, $game->hero_cards);
                    $game->opponent_cards = array_map($cardname, $game->opponent_cards);
                    $batch->add(['q' => ['_id' => $game->id], 'u' => ['$set' => $game], 'upsert' => true]);
                }
            } while($history->meta->next_page && end($history->history)->id > $last->id);
            try
            {
                $batch->execute();
            }
            catch(\Exception $e)
            {
                
            }
            $account->updated_at = new \MongoDate;
            $history == 'Unauthorized' or $account->save();
            $this->verbose and print("$thread $account\n");
            sleep($this->sleep);
        }
    }
}
