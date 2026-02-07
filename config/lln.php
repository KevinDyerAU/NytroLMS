<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LLN (Language, Literacy and Numeracy) Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the LLN assessment system.
    |
    */

    'quiz_id' => intval(env('LLN_QUIZ_ID', 11111)),

    'course_id' => intval(env('LLN_COURSE_ID', 11111)),

    'lesson_id' => intval(env('LLN_LESSON_ID', 11111)),

    'topic_id' => intval(env('LLN_TOPIC_ID', 11111)),

    'enforcement' => env('LLN_ENFORCEMENT', true),

    'skip_lln' => env('LLN_SKIP_LLN', false),

    /*
    |--------------------------------------------------------------------------
    | Excluded Categories
    |--------------------------------------------------------------------------
    |
    | Courses with these categories will skip all LLND logic and behave normally.
    |
    */
    'excluded_categories' => [
        'non_accredited',
        'accelerator',
    ],
];
