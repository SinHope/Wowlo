<?php

/*
|--------------------------------------------------------------------------
| Hangman Wheel Panda — categories + drawing parts
|--------------------------------------------------------------------------
| Single source of truth for the Hangman game (see docs/hangman-wheel-panda.md).
|
| The SERVER picks a secret puzzle at random from the chosen category and never
| sends it to the browser until the round is over — see HangmanController.
|
| Puzzles may be single WORDS or PHRASES. Only A–Z letters are guessed; spaces
| and punctuation (e.g. the apostrophe in "VALENTINE'S DAY") are ALWAYS shown,
| so phrases with spaces work. Single-word categories are gentler (fewer unique
| letters); the phrase-heavy ones near the bottom (Idioms, Movie Titles) are the
| tougher rounds. A "Surprise Me!" option (added in the controller) draws from
| every category.
|
| 'parts' is the panda drawing order. count(parts) === the max wrong guesses
| allowed before a loss (one body part per wrong letter/word guess).
*/

return [

    // Panda body parts, drawn in this order on each wrong guess. The LENGTH of
    // this list is the number of wrong guesses allowed (10) before losing —
    // the face fills in last (eyes, nose, mouth), matching the requested order.
    'parts' => [
        'face',       // 1 — the head outline appears first
        'body',       // 2
        'left hand',  // 3
        'right hand', // 4
        'left leg',   // 5
        'right leg',  // 6
        'left eye',   // 7
        'right eye',  // 8
        'nose',       // 9
        'mouth',      // 10 — once the mouth is drawn, the student loses
    ],

    // 20 themed categories + the original "Wowlo Mix" (kept from before). Each
    // is a list of puzzles (words or phrases). Letters are guessed; spaces and
    // punctuation are shown for free.
    'categories' => [

        'Animals' => [
            'ELEPHANT', 'KANGAROO', 'DOLPHIN', 'BUTTERFLY', 'PENGUIN',
            'CROCODILE', 'GIRAFFE', 'OCTOPUS', 'SQUIRREL', 'RHINOCEROS',
        ],

        'Food & Drink' => [
            'PIZZA', 'HAMBURGER', 'SPAGHETTI', 'CHOCOLATE', 'ICE CREAM',
            'SANDWICH', 'PANCAKES', 'ORANGE JUICE', 'POPCORN', 'FRENCH FRIES',
        ],

        'Fruits & Vegetables' => [
            'WATERMELON', 'STRAWBERRY', 'PINEAPPLE', 'BROCCOLI', 'BANANA',
            'CUCUMBER', 'TOMATO', 'PUMPKIN', 'GRAPES', 'CARROT',
        ],

        'Around the House' => [
            'TELEVISION', 'REFRIGERATOR', 'PILLOW', 'STAIRCASE', 'BATHROOM',
            'TOOTHBRUSH', 'BLANKET', 'KITCHEN SINK', 'WASHING MACHINE', 'FRONT DOOR',
        ],

        'School & Classroom' => [
            'PENCIL', 'BACKPACK', 'HOMEWORK', 'WHITEBOARD', 'CALCULATOR',
            'LIBRARY', 'NOTEBOOK', 'SCISSORS', 'TEACHER', 'RECESS',
        ],

        'Sports & Games' => [
            'BASKETBALL', 'SWIMMING', 'VOLLEYBALL', 'BADMINTON', 'GYMNASTICS',
            'SOCCER', 'BASEBALL', 'TABLE TENNIS', 'ICE HOCKEY', 'HIDE AND SEEK',
        ],

        'Jobs & Occupations' => [
            'DOCTOR', 'FIREFIGHTER', 'ENGINEER', 'ASTRONAUT', 'POLICE OFFICER',
            'CHEF', 'VETERINARIAN', 'ARCHITECT', 'BUS DRIVER', 'SCIENTIST',
        ],

        'Nature & Weather' => [
            'RAINBOW', 'THUNDERSTORM', 'WATERFALL', 'HURRICANE', 'SUNSHINE',
            'SNOWFLAKE', 'VOLCANO', 'MOUNTAIN', 'LIGHTNING', 'TORNADO',
        ],

        'Body & Health' => [
            'STOMACH', 'SHOULDER', 'ELBOW', 'EYEBROW', 'FINGERNAIL',
            'HEARTBEAT', 'MUSCLE', 'SKELETON', 'KNEECAP', 'EXERCISE',
        ],

        'Clothing & Fashion' => [
            'SWEATER', 'RAINCOAT', 'SUNGLASSES', 'NECKLACE', 'SNEAKERS',
            'BASEBALL CAP', 'SCARF', 'GLOVES', 'JACKET', 'WINTER BOOTS',
        ],

        'Transportation' => [
            'AIRPLANE', 'HELICOPTER', 'SUBMARINE', 'BICYCLE', 'MOTORCYCLE',
            'AMBULANCE', 'SAILBOAT', 'FIRE TRUCK', 'SPACESHIP', 'SCHOOL BUS',
        ],

        'Action Words (Verbs)' => [
            'JUMPING', 'DANCING', 'LAUGHING', 'WHISPERING', 'CLIMBING',
            'SNEEZING', 'STRETCHING', 'YAWNING', 'GALLOPING', 'TIPTOEING',
        ],

        'Colors & Shapes' => [
            'PURPLE', 'TRIANGLE', 'RECTANGLE', 'TURQUOISE', 'DIAMOND',
            'OCTAGON', 'YELLOW', 'CIRCLE', 'MAGENTA', 'PENTAGON',
        ],

        'Emotions & Feelings' => [
            'HAPPY', 'EXCITED', 'NERVOUS', 'JEALOUS', 'CURIOUS',
            'GRATEFUL', 'SURPRISED', 'EMBARRASSED', 'CONFUSED', 'PROUD',
        ],

        'Technology & Gadgets' => [
            'COMPUTER', 'HEADPHONES', 'SMARTPHONE', 'KEYBOARD', 'CAMERA',
            'VIDEO GAME', 'CHARGER', 'TABLET', 'ROBOT', 'TOUCHSCREEN',
        ],

        'Music & Instruments' => [
            'GUITAR', 'PIANO', 'TRUMPET', 'VIOLIN', 'DRUMS',
            'SAXOPHONE', 'FLUTE', 'ORCHESTRA', 'MICROPHONE', 'XYLOPHONE',
        ],

        'Places Around the World' => [
            'EGYPT', 'BRAZIL', 'AUSTRALIA', 'JAPAN', 'ICELAND',
            'THAILAND', 'ANTARCTICA', 'PARIS', 'GREAT WALL OF CHINA', 'AMAZON RAINFOREST',
        ],

        'Holidays & Celebrations' => [
            'BIRTHDAY', 'HALLOWEEN', 'THANKSGIVING', 'NEW YEAR', 'FIREWORKS',
            "VALENTINE'S DAY", 'WEDDING', 'GRADUATION', 'CARNIVAL', 'FESTIVAL',
        ],

        'Common Phrases & Idioms' => [
            'BREAK A LEG', 'PIECE OF CAKE', 'RAINING CATS AND DOGS', 'BEST FRIENDS FOREVER',
            'ONCE UPON A TIME', 'HOME SWEET HOME', 'BETTER LATE THAN NEVER',
            'PRACTICE MAKES PERFECT', 'TIME FLIES', 'EASY AS PIE',
        ],

        'Movie Titles (Family Favorites)' => [
            'THE LION KING', 'TOY STORY', 'FINDING NEMO', 'FROZEN', 'THE JUNGLE BOOK',
            'HOME ALONE', 'THE INCREDIBLES', 'HOW TO TRAIN YOUR DRAGON',
            'BEAUTY AND THE BEAST', 'KUNG FU PANDA',
        ],

        // The original Wowlo word bank — kept so nothing is lost.
        'Wowlo Mix' => [
            'PANDA', 'TIGER', 'MONKEY', 'RABBIT', 'DOLPHIN', 'ELEPHANT', 'GIRAFFE',
            'PENGUIN', 'KANGAROO', 'BUTTERFLY', 'OCTOPUS', 'LEOPARD', 'HAMSTER',
            'PENCIL', 'TEACHER', 'SCIENCE', 'LIBRARY', 'HOMEWORK', 'QUESTION',
            'ANSWER', 'LESSON', 'STUDENT', 'READING', 'SPELLING', 'NUMBER',
            'MANGO', 'BANANA', 'NOODLES', 'PANCAKE', 'BISCUIT', 'CHOCOLATE',
            'SANDWICH', 'PINEAPPLE', 'STRAWBERRY', 'POPCORN', 'CUCUMBER',
            'RAINBOW', 'MOUNTAIN', 'ISLAND', 'GARDEN', 'FOREST', 'JUNGLE',
            'VOLCANO', 'THUNDER', 'SUNSHINE', 'MORNING', 'EVENING', 'WEATHER',
            'WINDOW', 'BICYCLE', 'UMBRELLA', 'BLANKET', 'PILLOW', 'KITCHEN',
            'BIRTHDAY', 'HOLIDAY', 'PRESENT', 'BALLOON', 'TREASURE', 'ADVENTURE',
            'CHAMPION', 'JOURNEY', 'WHISTLE', 'PUZZLE', 'MAGNET', 'ROCKET',
        ],
    ],
];
