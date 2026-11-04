<?php

use Illuminate\Support\Carbon;

return [

    /*
    | Days that never count as leave days (Carbon day-of-week constants).
    */
    'week_off' => [Carbon::SUNDAY],

    /*
    | Employee codes are generated as prefix + zero-padded number, e.g. EMP-0001.
    */
    'employee_code_prefix' => 'EMP-',
    'employee_code_digits' => 4,

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
