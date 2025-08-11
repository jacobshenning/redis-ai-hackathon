<?php

namespace App\Jobs;

use App\Events\ArriveAtLocationEvent;
use App\Game;
use App\Services\EventStreamServiceContract;
use App\Services\GameServiceContract;
use App\Services\OpenAiServiceContract;
use App\Services\PromptServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateLocationJob implements ShouldQueue
{
    use Queueable;

    private OpenAiServiceContract $openAiService;
    private GameServiceContract $gameService;
    private EventStreamServiceContract $eventStreamService;

    private PromptServiceContract $promptService;

    /**
     * Create a new job instance.
     */
    public function __construct(public Game $game, public array $location)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->openAiService = App::make(OpenAiServiceContract::class);
        $this->gameService = App::make(GameServiceContract::class);
        $this->eventStreamService = App::make(EventStreamServiceContract::class);
        /** @var PromptServiceContract promptService */
        $this->promptService = App::make(PromptServiceContract::class);

        $biome = collect($this->game->region['biomes'])->random();
        $prompt = $this->promptService->getPrompt('locations.pre-prompt', ['locations.type'], ['biome' => $biome]);

        $seed = $this->openAiService->getResponse($prompt);

        // 1 Push quest, invisible
        $travelQuests = $this->generateTravelQuests($seed, $this->location['next']);

        // 2 Travel quest, paths.

        // 3-5 local quests (Start here).
        $localQuests = $this->generateLocalQuests($seed);

        $location = $this->location;

        $location['quests'] = array_merge($localQuests, $travelQuests);

        $location['description'] = $this->openAiService->getResponse(config('services.openai.location.prompt') . " $seed.  Location events:" . json_encode($location['quests']));

        for ($i = 0; $i < count($this->game->locations); $i++) {
            if ($this->game->locations[$i]['id'] === $location['id']) {
                $this->game->locations[$i] = $location;
                $this->gameService->saveGame($this->game);
                $event = new ArriveAtLocationEvent($this->game, $location);
                event($event);

                foreach($this->game->players as $id => $user) {
                    $this->eventStreamService->addEvent($id, $location['description']);
                }

                return;
            }
        }

        Log::error("Failure to generate location.");

        return;

        Log::info($seed);

        // Reference below

        $location = $event->location;
        $location->seed = $event->seed;

        $locationQuests = [];
        $keywords = collect();

        if ($event->index < 8) {
            $dynamicQuest = new Quest();
            $dynamicQuest->location_id = $location->id;
            $dynamicQuest->next_location = $event->index + 1;
            $dynamicQuest->save();
            $locationQuests[] = $dynamicQuest;
            $keywords->push('dynamic quest (for players who muck around)');

            $destinationQuest1 = new Quest();
            $destinationQuest1->location_id = $location->id;
            $destinationQuest1->next_location = $event->index + 1;
            $destinationQuest1->save();
            $locationQuests[] = $destinationQuest1;
            $keywords->push('travel quest (takes player to new location)');
        }

        // Create skip-ahead quests for locations 0-6
        if ($event->index < 7) {
            $destinationQuest2 = new Quest();
            $destinationQuest2->location_id = $location->id;
            $destinationQuest2->next_location = $event->index + 2;
            $destinationQuest2->save();
            $locationQuests[] = $destinationQuest2;
            $keywords->push('travel quest (takes player to new location)');
        }

        // Create local quests (3-5 per location)
        $localQuestCount = mt_rand(3, 5);
        for ($j = 0; $j < $localQuestCount; $j++) {
            $localQuest = new Quest();
            $localQuest->location_id = $location->id;
            $localQuest->save();
            $locationQuests[] = $localQuest;
        }

        $keywordString = $keywords->push(...collect(config('services.openai.quest.keywords'))->random($localQuestCount))->implode(', ');

        $locationQuestSummaries = json_decode($event->openAiService->getJsonResponse(
            config('services.openai.quest.prompt') . ". The events are occurring in this location: $event->seed" . " Generate a total of " . count($locationQuests) . " responses. Each quest should be based on these keywords respectively: $keywordString",
            config('services.openai.quest.text')
        )[0], true)['responses'];

        // Assign summaries to quests
        for ($j = 0; $j < count($locationQuests); $j++) {
            $quest = $locationQuests[$j];
            $quest->summary = $locationQuestSummaries[$j]['description'];
            $quest->trigger = $locationQuestSummaries[$j]['trigger'];
            $quest->enemies = $locationQuestSummaries[$j]['enemies'];
            $quest->keyword = $locationQuestSummaries[$j]['keyword'];
            $quest->save();
        }

        $summariesForLocationPrompt = [];

        foreach ($locationQuestSummaries as $locationQuestSummary) {
            $summariesForLocationPrompt[] = $locationQuestSummary['description'];
        }

        $location->intro = $event->openAiService->getResponse(config('services.openai.location.prompt') . " $event->seed.  Location events:" . json_encode($summariesForLocationPrompt));
        $location->save();

        if ($event->index === 0) {
            $game = Game::find($location->game_id);

            $game->land .= "\n" . $location->intro;

            $game->save();

            event(new StoryUpdated($game));
        }
    }

    private function generateTravelQuests(string $seed, array $next)
    {
        $totalTravelQuests = count($next);

        if (! $totalTravelQuests) {
            return [];
        }

        $quests = json_decode($this->openAiService->getJsonResponseOld(
            config('services.openai.quest.prompt') . ". The events are occurring in this location: $seed" . " Generate a total of $totalTravelQuests responses. Each quest should be based on these keywords respectively: (travel quest (takes player to new location)",
            config('services.openai.quest.text')
        )[0], true)['responses'];

        for ($i = 0; $i < $totalTravelQuests; $i++) {
            $quests[$i]['id'] = Str::random(8);
            $quests[$i]['destination'] = $next[$i];
        }

        return $quests;
    }

    private function generateLocalQuests(string $seed)
    {
        $totalQuests = mt_rand(3,5);

        $keywordString = collect(config('services.openai.quest.keywords'))->random($totalQuests)->implode(', ');

        $quests = json_decode($this->openAiService->getJsonResponseOld(
            config('services.openai.quest.prompt') . ". The events are occurring in this location: $seed" . " Generate a total of $totalQuests responses. Each quest should be based on these keywords respectively: $keywordString",
            config('services.openai.quest.text')
        )[0], true)['responses'];

        for ($i = 0; $i < count($quests); $i++) {
            $quests[$i]['id'] = Str::random(8);
        }

        return $quests;
    }
}
