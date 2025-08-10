<?php

namespace App\Listeners;

use App\Events\SeedLocationEvent;
use App\Events\StoryUpdated;
use App\Models\Game;
use App\Models\Quest;
use Illuminate\Contracts\Queue\ShouldQueue;

class SeedLocationListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SeedLocationEvent $event): void
    {
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

        $locationQuestSummaries = json_decode($event->openAiService->getJsonResponseOld(
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
}
