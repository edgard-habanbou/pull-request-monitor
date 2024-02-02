<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\RepositoryController;
use App\Models\Repository;

Route::get('/', function () {
    if (auth()->check()) {
        return view('home', [
            'repositories' => Repository::all()
        ]);
    }
    return view('home');
});

Route::get('/pullrequests', [PullRequestController::class, 'Main']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/login', [AuthController::class, 'login']);

// Repositories routes
Route::post('/add-repo', [RepositoryController::class, 'store']);
Route::post('/delete-repo/{id}', [RepositoryController::class, 'delete']);
