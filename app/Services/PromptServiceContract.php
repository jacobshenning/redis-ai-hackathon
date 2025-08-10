<?php

namespace App\Services;

interface PromptServiceContract
{
    public function getPrompt(string $path, array $seeds = [], array $variables = [], array $globals = ['tone', 'genre', 'era']): string;

    public function getShape($path): string;
}
