<?php

namespace App\Events;

use App\Models\Location;
use App\Services\OpenAiServiceContract;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeedLocationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Location $location;

    public string $seed;

    public int $index;

    public OpenAiServiceContract $openAiService;

    /**
     * Create a new event instance.
     */
    public function __construct(Location $location, string $seed, int $index, OpenAiServiceContract $openAiService)
    {
        $this->location = $location;
        $this->seed = $seed;
        $this->index = $index;
        $this->openAiService = $openAiService;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
