<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PullRequestController;


Route::get('/', function () {
    return view('login');
});

Route::get('/pull-requests/{ownerName}/{repoName}', [PullRequestController::class, 'Main']);
