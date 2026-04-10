<?php

namespace Database\Seeders;

use App\Models\Demo;
use Illuminate\Database\Seeder;

class MicorretosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $microretos = [

            // ─────────────────────────────────────────────────────────────────
            // DEMO 1 · Informática y Comunicaciones
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Informática y Comunicaciones',
                'retos'   => [
                    [
                        'titulo'             => 'Captura de bugs sin salir de la realidad virtual',
                        'empresa_nombre'     => 'ImmersaLab SL',
                        'quien_es'           => 'ImmersaLab SL desarrolla software y simuladores de realidad virtual para formación corporativa e industrial. Su equipo de 20 programadores, 5 diseñadores 3D y personal de QA crea entornos inmersivos para clientes del sector industrial y sanitario.',
                        'dia_a_dia'          => 'El equipo de QA realiza sesiones de varias horas probando simuladores con gafas VR. Cuando detectan un bug, deben quitarse las gafas, anotar el error en Jira o Excel, y volver a ponérselas — perdiendo contexto visual y coordenadas exactas del problema.',
                        'pregunta_reto'      => '¿Cómo podríamos permitir a los testers registrar bugs con contexto visual completo sin interrumpir la inmersión en el entorno VR?',
                        'dificultades'       => [
                            'Quitarse las gafas rompe la inmersión y hace perder el contexto exacto del bug',
                            'Los bugs en 3D requieren coordenadas (X, Y, Z) para reproducirse con fidelidad',
                            'El motor gráfico actual no admite plugins que superen los 20 MB',
                        ],
                        'que_necesitan'      => [
                            'Captura de pantalla o clip de vídeo del momento exacto del error',
                            'Registro automático de posición y orientación en el espacio 3D',
                            'Activación con un solo botón del mando sin interrumpir el flujo de prueba',
                        ],
                        'limitaciones'       => [
                            'No se pueden instalar plugins pesados en el motor gráfico',
                            'Los testers no pueden escribir mientras llevan las gafas puestas',
                            'La solución debe funcionar sin conexión de red continua',
                        ],
                        'prototipos'         => [
                            'Script nativo que captura screenshot + coordenadas al presionar el botón A del mando y genera un JSON exportable a Jira',
                            'Sistema de dictado por voz activado con el botón B: el tester describe el bug de viva voz y se transcribe automáticamente al ticket',
                            'Overlay minimalista en HUD con campo de texto predictivo accionable con ray-casting del mando',
                        ],
                        'ods_sugeridos'      => ['ODS 9 – Industria, innovación e infraestructura', 'ODS 8 – Trabajo decente y crecimiento económico'],
                        'soft_skills'        => ['Pensamiento computacional', 'Resolución de problemas técnicos complejos', 'Comunicación técnica'],
                        'evaluacion_oficial' => [
                            'RA2 – Desarrolla aplicaciones web utilizando código embebido en el servidor',
                            'CE2c – Se han utilizado herramientas de depuración y control de errores',
                            'CE2e – Se ha comprobado el correcto funcionamiento de las aplicaciones',
                        ],
                        'tips_profesorado'   => [
                            'Iniciar con un roleplay: un alumno simula ser el tester con las "gafas puestas" y otro toma notas — así se evidencia el problema real',
                            'Conectar el reto con el módulo de programación proponiendo que el prototipo sea evaluable como práctica de scripting',
                        ],
                        'nivel_grupo'        => 'Alto',
                        'ciclo'              => 'Desarrollo de Aplicaciones Web',
                        'modulo'             => 'Desarrollo web en entorno servidor',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // DEMO 2 · Administración y Gestión
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Administración y Gestión',
                'retos'   => [
                    [
                        'titulo'             => 'Archivo inteligente de documentación de clientes',
                        'empresa_nombre'     => 'Gestoría Atlántico SL',
                        'quien_es'           => 'Gestoría Atlántico SL presta servicios de contabilidad, fiscalidad y recursos humanos a más de 200 pymes de la isla. Sus 12 profesionales gestionan declaraciones, nóminas y trámites con la Administración de forma continua.',
                        'dia_a_dia'          => 'Cada empleado recibe facturas y tickets de sus clientes por WhatsApp, email y en papel. Sin una nomenclatura común, los documentos se guardan en carpetas personales y aparecen duplicados o perdidos justo antes de la campaña trimestral de IVA.',
                        'pregunta_reto'      => '¿Cómo podríamos centralizar en un único flujo la recepción y clasificación de documentos de clientes para que cualquier empleado los localice en menos de 30 segundos?',
                        'dificultades'       => [
                            'Los clientes usan canales distintos sin posibilidad de unificarlos por su cuenta',
                            'Personas mayores poco digitalizadas no adoptarán apps complejas',
                            'No hay presupuesto para nuevas suscripciones de software',
                        ],
                        'que_necesitan'      => [
                            'Un punto de entrada único para todos los documentos independientemente del canal',
                            'Nomenclatura automática o guiada: cliente + tipo + fecha',
                            'Acceso inmediato de cualquier empleado sin formación técnica avanzada',
                        ],
                        'limitaciones'       => [
                            'No se puede cambiar el software de contabilidad A3',
                            'Coste cero o mínimo (Google Workspace ya disponible)',
                            'La solución debe ser operable desde el móvil para los clientes',
                        ],
                        'prototipos'         => [
                            'Google Drive con carpetas por cliente + formulario Google que el cliente rellena para subir el documento y lo nombra automáticamente',
                            'Bot de WhatsApp (Twilio o WATI) que recibe el documento, pide el tipo de gasto y lo guarda con nombre correcto en Drive',
                            'Flujo Make/n8n: email → extrae adjunto → renombra → sube a carpeta correcta en Drive → notifica al gestor',
                        ],
                        'ods_sugeridos'      => ['ODS 8 – Trabajo decente y crecimiento económico', 'ODS 17 – Alianzas para lograr los objetivos'],
                        'soft_skills'        => ['Organización y gestión documental', 'Empatía con el cliente', 'Adaptación tecnológica'],
                        'evaluacion_oficial' => [
                            'RA3 – Realiza actividades de gestión administrativa de recursos humanos',
                            'CE3a – Se han aplicado las técnicas de archivo y clasificación de documentación',
                            'CE3b – Se han utilizado aplicaciones informáticas de gestión documental',
                        ],
                        'tips_profesorado'   => [
                            'Proponer que los alumnos "sean clientes" y envíen documentos por WhatsApp para evidenciar el caos antes de diseñar la solución',
                            'El prototipo de flujo Make/n8n puede conectarse con la unidad de automatización de procesos administrativos',
                        ],
                        'nivel_grupo'        => 'Medio',
                        'ciclo'              => 'Administración y Finanzas',
                        'modulo'             => 'Gestión de la documentación jurídica y empresarial',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // DEMO 3 · Comercio y Marketing
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Comercio y Marketing',
                'retos'   => [
                    [
                        'titulo'             => 'Stock sincronizado entre feria y tienda online',
                        'empresa_nombre'     => 'Sabores Canarios eShop',
                        'quien_es'           => 'Sabores Canarios eShop vende productos gastronómicos canarios — mojo, gofio, queso y vino — a través de su tienda online y en ferias locales. Un equipo de 4 personas gestiona logística, atención al cliente y redes sociales.',
                        'dia_a_dia'          => 'Cuando venden en una feria, actualizan el stock en papel. Al regresar, descubren que la tienda online ha vendido productos ya agotados, generando devoluciones y críticas negativas en Google.',
                        'pregunta_reto'      => '¿Cómo podríamos descontar stock de ventas presenciales en ferias y sincronizarlo automáticamente con la tienda online al recuperar conexión a internet?',
                        'dificultades'       => [
                            'Cobertura móvil inestable o inexistente en algunos recintos feriales',
                            'La persona de feria no tiene conocimientos técnicos avanzados',
                            'Cada desajuste de stock genera reseñas negativas difíciles de revertir',
                        ],
                        'que_necesitan'      => [
                            'Registro de ventas locales que funcione sin internet',
                            'Sincronización automática con WooCommerce/Shopify al recuperar conexión',
                            'Interfaz tan sencilla que funcione con una pantalla y un botón por producto',
                        ],
                        'limitaciones'       => [
                            'Sin inversión en hardware nuevo (solo móviles Android que ya tienen)',
                            'Sin suscripciones costosas',
                            'La operadora de feria no puede recibir formación técnica larga',
                        ],
                        'prototipos'         => [
                            'PWA con caché offline: registra ventas localmente y sincroniza vía API de WooCommerce al detectar conexión',
                            'Google Sheets con Apps Script: la hoja offline descuenta unidades y, al conectarse, lanza una macro que actualiza el inventario de la tienda online',
                            'App Glide (no-code) conectada a una hoja de cálculo como fuente de datos, con acción "restar 1" por producto y sincronización diferida',
                        ],
                        'ods_sugeridos'      => ['ODS 12 – Producción y consumo responsables', 'ODS 8 – Trabajo decente y crecimiento económico'],
                        'soft_skills'        => ['Orientación al cliente', 'Resolución de problemas bajo presión', 'Pensamiento digital'],
                        'evaluacion_oficial' => [
                            'RA4 – Gestiona el aprovisionamiento y el control de existencias',
                            'CE4a – Se han aplicado técnicas de control de inventario',
                            'CE4c – Se han utilizado herramientas informáticas de gestión de stocks',
                        ],
                        'tips_profesorado'   => [
                            'Simular el escenario en clase: sin wifi, tableta con la PWA, 10 "ventas" en 2 minutos, luego conectar y ver la sincronización en vivo',
                            'Vincular el reto con la unidad de e-commerce para que el prototipo sea evaluable como práctica de gestión de canal online',
                        ],
                        'nivel_grupo'        => 'Medio',
                        'ciclo'              => 'Comercio Internacional',
                        'modulo'             => 'Marketing y promoción en el punto de venta',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // DEMO 4 · Sanidad
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Sanidad',
                'retos'   => [
                    [
                        'titulo'             => 'Historia clínica digital en servidor propio',
                        'empresa_nombre'     => 'Clínica Bienestar Las Palmas',
                        'quien_es'           => 'Clínica Bienestar Las Palmas es una clínica privada de fisioterapia y nutrición con 8 profesionales sanitarios y 2 administrativos. Atienden a más de 300 pacientes al mes con seguimiento de tratamientos personalizado.',
                        'dia_a_dia'          => 'Cada fisioterapeuta guarda las notas de evolución de sus pacientes en papel o documentos Word personales. Cuando un paciente cambia de profesional o el terapeuta está de baja, el sustituto no tiene acceso al historial y debe empezar casi de cero.',
                        'pregunta_reto'      => '¿Cómo podríamos digitalizar el historial clínico de los pacientes garantizando el acceso por rol, la continuidad del tratamiento y el cumplimiento estricto del RGPD?',
                        'dificultades'       => [
                            'Los historiales no pueden subirse a servidores en la nube genéricos',
                            'Cada profesional tiene su propio sistema de notas incompatible con el resto',
                            'La normativa sanitaria y de protección de datos limita las opciones tecnológicas',
                        ],
                        'que_necesitan'      => [
                            'Base de datos de pacientes accesible solo por el profesional asignado y el admin',
                            'Registro de sesiones con fecha, evolución y próximos pasos',
                            'Solución alojada en servidor local o cloud certificado ENS',
                        ],
                        'limitaciones'       => [
                            'Ningún sistema en la nube no certificado (RGPD, LOPD-GDD)',
                            'El equipo no tiene perfil técnico para administrar servidores complejos',
                            'Presupuesto reducido: máximo una pequeña inversión inicial',
                        ],
                        'prototipos'         => [
                            'Aplicación web sencilla (Laravel + MySQL) alojada en servidor local de la clínica con autenticación por rol: admin, fisio, nutricionista',
                            'OpenEMR (sistema HIS open source) instalado en servidor propio, configurado y adaptado a los roles de la clínica',
                            'Notion autoalojado o Outline Wiki en servidor local con control de acceso granular por sección de paciente',
                        ],
                        'ods_sugeridos'      => ['ODS 3 – Salud y bienestar', 'ODS 10 – Reducción de las desigualdades'],
                        'soft_skills'        => ['Confidencialidad y ética profesional', 'Gestión de la información sanitaria', 'Responsabilidad legal'],
                        'evaluacion_oficial' => [
                            'RA5 – Realiza procedimientos administrativos aplicando la normativa vigente',
                            'CE5a – Se ha aplicado la normativa de protección de datos en el ámbito sanitario',
                            'CE5b – Se han gestionado historiales clínicos respetando los protocolos de confidencialidad',
                        ],
                        'tips_profesorado'   => [
                            'Empezar con análisis normativo: los alumnos leen un extracto del RGPD y del ENS y determinan qué queda prohibido antes de proponer soluciones',
                            'El diseño del sistema de roles es evaluable como práctica de bases de datos relacionales',
                        ],
                        'nivel_grupo'        => 'Alto',
                        'ciclo'              => 'Documentación y Administración Sanitaria',
                        'modulo'             => 'Sistemas de información y clasificación sanitaria',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // DEMO 5 · Electricidad y Electrónica
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Electricidad y Electrónica',
                'retos'   => [
                    [
                        'titulo'             => 'Parte de trabajo digital con firma en campo',
                        'empresa_nombre'     => 'SolarTech Canarias SL',
                        'quien_es'           => 'SolarTech Canarias SL instala y mantiene sistemas de energía solar fotovoltaica en Gran Canaria. Sus 10 técnicos realizan instalaciones, revisiones preventivas y reparaciones de averías diariamente.',
                        'dia_a_dia'          => 'Los técnicos rellenan partes de trabajo en papel durante las instalaciones. Al volver a la oficina, un administrativo los transcribe manualmente a Excel para facturar — un proceso de hasta 3 días con frecuentes errores que retrasan la facturación.',
                        'pregunta_reto'      => '¿Cómo podríamos permitir al técnico completar el parte de trabajo en campo desde el móvil, recoger la firma del cliente y generar la factura automáticamente al llegar a la oficina?',
                        'dificultades'       => [
                            'Los técnicos trabajan en tejados y zonas sin cobertura móvil estable',
                            'Solo disponen de teléfonos Android básicos de empresa',
                            'La transcripción manual genera errores que retrasan cobros y afectan la tesorería',
                        ],
                        'que_necesitan'      => [
                            'Formulario de parte de trabajo completable offline desde Android',
                            'Captura de firma digital del cliente al finalizar el trabajo',
                            'Generación automática del documento de factura al sincronizar',
                        ],
                        'limitaciones'       => [
                            'Sin tablets nuevas ni suscripciones por usuario',
                            'La solución debe funcionar con Android 10+ de gama media',
                            'El técnico debe completar el parte en menos de 2 minutos',
                        ],
                        'prototipos'         => [
                            'PWA instalada en el móvil Android: formulario con campos de trabajo, campo de firma táctil y exportación a PDF — se envía al sincronizar',
                            'Google Forms offline con complemento de firma (SignaturePad) + Apps Script que genera el PDF de factura en Drive al recibir el envío',
                            'App Glide conectada a Sheets: el técnico selecciona el tipo de trabajo, añade materiales de una lista y firma; Sheets llama a una macro de facturación',
                        ],
                        'ods_sugeridos'      => ['ODS 7 – Energía asequible y no contaminante', 'ODS 9 – Industria, innovación e infraestructura'],
                        'soft_skills'        => ['Gestión del tiempo', 'Atención al detalle', 'Comunicación con el cliente'],
                        'evaluacion_oficial' => [
                            'RA2 – Monta instalaciones fotovoltaicas aplicando técnicas de montaje',
                            'CE2f – Se han cumplimentado los documentos técnicos de la instalación',
                            'CE2g – Se han aplicado los protocolos de seguridad durante el montaje',
                        ],
                        'tips_profesorado'   => [
                            'Simular en clase el flujo completo: un alumno es el "técnico" en tejado (sin wifi) y otro es el "administrativo" que espera el parte; evidencian el cuello de botella',
                            'El PDF generado puede evaluarse también como práctica de documentación técnica',
                        ],
                        'nivel_grupo'        => 'Medio',
                        'ciclo'              => 'Instalaciones Eléctricas y Automáticas',
                        'modulo'             => 'Instalaciones eléctricas interiores',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // DEMO 6 · Hostelería y Turismo
            // ─────────────────────────────────────────────────────────────────
            [
                'familia' => 'Hostelería y Turismo',
                'retos'   => [
                    [
                        'titulo'             => 'Comandas digitales de bajo coste para hora punta',
                        'empresa_nombre'     => 'Rincón del Atlántico Restaurante',
                        'quien_es'           => 'Rincón del Atlántico es un restaurante de cocina canaria contemporánea con 40 comensales de capacidad. Sus 8 personas de equipo — cocina y sala — trabajan con carta de temporada mensual y menú diario para trabajadores.',
                        'dia_a_dia'          => 'Los camareros anotan pedidos en papel y los llevan físicamente a cocina. En hora punta los comanderos se acumulan, se pierden o se mojan. Los errores en pedidos generan platos devueltos, tiempos de espera altos y críticas negativas en TripAdvisor.',
                        'pregunta_reto'      => '¿Cómo podríamos digitalizar el proceso de toma y envío de comandas para que cocina reciba los pedidos al instante, sin inversión en hardware y con una curva de aprendizaje mínima para el equipo?',
                        'dificultades'       => [
                            'El equipo tiene edad media alta y poca experiencia con tecnología',
                            'No se puede detener el servicio para hacer formaciones largas',
                            'Los errores en hora punta generan pérdida de reputación online difícil de revertir',
                        ],
                        'que_necesitan'      => [
                            'El camarero selecciona la mesa y los platos desde el móvil en menos de 30 segundos',
                            'Cocina recibe el pedido en pantalla o impresora en tiempo real',
                            'Sin coste mensual ni hardware adicional',
                        ],
                        'limitaciones'       => [
                            'Sin TPV completo con hardware nuevo',
                            'Sin mensualidades elevadas',
                            'La formación del equipo no puede superar 20 minutos',
                        ],
                        'prototipos'         => [
                            'Waiterio (gratuito hasta 10 mesas): los camareros toman la comanda desde el móvil y cocina la ve en una tablet vieja reciclada',
                            'QR por mesa que abre un Google Form con los platos del día; las respuestas llegan a una hoja de Sheets visible en cocina en tiempo real',
                            'App hecha en Glide: menú editable por el encargado, el camarero selecciona mesa + platos + notas y cocina recibe notificación push',
                        ],
                        'ods_sugeridos'      => ['ODS 8 – Trabajo decente y crecimiento económico', 'ODS 12 – Producción y consumo responsables'],
                        'soft_skills'        => ['Gestión del cambio', 'Comunicación en equipo', 'Orientación al cliente'],
                        'evaluacion_oficial' => [
                            'RA3 – Atiende al cliente aplicando las técnicas de servicio en sala',
                            'CE3a – Se han aplicado los protocolos de toma de comandas',
                            'CE3c – Se han utilizado herramientas digitales de apoyo al servicio en sala',
                        ],
                        'tips_profesorado'   => [
                            'Hacer una práctica de "servicio en mesa" con comandas en papel primero y luego con la app: el contraste cronometrado es el mejor argumento',
                            'La evaluación del prototipo puede integrarse con la unidad de atención al cliente midiendo la reducción de errores',
                        ],
                        'nivel_grupo'        => 'Básico',
                        'ciclo'              => 'Servicios en Restauración',
                        'modulo'             => 'Técnicas de servicio y atención al cliente',
                        'duracion'           => '1 a 2 semanas',
                        'es_simulado'        => true,
                    ],
                ],
            ],

        ];

        foreach ($microretos as $grupo) {
            $demo = Demo::where('familia_profesional', $grupo['familia'])->first();
            if (!$demo) continue;

            foreach ($grupo['retos'] as $reto) {
                // Borrar el anterior para evitar datos corruptos del seeder previo
                \App\Models\Microreto::where('demo_id', $demo->id)
                    ->where('titulo', $reto['titulo'])
                    ->delete();

                \App\Models\Microreto::create(array_merge($reto, [
                    'demo_id'    => $demo->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
