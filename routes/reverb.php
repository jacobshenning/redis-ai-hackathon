<?php

use Laravel\Reverb\Facades\Reverb;
use Laravel\Reverb\Server\Events\ConnectionMessage;
use Illuminate\Support\Facades\Redis;

Reverb::channel('games.{code}', function ($connection, $payload, $code) {
    $streamKey = "game_stream:{$code}";
    $lastId = '$'; // Start at "new" messages only

    $connection->send(new ConnectionMessage([
        'channel' => "games.{$code}",
        'event' => 'stream.start',
        'data' => ['message' => "Stream started for game {$code}"],
    ]));

    while ($connection->isConnected()) {
        $messages = Redis::xread([$streamKey => $lastId], 1, 5000);

        if ($messages) {
            foreach ($messages as [$stream, $streamMessages]) {
                foreach ($streamMessages as [$id, $fields]) {
                    $lastId = $id;

                    $connection->send(new ConnectionMessage([
                        'channel' => "games.{$code}",
                        'event' => 'stream.data',
                        'data' => $fields,
                    ]));
                }
            }
        }
    }
});
