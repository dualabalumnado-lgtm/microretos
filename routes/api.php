<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatosFPController;


Route::get('/familias', [DatosFPController::class, 'getFamilias']);
Route::get('/familias/{familia}/ciclos', [DatosFPController::class, 'getCiclos']);
Route::get('/ciclos/{idCiclo}/modulos', [DatosFPController::class, 'getModulos']);
Route::get('/modulos/{idModulo}/ra-ce', [DatosFPController::class, 'getRaCe']);


use App\Http\Controllers\MicroretoController;

// Rutas de catálogo para lectura
Route::get('/familias', [DatosFPController::class, 'getFamilias']);
Route::get('/familias/{familia}/ciclos', [DatosFPController::class, 'getCiclos']);
Route::get('/ciclos/{idCiclo}/modulos', [DatosFPController::class, 'getModulos']);
Route::get('/modulos/{idModulo}/ra-ce', [DatosFPController::class, 'getRaCe']);

// Ruta de acción para generación con IA
Route::post('/generar-microreto', [MicroretoController::class, 'generar']);