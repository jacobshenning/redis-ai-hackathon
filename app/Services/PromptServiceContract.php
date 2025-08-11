<?php

namespace App\Services;

interface PromptServiceContract
{
    public function getPrompt(string $path, array $seeds = [], array $variables = [], array $globals = ['tone', 'genre', 'era', 'rule_of_rpg']): string;

    public function getShape($path): string;
}
