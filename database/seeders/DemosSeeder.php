<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemosSeeder extends Seeder
{
    public function run(): void
    {
        $demos = [

            // ──────────────────────────────────────────────────────────────────
            // DEMO 1 · Informática y Comunicaciones (demo original)
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Informática y Comunicaciones',
                'etiqueta'             => '🖥️ Informática y Comunicaciones',
                'empresa_nombre'       => 'ImmersaLab SL',
                'empresa_sector'       => 'Informática y Comunicaciones',
                'empresa_tamano'       => 'Mediana (50-250)',
                'empresa_web'          => 'www.immersalab.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Desarrollamos software y simuladores de realidad virtual para formación corporativa e industrial. Tenemos un equipo de 20 programadores, 5 diseñadores 3D y personal de Calidad (QA).',
                'friccion_area'        => 'Testing y reporte de bugs en entornos 3D interactivos.',
                'friccion_problema'    => 'Los testers tienen que quitarse las gafas de realidad virtual (VR) cada vez que encuentran un fallo para anotarlo en un Excel o Jira en el ordenador. Esto corta la inmersión, pierden el hilo y se olvidan de detalles visuales exactos del bug.',
                'restricciones'        => json_encode(['Software cerrado', 'Falta de tiempo']),
                'otra_limitacion'      => 'No podemos integrar plugins externos pesados en el motor gráfico actual sin romper el rendimiento y los FPS del simulador.',
                'lo_que_no_quieren'    => 'Un sistema de tickets tradicional fuera del entorno de las gafas VR.',
                'consecuencias'        => json_encode(['Pérdida de tiempo', 'Errores frecuentes']),
                'otra_consecuencia'    => 'Bugs difíciles de reproducir por falta de contexto visual y coordenadas exactas.',
                'expectativas_alumno'  => 'Que investigue soluciones de captura de datos in-app o proponga un flujo mediante scripts ligeros que permita registrar coordenadas (X, Y, Z) y capturas de pantalla pulsando un solo botón en los mandos VR, guardándolo automáticamente.',
                'nivel_grupo'          => 'Alto',
                'curso_seleccionado'   => 2,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

            // ──────────────────────────────────────────────────────────────────
            // DEMO 2 · Administración y Gestión
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Administración y Gestión',
                'etiqueta'             => '📋 Administración y Gestión',
                'empresa_nombre'       => 'Gestoría Atlántico SL',
                'empresa_sector'       => 'Administración y Gestión',
                'empresa_tamano'       => 'Pequeña (10-50)',
                'empresa_web'          => 'www.gestoriaatlantico.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Somos una gestoría que ofrece servicios de contabilidad, fiscalidad y recursos humanos a más de 200 pymes de la isla. Un equipo de 12 personas gestiona declaraciones, nóminas y trámites con la Administración Pública de forma continua.',
                'friccion_area'        => 'Recepción y archivo de documentación de clientes.',
                'friccion_problema'    => 'Los clientes envían sus facturas y tickets por WhatsApp, email y en papel. Cada empleado los guarda en carpetas distintas sin una nomenclatura común, lo que provoca que documentos se pierdan o dupliquen justo antes de la campaña de IVA trimestral.',
                'restricciones'        => json_encode(['Presupuesto Cero/Muy Bajo', 'Resistencia al cambio']),
                'otra_limitacion'      => 'Los clientes con mayor volumen de documentos son personas mayores poco digitalizadas que no usarán una app compleja.',
                'lo_que_no_quieren'    => 'Cambiar el software de contabilidad actual (A3) ni pagar suscripciones adicionales.',
                'consecuencias'        => json_encode(['Pérdida de tiempo', 'Errores frecuentes', 'Insatisfacción del cliente']),
                'otra_consecuencia'    => 'Retrasos en presentación de impuestos con el consiguiente riesgo de sanciones para los clientes.',
                'expectativas_alumno'  => 'Que diseñe un flujo digital sencillo (puede ser con Google Drive, formularios o herramientas No-Code) que permita a los clientes enviar sus documentos de forma ordenada y al equipo localizarlos en segundos.',
                'nivel_grupo'          => 'Medio',
                'curso_seleccionado'   => 2,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

            // ──────────────────────────────────────────────────────────────────
            // DEMO 3 · Comercio y Marketing
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Comercio y Marketing',
                'etiqueta'             => '🛍️ Comercio y Marketing',
                'empresa_nombre'       => 'Sabores Canarios eShop',
                'empresa_sector'       => 'Comercio y Marketing',
                'empresa_tamano'       => 'Micropyme (1-10)',
                'empresa_web'          => 'www.saborescanarios.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Vendemos productos gastronómicos canarios (mojo, gofio, queso, vino) a través de una tienda online y en ferias locales. Somos 4 personas: 2 en logística, 1 en atención al cliente y 1 gestionando redes sociales y la web.',
                'friccion_area'        => 'Gestión del stock y sincronización entre tienda física (ferias) y tienda online.',
                'friccion_problema'    => 'Cuando vendemos en una feria, actualizamos el stock manualmente en una hoja de papel. Al volver, nos encontramos con que la tienda online ha vendido productos que ya no tenemos, generando devoluciones y críticas negativas.',
                'restricciones'        => json_encode(['Presupuesto Cero/Muy Bajo', 'Internet inestable']),
                'otra_limitacion'      => 'En las ferias no siempre hay cobertura estable y la persona responsable no tiene conocimientos técnicos avanzados.',
                'lo_que_no_quieren'    => 'Un sistema que requiera conexión permanente a internet ni formación técnica compleja.',
                'consecuencias'        => json_encode(['Insatisfacción del cliente', 'Costes innecesarios', 'Pérdida de tiempo']),
                'otra_consecuencia'    => 'Daño reputacional por reseñas negativas en Google y redes sociales por pedidos que no podemos servir.',
                'expectativas_alumno'  => 'Que proponga una solución offline-first (puede ser una app PWA, hoja de cálculo con sincronización diferida o similar) que permita descontar stock en ferias y sincronizarlo con la tienda online al recuperar conexión.',
                'nivel_grupo'          => 'Medio',
                'curso_seleccionado'   => 2,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

            // ──────────────────────────────────────────────────────────────────
            // DEMO 4 · Sanidad
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Sanidad',
                'etiqueta'             => '🏥 Sanidad',
                'empresa_nombre'       => 'Clínica Bienestar Las Palmas',
                'empresa_sector'       => 'Sanidad',
                'empresa_tamano'       => 'Pequeña (10-50)',
                'empresa_web'          => 'www.clinicabienestar.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Somos una clínica privada de fisioterapia y nutrición con 8 profesionales sanitarios y 2 administrativos. Atendemos a más de 300 pacientes al mes con citas presenciales y seguimiento de tratamientos.',
                'friccion_area'        => 'Seguimiento de la evolución de los pacientes entre sesiones.',
                'friccion_problema'    => 'Cada fisioterapeuta lleva sus notas de evolución del paciente en papel o en documentos Word personales. Cuando un paciente cambia de profesional o el terapeuta está de baja, el sustituto no tiene acceso rápido al historial y tiene que empezar casi de cero en la anamnesis.',
                'restricciones'        => json_encode(['Software cerrado', 'Normativa RGPD']),
                'otra_limitacion'      => 'Cualquier solución debe cumplir estrictamente con la normativa sanitaria y de protección de datos. No podemos subir historiales a servidores en la nube no certificados.',
                'lo_que_no_quieren'    => 'Sistemas en la nube genéricos que no cumplan con el ENS o normativa sanitaria española.',
                'consecuencias'        => json_encode(['Insatisfacción del cliente', 'Pérdida de tiempo', 'Riesgos de seguridad']),
                'otra_consecuencia'    => 'Riesgo de discontinuidad en los tratamientos que puede afectar negativamente a la recuperación del paciente.',
                'expectativas_alumno'  => 'Que analice los requisitos legales (RGPD, LOPD-GDD) y proponga un diseño de sistema de historia clínica digital local o en servidor propio, con control de accesos por rol (fisio, nutricionista, admin).',
                'nivel_grupo'          => 'Alto',
                'curso_seleccionado'   => 2,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

            // ──────────────────────────────────────────────────────────────────
            // DEMO 5 · Electricidad y Electrónica
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Electricidad y Electrónica',
                'etiqueta'             => '⚡ Electricidad y Electrónica',
                'empresa_nombre'       => 'SolarTech Canarias SL',
                'empresa_sector'       => 'Electricidad y Electrónica',
                'empresa_tamano'       => 'Pequeña (10-50)',
                'empresa_web'          => 'www.solartechcanarias.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Instalamos y mantenemos sistemas de energía solar fotovoltaica en viviendas y empresas de Gran Canaria. Un equipo de 10 técnicos realiza instalaciones, revisiones preventivas y reparaciones de averías.',
                'friccion_area'        => 'Gestión de partes de trabajo e incidencias en campo.',
                'friccion_problema'    => 'Los técnicos rellenan los partes de trabajo en papel durante la instalación. Al llegar a la oficina, el administrativo los transcribe manualmente a una hoja Excel para facturar. El proceso tarda hasta 3 días, genera errores de transcripción y retrasa la facturación.',
                'restricciones'        => json_encode(['Internet inestable', 'Equipos obsoletos']),
                'otra_limitacion'      => 'Los técnicos trabajan en tejados y zonas sin buena cobertura. Solo disponen de teléfonos Android básicos de empresa.',
                'lo_que_no_quieren'    => 'Soluciones que requieran tablets nuevas o suscripciones costosas por usuario.',
                'consecuencias'        => json_encode(['Pérdida de tiempo', 'Errores frecuentes', 'Costes innecesarios']),
                'otra_consecuencia'    => 'Retrasos en la facturación que generan problemas de tesorería en la empresa.',
                'expectativas_alumno'  => 'Que diseñe una solución móvil offline (PWA o app ligera) que permita al técnico completar el parte en campo (con firma digital del cliente), y se sincronice automáticamente al recuperar conexión para generar la factura directamente.',
                'nivel_grupo'          => 'Medio',
                'curso_seleccionado'   => 2,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

            // ──────────────────────────────────────────────────────────────────
            // DEMO 6 · Hostelería y Turismo
            // ──────────────────────────────────────────────────────────────────
            [
                'familia_profesional'  => 'Hostelería y Turismo',
                'etiqueta'             => '🏨 Hostelería y Turismo',
                'empresa_nombre'       => 'Rincón del Atlántico Restaurante',
                'empresa_sector'       => 'Hostelería y Turismo',
                'empresa_tamano'       => 'Micropyme (1-10)',
                'empresa_web'          => 'www.rincondelatlantico.demo',
                'empresa_centro'       => null,
                'dia_a_normal'         => 'Somos un restaurante de cocina canaria contemporánea con 40 comensales de capacidad. Un equipo de 8 personas (cocina y sala) trabajamos con carta de temporada que cambia cada mes y ofrecemos menú diario para trabajadores.',
                'friccion_area'        => 'Comunicación entre sala y cocina durante el servicio.',
                'friccion_problema'    => 'Los camareros anotan los pedidos en papel y los llevan físicamente a cocina. En horas punta, los comanderos se acumulan, se pierden o se mojan. Los errores en pedidos generan devoluciones de platos y clientes insatisfechos que esperan demasiado.',
                'restricciones'        => json_encode(['Presupuesto Cero/Muy Bajo', 'Resistencia al cambio']),
                'otra_limitacion'      => 'El equipo tiene una edad media alta y poca experiencia con tecnología. No podemos parar el servicio para hacer formaciones largas.',
                'lo_que_no_quieren'    => 'Sistemas de TPV completos con hardware nuevo ni mensualidades elevadas.',
                'consecuencias'        => json_encode(['Insatisfacción del cliente', 'Errores frecuentes', 'Pérdida de tiempo']),
                'otra_consecuencia'    => 'Pérdida de reputación en plataformas de reservas como TripAdvisor por los tiempos de espera.',
                'expectativas_alumno'  => 'Que investigue soluciones de comandas digitales gratuitas o de bajo coste (apps como Waiterio, Sunday o desarrollos propios con QR) y proponga la más adecuada con un plan de implementación que minimice la curva de aprendizaje del equipo.',
                'nivel_grupo'          => 'Básico',
                'curso_seleccionado'   => 1,
                'duracion'             => '1 a 2 semanas',
                'cantidad_microretos'  => 3,
            ],

        ];

        foreach ($demos as $demo) {
            DB::table('demos')->updateOrInsert(
                ['familia_profesional' => $demo['familia_profesional']],
                array_merge($demo, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}