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
    | How many of the user's own latest leave requests the dashboard lists.
    */
    'dashboard_recent_leaves' => 5,

    /*
    | Names shown per day on the Leave calendar before "+N more".
    */
    'calendar_names_per_day' => 3,

    /*
    | List search boxes: longer input is cut to this many characters.
    */
    'search_max_length' => 100,

    /*
    | How many notifications the header bell lists (newest first).
    */
    'header_notifications' => 8,

    /*
    | How often an open page asks for new notifications, in seconds (paused while the tab is hidden).
    */
    'notification_poll_seconds' => 30,

    /*
    | How long the download link in an "export ready" notification works, in minutes.
    */
    'export_link_minutes' => 60 * 24,

    /*
    | API: days an access token from /api/v1/login works, and requests per minute
    | (per user for the API, per email + IP for login attempts).
    */
    'api_token_days' => 30,
    'api_rate_limit' => 60,
    'api_login_rate_limit' => 5,

    /*
    | Display format for dates in views and exports until Admin picks one on the Settings page,
    | and the formats Admin can pick from (PHP date tokens – flatpickr uses the same ones).
    */
    'date_format' => 'd M Y',
    'date_formats' => ['d M Y', 'd/m/Y', 'm/d/Y', 'Y-m-d'],

    /*
    | Largest logo Admin can upload on the Settings page, in KB (also used as the favicon).
    */
    'logo_max_kb' => 1024,

    /*
    | Prefills the login form – only when APP_ENV=local, never on a real server.
    */
    'dev_login' => [
        'email' => env('DEV_LOGIN_EMAIL'),
        'password' => env('DEV_LOGIN_PASSWORD'),
    ],

];
