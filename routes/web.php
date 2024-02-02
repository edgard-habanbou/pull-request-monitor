<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PullRequestController;
use App\Http\Controllers\RepositoryController;
use App\Models\Repository;

Route::get('/', function () {

    return view('home', [
        'repositories' => Repository::all()
    ]);
});

Route::get('/pull-requests/{ownerName}/{repoName}', [PullRequestController::class, 'Main']);

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/login', [AuthController::class, 'login']);

// Repositories routes
Route::post('/add-repo', [RepositoryController::class, 'store']);
