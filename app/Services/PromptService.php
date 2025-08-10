<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PromptService implements PromptServiceContract
{

    public function getPrompt(string $path, array $seeds = [], array $variables = [], array $globals = ['tone', 'genre', 'era']): string
    {
        $mainPrompt = $this->getMainPrompt($path, $seeds, $variables);

        $globalPrompts = $this->getGlobalPrompts($globals);

        return implode(" ", array_merge($globalPrompts, [$mainPrompt]));
    }

    private function getMainPrompt(string $path, array $seeds, array $variables): string
    {
        $prompt = $this->config($path);

        foreach ($seeds as $seed) {
            $key = last(explode('.', $seed));
            $seedValue = collect($this->config($seed))->random();

            // A special thing we do for the variable count
            if (key_exists('count', $variables) && $variables['count']) {
                $seedValues = [];

                for ($i = 0; $i < $variables['count']; $i++) {
                    $seedValues[] = collect($this->config($seed))->random();
                }

                $seedValue = json_encode($seedValues);
            }

            $prompt = str_replace("%$key%", $seedValue, $prompt);
        }

        foreach ($variables as $variablesName => $variableValue) {
            $prompt = str_replace("%$variablesName%", $variableValue, $prompt);
        }

        return $prompt;
    }

    private function getGlobalPrompts(array $globals): array
    {
        $prompts = [];

        foreach ($globals as $global) {
            $prompts[] = $this->config("global.$global");
        }

        return $prompts;
    }

    public function getShape($path): string
    {
        $shape = $this->config($path);

        $transformedSchema = $this->transformSchema($shape);

        return json_encode([
            "format" => [
                "type" => "json_schema",
                "name" => "quest_response",
                "schema" => [
                    "type" => "object",
                    "properties" => $transformedSchema['properties'],
                    "required" => $transformedSchema['required'],
                    "additionalProperties" => false
                ],
                "strict" => true
            ]
        ]);
    }

    private function transformSchema(array $schema): array
    {
        $properties = [];
        $required = [];

        foreach ($schema as $key => $value) {
            // Add to required fields
            $required[] = $key;

            // Transform the property
            $properties[$key] = $this->transformProperty($value);
        }

        return [
            'properties' => $properties,
            'required' => $required
        ];
    }

    private function transformProperty(array $property): array
    {
        $transformed = [];

        // Copy the type
        if (isset($property['type'])) {
            $transformed['type'] = $property['type'];
        }

        // Handle array items
        if (isset($property['items'])) {
            $items = $property['items'];

            // Check if items is a direct property list (no explicit type)
            if (!isset($items['type']) && !empty($items)) {
                // This is a shorthand object definition
                $nestedSchema = $this->transformSchema($items);
                $transformed['items'] = [
                    'type' => 'object',
                    'properties' => $nestedSchema['properties'],
                    'required' => $nestedSchema['required'],
                    'additionalProperties' => false
                ];
            } else {
                // Regular items definition
                $transformed['items'] = $this->transformProperty($items);
            }
        }

        // Handle object properties (nested objects)
        if (isset($property['properties'])) {
            $nestedSchema = $this->transformSchema($property['properties']);
            $transformed['properties'] = $nestedSchema['properties'];
            $transformed['required'] = $nestedSchema['required'];
            $transformed['additionalProperties'] = false;
        }

        return $transformed;
    }

    private function config($path = ""): mixed
    {
        return config('prompts.' . $path);
    }
}
