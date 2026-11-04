<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeStatusController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::resource('departments', DepartmentController::class)->except('show');
    Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('employees/{employee}/status', EmployeeStatusController::class)->name('employees.status');
});

require __DIR__.'/auth.php';
