<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

//Broadcast::channel('games.{gameId}', function (User $user, string $gameCode) {
//    return $user->game_id === Game::where('code', $gameCode)->first()->getKey();
//});

//Broadcast::channel('games.{gameCode}', function (User $user, string $gameCode) {
//    return ['id' => $user->id, 'name' => $user->name];
//});
Broadcast::channel('users.{id}', function (?User $user, string $id) {
    if ($id != $user->getKey()) {
        return false;
    }

    return [
        'id' => $user->getKey(),
        'name' => $user->name,
    ];
});

Broadcast::channel('games.{code}', function (?User $user, string $code) {
    $gameService = \Illuminate\Support\Facades\App::make(\App\Services\GameServiceContract::class);

    $gameExists = $gameService->loadGame($code) !== null;

    if (! $user || ! $gameExists) {
        return false;
    }

    return [
        'id' => $user->getKey(),
        'name' => $user->name,
    ];
});


Broadcast::channel('games.{code}.{id}', function (?User $user, string $code, string $id) {
    if (! $user) {
        return false;
    }

    /** @var \App\Services\GameServiceContract $gameService */
    $gameService = \Illuminate\Support\Facades\App::make(\App\Services\GameServiceContract::class);

    $game = $gameService->loadGame($code);

    if (! $game) {
        return false;
    }

    $locations = $game->locations;

    if (! $locations || empty($locations)) {
        return false;
    }

    foreach ($locations as $location) {
        if ($location['id'] == $id) {
            return [
                'id' => $user->getKey(),
                'name' => $user->name,
            ];
        }
    }

    return false;
});

