<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Lecturer;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Leaving/switching impersonation must be reachable while authenticated as the student.
Route::post('/impersonate/stop', [Lecturer\ImpersonationController::class, 'stop'])
    ->name('impersonate.stop')->middleware('auth');
Route::post('/impersonate/switch/{student}', [Lecturer\ImpersonationController::class, 'switchTo'])
    ->name('impersonate.switch')->middleware('auth');
Route::get('/impersonate/students', [Lecturer\ImpersonationController::class, 'pickerData'])
    ->name('impersonate.students')->middleware('auth');

// ── Student ───────────────────────────────────────────────────────
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [Student\DashboardController::class, 'profile'])->name('profile');
    Route::patch('/profile', [Student\DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/record', [Student\RecordController::class, 'index'])->name('record');

    Route::post('/case/{forensicCase}/unlock', [Student\DashboardController::class, 'unlockCase'])->name('case.unlock');
    Route::get('/case/{forensicCase}', [Student\CaseController::class, 'show'])->name('case.show');
    Route::post('/case/{forensicCase}/answer', [Student\CaseController::class, 'saveAnswer'])->name('case.save-answer');
    Route::post('/case/{forensicCase}/log', [Student\CaseController::class, 'logActivity'])->name('case.log');
    Route::get('/case/{forensicCase}/questions.pdf', [Student\ReportController::class, 'downloadQuestions'])->name('case.questions');

    Route::get('/case/{forensicCase}/report', [Student\ReportController::class, 'show'])->name('report.show');
    Route::post('/case/{forensicCase}/report', [Student\ReportController::class, 'store'])->name('report.submit');
    Route::post('/case/{forensicCase}/report/draft', [Student\ReportController::class, 'saveDraft'])->name('report.draft');
});

// ── Lecturer ──────────────────────────────────────────────────────
Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::get('/dashboard', [Lecturer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [Lecturer\DashboardController::class, 'profile'])->name('profile');
    Route::patch('/profile', [Lecturer\DashboardController::class, 'updateProfile'])->name('profile.update');

    Route::get('/students', [Lecturer\DashboardController::class, 'students'])->name('students');
    Route::patch('/students/{student}/assign', [Lecturer\DashboardController::class, 'assignStudent'])->name('students.assign');
    Route::delete('/students/{student}/assign', [Lecturer\DashboardController::class, 'unassignStudent'])->name('students.unassign');
    Route::get('/gradebook', [Lecturer\GradebookController::class, 'index'])->name('gradebook');
    Route::get('/gradebook/export', [Lecturer\GradebookController::class, 'export'])->name('gradebook.export');

    Route::get('/progress', [Lecturer\ProgressController::class, 'index'])->name('progress');
    Route::get('/progress/{student}', [Lecturer\ProgressController::class, 'show'])->name('progress.show');

    Route::post('/view-as/{student}', [Lecturer\ImpersonationController::class, 'start'])->name('impersonate.start');

    Route::get('/case/create', [Lecturer\CaseController::class, 'create'])->name('case.create');
    Route::post('/case', [Lecturer\CaseController::class, 'store'])->name('case.store');
    Route::post('/case/generate-ai', [Lecturer\CaseController::class, 'generateAI'])->name('case.generate-ai');
    Route::get('/case/{forensicCase}/edit', [Lecturer\CaseController::class, 'edit'])->name('case.edit');
    Route::put('/case/{forensicCase}', [Lecturer\CaseController::class, 'update'])->name('case.update');
    Route::delete('/case/{forensicCase}', [Lecturer\CaseController::class, 'destroy'])->name('case.destroy');
    Route::patch('/case/{forensicCase}/toggle-lock', [Lecturer\CaseController::class, 'toggleLock'])->name('case.toggle-lock');
    Route::patch('/case/{forensicCase}/toggle-publish', [Lecturer\CaseController::class, 'togglePublish'])->name('case.toggle-publish');
    Route::get('/case/{forensicCase}/questions.pdf', [Lecturer\CaseController::class, 'downloadQuestionPdf'])->name('case.questions');

    Route::get('/case/{forensicCase}/reports', [Lecturer\ReportController::class, 'index'])->name('report.index');
    Route::get('/case/{forensicCase}/reports/{enrollment}', [Lecturer\ReportController::class, 'show'])->name('report.show');
    Route::patch('/case/{forensicCase}/reports/{enrollment}/grade', [Lecturer\ReportController::class, 'grade'])->name('report.grade');
    Route::post('/case/{forensicCase}/reports/{enrollment}/ai-evaluate', [Lecturer\ReportController::class, 'aiEvaluate'])->name('report.ai-evaluate');
    Route::get('/case/{forensicCase}/reports/{enrollment}/answer.pdf', [Lecturer\ReportController::class, 'downloadAnswer'])->name('report.answer');
    Route::get('/case/{forensicCase}/reports/{enrollment}/pdf', [Lecturer\ReportController::class, 'exportPdf'])->name('report.export-pdf');
});
