<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;

class OpenAiService implements OpenAiServiceContract
{
    /**
     * @throws Exception
     */
    public function getResponse(string $input): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-4.1',
            'top_p' => 0.1,
            'input' => $input,
        ]);

        if ($response->successful()) {
            $aiResponse = $response->json();

            return $aiResponse['output'][0]['content'][0]['text'];
        } else {
            throw new Exception("Invalid response from open ai");
        }
    }

    /**
     * @throws Exception
     */
    public function getJsonResponse(string $input, string $text)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/responses', [
            'model' => 'gpt-4.1',
            'top_p' => 0.1,
            'input' => $input,
            'text' => json_decode($text)
        ]);

        if ($response->successful()) {
            $aiResponse = $response->json();

            return (array) $aiResponse['output'][0]['content'][0]['text'];
        } else {
            dd($response->body());
            throw new Exception("Invalid response from open ai");
        }
    }

    /**
     * @throws Exception
     */
    public function getResponses(string $input, int $count): array
    {
        $responses = Http::pool(function (Pool $pool) use ($input, $count) {
            $requests = [];

            for ($i = 0; $i < $count; $i++) {
                $requests[] = $pool->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/responses', [
                    'model' => 'gpt-4.1',
                    'top_p' => 0.4,
                    'input' => $input,
                ]);
            }

            return $requests;
        });

        $results = [];

        foreach ($responses as $response) {
            if ($response->ok()) {
                $aiResponse = $response->json();
                $results[] = $aiResponse['output'][0]['content'][0]['text'];
            } else {
                throw new Exception("Invalid response from open ai {$response->body()}");
            }
        }

        return $results;
    }


}
