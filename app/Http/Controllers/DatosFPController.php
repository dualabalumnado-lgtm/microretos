<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CicloFormativo;
use App\Models\Modulo;
use App\Models\ResultadoAprendizaje;

class DatosFPController extends Controller
{
    // Obtenemos todas las Familias Profesionales (sin repetir)
    public function getFamilias()
    {
        $familias = CicloFormativo::select('familia')
            ->whereNotNull('familia')
            ->where('familia', '!=', '')
            ->distinct()
            ->orderBy('familia')
            ->pluck('familia');

        return response()->json($familias);
    }

    // Obtenemos los Ciclos de una Familia concreta
    public function getCiclos($familia)
    {
        // Decodificamos por si la URL trae espacios o tildes 
        $familiaDecodificada = urldecode($familia);
        
        $ciclos = CicloFormativo::where('familia', $familiaDecodificada)
            ->orderBy('nombre')
            ->get();

        return response()->json($ciclos);
    }

    // Obtenemos los Módulos de un Ciclo concreto
    public function getModulos($idCiclo)
    {
        $modulos = Modulo::where('idcicloformativo', $idCiclo)
            ->orderBy('curso')
            ->orderBy('nombre')
            ->get();

        return response()->json($modulos);
    }

    // Obtenemos los RA y sus CE de un Módulo concreto
    public function getRa_Ce($idModulo)
    {
        //Teaer los RA, y con ellos (with), sus Criterios de Evaluación.
        $ras = ResultadoAprendizaje::with('criteriosEvaluacion')
            ->where('idmodulo', $idModulo)
            ->get();

        return response()->json($ras);
    }
}