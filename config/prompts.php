<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI prompts are stored here.
    |--------------------------------------------------------------------------
    |
    | This file is for saving the reused prompts so that they aren't cluttering up
    | our code.
    |
    */

    'global' => [
        'tone' => 'Write for a fifth grade reading level.',
        'genre' => 'Write for the genre fantasy epic.',
        'era' => 'Write things based in European Middle Ages'
    ],

    'characters' => [
        'player' => [
            'prompt' => 'Give a name and description for %count% characters. Do not give them any equipment or powers. They are a %species% of the %class% respectively.',
            'shape' => [
                "characters" => [
                    "type" => "array",
                    "items" => [
                        "name" => [
                            "type" => "string"
                        ],
                        "description" => [
                            "type" => "string"
                        ],
                    ]
                ]
            ],
            'species' => ['Human', 'Elf', 'Giant', 'Dwarf', 'Orc'],
            'class' => ['Warrior', 'Peasant', 'Noble']
        ]
    ],

    'weapons' => [
        'prompt' => 'Give name, description, and origin for %count% weapons. They are %type% of %power% respectively.',
        'shape' => [
            "weapons" => [
                "type" => "array",
                "items" => [
                    "name" => [
                        "type" => "string"
                    ],
                    "description" => [
                        "type" => "string"
                    ],
                    "origin" => [
                        "type" => "string"
                    ],
                ]
            ]
        ],
        'type' => ['arming sword', 'axe', 'bow', 'crossbow', 'pike', 'war hammer', 'longsword', 'glaive', 'bastard sword', 'greatsword'],
        'power' => ['fire', 'frost', 'lightning', 'wind', 'light']
    ],

    'story' => [
        'prompt' => "Describe of a %descriptor% land for the story to take place in. Give it a name that starts with '%StartingLetter% and is %syllables% syllables long. This land is %climate% mainly %landforms% and %biomes%",
        //  Add this to prompt later "but send 3-8 biomes you think would be present in the land."

        'descriptor' => ["battle-scarred", "prospering", "forgotten", "cursed", "impoverished", "peaceful", "troubled"],
        'landforms' => ["Mountains", "Dessert", "Valley", "Coast", "Island", "Canyon"],
        'biomes' => ["Forest", "Sand", "Grasslands", "Rainforest", "Jungle", "Swamp", "Tundra"],
        'climate' => ["Hot", "Humid", "Cold", "Rainy", "Snowy", "Tropical", "Polar", "Stormy"]
    ]

];
