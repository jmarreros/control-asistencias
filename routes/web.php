<?php

use App\Http\Controllers\AccessLogController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPlanController;
use App\Http\Controllers\StudentPortalController;
use Illuminate\Support\Facades\Route;

// Portal alumno — búsqueda pública por DNI (sin autenticación)
Route::get('/', [StudentPortalController::class, 'publicSearch'])->name('student.search');
Route::get('/student/lookup', [StudentPortalController::class, 'lookup'])->name('student.lookup');

// --- Autenticación PIN (admin) ---
Route::get('/login', [PinController::class, 'show'])->name('login');
Route::post('/login', [PinController::class, 'authenticate'])
    ->middleware('throttle:5,1')
    ->name('login.post');
Route::post('/logout', [PinController::class, 'logout'])->name('logout');

// --- Rutas admin protegidas por PIN ---
Route::middleware(['check.pin', 'session.timeout', 'log.access'])->group(function () {

    Route::get('/admin', [DashboardController::class, 'index'])->name('dashboard');

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
    Route::get('attendance/student/{student}', [AttendanceController::class, 'takeByStudent'])->name('attendance.take-student');
    Route::get('attendance/{clase}/take', [AttendanceController::class, 'take'])->name('attendance.take');
    Route::post('attendance/{clase}/save', [AttendanceController::class, 'save'])->name('attendance.save');
    Route::post('attendance/{clase}/toggle', [AttendanceController::class, 'toggle'])->name('attendance.toggle');
    Route::post('attendance/{clase}/add-student', [AttendanceController::class, 'addStudent'])->name('attendance.add-student');

    // Configuración
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    // Importación
    Route::get('import', [ImportController::class, 'show'])->name('import.show');
    Route::post('import', [ImportController::class, 'import'])->name('import.process');

    // Logs de acceso
    Route::get('logs', [AccessLogController::class, 'index'])->name('logs.index');
    Route::delete('logs', [AccessLogController::class, 'destroy'])->name('logs.destroy');

    // Reportes
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/students/export', [ReportController::class, 'studentsExport'])->name('reports.students.export');
    Route::get('reports/earnings', [ReportController::class, 'earnings'])->name('reports.earnings');
    Route::get('reports/earnings/export', [ReportController::class, 'earningsExport'])->name('reports.earnings.export');
    Route::get('reports/clase/{clase}', [ReportController::class, 'byClase'])->name('reports.clase');
    Route::get('reports/clase/{clase}/student/{student}', [ReportController::class, 'byClaseStudent'])->name('reports.clase.student');
    Route::get('reports/student/{student}', [ReportController::class, 'byStudent'])->name('reports.student');
});
