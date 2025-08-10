<?php

namespace App\Events;

use App\Models\Game;
use App\Services\OpenAiServiceContract;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeedGameEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Game $game;
    public OpenAiServiceContract $openAiService;

    /**
     * Create a new event instance.
     */
    public function __construct(Game $game, OpenAiServiceContract $openAiService)
    {
        $this->game = $game;
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
