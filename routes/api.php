<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatosFPController;
use App\Http\Controllers\MicroretoIAController;

/*
|--------------------------------------------------------------------------
| API Routes - DuaLab Studio
|--------------------------------------------------------------------------
*/

Route::get('/microretos', [MicroretoIAController::class, 'index']);
// (Si prefieres ponerlo en DatosFPController, cambia la clase)
// --- RUTAS DE CONSULTA (LECTURA) ---
// Estas rutas alimentan tus desplegables en cascada

// API para buscar microreto para mostrar en Detalle-Microreto
Route::get('/microretos/{id}', [MicroretoIAController::class, 'show']);

// 1. Nuevas rutas B2B (Empresas y Familias asociadas)
Route::get('/empresas', [DatosFPController::class, 'getEmpresas']);
Route::get('/empresas/{id}/familias', [DatosFPController::class, 'getFamiliasPorEmpresa']);

// 2. Rutas Académicas clásicas
Route::get('/familias', [DatosFPController::class, 'getFamilias']);
Route::get('/familias/{familia}/ciclos', [DatosFPController::class, 'getCiclos']);
Route::get('/ciclos/{idCiclo}/modulos', [DatosFPController::class, 'getModulos']);
Route::get('/modulos/{idModulo}/ra-ce', [DatosFPController::class, 'getRaCe']); // Se llama ra-ce en URL, getRaCe en método

// Crear nueva empresa
Route::post('/empresas', [\App\Http\Controllers\DatosFPController::class, 'guardarEmpresa']);

// Actualizar información de empresa existente
Route::put('/empresas/{id}', [\App\Http\Controllers\DatosFPController::class, 'actualizarEmpresa']);



// Actualizar información faltante de la empresa 
Route::put('/empresas/{id}', [\App\Http\Controllers\DatosFPController::class, 'actualizarEmpresa']);
//-------------------------------------------------------------//

// --- RUTAS DE ACCIÓN (ESCRITURA / IA) ---
// Estas rutas procesan la generación y el guardado

// Genera el JSON con OpenAI
Route::post('/generar-microreto', [MicroretoIAController::class, 'generar']);

// Guarda el resultado final en la base de datos
Route::post('/guardar-microreto-bd', [MicroretoIAController::class, 'guardarEnBD']);

// Guarda lote de microretos en la base de datos
Route::post('/guardar-microretos-lote', [MicroretoIAController::class, 'guardarLote']); 

// Borrar microreto
Route::delete('/microretos/{id}', [MicroretoIAController::class, 'destroy']);

// LOGIN Y LOGOUT
use App\Http\Controllers\AdminAuthController;

Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout']);



Route::get('/importar-excel', function () {
    // 1. Buscamos el archivo que acabas de guardar
    $path = storage_path('app/agraria.csv'); ///////////////////////////////////////
    if (!file_exists($path)) return "No se encuentra el archivo informatica.csv en storage/app/";

    $file = fopen($path, 'r');

    // 2. Ignoramos las primeras 4 líneas (las de colores y cabeceras)
    for ($i = 0; $i < 4; $i++) {
        fgetcsv($file, 0, ',', '"');
    }

    $contador = 0;

    // 3. Leemos línea por línea
    while (($data = fgetcsv($file, 0, ',', '"')) !== false) {
        // Si la columna 4 (Razón social) está vacía, saltamos esta fila
        if (empty($data[4])) continue; 

        // 4. Guardamos la Empresa
        $empresa = \App\Models\Empresa::create([
            'cif'               => $data[2] ?? null,
            'nombre_comercial'  => $data[4], // Usamos Razón Social porque Nombre Comercial estaba vacío
            'telefono'          => $data[5] ?? null,
            'estado_contacto'   => $data[6] ?? null,
            'fecha_cita'        => $data[7] ?? null,
            'persona_contacto'  => $data[8] ?? null,
            'email_general'     => $data[9] ?? null,
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

        // 5. La vinculamos a la Familia en la tabla pivote
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => 'Agraria' /////////////////////////////////////
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} empresas y se han vinculado a la familia de Informática.";
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

Route::get('/importar-excel-5', function () {
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

        // 4. Asignamos la Familia Profesional
        \Illuminate\Support\Facades\DB::table('empresa_familia')->insert([
            'empresa_id' => $empresa->id,
            'familia'    => 'Energía y Agua' 
        ]);

        $contador++;
    }

    fclose($file);
    return "¡BINGO! Se han importado correctamente {$contador} nuevas empresas del sector Energía.";
});