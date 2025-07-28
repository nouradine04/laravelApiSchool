<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\SubjectController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:api')->group(function () {
    // Authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Dashboard (Admin seulement)
   // Route::middleware('role:admin')->group(function () { });
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        Route::get('/dashboard/class-stats', [DashboardController::class, 'getClassStats']);
        Route::get('/dashboard/grade-stats', [DashboardController::class, 'getGradeStats']);
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity']);
        Route::get('/dashboard/monthly-stats', [DashboardController::class, 'getMonthlyStats']);


    // Gestion des étudiants (Admin seulement)
   // Route::middleware('role:admin')->group(function () {  });
        Route::apiResource('students', StudentController::class);


    // Gestion des enseignants (Admin seulement)
   // Route::middleware('role:admin')->group(function () { });
        Route::apiResource('teachers', TeacherController::class);
        Route::apiResource('classes', ClasseController::class);
        Route::apiResource('subjects', SubjectController::class);


    // Gestion des notes
  //  Route::middleware('role:admin|teacher')->group(function () { });
        Route::apiResource('grades', GradeController::class);


    // Consultation des notes d'un étudiant
    Route::get('/students/{student}/grades', [GradeController::class, 'getStudentGrades']);
       // ->middleware('role:admin|teacher|parent|student');

    // Gestion des bulletins
    // Route::middleware('role:admin')->group(function () { });
        Route::post('/report-cards/generate', [ReportCardController::class, 'generate']);


    // Consultation des bulletins
    Route::get('/report-cards', [ReportCardController::class, 'index']) ;
      //  ->middleware('role:admin|parent|student');

    Route::get('/report-cards/{reportCard}', [ReportCardController::class, 'show']) ;
       // ->middleware('role:admin|parent|student');

    Route::get('/report-cards/{reportCard}/download', [ReportCardController::class, 'download']);
       // ->middleware('role:admin|parent|student');
});

Route::get('/test', function () {
    return response()->json(['message' => 'API Laravel fonctionne !']);
});

