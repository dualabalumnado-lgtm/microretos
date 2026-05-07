<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $familias = [
            'Actividades Físicas y Deportivas',
            'Administración y Gestión',
            'Aeronáutica',
            'Agraria',
            'Artes Gráficas',
            'Artes y Artesanías',
            'Biotecnología',
            'Comercio y Marketing',
            'Edificación y Obra Civil',
            'Electricidad y Electrónica',
            'Energía y Agua',
            'Fabricación Mecánica',
            'Hostelería y Turismo',
            'Imagen Personal',
            'Imagen y Sonido',
            'Industrias Alimentarias',
            'Industrias Extractivas',
            'Informática y Comunicaciones',
            'Instalación y Mantenimiento',
            'Logística y Almacén',
            'Madera, Mueble y Corcho',
            'Marítimo-Pesquera',
            'Química',
            'Sanidad',
            'Seguridad y Medio Ambiente',
            'Servicios Socioculturales y a la Comunidad',
            'Telecomunicaciones',
            'Textil, Confección y Piel',
            'Transporte y Mantenimiento de Vehículos',
            'Vidrio y Cerámica',
        ];

        foreach ($familias as $nombre) {
            DB::table('familias')->insertOrIgnore([
                'nombre'     => $nombre,
                'imagen_url' => null,
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }

        // Eliminar el duplicado roto de "Energía y Agua" con encoding incorrecto (id=9)
        // si aún existiera tras el seeder
        $buena = DB::table('familias')->where('nombre', 'Energía y Agua')->orderBy('id')->value('id');
        DB::table('familias')
            ->where('nombre', 'LIKE', 'Energ%y Agua')
            ->where('id', '!=', $buena)
            ->delete();
    }

    public function down(): void {}
};
