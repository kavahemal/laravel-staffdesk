<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveExportController;
use App\Http\Controllers\LeaveReportController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('notifications/bell', [NotificationController::class, 'bell'])->name('notifications.bell');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('departments', DepartmentController::class)->except('show');
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('employees/{employee}/status', EmployeeStatusController::class)->name('employees.status');
    Route::resource('leave-types', LeaveTypeController::class)->except('show');

    Route::get('leaves/balance', [LeaveController::class, 'balance'])->name('leaves.balance');
    Route::get('leaves/record', [LeaveController::class, 'record'])->name('leaves.record');
    Route::post('leaves/record', [LeaveController::class, 'storeRecord'])->name('leaves.record.store');
    Route::resource('leaves', LeaveController::class)->only(['index', 'create', 'store']);
    Route::patch('leaves/{leave}/cancel', [LeaveController::class, 'cancel'])->name('leaves.cancel');

    Route::get('approvals', [LeaveApprovalController::class, 'index'])->name('approvals.index');
    Route::patch('leaves/{leave}/approve', [LeaveApprovalController::class, 'approve'])->name('leaves.approve');
    Route::patch('leaves/{leave}/reject', [LeaveApprovalController::class, 'reject'])->name('leaves.reject');

    Route::get('reports/leaves', [LeaveReportController::class, 'index'])->name('reports.leaves');
    Route::post('reports/leaves/export', [LeaveExportController::class, 'store'])->name('reports.leaves.export');
    Route::get('reports/exports/{export}/download', [LeaveExportController::class, 'download'])
        ->middleware('signed:relative')->name('reports.exports.download');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
