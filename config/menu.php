<?php

/*
| Sidebar menu. Each item: title, route, active (route pattern for highlighting),
| icon (name in components/icon.blade.php), permission (null = every signed-in user).
*/

return [
    [
        'title' => 'Dashboard',
        'route' => 'dashboard',
        'active' => 'dashboard',
        'icon' => 'dashboard',
        'permission' => null,
    ],
];
