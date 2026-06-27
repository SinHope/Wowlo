<?php

/*
|--------------------------------------------------------------------------
| Multiplication Rabbit — levels
|--------------------------------------------------------------------------
| Single source of truth for the Multiplication game (see
| docs/multiplication-game.md).
|
| Multiplication questions are GENERATED, not listed, so a level is just the
| digit range of each factor. The client generates questions within these
| ranges; the server uses the SAME ranges to validate every submitted factor
| and to mark the round (a * b) — it never trusts a client-submitted score.
|
| A level present here is PLAYABLE. The virtual "Mixed" level (see
| mixed_level) draws each question from a random real level.
|
| 'a' / 'b' are inclusive [min, max] ranges. 1 by 1 deliberately includes ×1.
*/

return [

    // The round sizes the student may choose (the 10/15/20 modal). The server
    // clamps the request to this set; default_count is used if it's missing.
    'question_counts' => [10, 15, 20],
    'default_count'   => 10,

    // The virtual mixed level's label (drawn at random across the levels below).
    'mixed_level' => 'Mixed',

    'levels' => [
        '1 by 1 digit'  => ['a' => [1, 9],     'b' => [1, 9]],
        '2 by 2 digits' => ['a' => [10, 99],   'b' => [10, 99]],
        '3 by 2 digits' => ['a' => [100, 999], 'b' => [10, 99]],
        '3 by 3 digits' => ['a' => [100, 999], 'b' => [100, 999]],
    ],
];
