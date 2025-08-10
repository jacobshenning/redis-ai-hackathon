<?php

namespace App\Http\Controllers;

use App\Events\ChangedLocationEvent;
use App\Events\GameDataBroadcastEvent;
use App\Jobs\AttemptToStartGameJob;
use App\Events\SeedGameEvent;
use App\Events\StartingCharactersEvent;
use App\Events\StartingEquipmentEvent;
use App\Jobs\GenerateLocationJob;
use App\Models\Game;
use App\Models\User;
use App\Services\EventStreamServiceContract;
use App\Services\GameServiceContract;
use App\Services\OpenAiServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{

    public function start(Request $request, GameServiceContract $gameService)
    {
        // Validate  our data
        $validated = $request->validate(['hostName' => 'string|required']);

        // Create our user
        $user = new User();

        $user->name = $validated['hostName'];

        $user->save();

        Auth::login($user);

        // Start Game
        $game = $gameService->startNewGame($user);

        // Go to game page
        return redirect()->route('game.play', $game->code);
    }

    public function play(string $code, Request $request, GameServiceContract $gameService, EventStreamServiceContract $eventStreamService): Response
    {
        // Load our game
        $game = $gameService->loadGame($code);

        // If we can't find our game, we bail.
        if (! $game) {
            abort(404);
        }

        $startingCharacters = $game->gameStartOptions['characters'];
        $startingEquipment = $game->gameStartOptions['equipment'];
        $playerInfo = $game->players[$request->user()->getKey()];
        $playerCharacter = null;
        $playerEquipment = null;

        if (key_exists('character', $playerInfo) && count($playerInfo['character'])) {
            $playerCharacter = $playerInfo['character'];
            $startingCharacters = null;
        }

        if (key_exists('equipment', $playerInfo) && count($playerInfo['equipment'])) {
            $playerEquipment = $playerInfo['equipment'];
            $startingEquipment = null;
        }

        $playerLocation = null;
        if (key_exists('location', $playerInfo) && $playerInfo['location']) {
            $playerLocation = $playerInfo['location'];
        }
        $content = "";

        if ($game->closed) {
            $content = $eventStreamService->getLastEvents($request->user()->getKey());
            if (empty($content)) {
                $content = "";
            }
        } else if (key_exists('description', $game->region)) {
            $content = $game->region['description'] ?? "";
        }

        return Inertia::render('game', [
            'playerLocation' => $playerLocation,
            'character' => $playerCharacter,
            'equipment' => $playerEquipment,
            'gameCode' => $game->code,
            'content' => $content,
            'user' => $request->user(),
            'startCharacters' => $startingCharacters,
            'startEquipment' => $startingEquipment
        ]);
    }

    public function pickCharacter(string $code, Request $request, GameServiceContract $gameServiceContract)
    {
        $name = $request->validate(['name' => 'string'])['name'];

        $game = $gameServiceContract->loadGame($code);

        $characters =  $game->gameStartOptions['characters'];

        $character = [];

        try {

        for ($i = 0; $i < count($characters); $i++) {
            if ($characters[$i]['name'] === $name) {
                $character = $characters[$i];
                $character[$i] = $characters[0];
                array_shift($characters);
                $game->gameStartOptions['characters'] = $characters;
                $game->players[$request->user()->getKey()]['character'] = $character;
                $gameServiceContract->saveGame($game);
                event(new StartingCharactersEvent($game, $characters));
                return redirect()->route('game.play', $game->code);
            }
        }
        } catch (\Exception $e) {
            dd($game->gameStartOptions['characters'], $e, $characters);
        }

        abort(404);
    }

    public function pickEquipment(string $code, Request $request, GameServiceContract $gameServiceContract)
    {
        $name = $request->validate(['name' => 'string'])['name'];

        $game = $gameServiceContract->loadGame($code);

        $equipment =  $game->gameStartOptions['equipment'];

        for ($i = 0; $i < count($equipment); $i++) {
            if ($equipment[$i]['name'] === $name) {
                $playerPick = $equipment[$i];
                $equipment[$i] = $equipment[0];
                array_shift($equipment);
                $game->gameStartOptions['equipment'] = $equipment;
                $game->players[$request->user()->getKey()]['equipment'] = $playerPick;
                $gameServiceContract->saveGame($game);
                event(new StartingEquipmentEvent($game, $equipment));
                dispatch(new AttemptToStartGameJob($game));
                return redirect()->route('game.play', $game->code);
            }
        }

        abort(404);
    }


    public function narrate(string $gameCode, Request $request, OpenAiServiceContract $openAiService, GameServiceContract $gameService)
    {
        $promptAttempt = $request->validate(['action' => 'string'])['action'];

        $game = $gameService->loadGame($gameCode);

        $user = $request->user();

        $locationId = $game->players[$user->getKey()]['location'];
        $locationIndex = null;
        $location = null;

        foreach ($game->locations as $key => $value) {
            if ($value['id'] === $locationId) {
                $locationIndex = $key;
                $location = $value;
            }
        }

        $user = Auth::user();
        $name = $user->name;

        [
            'prompt' => $prompt,
            'penalty' => $penalty
        ] = json_decode($openAiService->getJsonResponseOld(config('services.openai.player.filter') . "Here is the prompt by $name: '$promptAttempt'", config('services.openai.player.filter_text'))[0], true);

        $broadcastEvent = new GameDataBroadCastEvent($game, $prompt);

        broadcast($broadcastEvent);

        if (key_exists('combat', $game->players[$user->getKey()]) && ! empty($game->players[$user->getKey()]['combat'])) {
            $data = $game->players[$user->getKey()]['combat'];

            $character = $game->players[$user->getKey()]['character'];
            $equipment = $game->players[$user->getKey()]['equipment'];

            $characterData = json_encode([
                'character'=> $character,
                'equipment' => $equipment
            ]);

            $quest = $data['quest'];
            $attackers = $data['attackers'];
            $originalResult = $data['result'];

            $luck = (mt_rand(1, 6) + mt_rand(1, 6));

            [
                'result' => $result,
                'attackersRemoved' => $attackersRemoved,
                'playerDied' => $playerDied,
                'playerRanAway' => $playerRanAway,
                'playerWon' => $playerWon
            ] = json_decode($openAiService->getJsonResponseOld(config('services.openai.player.combat') . " Player triggered a event like so: {$quest['trigger']}. Against these attackers: " . json_encode($attackers) . ". Character Data: $characterData Player luck: $luck. Player penalty: $penalty. This is what happened so far: $originalResult. Player is trying to do this: $prompt", config('services.openai.player.combat_text'))[0], true);

            $game->players[$user->getKey()]['combat']['result'] .= " $result";

            if (count($attackersRemoved)) {
                foreach ($attackersRemoved as $attackerRemoved) {
                    $name = $attackerRemoved['name'];

                    for ($j = 0; $j < count($attackers); $j++) {
                        if ($attackers[$j]['name'] == $name) {
                            $attackers[$j] = $attackers[0];
                            array_shift($attackers);
                        }
                    }
                }

                $game->players[$user->getKey()]['combat']['attackers'] = $attackers;
            }


            if ($playerDied) {
                // @TODO end game for this player. They can only spectate.
            }

            if ($playerRanAway) {
                $game->players[$user->getKey()]['combat'] = null;
            }

            if ($playerWon) {
                $game->players[$user->getKey()]['combat'] = null;
            }

            $gameService->saveGame($game);

            $broadcastEvent = new GameDataBroadCastEvent($game, $result);

            broadcast($broadcastEvent);

            return;
        }

        $luck = (mt_rand(1, 6) + mt_rand(1, 6));

        $quests = $location['quests'];


        $triggers = [];


        foreach ($quests as $quest) {
            $triggers[] = $quest['trigger'];
        }

        $broadcastEvent = new GameDataBroadCastEvent($game, json_encode($triggers));

        broadcast($broadcastEvent);

        [
            'result' => $result,
            'lootGained' => $lootGained,
            'lootLost' => $lootLost,
            'event' => $event,
        ] = json_decode($openAiService->getJsonResponseOld(config('services.openai.player.result') . " Player prompt: $prompt. Player luck: $luck. Player penalty: $penalty. Location details: {$location['description']}. Player is regular human. Player inventory is empty. Quest data " . json_encode($quests) , config('services.openai.player.result_text'))[0], true);

        $broadcastEvent = new GameDataBroadCastEvent($game, $result);

        broadcast($broadcastEvent);

        if ($event) {

            $broadcastEvent = new GameDataBroadCastEvent($game, "Event triggered");

            broadcast($broadcastEvent);

            $questIndex = null;
            $quest = null;

            foreach ($quests as $key => $value) {
                if ($value['id'] == $event) {
                    $questIndex = $key;
                    $quest = $value;
                }
            }

            if (! $quest) {
                $broadcastEvent = new GameDataBroadCastEvent($game, "The AI decided it would be best to not feed the next step to the procedural game... Maybe try again?");

                broadcast($broadcastEvent);

                return;
            }

            if (stripos($quest['keyword'], 'travel') !== false) {

                $broadcastEvent = new GameDataBroadCastEvent($game, "{$user->name} travels to new location");
                broadcast($broadcastEvent);

                $job = new ChangedLocationEvent($quest['destination'], $user->getKey());

                dispatch($job);

                $newLocation = collect($game->locations)->filter(fn ($location) => $location['id'] = $quest['destination'])->first();

                $job = new GenerateLocationJob($game,$newLocation);

                dispatch($job);

                return;
            }

            $totalEnemies = $quest['enemies'];
            $characterLevels = [];
            $weaponLevels = [];

            for ($i = 0; $i < $totalEnemies; $i++) {
                $characterLevels[] = mt_rand(1,10);
                $weaponLevels[] = mt_rand(1,5);
            }

            [
                'characters' => $characters,
            ] = json_decode($openAiService->getJsonResponseOld(config('services.openai.character.enemy') . " {$quest['description']}. Create $totalEnemies characters. Character levels are " .  json_encode($characterLevels) . " respectively. Min level is 1 and max level is 10." , config('services.openai.characters.text'))[0], true);

            for ($i = 0; $i < $totalEnemies; $i++) {
                $characters[$i]['level'] = $characterLevels[$i];

                $weapon = json_decode($openAiService->getJsonResponseOld(config('services.openai.equipment.weapon')[$weaponLevels[$i] - 1] . ". Weapon is for {$characters[$i]['name']}, {$characters[$i]['description']}.", config('services.openai.equipment.text'))[0], true);

                $characters[$i]['weapon'] = $weapon;
                $characters[$i]['weapon']['level'] = $weaponLevels[$i];
            }

            $quest['attackers'] = $characters;

            $game->locations[$locationIndex]['quests'][$questIndex] = $quest;

            $game->players[$user->getKey()]['combat'] = [
                'quest' =>$quest,
                'attackers' => $characters,
                'result' => $result
            ];

            $gameService->saveGame($game);

            $event = $openAiService->getResponse("The player was doing the following: $result. Now the player is in combat by $totalEnemies enemies. Describe the attackers for the player. Use observational language. Here are the attackers information " . json_encode($characters));

            $broadcastEvent = new GameDataBroadCastEvent($game, $event);

            broadcast($broadcastEvent);
        } else {
            $broadcastEvent = new GameDataBroadCastEvent($game, "No event triggered");

            broadcast($broadcastEvent);
        }


//        $game->land = "$content $event";

//        $eventStreamService->addEvent($user->getKey(), 'story', [$content]);

//        $game->save();

//        event(StoryUpdated::broadcast($game));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, OpenAiServiceContract $openAiService)
    {
        $validated = $request->validate(['hostName' => 'string|required']);

        do {
            $code = Str::upper(Str::random(6));

            $unique = Game::query()->where('code', $code)->doesntExist();
        } while (! $unique);

        $game = new Game();

        $descriptors = collect([
            ...collect(config('services.openai.story.history'))->random(2),
            ...collect(config('services.openai.story.geography.shape'))->random(2),
            ...collect(config('services.openai.story.geography.terrain'))->random(2),
            ...collect(config('services.openai.story.geography.climate'))->random(2),
        ])->toArray();

        $startingLetter = Str::random(1);
        $syllables = mt_rand(2, 5);

        $prompt = config('services.openai.story.prompt') . ". The name will start with $startingLetter and be $syllables syllables long. Here are some descriptors you need to use: " . implode(', ', $descriptors);

        $data = json_decode($openAiService->getJsonResponseOld($prompt, config('services.openai.story.text'))[0], true);

        $story = $data['description'];

        $game->land = $story;
        $game->code = $code;
        $game->biomes = json_encode($data['biomes']);

        $game->save();

        $user = new User();

        $user->name = $validated['hostName'];
        $user->game_id = $game->getKey();

        $user->save();

        Auth::login($user);

        $event = new SeedGameEvent($game, $openAiService);

        event($event);

        return redirect()->route('game', $game->code);
    }

    public function join(string $gameCode, Request $request, GameServiceContract $gameService)
    {
        $game = $gameService->loadGame($gameCode);

        if ($game->closed) {
            abort(403);
        }

        if (!$game) {
            abort(404);
        }

        $validated = $request->validate([
           'playerName' => 'required|string'
        ]);

        $user = new User();

        $user->name = $validated['playerName'];

        $user->save();

        Auth::login($user);

        $game->players[$user->getKey()] = [];

        $gameService->saveGame($game);

        return redirect()->route('game.play', $game->code);
    }
}
