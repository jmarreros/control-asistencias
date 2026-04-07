<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentPlanController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Autenticación PIN
Route::get('/login', [PinController::class, 'show'])->name('login');
Route::post('/login', [PinController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [PinController::class, 'logout'])->name('logout');

// Rutas protegidas por PIN
Route::middleware('check.pin')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Alumnos
    Route::resource('students', StudentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Planes de alumnos
    Route::get('students/{student}/plans', [StudentPlanController::class, 'index'])->name('students.plans.index');
    Route::post('students/{student}/plans', [StudentPlanController::class, 'store'])->name('students.plans.store');
    Route::delete('students/{student}/plans/{plan}', [StudentPlanController::class, 'destroy'])->name('students.plans.destroy');

    // Clases
    Route::resource('clases', ClaseController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    // Matrícula
    Route::get('clases/{clase}/enroll', [EnrollmentController::class, 'edit'])->name('clases.enroll');
    Route::post('clases/{clase}/enroll', [EnrollmentController::class, 'update'])->name('clases.enroll.update');

    // Asistencias
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/{clase}/take', [AttendanceController::class, 'take'])->name('attendance.take');
    Route::post('attendance/{clase}/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::post('attendance/{clase}/toggle', [AttendanceController::class, 'toggle'])->name('attendance.toggle');
    Route::post('attendance/{clase}/add-student', [AttendanceController::class, 'addStudent'])->name('attendance.add-student');

    // Configuración
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    // Reportes
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/earnings', [ReportController::class, 'earnings'])->name('reports.earnings');
    Route::get('reports/earnings/export', [ReportController::class, 'earningsExport'])->name('reports.earnings.export');
    Route::get('reports/clase/{clase}', [ReportController::class, 'byClase'])->name('reports.clase');
    Route::get('reports/student/{student}', [ReportController::class, 'byStudent'])->name('reports.student');
});
