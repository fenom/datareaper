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
        $guzzlehttp = new \GuzzleHttp\Client(["base_uri" => "https://trackobot.com/profile/history.json"]);
        $datareaper = \DB::getMongoDB();
        $batch = new \MongoUpdateBatch($datareaper->games, ["ordered" => false]);
        foreach(TrackobotAccount::all() as $account)
        {
            echo"$account\n";
            $last = Game::whereUsername($account->username)->orderBy('id', 'desc')->first() ?: (object)["id" => 0];
            $history = (object)["meta" => (object)["next_page" => 1]];
            do
            {
                try
                {
                    $history=json_decode($guzzlehttp->get("",["query"=>['username' => $account->username, 'token' => $account->token, "page" => $history->meta->next_page], 'http_errors' => false])->getBody());
                }
                catch(\Exception $e)
                {
                    echo"$e\n";
                }
                if (isset($history->error))
                {
                    print_r($history);
                    $account->delete();
                    break;
                }
                print_r($history->meta);
                foreach($history->history as $game)
                {
                    if($game->id <= $last->id)
                        break;
                    $game->hero_deck = $game->opponent_deck = null;
                    isset($game->legend) and $game->rank = 0;
                    $game->player = $account->_id;
                    $game->username = $account->username;
                    $game->token = $account->token;
                    $game->added = new \MongoDate(strtotime($game->added));
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
