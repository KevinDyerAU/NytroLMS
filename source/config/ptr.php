<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PTR (Pre-Training Review) Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the PTR assessment system.
    |
    */

    'quiz_id' => intval(env('PTR_QUIZ_ID', 11112)),

    'course_id' => intval(env('PTR_COURSE_ID', 11112)),

    'lesson_id' => intval(env('PTR_LESSON_ID', 11112)),

    'topic_id' => intval(env('PTR_TOPIC_ID', 11112)),

    'enforcement' => env('PTR_ENFORCEMENT', true),

    'skip_ptr' => env('PTR_SKIP_PTR', false),

    /*
    |--------------------------------------------------------------------------
    | Implementation Date
    |--------------------------------------------------------------------------
    |
    | Enrolments created before this date will be grandfathered (exempt from PTR).
    | Format: Y-m-d (e.g., '2025-09-01')
    |
    */
    'implementation_date' => env('PTR_IMPLEMENTATION_DATE', '2025-09-01'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Categories
    |--------------------------------------------------------------------------
    |
    | Courses with these categories will skip PTR quiz and show "PTR Skipped" message.
    |
    */
    'excluded_categories' => [
        'non_accredited',
        'accelerator',
    ],
];
