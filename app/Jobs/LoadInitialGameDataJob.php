<?php

namespace App\Jobs;

use App\Events\GameDataBroadcastEvent;
use App\Events\StartingCharactersEvent;
use App\Events\StartingEquipmentEvent;
use App\Game;
use App\Services\GameServiceContract;
use App\Services\OpenAiServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
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

        $prompt = config('services.openai.story.prompt') . ". The name will start with $startingLetter and be $syllables syllables long. Here are some descriptors you need to use: " . implode(', ', $descriptors);

        $data = json_decode($this->openAiService->getJsonResponse($prompt, config('services.openai.story.text'))[0], true);

        $this->game->region = $data;

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new GameDataBroadCastEvent($this->game, $data['description']);

        broadcast($broadcastEvent);


    }

    private function loadStartingCharacters()
    {
        $data = [
            [
                "Name" => "Sammy",
                "Race" => "Shark",
                "Description" => "Sammy is a shark who can breath on land and slither across the ground",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Luna",
                "Race" => "Wolf",
                "Description" => "Luna is a mystical wolf with silver fur that glows under moonlight",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Zephyr",
                "Race" => "Phoenix",
                "Description" => "Zephyr is a phoenix who can control wind currents and regenerate from ashes",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Coral",
                "Race" => "Mermaid",
                "Description" => "Coral is a mermaid who can walk on land using magical leg transformation",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Rocky",
                "Race" => "Golem",
                "Description" => "Rocky is a stone golem with incredible strength and earth manipulation abilities",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Whisper",
                "Race" => "Shadow Fox",
                "Description" => "Whisper is a shadow fox that can phase through solid objects and become invisible",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Blaze",
                "Race" => "Dragon",
                "Description" => "Blaze is a young dragon with the ability to breathe different colored flames",
                "hands" => 2,
                "level" => 1
            ],
            [
                "Name" => "Echo",
                "Race" => "Crystal Owl",
                "Description" => "Echo is a crystal owl who can manipulate sound waves and see through illusions",
                "hands" => 2,
                "level" => 1
            ]
        ];

        $this->game->gameStartOptions['characters'] = $data;

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new StartingCharactersEvent($this->game, $data);

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

        $this->game->gameStartOptions['equipment'] = $data;

        $this->gameService->saveGame($this->game);

        $broadcastEvent = new StartingEquipmentEvent($this->game, $data);

        broadcast($broadcastEvent);
    }
}
