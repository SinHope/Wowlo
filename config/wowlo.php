<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fee view password
    |--------------------------------------------------------------------------
    | Parents must enter this shared password to unlock the fee/payment
    | section. Stored in .env so it never lives in the codebase.
    */
    'fee_view_password' => env('FEE_VIEW_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | PayNow number
    |--------------------------------------------------------------------------
    | Appended to the generated WhatsApp billing message so parents know
    | where to send payment. Not a secret (it is shared with parents anyway).
    */
    'paynow_number' => env('PAYNOW_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Default currency
    |--------------------------------------------------------------------------
    */
    'currency' => env('WOWLO_CURRENCY', 'SGD'),

    /*
    |--------------------------------------------------------------------------
    | Contact inbox
    |--------------------------------------------------------------------------
    | Where the public "Contact us" form delivers messages. Defaults to the
    | owner's email; override per-environment with CONTACT_EMAIL in .env.
    */
    'contact_email' => env('CONTACT_EMAIL', 'nasmerfontanilla@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Promote PWA install
    |--------------------------------------------------------------------------
    | Whether to show the "Install app" button + the install toast. A PWA is
    | bound to its ORIGIN: anyone who installs from a temporary domain (e.g.
    | wowlo.onrender.com) must reinstall once we move to the permanent domain.
    | (Their data is always safe — it lives server-side, not on the device.)
    | So keep this FALSE while on the throwaway subdomain, and flip it TRUE the
    | day we cut over to the permanent domain, so installs happen only once.
    | Defaults TRUE for local dev so the button is visible while building.
    */
    'promote_install' => env('PWA_PROMOTE_INSTALL', true),

    /*
    |--------------------------------------------------------------------------
    | Levels
    |--------------------------------------------------------------------------
    | Canonical list of school levels. Exam papers are organised first by
    | level, then subject, then year. Single source of truth for the upload
    | dropdown, validation, and grouping order. Order here is display order.
    */
    'levels' => [
        'Primary 1',
        'Primary 2',
        'Primary 3',
        'Primary 4',
        'Primary 5',
        'Primary 6',
        'Secondary 1',
        'Secondary 2',
        'Secondary 3',
        'Secondary 4',
        'Secondary 5',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam types
    |--------------------------------------------------------------------------
    | Singapore primary/secondary assessment schedule. Used by the quiz
    | create form and validation. Key = stored value, value = display label.
    */
    'exam_types' => [
        'WA1'                => 'WA1 (Weighted Assessment 1)',
        'MidYear'            => 'Mid-Year Exam',
        'WA2'                => 'WA2 (Weighted Assessment 2)',
        'EndYear'            => 'End-Year Exam',
        'Quiz'               => 'Quiz',
        'PeriodicAssessment' => 'Periodic Assessment',
        'TopicEvaluation'    => 'Topic Evaluation',
        'PSLE'               => 'PSLE',
        'PrelimPSLE'         => 'Prelim (PSLE)',
        'NLevel'             => 'N Level',
        'PrelimNLevel'       => 'Prelim (N Level)',
        'OLevel'             => 'O Level',
        'PrelimOLevel'       => 'Prelim (O Level)',
        'CompetitionPrep'    => 'Competition Preparation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subjects
    |--------------------------------------------------------------------------
    | Canonical list of subjects. Single source of truth for the exam-paper
    | upload dropdown, its validation rule, and the student filter. Add a
    | subject here and it appears everywhere. Order here is display order.
    */
    'subjects' => [
        'English',
        'Mathematics',
        'Science',
        'Malay',
        'Chinese',
        'Tamil',
        'Hindi',
        'Music',
        'Art',
        'G1 Mathematics',
        'G2 Mathematics',
        'G3 Mathematics',
        'G1 English',
        'G2 English',
        'G3 English',
        'G1 Science',
        'G2 Science',
        'G3 Science',
        'Geography',
        'History',
        'Social Studies',
        'Combined Science(Chemistry/Physics)',
        'Pure Chemistry',
        'Pure Physics',
        'IP Subject(English/Math/Science)',
        'Web Development',
        'Others',
    ],

];
