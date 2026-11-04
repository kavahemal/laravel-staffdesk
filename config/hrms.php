<?php

use Illuminate\Support\Carbon;

return [

    /*
    | Default work week until Admin saves one on the Settings page.
    | Keys are Carbon day-of-week constants; values: full (1 leave day), half (0.5), off (0).
    */
    'work_week' => [
        Carbon::MONDAY => 'full',
        Carbon::TUESDAY => 'full',
        Carbon::WEDNESDAY => 'full',
        Carbon::THURSDAY => 'full',
        Carbon::FRIDAY => 'full',
        Carbon::SATURDAY => 'full',
        Carbon::SUNDAY => 'off',
    ],

    /*
    | Employee codes are generated as prefix + zero-padded number, e.g. EMP-0001.
    */
    'employee_code_prefix' => 'EMP-',
    'employee_code_digits' => 4,

    /*
    | Roles that can be given to an employee. Admin is the system account and has no employee profile.
    */
    'employee_roles' => ['HR', 'Manager', 'Employee'],

    /*
    | Rows per page in every list.
    */
    'per_page' => 15,

    /*
    | List search boxes: longer input is cut to this many characters.
    */
    'search_max_length' => 100,

    /*
    | How many notifications the header bell lists (newest first).
    */
    'header_notifications' => 8,

    /*
    | Display format for dates in views and exports (PHP date format).
    */
    'date_format' => 'd M Y',

    /*
    | Prefills the login form – only when APP_ENV=local, never on a real server.
    */
    'dev_login' => [
        'email' => env('DEV_LOGIN_EMAIL'),
        'password' => env('DEV_LOGIN_PASSWORD'),
    ],

];
