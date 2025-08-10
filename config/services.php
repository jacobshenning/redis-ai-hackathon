<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' =>  env('OPEN_API_KEY'),
        'loot_shape' => [
            'name' => 'unique-name',
            'type' => ['Attack', 'Defense'],
            'slot' => ['one-handed', 'two-handed', 'head', 'chest', 'legs', 'feet'],
            'power' => '2-12'
        ],

        'story' => [
            'prompt' => "Describe things at a fifth grade reading level. Write a one-paragraph description of a feudal fantasy realm that serves as the setting for this campaign. Describe the geographical nature of this land. Do not provide a count of anything. Focus on the realm's defining characteristics rather than specific locations or immediate surroundings. The land and its biomes should be soley derived from the descriptors provided to you. Include a list 5-8 biomes you've used under the biomes field in the JSON response.",
            'history' => [
                'war-torn', 'battle-scarred', 'bloodstained', 'peaceful', 'tranquil', 'harmonious', 'troubled',
                'haunted', 'cursed', 'blessed', 'forgotten', 'mythical', 'ancient', 'timeless', 'fallen', 'ruined', 'abandoned',
                'reclaimed', 'restored', 'declining', 'fading', 'crumbling', 'rising', 'ascendant',
                'prosperous', 'flourishing', 'thriving', 'struggling', 'impoverished', 'desolate', 'barren',
                'scarred', 'wounded', 'healing', 'recovering', 'resilient', 'enduring', 'persistent',
                'volatile', 'unstable', 'chaotic', 'turbulent', 'storm-tossed', 'strife-ridden', 'contested',
                'disputed', 'embattled', 'besieged', 'isolated', 'cut-off', 'secluded', 'hidden', 'secret',
                'mysterious', 'enigmatic', 'puzzling', 'strange', 'uncanny', 'otherworldly', 'magical',
                'enchanted', 'bewitched', 'spell-bound', 'touched-by-fate', 'star-crossed', 'doomed',
                'prophesied', 'destined', 'chosen', 'marked', 'tainted', 'corrupted', 'poisoned', 'diseased',
                'plagued', 'ritualistic', 'ceremony-marked', 'honorable',
            ],
            'geography' => [
                'shape' => [
                    'archipelago', 'peninsula', 'plateau', 'basin', 'delta', 'canyon', 'gorge', 'valley', 'ridge',
                    'fjord', 'strait', 'isthmus', 'cape', 'bay', 'inlet', 'cove', 'lagoon', 'atoll', 'mesa',
                    'butte', 'escarpment', 'terrace', 'shelf', 'slope', 'incline', 'decline', 'rise', 'fall',
                    'crater', 'caldera', 'rift', 'trench', 'hollow', 'depression', 'elevation', 'mound', 'knoll',
                    'hillock', 'hummock', 'dune', 'bluff', 'precipice', 'ledge', 'outcrop', 'formation', 'spire',
                    'pinnacle', 'arch', 'bridge', 'pass', 'gap', 'notch', 'saddle', 'col', 'divide', 'watershed'
                ],
                'terrain' => [
                    'mountainous', 'coastal', 'desert', 'forested', 'grassland', 'tundra', 'volcanic', 'swampy',
                    'marshland', 'badlands', 'highland', 'lowland', 'steppes', 'moorland', 'woodland', 'lakeland',
                    'prairie', 'savanna', 'taiga', 'chaparral', 'scrubland', 'heathland', 'wetland', 'dryland',
                    'pampas', 'plains', 'meadows', 'fields', 'pastures', 'ranges', 'hills', 'valleys', 'peaks',
                    'cliffs', 'shores', 'beaches', 'dunes', 'oasis', 'jungle', 'rainforest', 'deciduous', 'coniferous',
                    'alpine', 'subalpine', 'boreal', 'temperate', 'subtropical', 'equatorial', 'polar', 'arctic',
                    'subarctic', 'continental', 'insular', 'riverine', 'lacustrine', 'estuarine'
                ],
                'climate' => [
                    'windswept', 'misty', 'foggy', 'stormy', 'rainy', 'sunny', 'cloudy', 'overcast', 'clear',
                    'bright', 'dim', 'shadowy', 'dark', 'luminous', 'gleaming', 'sparkling', 'shimmering',
                    'humid', 'dry', 'arid', 'moist', 'damp', 'wet', 'parched', 'scorching', 'blazing', 'burning',
                    'freezing', 'frozen', 'icy', 'frosty', 'snowy', 'sleety', 'hailing', 'drizzling', 'pouring',
                    'tropical', 'temperate', 'mild', 'moderate', 'extreme', 'harsh', 'gentle', 'pleasant',
                    'oppressive', 'stifling', 'suffocating', 'refreshing', 'invigorating', 'bracing', 'crisp',
                    'balmy', 'sultry', 'muggy', 'sticky', 'clammy', 'steamy', 'smoky', 'hazy', 'dusty', 'sandy',
                    'muddy', 'salty', 'briny', 'fresh', 'pure', 'clean', 'polluted', 'tainted', 'toxic', 'noxious'
                ]
            ],
            'text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "location_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
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
                        "required" => ["description", "biomes"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ])
        ],

        'location' => [
            'seed' => 'Describe things at a fifth grade reading level. Describe a location in one sentence for another AI to use as a seed when generating other content. Location is isolated and small. Describe the area, not things in the area, and describe it curtly. This location belongs to the land described here:',
            'prompt' => 'Describe things at a fifth grade reading level. Describe this location in one paragraph from the perspective of someone observing it. Keep your tone simple and easy to read, not being overly detailed, but instead just stating things as if they are a matter of fact. You are not a narrator, your are a camera, so focus on what is immediately visible and actionable. Use neutral, observational language that presents what can be seen without interpreting or suggesting actions. Only use a couple of sentences to describe the overall location, giving the viewer a perspective of the overall vicinity. The remaining efforts should go to describing each location event. Naturally include the location event trigger inside of the paragraph as something else the player sees. Here is the location seed: ',
            'text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "location_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "responses" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "string"
                                ]
                            ]
                        ],
                        "required" => ["responses"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ]),

            'types' => [
                'settlement',
                'structure',
                'nature',
                'creature',
                'event',
            ],
        ],

        'quest' => [
            'prompt' => "Describe things at a fifth grade reading level. Generate distinct RPG event scenarios with the following requirements:1. TRIGGER: A simple, natural action that would initiate the event (like \"entering the cave\", \"crossing the bridge\", \"picking the fruit\", \"opening the door\"). Do not add unncessary conditions for a trigger. For example, 'swimming through a river' should be 'crossing a river' as the method of crossing is up to the player. Or 'opening a door' should be 'entering a room' because players can choose to smash doors or climb through windows. For travel events, the trigger should the player choosing to ride a boat, walk down a path, etc (no surprises). 2. DESCRIPTION: What is visibly present at this location. Include: The physical environment and notable features, something appealing or intriguing that might draw interest, subtle hints of potential danger WITHOUT revealing hidden enemies, use only observational language - describe what exists, not what anyone does 3. ENEMIES: Specify the exact number of hostile creatures that would appear if the event triggers. Do NOT: Describe player actions or speak for the player, reveal hidden enemies directly in the description, use complex or obscure trigger conditions, write from the player's perspective (\"you see\", \"you notice\"). 4. KEYWORD: Use the keywords given to influence the event you're creating. If a keyword says travel then you need the event to be a road, boat, cave, or some other way to travel away from the area to a new location. Format the response as JSON with \"trigger\", \"description\", \"enemies\", and \"keyword\" fields.",
            'text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "quest_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "responses" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "enemies" => [
                                            "type" => "number"
                                        ],
                                        "trigger" => [
                                            "type" => "string"
                                        ],
                                        "description" => [
                                            "type" => "string"
                                        ],
                                        "keyword" => [
                                            "type" => "string"
                                        ]
                                    ],
                                    "required" => ["trigger", "description", "enemies", "keyword"],
                                    "additionalProperties" => false
                                ]
                            ]
                        ],
                        "required" => ["responses"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ]),



            'keywords' => [
                'ambush',
                'brawl',
                'loot',
                'bounty',
                'ritual',
                'duel',
                'hunt',
                'piracy',
                'cultist',
                'infestation',
                'trial',
                'monster',
            ]
        ],

        'characters' => [
            'player' => 'Describe an ',
            'enemy' => 'Describe things at a fifth grade reading level. Describe enemies. Give them a name and race based on the context given by this event: ',

            'text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "quest_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "characters" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "name" => [
                                            "type" => "string"
                                        ],
                                        "description" => [
                                            "type" => "string"
                                        ],
                                    ],
                                    "required" => ["name", "description"],
                                    "additionalProperties" => false
                                ]
                            ]
                        ],
                        "required" => ["characters"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ])
        ],

        'equipment' => [
            'weapon' => [
                'Describe things at a fifth grade reading level. Create a weak weapon. Decide the type of weapon. Decide if weapon is ranged or not. Describe it as worn but still useful. Give it a generic name. Decide if the weapon is one-handed or two-handed. Give it a speculative origin story, as if its just a guess, and nobody really knows or cares where it came from.',
                'Describe things at a fifth grade reading level. Create a basic weapon. Decide the type of weapon. Decide if weapon is ranged or not. Describe it as basic and common. Give it a generic name. Decide if the weapon is one-handed or two-handed. Give it a boring origin story - something common and basic.',
                'Describe things at a fifth grade reading level. Create a good weapon. Decide the type of weapon. Decide if weapon is ranged or not. Describe it as respectable and reliable. Give it a simple but memorable one-word name. Decide if the weapon is one-handed or two-handed. Give it a respectful origin story, naming the family or forge who produced it.',
                'Describe things at a fifth grade reading level. Create a great weapon. Decide the type of weapon. Decide if weapon is ranged or not. Describe it as powerful and rare. Give it a powerful name. Decide if the weapon is one-handed or two-handed. Give it an epic origin story with multiple stages for it to become what it is today.',
                'Describe things at a fifth grade reading level. Create a legendary weapon. Decide the type of weapon. Decide if weapon is ranged or not. Describe it as a weapon with no equal. Give it a legendary name of three words. Append the origin of the weapon to the name so it says "name of origin". Decide if its one-handed or two-handed. Give it an epic origin story, one that sound impossible, and can span solar systems and dimensions.'
            ],

            'text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "quest_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "name" => [
                                "type" => "string",
                            ],
                            'type' => [
                                "type" => "string",
                            ],
                            "description" => [
                                "type" => "string"
                            ],
                            'origin' => [
                                'type' => "string"
                            ],
                            'hands' => [
                                "type" => "number"
                            ],
                            'ranged' => [
                                "type" => "boolean"
                            ]
                        ],
                        "required" => ["name", 'type', 'description', 'origin', 'hands', 'ranged'],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ]),

            'descriptors' => [
                "Ancient", "Eternal", "Timeless", "Forgotten", "Lost", "Hidden", "Secret", "Mysterious", "Legendary", "Mythical", "Fabled", "Renowned", "Famous", "Notorious", "Infamous",
                "Wrathful", "Furious", "Raging", "Savage", "Fierce", "Brutal", "Merciless", "Ruthless", "Vicious", "Cruel", "Bloodthirsty", "Vengeful", "Hateful", "Malicious", "Sinister",
                "Serene", "Peaceful", "Calm", "Tranquil", "Gentle", "Kind", "Noble", "Pure", "Holy", "Sacred", "Blessed", "Divine", "Righteous", "Just", "Honorable",
                "Swift", "Quick", "Fast", "Rapid", "Fleet", "Agile", "Nimble", "Light", "Graceful", "Elegant", "Smooth", "Fluid", "Sharp", "Keen", "Precise",
                "Mighty", "Powerful", "Strong", "Robust", "Sturdy", "Solid", "Heavy", "Massive", "Colossal", "Gigantic", "Enormous", "Immense", "Tremendous", "Overwhelming", "Devastating",
                "Shattered", "Broken", "Cracked", "Fractured", "Ruined", "Worn", "Weathered", "Aged", "Corroded", "Tarnished", "Faded", "Dulled", "Chipped", "Scarred", "Damaged",
                "Pristine", "Perfect", "Flawless", "Immaculate", "Spotless", "Gleaming", "Polished", "Shining", "Brilliant", "Radiant", "Luminous", "Glowing", "Sparkling", "Dazzling", "Resplendent",
                "Cursed", "Damned", "Doomed", "Hexed", "Jinxed", "Accursed", "Blighted", "Corrupted", "Tainted", "Poisoned", "Infected", "Diseased", "Rotting", "Decaying", "Withering",
                "Crystalline", "Ethereal", "Ghostly", "Spectral", "Phantom", "Translucent", "Transparent", "Invisible", "Intangible", "Incorporeal", "Wispy", "Misty", "Shadowy", "Vaporous", "Gaseous",
                "Jagged", "Rough", "Coarse", "Serrated", "Barbed", "Spiked", "Thorned", "Ridged", "Grooved", "Textured", "Bumpy", "Uneven", "Irregular", "Crooked", "Twisted"
            ],
            'elements' => [
                "Fire", "Water", "Earth", "Air",
                "Ice", "Lightning", "Thunder", "Light", "Shadow", "Darkness", "Metal", "Wood", "Crystal", "Steam", "Magma", "Poison", "Acid", "Nature",
                "Solar", "Lunar", "Stellar", "Cosmic", "Void", "Nebula", "Meteor", "Aurora", "Eclipse", "Comet", "Astral", "Celestial", "Radiant", "Twilight", "Dawn",
                "Arcane", "Divine", "Infernal", "Spectral", "Ethereal", "Psychic", "Soul", "Spirit", "Phantom", "Wraith", "Blessed", "Cursed", "Unholy", "Sacred", "Temporal", "Dimensional", "Planar", "Runic",
                "Storm", "Hurricane", "Earthquake", "Tsunami", "Avalanche", "Tornado", "Blizzard", "Drought", "Flood", "Wildfire", "Frost", "Hail", "Sandstorm", "Windstorm", "Volcanic",
                "Kinetic", "Thermal", "Electric", "Magnetic", "Sonic", "Plasma", "Nuclear", "Gravitational", "Quantum", "Photonic", "Vibrational", "Resonant",
                "Fury", "Serenity", "Chaos", "Order", "Dream", "Nightmare", "Memory", "Hope", "Despair", "Will",
                "Prismatic", "Chromatic", "Harmonic", "Crystalline", "Mineral", "Organic", "Synthetic", "Living", "Undead", "Elemental", "Primordial", "Ancient", "Modern"
            ],

        ],

        'player' => [
            'filter' => "You are responsible for filtering and rewriting RPG prompts given by players. Players can enter any prompt they want. Its your job to write the prompts in the correct tense, write the prompts from the correct perspective, and to write out inappropriate content. When you respond, format it in JSON, and have two fields: prompt and penalty. If the player writes implausible prompts, like claiming they have a gun when then actually only have a sword, make penalty a one. For sexual content, make the penalty a number 1-5, 5 being the worst. Rewrite inappropriate content as failed, rephrase the inappropriate words so players only can see something bad was attempted, and write it as past tense to make sure players understand it did fail. And for prompts that are perfectly fine, simply write them in future tense using character names and not pronouns.",
            'result' => "You are a game master determining outcomes. Based on the player's action, equipped items, luck (2-12), and penalty (0-5), decide what happens based on the player prompt. Do not describe the player as doing anything beyond the prompt they created. Do not repeat the prompt in your response (prompt is already on screen). Be clear as to whether the player succeeded or failed in their attempt. You can describe things happening to the player, but do not act on players behalf. Do not introduce anything new like characters, plot twists, or shadows in the distance. Your role is to only analyze the current action and then describe it. Do not mention luck, penalty, or event triggers in your response. You must also decide if an event was triggered. When deciding if an event is triggered, read between the lines, for example if walking through the door triggers the event then so should climbing through the window. If the event is triggered, return its id. Luck values or penalty numbers in the result field.",

            'combat' => "You are a game master determining outcomes. Based on the player's action, equipped items, luck (2-12), and penalty (0-5), decide what happens based on the player prompt. Do not describe the player as doing anything beyond the prompt they created. Do not repeat the prompt in your response (prompt is already on screen). Be clear as to whether the player succeeded or failed in their attempt. You can describe things happening to the player, but do not act on players behalf. Do not introduce anything new like characters, plot twists, or shadows in the distance. Your role is to only analyze the current action and then describe it. Do not mention luck, penalty, or event triggers in your response. Do not let the player die too easily. Put incapacitated enemies in the attackersRemoved field.",

            'filter_text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "filter_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "prompt" => [
                                "type" => "string"
                            ],
                            "penalty" => [
                                "type" => "number"
                            ]
                        ],
                        "required" => ["prompt", "penalty"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ]),

            'result_text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "filter_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "result" => [
                                "type" => "string"
                            ],
                            "event" => [
                                "type" => ["string", "null"]
                            ],
                            "lootGained" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "type" => [
                                            "type" => "string"
                                        ],
                                        "level" => [
                                            "type" => "string"
                                        ]
                                    ],
                                    "required" => ["type", "level"],
                                    "additionalProperties" => false
                                ]
                            ],
                            "lootLost" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "string"
                                ]
                            ]
                        ],
                        "required" => ["result", "event", "lootGained", "lootLost"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ]),
            'combat_text' => json_encode([
                "format" => [
                    "type" => "json_schema",
                    "name" => "filter_response",
                    "schema" => [
                        "type" => "object",
                        "properties" => [
                            "result" => [
                                "type" => "string"
                            ],
                            "attackersRemoved" => [
                                "type" => "array",
                                "items" => [
                                    "type" => "object",
                                    "properties" => [
                                        "name" => [
                                            "type" => "string"
                                        ]
                                    ],
                                    "required" => ["name"],
                                    "additionalProperties" => false
                                ]
                            ],
                            "playerDied" => [
                                "type" => ["boolean"]
                            ],
                            "playerRanAway" => [
                                "type" => ["boolean"]
                            ],
                            "playerWon" => [
                                "type" => ["boolean"]
                            ]
                        ],
                        "required" => ["result", "attackersRemoved", "playerDied", "playerRanAway", "playerWon"],
                        "additionalProperties" => false
                    ],
                    "strict" => true
                ]
            ])
        ],

        'character_prompt' => "In one paragraph, describe and name a character, including their race and class. Do not give them any equipment or armor - All characters start out with very little.",
        'monster_prompt' => 'Describe a monster based on the location and level.',
        'location_seed' => 'Describe a location in one sentence. The location must be isolated - Nothing interesting immediately outside of it. The location must be small - Not a city, a village. Not a fortress, a small castle. Not a forest - an opening in the forest. This location belongs to the land described here:',
        'location_prompt' => 'In one paragraph, describe a location and the biome its in. Describe in a practical way, a way a player could feel like there are several things they can immediately interact with in this text-based game. Do not speak on behalf of the player. Do not describe any other characters. Only scenery.',
        'filter_prompt' => "You are responsible for filtering and rewriting RPG prompts given by players. Players can enter any prompt they want. Its your job to write the prompts in the correct tense, write the prompts from the correct perspective, and to write out inappropriate content. When you respond, format it in JSON, and have two fields: prompt and penalty. If the player writes implausible prompts, like claiming they have a gun when then actually only have a sword, make penalty a one. For sexual content, make the penalty a number 1-5, 5 being the worst. Rewrite inappropriate content as failed, rephrase the inappropriate words so players only can see something bad was attempted, and write it as past tense to make sure players understand it did fail. And for prompts that are perfectly fine, simply write them in future tense using character names and not pronouns. Make sure your response is pure and parsable JSON with nothing extra.",
        'result_prompt' => "You are a game master determining outcomes. Based on the player's action, equipped items, luck (2-12), and penalty (0-5), decide what happens. Return only parsable JSON with: \"lootLost\" (array of lost items), \"lootGained\" (array of gained items), and \"prompt\" (narrative description of what occurred). Do not mention dice rolls, luck values, or penalty numbers in the narrative.",
        'elements' => [
            "Fire", "Water", "Earth", "Air",
            "Ice", "Lightning", "Thunder", "Light", "Shadow", "Darkness", "Metal", "Wood", "Crystal", "Steam", "Magma", "Poison", "Acid", "Nature",
            "Solar", "Lunar", "Stellar", "Cosmic", "Void", "Nebula", "Meteor", "Aurora", "Eclipse", "Comet", "Astral", "Celestial", "Radiant", "Twilight", "Dawn",
            "Arcane", "Divine", "Infernal", "Spectral", "Ethereal", "Psychic", "Soul", "Spirit", "Phantom", "Wraith", "Blessed", "Cursed", "Unholy", "Sacred", "Temporal", "Dimensional", "Planar", "Runic",
            "Storm", "Hurricane", "Earthquake", "Tsunami", "Avalanche", "Tornado", "Blizzard", "Drought", "Flood", "Wildfire", "Frost", "Hail", "Sandstorm", "Windstorm", "Volcanic",
            "Kinetic", "Thermal", "Electric", "Magnetic", "Sonic", "Plasma", "Nuclear", "Gravitational", "Quantum", "Photonic", "Vibrational", "Resonant",
            "Fury", "Serenity", "Chaos", "Order", "Dream", "Nightmare", "Memory", "Hope", "Despair", "Will",
            "Prismatic", "Chromatic", "Harmonic", "Crystalline", "Mineral", "Organic", "Synthetic", "Living", "Undead", "Elemental", "Primordial", "Ancient", "Modern"
        ],

        [
            "Mountain", "Ocean", "Desert", "Forest", "Valley", "River", "Lake", "Cave", "Volcano", "Glacier", "Canyon", "Plain", "Swamp", "Jungle", "Tundra", "Cliff", "Peak", "Abyss", "Crater", "Gorge",
            "Sun", "Moon", "Stars", "Comet", "Nebula", "Galaxy", "Void", "Cosmos", "Aurora", "Eclipse", "Meteor", "Constellation", "Supernova", "Blackhole", "Pulsar",
            "Heaven", "Hell", "Purgatory", "Limbo", "Paradise", "Underworld", "Afterlife", "Eternity", "Infinity", "Beyond", "Sanctuary", "Temple", "Shrine", "Cathedral", "Monastery",
            "Ancient", "Forgotten", "Lost", "Hidden", "Buried", "Ruined", "Abandoned", "Sealed", "Cursed", "Blessed", "Sacred", "Profane", "Forbidden", "Secret", "Mythical",
            "Dragon", "Phoenix", "Kraken", "Leviathan", "Titan", "Giant", "Demon", "Angel", "God", "Beast", "Spirit", "Wraith", "Lich", "Elder", "Ancient One",
            "Castle", "Tower", "Fortress", "Citadel", "Palace", "Dungeon", "Crypt", "Tomb", "Vault", "Chamber", "Hall", "Arena", "Colosseum", "Battlefield", "Throne",
            "Storm", "Hurricane", "Typhoon", "Blizzard", "Tornado", "Earthquake", "Avalanche", "Tsunami", "Wildfire", "Lightning", "Thunder", "Tempest", "Maelstrom", "Whirlpool", "Geyser",
            "Crystal", "Diamond", "Ruby", "Sapphire", "Emerald", "Obsidian", "Quartz", "Amethyst", "Onyx", "Pearl", "Opal", "Jade", "Amber", "Topaz", "Garnet",
            "Forge", "Anvil", "Furnace", "Smithy", "Workshop", "Laboratory", "Academy", "Library", "Archive", "Observatory", "Altar", "Ritual", "Ceremony", "Sacrifice", "Binding",
            "Time", "Eternity", "Moment", "Era", "Age", "Epoch", "Dawn", "Dusk", "Twilight", "Midnight", "Noon", "Yesterday", "Tomorrow", "Past", "Future"
        ],


        [
            "Ancient", "Eternal", "Timeless", "Forgotten", "Lost", "Hidden", "Secret", "Mysterious", "Legendary", "Mythical", "Fabled", "Renowned", "Famous", "Notorious", "Infamous",
            "Wrathful", "Furious", "Raging", "Savage", "Fierce", "Brutal", "Merciless", "Ruthless", "Vicious", "Cruel", "Bloodthirsty", "Vengeful", "Hateful", "Malicious", "Sinister",
            "Serene", "Peaceful", "Calm", "Tranquil", "Gentle", "Kind", "Noble", "Pure", "Holy", "Sacred", "Blessed", "Divine", "Righteous", "Just", "Honorable",
            "Swift", "Quick", "Fast", "Rapid", "Fleet", "Agile", "Nimble", "Light", "Graceful", "Elegant", "Smooth", "Fluid", "Sharp", "Keen", "Precise",
            "Mighty", "Powerful", "Strong", "Robust", "Sturdy", "Solid", "Heavy", "Massive", "Colossal", "Gigantic", "Enormous", "Immense", "Tremendous", "Overwhelming", "Devastating",
            "Shattered", "Broken", "Cracked", "Fractured", "Ruined", "Worn", "Weathered", "Aged", "Corroded", "Tarnished", "Faded", "Dulled", "Chipped", "Scarred", "Damaged",
            "Pristine", "Perfect", "Flawless", "Immaculate", "Spotless", "Gleaming", "Polished", "Shining", "Brilliant", "Radiant", "Luminous", "Glowing", "Sparkling", "Dazzling", "Resplendent",
            "Cursed", "Damned", "Doomed", "Hexed", "Jinxed", "Accursed", "Blighted", "Corrupted", "Tainted", "Poisoned", "Infected", "Diseased", "Rotting", "Decaying", "Withering",
            "Crystalline", "Ethereal", "Ghostly", "Spectral", "Phantom", "Translucent", "Transparent", "Invisible", "Intangible", "Incorporeal", "Wispy", "Misty", "Shadowy", "Vaporous", "Gaseous",
            "Jagged", "Rough", "Coarse", "Serrated", "Barbed", "Spiked", "Thorned", "Ridged", "Grooved", "Textured", "Bumpy", "Uneven", "Irregular", "Crooked", "Twisted"
        ]
    ]
];
