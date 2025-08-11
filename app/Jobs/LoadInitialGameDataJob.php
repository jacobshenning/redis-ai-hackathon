<?php

namespace App\Jobs;

use App\Events\GameDataBroadcastEvent;
use App\Events\StartingCharactersEvent;
use App\Events\StartingEquipmentEvent;
use App\Game;
use App\Services\GameServiceContract;
use App\Services\OpenAiServiceContract;
use App\Services\PromptServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoadInitialGameDataJob implements ShouldQueue
{
    use Queueable;

    public Game $game;
    public GameServiceContract $gameService;
    public OpenAiServiceContract $openAiService;
    public PromptServiceContract $promptService;

    /**
     * Create a new job instance.
     */
    public function __construct(Game $game)
    {
        $this->game = $game;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->gameService = App::make(GameServiceContract::class);
        $this->openAiService = App::make(OpenAiServiceContract::class);
        $this->promptService = App::make(PromptServiceContract::class);
        $this->loadGameData();

    }

    public function loadGameData()
    {
        $this->loadGameContent();

        $this->loadStartingCharacters();

        $this->loadStartingWeapons();
    }

    private function loadGameContent()
    {
        $startingLetter = Str::random(1);
        $syllables = mt_rand(2, 5);

        $prompt = $this->promptService->getPrompt('story.prompt', ['story.descriptor', 'story.landforms', 'story.biomes', 'story.climate'], ['StartingLetter' => $startingLetter, 'syllables' => $syllables]);

        $data = $this->openAiService->getJsonResponse($prompt, $this->promptService->getShape('story.shape'));

        $this->gameService->setField($this->game->code, 'region', $data);

        $broadcastEvent = new GameDataBroadCastEvent($this->game, $data['description']);

        broadcast($broadcastEvent);


    }

    private function loadStartingCharacters()
    {
        $data = $this->openAiService->getJsonResponse(
            $this->promptService->getPrompt('characters.player.prompt', ['characters.player.species', 'characters.player.class'], ['count' => 12]),
            $this->promptService->getShape('characters.player.shape')
        );

        $this->gameService->setField($this->game->code, 'gameStartOptions', $data['characters'], 'characters');

        $broadcastEvent = new StartingCharactersEvent($this->game,  $data['characters']);

        broadcast($broadcastEvent);
    }

    private function loadStartingWeapons()
    {
        $data = $this->openAiService->getJsonResponse(
            $this->promptService->getPrompt('weapons.prompt', ['weapons.type', 'weapons.power'], ['count' => 12]),
            $this->promptService->getShape('weapons.shape')
        );

        $this->game->gameStartOptions['equipment'] = $data['weapons'];

        $this->gameService->setField($this->game->code, 'gameStartOptions', $data['weapons'],'equipment');

        $broadcastEvent = new StartingEquipmentEvent($this->game, $data['weapons']);

        broadcast($broadcastEvent);
    }
}
