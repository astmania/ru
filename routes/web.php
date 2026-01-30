<?php

use Illuminate\Support\Facades\Route;

// Главная страница
Route::get('/', function () {
    return view('welcome');
});

// SPA маршруты - возвращаем welcome.blade.php для Vue Router
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$');
