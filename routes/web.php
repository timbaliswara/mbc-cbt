<?php

use App\Models\ExamAttempt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('student.token');
});

Route::get('/admin/login', fn () => view('pages.login'))->name('login');

Route::post('/admin/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => view('pages.admin-dashboard'))->name('dashboard');
    Route::get('/exams', fn () => view('pages.admin-exams'))->name('exams');
    Route::get('/questions', fn () => view('pages.admin-questions'))->name('questions');
    Route::get('/tokens', fn () => view('pages.admin-tokens'))->name('tokens');
    Route::get('/results', fn () => view('pages.admin-results'))->name('results');
    Route::get('/results/{attempt}', fn (ExamAttempt $attempt) => view('pages.admin-result-detail', compact('attempt')))->name('results.show');
    Route::get('/guide', fn () => view('pages.admin-guide'))->name('guide');
});

Route::get('/ujian', fn () => view('pages.student-token'))->name('student.token');
Route::get('/ujian/{attempt}', fn (ExamAttempt $attempt) => view('pages.student-exam', compact('attempt')))->name('student.exam');
Route::get('/hasil/{attempt}', fn (ExamAttempt $attempt) => view('pages.student-result', compact('attempt')))->name('student.result');
