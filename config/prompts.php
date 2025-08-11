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
        'era' => 'Write things based in European Middle Ages',
        'rule_of_rpg' => 'Never act on behalf of the player. Do perceive a player intent, action, or thought - Because the player is of their own mind.'
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

    'action' => [
        'filter' =>  [
            //You are responsible for filtering and rewriting RPG prompts given by players. Players can enter any prompt they want. Its your job to write the prompts in the correct tense, write the prompts from the correct perspective, and to write out inappropriate content. When you respond, format it in JSON, and have two fields: prompt and penalty. If the player writes implausible prompts, like claiming they have a gun when then actually only have a sword, make penalty a one. For sexual content, make the penalty a number 1-5, 5 being the worst. Rewrite inappropriate content as failed, rephrase the inappropriate words so players only can see something bad was attempted, and write it as past tense to make sure players understand it did fail. And for prompts that are perfectly fine, simply write them in future tense using character names and not pronouns.
            'prompt' => '%user% said "%attempt%." Rewrite this to be appropriate for the story by making it future tense and replacing explicit or sexual content with vague placeholders. If you do replace explicit or sexual content with a placeholder, make sure its clear the player tried to do something bad in the placeholder, and give them a penalty of 1-3. Do not write any additional information except rewriting the user text. Replace phrases like "I will" and "I did" with "Will try to" or "Will attempt". Always reference the player by name in the third person.',
            'shape' => [
                "prompt" => [
                    "type" => "string"
                ],
                "penalty" => [
                    "type" => "number",
                ]
            ],
        ],
        'logic' => [
            'prompt' => '%name% wants to "%prompt%." They are in this location: %location%. They have these events available: %quest%. This is their character and equipment: %character%. They have luck (2-12) of %luck%. They have a penalty of (1-3) %penalty%. The analysis is where you explain what the player attempted and what happened. You may, briefly, recommend a course of action. If the penalty is greater than zero, 1,2, or 3, do the following respectively; Suggest player humiliation, suggest player loses loot, suggest player dies. If an event is triggered, return the id in your response. The result should be a single sentence brief observation about what happened, and whether it worked, or was silly, or even eluding to the fact that an event was triggered.',
            'shape' => [
                "result" => [
                    "type" => "string"
                ],
                "trigger" => [
                    "type" => "string",
                ],
                "analysis" => [
                    "type" => "string",
                ]
            ],
        ],
        'result' => [
            'prompt' => '%name% has "%result%" and you should probably do something along the lines of %analysis%. Here is the character: %character%. If loot is lost, return the ID of the lost loot, unless they have no loot, in which case kill them. If you do need to kill them, do not write much story around it, just make it something like a stray arrow or a meteor.',
            'shape' => [
                "result" => [
                    "type" => "string"
                ],
                "lootLost" => [
                    "type" => "string",
                ],
                "killPlayer" => [
                    "type" => "boolean",
                ]
            ],
        ]
    ],

    'locations' => [
        'pre-prompt' => 'Briefly describe a %type% in a %biome%',
        'prompt' => '',
        'type' => ['small isolated tower', 'village', 'clearing', 'river port', 'spring', 'quarry']
    ],

    'story' => [
        'prompt' => "Describe of a %descriptor% land for the story to take place in. Give it a name that starts with '%StartingLetter% and is %syllables% syllables long. This land is %climate% mainly %landforms% and %biomes%.",
        'shape' => [
            "description" => [
                "type" => "string"
            ],
            "biomes" => [
                "type" => "array",
                "items" => [
                    "type" => "string"
                ]
            ]
        ],

        'descriptor' => ["battle-scarred", "prospering", "forgotten", "cursed", "impoverished", "peaceful", "troubled"],
        'landforms' => ["Mountains", "Dessert", "Valley", "Coast", "Island", "Canyon"],
        'biomes' => ["Forest", "Sand", "Grasslands", "Rainforest", "Jungle", "Swamp", "Tundra"],
        'climate' => ["Hot", "Humid", "Cold", "Rainy", "Snowy", "Tropical", "Polar", "Stormy"]
    ]

];
