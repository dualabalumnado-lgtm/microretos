<?php

use Illuminate\Support\Facades\Route;

// Le decimos a Laravel: "Cualquier cosa que escriban en la URL, 
// mándala a la vista 'welcome' y deja que Vue.js decida qué pantalla mostrar"
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');