<?php

namespace App\Services;

use App\Game;
use App\Jobs\LoadInitialGameDataJob;
use App\Models\User;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Str;

class GameService implements GameServiceContract
{
    public function __construct(protected RedisManager $redis, protected string $prefix = 'game') {}

    public function startNewGame(User $user): Game
    {
        $code = strtoupper(Str::random(6));

        $locations = $this->generateLocationMap();

        $game = new Game($code, now(), $locations, [$user->getKey() => []]);

        $this->saveGame($game);

        $job = new LoadInitialGameDataJob($game);

        dispatch($job);

        return $game;
    }

    public function saveGame(Game $game): bool
    {
        $data = [
            'code' => $game->code,
            'created_at' => $game->created_at,
            'players' => $game->players,
            'locations' => $game->locations,
            'gameStartOptions' => $game->gameStartOptions,
            'region' => $game->region,
            'closed' => $game->closed
        ];

        return $this->redis->setex(
            $this->getKey($game->code),
            3600, // 1 hour in seconds
            json_encode($data)
        );
    }

    public function loadGame(string $code): ?Game
    {
        $data = $this->redis->get($this->getKey($code));

        if (! $data) {
            return null;
        }

        return new Game(...json_decode($data, true));
    }

    public function joinGame(string $code, User $user): bool
    {
        $key = $this->getKey($code);
        $gameData = $this->load($code);

        if (!$gameData) {
            return false;
        }

        $gameData['players'][] = $user->getKey();

        $this->redis->setex(
            $key,
            3600,
            json_encode($gameData)
        );

        return true;
    }

    public function getGame(string $code): ?array
    {
        $data = $this->redis->get($this->getKey($code));

        return $data ? json_decode($data, true) : null;
    }

    public function updateGame(string $code, array $data): bool
    {
        $key = $this->getKey($code);
        $gameData = $this->load($code);

        if (!$gameData) {
            return false;
        }

        $gameData['data'] = array_merge($gameData['data'], $data);
        $gameData['updated_at'] = now()->toISOString();

        $this->redis->setex(
            $key,
            3600,
            json_encode($gameData)
        );

        return true;
    }

    public function end(string $code): bool
    {
        return $this->redis->del($this->getKey($code)) > 0;
    }

    public function exists(string $code): bool
    {
        return $this->redis->exists($this->getKey($code));
    }

    protected function getKey(string $code): string
    {
        return "{$this->prefix}:{$code}";
    }

    private function generateLocationMap(): array
    {
        $locations = [];

        for ($i = 0; $i < 9; $i++) {
            $locationId = Str::random(12);
            $locations[] = [
                'id' => $locationId,
                'next' => []
            ];
        }

        // Static map for now.
        $locations[0]['next'] = [$locations[1]['id'], $locations[2]['id']];
        $locations[1]['next'] = [$locations[3]['id'], $locations[4]['id']];
        $locations[2]['next'] = [$locations[4]['id'], $locations[5]['id']];
        $locations[3]['next'] = [$locations[6]['id']];
        $locations[4]['next'] = [$locations[6]['id'], $locations[7]['id']];
        $locations[5]['next'] = [$locations[7]['id']];
        $locations[6]['next'] = [$locations[8]['id']];
        $locations[7]['next'] = [$locations[8]['id']];

        return $locations;
    }
}
