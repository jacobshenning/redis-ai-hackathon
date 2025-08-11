<?php

namespace App\Services;

use App\Game;
use App\Models\User;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Str;

interface GameServiceContract
{
    public function startNewGame(User $user): Game;

    public function saveGame(Game $game): bool;

    public function setField($code, $field, $value, $key = null): bool;

    public function loadGame(string $code): ?Game;

    public function joinGame(string $code, User $user): bool;
    public function getGame(string $code): ?array;

    public function updateGame(string $code, array $data): bool;

    public function end(string $code): bool;

    public function exists(string $code): bool;
}
