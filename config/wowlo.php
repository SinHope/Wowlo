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

    // Payment details are now per-tutor (users.payment_instructions, set in the
    // tutor's profile) and flow into their WhatsApp bills — see App\Services\BillMessage.

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
    /*
    |--------------------------------------------------------------------------
    | Homework statuses
    |--------------------------------------------------------------------------
    | The verdict lifecycle for a homework. 'pending' is the default; the
    | STUDENT may only claim 'submitted' (an "I've done this" claim awaiting
    | the tutor's check); the TUTOR owns the authoritative 'done' / 'not_done'
    | verdict. "Overdue" is NOT stored here — it's derived from due_date (see
    | Homework::isOverdue()). Key = stored value, value = display label. This
    | list mirrors the Postgres homeworks_status_check constraint.
    */
    'homework_statuses' => [
        'pending'   => 'Pending',
        'submitted' => 'Awaiting check',
        'done'      => 'Done',
        'not_done'  => 'Not done',
    ],

    /*
    |--------------------------------------------------------------------------
    | Answer sheets (Resources)
    |--------------------------------------------------------------------------
    | "OAS / answer sheet" types and their status lifecycle. A sheet is either
    | an MCQ/OAS sheet (numbered rows of options) or a short-answers sheet
    | (numbered free-text rows). Status: 'sent' (tutor → student, awaiting fill),
    | 'submitted' (awaiting the tutor's manual marking), 'marked' (done). Both
    | lists mirror the Postgres CHECK constraints on the answer_sheets table —
    | add a value here and you MUST update the matching constraint via migration.
    */
    'answer_sheet_types' => [
        'mcq'          => 'MCQ / OAS Sheet',
        'short_answer' => 'Short Answers Sheet',
    ],

    'answer_sheet_statuses' => [
        'sent'      => 'Awaiting student',
        'submitted' => 'Awaiting marking',
        'marked'    => 'Marked',
    ],

    /*
    |--------------------------------------------------------------------------
    | Banner notification audiences
    |--------------------------------------------------------------------------
    | Who an app-wide banner (super_admin only) is shown to. Single source of
    | truth for the compose radio buttons, the Rule::in validation, and
    | Banner::visibleTo(). Mirrors the Postgres banners_audience_check
    | constraint — add a value here and you MUST update that constraint via
    | migration. Key = stored value, value = display label.
    */
    'banner_audiences' => [
        'everyone' => 'Everyone (tutors + students)',
        'tutors'   => 'Tutors only',
        'students' => 'Students only',
    ],

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
