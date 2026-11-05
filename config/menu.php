<?php

/*
| Sidebar menu. Each item: title, route, active (route pattern or list of patterns for highlighting),
| icon (Heroicons outline name, see heroicons.com), permission (null = every signed-in user;
| a list = any one of them).
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
        'title' => 'My leaves',
        'route' => 'leaves.index',
        'active' => ['leaves.index', 'leaves.create'],
        'icon' => 'calendar-days',
        'permission' => 'leaves.apply',
    ],
    [
        'title' => 'Leave balance',
        'route' => 'leaves.balance',
        'active' => 'leaves.balance',
        'icon' => 'chart-pie',
        'permission' => 'leaves.apply',
    ],
    [
        'title' => 'Leave requests',
        'route' => 'approvals.index',
        'active' => ['approvals.*', 'leaves.record'],
        'icon' => 'clipboard-document-check',
        'permission' => ['leaves.approve-any', 'leaves.approve-team'],
    ],
    [
        'title' => 'Leave report',
        'route' => 'reports.leaves',
        'active' => 'reports.*',
        'icon' => 'document-chart-bar',
        'permission' => 'reports.view',
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
    [
        'title' => 'Leave types',
        'route' => 'leave-types.index',
        'active' => 'leave-types.*',
        'icon' => 'tag',
        'permission' => 'leave-types.manage',
    ],
    [
        'title' => 'Settings',
        'route' => 'settings.edit',
        'active' => 'settings.*',
        'icon' => 'cog-6-tooth',
        'permission' => 'settings.manage',
    ],
];
