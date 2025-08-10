<?php

namespace App;


class Game
{

    public function __construct(
        public readonly string $code,
        public readonly string $created_at,
        public array $locations,
        public array $players = [],
        public array $region = ['biomes' => [], 'description' => ''],
        public array $gameStartOptions = ['characters' => [], 'equipment' => []],
        public bool $closed = false,
    ) {}
}
