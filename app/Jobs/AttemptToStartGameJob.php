<?php

namespace App\Jobs;

use App\Events\ChangedLocationEvent;
use App\Events\GameDataBroadcastEvent;
use App\Game;
use App\Services\EventStreamService;
use App\Services\EventStreamServiceContract;
use App\Services\GameService;
use App\Services\GameServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;

class AttemptToStartGameJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly Game $game)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $players = $this->game->players;

        // When all characters have chose a weapon, the game may start.
        foreach ($players as $player) {
            if (! key_exists('equipment', $player) || empty($player['equipment'])) {
                return;
            }
        }

        $event = new GameDataBroadcastEvent($this->game, "\n Game has started. No new players can join");

        event($event);

        $this->game->closed = true;

        /** @var GameService $gameService */
        $gameService = App::make(GameServiceContract::class);

        $gameService->saveGame($this->game);

        /** @var EventStreamServiceContract $eventStream */
        $eventStream = App::make(EventStreamServiceContract::class);

        $startingLocation = $this->game->locations[0];

        foreach ($players as $id => $player) {
            $eventStream->addEvent($id, $this->game->region['description'] . "\n Game has started. No new players can join \n");
            $this->game->players[$id]['location'] = $startingLocation['id'];
            $event = new ChangedLocationEvent($startingLocation['id'], $id, null);
            event($event);
        }

        $generateLocationJob = new GenerateLocationJob($this->game, $startingLocation);

        dispatch($generateLocationJob);
    }
}
