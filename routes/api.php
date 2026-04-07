<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatosFPController;
use App\Http\Controllers\MicroretoIAController;
use App\Http\Controllers\AdminAuthController;

/*
|--------------------------------------------------------------------------
| API Routes - DuaLab Studio
|--------------------------------------------------------------------------
*/

// --- RUTAS PÚBLICAS (sin autenticación) ---

// Biblioteca pública: throttle para evitar scraping masivo
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/microretos',         [MicroretoIAController::class, 'index']);
    Route::get('/microretos/{id}',    [MicroretoIAController::class, 'show']);
    Route::get('/familias',           [DatosFPController::class, 'getFamilias']);
});

// Datos académicos (ciclos, módulos) — throttle estándar
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/familias/{familia}/ciclos',  [DatosFPController::class, 'getCiclos']);
    Route::get('/ciclos/{idCiclo}/modulos',   [DatosFPController::class, 'getModulos']);
    Route::get('/modulos/{idModulo}/ra-ce',   [DatosFPController::class, 'getRaCe']);
});

// Auth pública
Route::post('/admin/login', [AdminAuthController::class, 'login']);


// --- RUTAS PROTEGIDAS (requieren token Sanctum) ---

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);

    // Empresas — datos completos solo para usuarios autenticados
    Route::get('/empresas',              [DatosFPController::class, 'getEmpresas']);
    Route::get('/empresas/{id}/familias',[DatosFPController::class, 'getFamiliasPorEmpresa']);
    Route::post('/empresas',             [DatosFPController::class, 'guardarEmpresa']);
    Route::put('/empresas/{id}',         [DatosFPController::class, 'actualizarEmpresa']);

    // Generación IA: throttle estricto (5 generaciones/minuto por usuario)
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/generar-microreto', [MicroretoIAController::class, 'generar']);
    });

    // Guardado y borrado de microretos
    Route::post('/guardar-microreto-bd',      [MicroretoIAController::class, 'guardarEnBD']);
    Route::post('/guardar-microretos-lote',   [MicroretoIAController::class, 'guardarLote']);
    Route::delete('/microretos/{id}',         [MicroretoIAController::class, 'destroy']);

});

// -------- IMPORTACIONES (protegidas con auth) ---------
Route::middleware('auth:sanctum')->get('/importar-excel', function () {
    $path = storage_path('app/agraria.csv'); ///////////////////////////////////////
    if (!file_exists($path)) return "No se encuentra el archivo agraria.csv en storage/app/";

    $familiaImport = 'Agraria'; /////////////////////////////////////
    $familiaId     = \App\Models\Familia::where('nombre', $familiaImport)->value('id');

    $file     = fopen($path, 'r');
    $contador = 0;

    // Ignoramos las primeras 4 líneas (cabeceras)
    for ($i = 0; $i < 4; $i++) fgetcsv($file, 0, ',', '"');

    \Illuminate\Support\Facades\DB::transaction(function () use ($file, $familiaImport, $familiaId, &$contador) {
        while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
            if (empty($data[4])) continue;

            $empresa = \App\Models\Empresa::create([
                'cif'               => $data[2]  ?? null,
                'nombre_comercial'  => $data[4],
                'telefono'          => $data[5]  ?? null,
                'estado_contacto'   => $data[6]  ?? null,
                'fecha_cita'        => $data[7]  ?? null,
                'persona_contacto'  => $data[8]  ?? null,
                'email_general'     => $data[9]  ?? null,
                'posicion_contacto' => $data[10] ?? null,
                'sector'            => $data[11] ?? null,
                'actividad'         => $data[12] ?? null,
                'horario_atencion'  => $data[13] ?? null,
                'direccion'         => $data[14] ?? null,
                'numero'            => $data[15] ?? null,
                'otros_direccion'   => $data[16] ?? null,
                'codigo_postal'     => $data[17] ?? null,
                'municipio'         => $data[18] ?? null,
                'provincia'         => $data[19] ?? null,
                'web'               => $data[20] ?? null,
                'proyecto_asociado' => $data[21] ?? null,
            ]);

            \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
                'empresa_id' => $empresa->id,
                'familia'    => $familiaImport,
                'familia_id' => $familiaId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $contador++;
        }
    });

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} empresas.";
});

