<?php

/*
| Sidebar menu, in display order. Each item: group (section heading – items of one group stay together),
| title, route, active (route pattern or list of patterns for highlighting), icon (Heroicons outline
| name, see heroicons.com), permission (null = every signed-in user; a list = any one of them).
| A section is hidden when the user can see none of its items.
*/

return [
    [
        'group' => 'Menu',
        'title' => 'Dashboard',
        'route' => 'dashboard',
        'active' => 'dashboard',
        'icon' => 'squares-2x2',
        'permission' => null,
    ],
    [
        'group' => 'Menu',
        'title' => 'Leave calendar',
        'route' => 'leaves.calendar',
        'active' => 'leaves.calendar',
        'icon' => 'calendar',
        'permission' => ['leaves.apply', 'leaves.approve-any', 'leaves.approve-team'],
    ],
    [
        'group' => 'My leave',
        'title' => 'My leaves',
        'route' => 'leaves.index',
        'active' => ['leaves.index', 'leaves.create'],
        'icon' => 'calendar-days',
        'permission' => 'leaves.apply',
    ],
    [
        'group' => 'My leave',
        'title' => 'Leave balance',
        'route' => 'leaves.balance',
        'active' => 'leaves.balance',
        'icon' => 'chart-pie',
        'permission' => 'leaves.apply',
    ],
    [
        'group' => 'Leave management',
        'title' => 'Leave requests',
        'route' => 'approvals.index',
        'active' => ['approvals.*', 'leaves.record'],
        'icon' => 'clipboard-document-check',
        'permission' => ['leaves.approve-any', 'leaves.approve-team'],
    ],
    [
        'group' => 'Leave management',
        'title' => 'Leave report',
        'route' => 'reports.leaves',
        'active' => 'reports.*',
        'icon' => 'document-chart-bar',
        'permission' => 'reports.view',
    ],
    [
        'group' => 'Leave management',
        'title' => 'Leave types',
        'route' => 'leave-types.index',
        'active' => 'leave-types.*',
        'icon' => 'tag',
        'permission' => 'leave-types.manage',
    ],
    [
        'group' => 'Organisation',
        'title' => 'Employees',
        'route' => 'employees.index',
        'active' => 'employees.*',
        'icon' => 'users',
        'permission' => 'employees.manage',
    ],
    [
        'group' => 'Organisation',
        'title' => 'Departments',
        'route' => 'departments.index',
        'active' => 'departments.*',
        'icon' => 'building-office',
        'permission' => 'departments.manage',
    ],
    [
        'group' => 'Organisation',
        'title' => 'Settings',
        'route' => 'settings.edit',
        'active' => 'settings.*',
        'icon' => 'cog-6-tooth',
        'permission' => 'settings.manage',
    ],
];
