<?php

namespace App\Listeners;

use App\Events\SeedGameEvent;
use App\Events\SeedLocationEvent;
use App\Models\Location;
use App\Models\Quest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SeedGameListener implements ShouldQueue
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
    public function handle(SeedGameEvent $event): void
    {
        $locations = [];

        // Create 9 locations
        for ($i = 0; $i < 1; $i++) {
            $location = new Location();
            $location->game_id = $event->game->id;
            $location->save();
            $locations[] = $location;
        }

        $biome = json_decode($event->game->biomes)[array_rand(json_decode($event->game->biomes))];
        $locationType = config('services.openai.location.types')[array_rand(config('services.openai.location.types'))];

        $locationSeeds = json_decode($event->openAiService->getJsonResponse(
            config('services.openai.location.seed') . " {$event->game->land}. Make them in this biome: $biome. The location type is: $locationType",
            config('services.openai.location.text')
        )[0], true)['responses'];

        for ($i = 0; $i < count($locations); $i++) {
            $event = new SeedLocationEvent($locations[$i], $locationSeeds[$i] . " $biome $locationType", $i, $event->openAiService);

            event($event);
        }


        /// Reference code
        ///

    }
}