/* 

Route::get('/importar-excel-nuevo', function () {
    // 1. CAMBIA AQUÍ el nombre de tu archivo CSV
    $path = storage_path('app/madera_2.csv'); ////////////////////////////////////////////
    if (!file_exists($path)) return "No se encuentra el archivo en storage/app/";

    $file = fopen($path, 'r');

    // Como en tu foto las cabeceras están en la fila 1, solo ignoramos 1 línea
    fgetcsv($file, 0, ',', '"');

    $contador = 0;

    // Leemos línea por línea
    while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
        
        // Si la columna 0 (Nombre) está vacía, saltamos esta fila
        if (!isset($data[0]) || empty(trim($data[0]))) continue; 

        // 2. Mapeo adaptado a tu nueva imagen
        $empresa = \App\Models\Empresa::create([
            'nombre_comercial'  => trim($data[0]),
            'direccion'         => trim($data[1]) ?? null,
            'cif'               => trim($data[2]) ?? null,
            'provincia'         => trim($data[3]) ?? null,
            'municipio'         => trim($data[4]) ?? null,
            'sector'            => trim($data[5]) ?? null,
            'email_general'     => trim($data[6]) ?? null,
            'telefono'          => trim($data[7]) ?? null,
            'estado_contacto'   => trim($data[8]) ?? null, // Usamos la columna "NOTA" para esto
            
            // --- ASIGNAMOS EL CENTRO EDUCATIVO ---
            // 3. CAMBIA AQUÍ el nombre del nuevo colegio
            'centro_educativo'  => 'IES Aguas Nuevas', 
        ]);

        // 4. La vinculamos a la Familia Profesional
        // Viendo tu Excel, casi todas son Agrarias, así que lo puedes dejar fijo aquí
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => 'Madera, Mueble y Corcho' //////////////////////////////////////////////////////////
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} nuevas empresas.";
}); */

/* Route::get('/importar-excel-3', function () {
    // 1. CAMBIA AQUÍ el nombre de tu nuevo archivo CSV
    $path = storage_path('app/madera_2.csv'); ///////////////////////////////////////77
    if (!file_exists($path)) return "No se encuentra el archivo en storage/app/";

    $file = fopen($path, 'r');

    // Ignoramos la primera línea (cabeceras rojas)
    fgetcsv($file, 0, ',', '"');

    $contador = 0;

    // Leemos línea por línea
    while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
        
        // El Nombre Comercial está en la columna D (índice 3). Si está vacía, saltamos.
        if (!isset($data[3]) || empty(trim($data[3]))) continue; 

        // 2. Mapeo adaptado a la estructura de tu nuevo Excel (Filas amarillas)
        $empresa = \App\Models\Empresa::create([
            'cif'               => trim($data[2] ?? null),  // C
            'nombre_comercial'  => trim($data[3] ?? null),  // D
            'razon_social'      => trim($data[4] ?? null),  // E
            'telefono'          => trim($data[5] ?? null),  // F
            'estado_contacto'   => trim($data[6] ?? null),  // G
            'fecha_cita'        => trim($data[7] ?? null),  // H
            'persona_contacto'  => trim($data[8] ?? null),  // I
            'email_general'     => trim($data[9] ?? null),  // J
            'web'               => trim($data[10] ?? null), // K
            'posicion_contacto' => trim($data[11] ?? null), // L
            'sector'            => trim($data[12] ?? null), // M
            'actividad'         => trim($data[13] ?? null), // N
            'horario_atencion'  => trim($data[14] ?? null), // O
            'direccion'         => trim($data[15] ?? null), // P
            'numero'            => trim($data[16] ?? null), // Q
            'otros_direccion'   => trim($data[17] ?? null), // R
            'codigo_postal'     => trim($data[18] ?? null), // S
            'municipio'         => trim($data[19] ?? null), // T
            'provincia'         => trim($data[20] ?? null), // U (Asumiendo que está en la U)
            
            // --- ASIGNAMOS EL CENTRO EDUCATIVO ---
            
            'centro_educativo'  => 'IES Nombre Del Centro', 
        ]);

        // 4. La vinculamos a la Familia Profesional
        // Veo en la imagen sectores como "Madera, Mueble y Corcho" o el ciclo "CFGM - SMR" (Informática)
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => 'Madera, Mueble y Corcho' // //////////////////////////////////77
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} nuevas empresas con la nueva estructura.";
}); */



