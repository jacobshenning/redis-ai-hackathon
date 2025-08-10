<?php

namespace App\Services;

interface EventStreamServiceContract
{
    public function addEvent(string $userId, string $eventData): string;

    public function getLastEvents(string $userId, int $totalEvents = 10): string;
}
