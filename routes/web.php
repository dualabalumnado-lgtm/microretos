<?php

use Illuminate\Support\Facades\Route;

// Cualquier ruta que el usuario escriba, le devolvemos la vista "welcome"
// y dejamos que Vue Router decida qué componente mostrar.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');