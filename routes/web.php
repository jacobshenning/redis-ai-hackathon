<?php

use App\Services\EventStreamServiceContract;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('start');
})->name('start');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::post('/game/{code}/pickCharacter', [\App\Http\Controllers\GameController::class, 'pickCharacter'])->name('game.pickCharacter');
    Route::post('/game/{code}/pickEquipment', [\App\Http\Controllers\GameController::class, 'pickEquipment'])->name('game.pickEquipment');
    Route::get('/game/{code}', [\App\Http\Controllers\GameController::class, 'play'])->name('game.play');

});

Route::post('/game/start', [\App\Http\Controllers\GameController::class, 'start'])->name('game.start');
Route::post('/game/store', [\App\Http\Controllers\GameController::class, 'store'])->name('game.start');
Route::post('/game/{gameCode}/narrate', [\App\Http\Controllers\GameController::class, 'narrate'])->name('game.start');
Route::post('/game/{gameCode}/join', [\App\Http\Controllers\GameController::class, 'join'])->name('game.join');

Route::get('/search', [\App\Http\Controllers\SearchUserController::class, 'search']);
Route::get('/create-index', [\App\Http\Controllers\SearchUserController::class, 'createIndex']);
Route::get('/index', [\App\Http\Controllers\SearchUserController::class, 'indexUsers']);

