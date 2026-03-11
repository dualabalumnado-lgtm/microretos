<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    // Buscamos  archivo de Vue compilado
    $path = public_path('index.html');
    
    // Si existe, se lo mandamos al usuario
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    
    return "Error: No se encuentra el frontend compilado.";
})->where('any', '.*');