<?php

namespace App\Services;

use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Log;

class EventStreamService implements EventStreamServiceContract
{
    public function __construct(protected RedisManager $redis)
    {
    }

    /**
     * Add an event to the Redis Stream for the given user.
     *
     * @param string $userId
     * @param array $eventData
     * @return string Redis message ID
     */
    public function addEvent(string $userId, string $eventData): string
    {
        Log::info("Hit stream add event");

        $streamName = "user_stream:{$userId}";

        return $this->redis->xadd($streamName, '*', ['data' => $eventData]);
    }

    /**
     * Get the last 10 events for the given user.
     *
     * @param string $userId
     * @return array
     */
    public function getLastEvents(string $userId, int $totalEvents = 20): string
    {
        $streamName = "user_stream:{$userId}";

        $result = $this->redis->xrange($streamName, '-', '+');

        if (empty($result)) {
            dd("Its empty");
        }

        $result = array_slice($result, -10, 10, true);

        $data = "";
        foreach ($result as $messageId => $value) {
            $data .= $value['data'];
        }

        return $data;
    }
}
