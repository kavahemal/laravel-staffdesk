<?php

/*
| Sidebar menu. Each item: title, route, active (route pattern for highlighting),
| icon (Heroicons outline name, see heroicons.com), permission (null = every signed-in user).
*/

return [
    [
        'title' => 'Dashboard',
        'route' => 'dashboard',
        'active' => 'dashboard',
        'icon' => 'squares-2x2',
        'permission' => null,
    ],
    [
        'title' => 'Departments',
        'route' => 'departments.index',
        'active' => 'departments.*',
        'icon' => 'building-office',
        'permission' => 'departments.manage',
    ],
    [
        'title' => 'Employees',
        'route' => 'employees.index',
        'active' => 'employees.*',
        'icon' => 'users',
        'permission' => 'employees.manage',
    ],
];