/* Route::get('/importar-excel-4', function () {
    // 1. CAMBIA AQUÍ el nombre de tu nuevo archivo CSV
    $path = storage_path('app/transporte_2.csv');  //////////////////////////////////////
    if (!file_exists($path)) return "No se encuentra el archivo en storage/app/";

    $file = fopen($path, 'r');

    // Ignoramos la primera línea (cabeceras)
    fgetcsv($file, 0, ',', '"');

    $contador = 0;

    // Leemos línea por línea
    while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
        
        // El Nombre Comercial está en la columna D (índice 3). Si está vacía, saltamos.
        if (!isset($data[3]) || empty(trim($data[3]))) continue; 

        // 2. Mapeo adaptado a la estructura de tu nuevo Excel (Filas Rosas/Naranjas)
        $empresa = \App\Models\Empresa::create([
            'cif'               => trim($data[2] ?? null),  // Col C
            'nombre_comercial'  => trim($data[3] ?? null),  // Col D
            
            // Faltan Razón Social en el Excel, pasamos directo al teléfono
            'telefono'          => trim($data[4] ?? null),  // Col E
            'estado_contacto'   => trim($data[5] ?? null),  // Col F
            'fecha_cita'        => trim($data[6] ?? null),  // Col G
            'persona_contacto'  => trim($data[7] ?? null),  // Col H
            'email_general'     => trim($data[8] ?? null),  // Col I
            'web'               => trim($data[9] ?? null),  // Col J
            
            // Falta "Posición", pasamos directo al Sector
            'sector'            => trim($data[10] ?? null), // Col K
            'actividad'         => trim($data[11] ?? null), // Col L
            'horario_atencion'  => trim($data[12] ?? null), // Col M
            'direccion'         => trim($data[13] ?? null), // Col N
            'numero'            => trim($data[14] ?? null), // Col O
            'otros_direccion'   => trim($data[15] ?? null), // Col P
            'codigo_postal'     => trim($data[16] ?? null), // Col Q
            'municipio'         => trim($data[17] ?? null), // Col R
            'provincia'         => trim($data[18] ?? null), // Col S
            
           
            'centro_educativo'  => 'IES Aguas Nuevas', 
        ]);

        // 4. La vinculamos a la Familia Profesional
        // Viendo el Excel, el sector es Transporte y Mantenimiento
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => 'Transporte y Mantenimiento de Vehículos' // <--- CAMBIA ESTO si es necesario
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} nuevas empresas (Formato Transporte).";
}); */

Route::middleware('auth:sanctum')->get('/importar-excel-5', function () {
    // 1. CAMBIA AQUÍ el nombre de tu archivo CSV de Energías
    $path = storage_path('app/energia_2.csv'); 
    if (!file_exists($path)) return "No se encuentra el archivo en storage/app/";

    $file = fopen($path, 'r');

    // Ignoramos la primera línea (cabeceras)
    fgetcsv($file, 0, ',', '"');

    $contador = 0;

    // Leemos línea por línea
    while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
        
        // ¡OJO! Ahora el Nombre Comercial cae en el índice 4 por el desfase del CSV
        if (!isset($data[4]) || empty(trim($data[4]))) continue; 

        // 2. Mapeo corregido con el desfase exacto del log de SQL
        $empresa = \App\Models\Empresa::create([
            'cif'               => trim($data[3] ?? null),
            'nombre_comercial'  => trim($data[4] ?? null),
            'razon_social'      => trim($data[5] ?? null),
            'telefono'          => trim($data[6] ?? null),
            'estado_contacto'   => trim($data[7] ?? null),
            'fecha_cita'        => trim($data[8] ?? null),
            'persona_contacto'  => trim($data[9] ?? null),
            'email_general'     => trim($data[10] ?? null),
            'web'               => trim($data[11] ?? null), // ¡Ahora sí cae en el campo TEXT!
            'posicion_contacto' => trim($data[12] ?? null),
            'sector'            => trim($data[13] ?? null), // Aquí pondrá "Energía"
            'actividad'         => trim($data[14] ?? null),
            'horario_atencion'  => trim($data[15] ?? null),
            'direccion'         => trim($data[16] ?? null),
            'numero'            => trim($data[17] ?? null),
            'otros_direccion'   => trim($data[18] ?? null),
            'codigo_postal'     => trim($data[19] ?? null),
            'municipio'         => trim($data[20] ?? null),
            'provincia'         => trim($data[21] ?? null),
            
            
            'centro_educativo'  => 'IES Aguas Nuevas', 
        ]);

        // 4. Asignamos la Familia Profesional (con FK normalizada)
        $familiaImport5 = 'Energía y Agua';
        $familiaId5 = \App\Models\Familia::where('nombre', $familiaImport5)->value('id');
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => $familiaImport5,
            'familia_id' => $familiaId5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} nuevas empresas del sector Energía.";
});