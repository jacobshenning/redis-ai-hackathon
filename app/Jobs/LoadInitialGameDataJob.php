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
        $descriptors = collect([
            ...collect(config('services.openai.story.history'))->random(2),
            ...collect(config('services.openai.story.geography.shape'))->random(2),
            ...collect(config('services.openai.story.geography.terrain'))->random(2),
            ...collect(config('services.openai.story.geography.climate'))->random(2),
        ])->toArray();

        $startingLetter = Str::random(1);
        $syllables = mt_rand(2, 5);

        /** @var PromptServiceContract $promptService */
        $promptService = App::make(PromptServiceContract::class);

        $prompt = $promptService->getPrompt('story.prompt', ['story.descriptor', 'story.landforms', 'story.biomes', 'story.climate'], ['StartingLetter' => $startingLetter, 'syllables' => $syllables]);

//        $prompt = config('services.openai.story.prompt') . ". Create a name that starts with $startingLetter and is $syllables syllables long. Here are some descriptors you need to use: " . implode(', ', $descriptors);

        $data = json_decode($this->openAiService->getJsonResponseOld($prompt, config('services.openai.story.text'))[0], true);

        $this->game->region = $data;

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new GameDataBroadCastEvent($this->game, $data['description']);

        broadcast($broadcastEvent);


    }

    private function loadStartingCharacters()
    {
        /** @var PromptServiceContract $promptService */
        $promptService = App::make(PromptServiceContract::class);

        $data = $this->openAiService->getJsonResponse(
            $promptService->getPrompt('characters.player.prompt', ['characters.player.species', 'characters.player.class'], ['count' => 12]),
            $promptService->getShape('characters.player.shape')
        );

        $this->game->gameStartOptions['characters'] = $data['characters'];

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new StartingCharactersEvent($this->game,  $data['characters']);

        broadcast($broadcastEvent);
    }

    private function loadStartingWeapons()
    {
        $data = [
            [
                "name" => "chipped iron sword",
                "type" => "sword",
                "description" => "A chipped iron sword with a worn leather handle. Its seen better days, but it is still handy in a fight.",
                "handsRequired" => 2,
                "ranged" => false,
                "level" => 1
            ],
            [
                "name" => "rusted dagger",
                "type" => "dagger",
                "description" => "A small, rusted blade with a cracked bone handle. Despite its poor condition, it remains sharp enough to find gaps in armor.",
                "handsRequired" => 1,
                "ranged" => false,
                "level" => 1
            ],
            [
                "name" => "oak longbow",
                "type" => "bow",
                "description" => "A well-crafted longbow made from seasoned oak wood. The bowstring is taut and ready, promising accurate shots at distant targets.",
                "handsRequired" => 2,
                "ranged" => true,
                "level" => 2
            ],
            [
                "name" => "bronze war hammer",
                "type" => "hammer",
                "description" => "A heavy bronze-headed hammer with an ash wood handle. Its weight makes it devastating against armored foes, though it requires strength to wield effectively.",
                "handsRequired" => 2,
                "ranged" => false,
                "level" => 2
            ],
            [
                "name" => "throwing knives",
                "type" => "throwing weapon",
                "description" => "A set of three balanced steel knives designed for throwing. Each blade is perfectly weighted for accuracy at medium range.",
                "handsRequired" => 1,
                "ranged" => true,
                "level" => 1
            ],
            [
                "name" => "enchanted staff of embers",
                "type" => "staff",
                "description" => "A gnarled wooden staff topped with a glowing red crystal. Faint wisps of smoke curl from its tip, and the air around it shimmers with heat.",
                "handsRequired" => 2,
                "ranged" => true,
                "level" => 3
            ],
            [
                "name" => "steel rapier",
                "type" => "sword",
                "description" => "An elegant steel rapier with an ornate basket hilt. Its needle-like point and balanced design make it perfect for precise thrusting attacks.",
                "handsRequired" => 1,
                "ranged" => false,
                "level" => 2
            ],
            [
                "name" => "iron-bound club",
                "type" => "club",
                "description" => "A thick wooden club reinforced with iron bands. Simple but effective, it delivers crushing blows that can shatter bones.",
                "handsRequired" => 1,
                "ranged" => false,
                "level" => 1
            ],
            [
                "name" => "masterwork crossbow",
                "type" => "crossbow",
                "description" => "A precision-crafted crossbow with steel limbs and an intricate trigger mechanism. Its powerful draw allows for devastating long-range attacks.",
                "handsRequired" => 2,
                "ranged" => true,
                "level" => 3
            ]
        ];

        /** @var PromptServiceContract $promptService */
        $promptService = App::make(PromptServiceContract::class);

        $data = $this->openAiService->getJsonResponse(
            $promptService->getPrompt('weapons.prompt', ['weapons.type', 'weapons.power'], ['count' => 12]),
            $promptService->getShape('weapons.shape')
        );

        $this->game->gameStartOptions['equipment'] = $data['weapons'];

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new StartingEquipmentEvent($this->game, $data['weapons']);

        broadcast($broadcastEvent);
    }
}
