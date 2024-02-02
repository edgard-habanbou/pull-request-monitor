<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PullRequestController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/pull-requests', [PullRequestController::class, 'Main']);
