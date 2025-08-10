<?php

namespace App\Services;

interface OpenAiServiceContract
{

    public function getResponse(string $input): string;

    public function getJsonResponseOld(string $input, string $text);

    public function getJsonResponse(string $input, string $text);

    public function getResponses(string $input, int $count): array;

}
