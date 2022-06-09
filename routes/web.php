<?php 

use Illuminate\Support\Facades\Route;

Route::get('/reynolds-dbf', [Sreynoldsjr\ReynoldsDbf\Http\Controllers\DbfController::class, 'index'])->name('reynolds-dbf');
