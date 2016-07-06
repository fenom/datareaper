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

    protected $trackobot_account;

    /**
     * Create a new job instance.
     *
     * @param  TrackobotAccount  $trackobot_account
     * @return void
     */
    public function __construct()
    {
        //$this->trackobot_account = $trackobot_account;
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
        $batch = new \MongoUpdateBatch($datareaper->games, ['ordered' => false]);
        $pid = pcntl_fork();
        foreach(TrackobotAccount::all() as $account)
        {
            if((bool) $pid != substr($account->username,-4) % 2)
                continue;
            //echo"$pid $account\n";
            $last = Game::whereUsername($account->username)->orderBy('id', 'desc')->first() ?: (object)["id" => 0];
            $history = (object)['meta' => (object)['next_page' => 1]];
            do
            {
                try
                {
                    $history = json_decode($guzzlehttp->get('', ['query'=>['username' => $account->username, 'token' => $account->token, 'page' => $history->meta->next_page], 'http_errors' => false])->getBody());
                }
                catch(\Exception $e)
                {
                    echo"$e\n";
                }
                if (isset($history->error))
                {
                    print_r($history);
                    echo"$account\n";
                    $account->delete();
                    break;
                }
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
        }
    }
}
