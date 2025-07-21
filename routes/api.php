<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\ParserController;

Route::apiResource('books', BookController::class)->only(['index']);
Route::apiResource('authors', AuthorController::class)->only(['index']);
Route::apiResource('genres', GenreController::class)->only(['index']);

Route::post('parse', [ParserController::class, 'parse']);
Route::post('books/search', [BookController::class, 'search']);