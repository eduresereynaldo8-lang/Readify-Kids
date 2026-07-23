<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ReadingMaterialController;
use App\Http\Controllers\VoiceRecordingController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\GameController;

// ── Public routes (no login needed) ──────────────────────────
Route::get('/',        [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
Route::get('/logout',  [AuthController::class, 'logout'])->name('logout');

// Teacher registration
Route::get('/register',  [AuthController::class, 'showTeacherRegister'])->name('register');
Route::post('/register', [AuthController::class, 'registerTeacher'])->name('register.post');

// ── Teacher routes (must be logged in as teacher) ─────────────
Route::middleware(['auth', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'teacherDashboard'])->name('dashboard');

    // Student management
    Route::get('/students',          [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create',   [StudentController::class, 'create'])->name('students.create');
    Route::post('/students',         [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{id}',     [StudentController::class, 'show'])->name('students.show');
    Route::get('/students/{id}/edit',[StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{id}',     [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{id}',  [StudentController::class, 'destroy'])->name('students.destroy');

    // Activity management (unified list & show)
    Route::get('/activities',           [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/{id}',      [ActivityController::class, 'show'])->name('activities.show');
    Route::delete('/activities/{id}',   [ActivityController::class, 'destroy'])->name('activities.destroy');

    // Read Aloud activity creation
    Route::get('/activities/create/readaloud',    [ActivityController::class, 'createReadAloud'])->name('activities.create.readaloud');
    Route::post('/activities/store/readaloud',    [ActivityController::class, 'storeReadAloud'])->name('activities.store.readaloud');
    Route::get('/activities/{id}/edit/readaloud', [ActivityController::class, 'editReadAloud'])->name('activities.edit.readaloud');
    Route::put('/activities/{id}/update/readaloud',[ActivityController::class, 'updateReadAloud'])->name('activities.update.readaloud');

    // Battle activity creation
    Route::get('/activities/create/battle',    [ActivityController::class, 'createBattle'])->name('activities.create.battle');
    Route::post('/activities/store/battle',    [ActivityController::class, 'storeBattle'])->name('activities.store.battle');
    Route::get('/activities/{id}/edit/battle', [ActivityController::class, 'editBattle'])->name('activities.edit.battle');
    Route::put('/activities/{id}/update/battle',[ActivityController::class, 'updateBattle'])->name('activities.update.battle');

    // Evaluations
    Route::get('/evaluations',      [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::get('/evaluations/{id}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('/evaluations',     [EvaluationController::class, 'store'])->name('evaluations.store');

    // Reading materials
    Route::get('/materials',        [ReadingMaterialController::class, 'index'])->name('materials.index');
    Route::post('/materials',       [ReadingMaterialController::class, 'store'])->name('materials.store');
    Route::delete('/materials/{id}',[ReadingMaterialController::class, 'destroy'])->name('materials.destroy');

    // Progress & leaderboard
    Route::get('/progress',     [DashboardController::class, 'progress'])->name('progress');
    Route::get('/leaderboard',  [LeaderboardController::class, 'index'])->name('leaderboard');

    
});

// ── Student routes (must be logged in as student) ─────────────
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {

    Route::get('/dashboard',    [DashboardController::class, 'studentDashboard'])->name('dashboard');

    // Activities
    Route::get('/activities',        [ActivityController::class, 'studentIndex'])->name('activities.index');
    Route::get('/activities/{id}',   [ActivityController::class, 'studentShow'])->name('activities.show');
    Route::post('/activities/{id}/submit', [ActivityController::class, 'submit'])->name('activities.submit');

    // Voice recordings
    Route::get('/read-aloud',             [VoiceRecordingController::class, 'index'])->name('readaloud.index');
    Route::get('/read-aloud/{id}',        [VoiceRecordingController::class, 'show'])->name('readaloud.show');
    Route::post('/read-aloud/{id}/upload',[VoiceRecordingController::class, 'upload'])->name('readaloud.upload');

    // Progress & leaderboard
    Route::get('/progress',    [DashboardController::class, 'studentProgress'])->name('progress');
    Route::get('/leaderboard', [LeaderboardController::class, 'studentIndex'])->name('leaderboard');

    // Game routes
Route::get('/game',                    [GameController::class, 'index'])->name('game.index');
Route::get('/game/start/{activityId}', [GameController::class, 'start'])->name('game.start');
Route::get('/game/battle/{sessionId}', [GameController::class, 'battle'])->name('game.battle');
Route::post('/game/battle/{sessionId}/round', [GameController::class, 'submitRound'])->name('game.submitRound');
});