-- ============================================================
-- IMPORTACIÓN: Sistemas de Energías Renovables
-- Ciclo Formativo de Grado Superior (RD 1584/2011)
-- Familia profesional nueva: Electricidad y Electrónica
-- ============================================================
-- ANTES DE EJECUTAR — revisa estos dos valores:
--
--   @idCiclo_boe  → código oficial BOE del ciclo (e.g. 3581)
--                   Consulta el RD 1584/2011 o la BD del MECD.
--                   Puedes dejarlo en 0 si no lo necesitas.
--
--   imagen_url    → ruta de la imagen para la familia.
--                   Déjala NULL si aún no tienes el archivo.
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. FAMILIA PROFESIONAL (nueva)
-- ============================================================
INSERT IGNORE INTO familias (nombre, imagen_url)
VALUES ('Electricidad y Electrónica', NULL);

SET @familia_id = (
  SELECT id FROM familias
  WHERE nombre = 'Electricidad y Electrónica'
  LIMIT 1
);

-- ============================================================
-- 2. CICLO FORMATIVO
-- ============================================================
-- Cambia 0 por el código BOE real si lo tienes (ver nota arriba)
SET @idCiclo_boe = 0;

INSERT IGNORE INTO ciclos_formativos
  (idCiclo, nombre, familia, familia_id, grado, referenciaBOE, siglasGrado)
VALUES (
  @idCiclo_boe,
  'Sistemas de Energías Renovables',
  'Electricidad y Electrónica',   -- campo legacy texto
  @familia_id,                    -- FK a familias.id
  'Grado Superior',
  'RD 1584/2011',
  ''                              -- siglasGrado: '' según patrón existente en BD
);

SET @ciclo_id = (
  SELECT id FROM ciclos_formativos
  WHERE nombre = 'Sistemas de Energías Renovables'
  LIMIT 1
);

-- ============================================================
-- 3. MÓDULOS
-- ============================================================
-- codigoBOE y horastotales: rellena con los valores reales del BOE
-- si los tienes; se dejan vacíos/0 como el resto de módulos sin dato.
-- curso 1 = primer curso, curso 2 = segundo curso (estimado según CSV)
INSERT INTO modulos (idAreaSC, idcicloformativo, codigoBOE, nombre, curso, horastotales)
VALUES
  (0, @ciclo_id, '', 'Sistemas eléctricos en centrales',    1, 0),
  (0, @ciclo_id, '', 'Subestaciones eléctricas',            1, 0),
  (0, @ciclo_id, '', 'Telecontrol y automatismos',          1, 0),
  (0, @ciclo_id, '', 'Prevención de riesgos eléctricos',    1, 0),
  (0, @ciclo_id, '', 'Sistemas de energías renovables',     1, 0),
  (0, @ciclo_id, '', 'Configuración instalaciones FV',      2, 0),
  (0, @ciclo_id, '', 'Gestión montaje FV',                  2, 0),
  (0, @ciclo_id, '', 'Gestión montaje parques eólicos',     2, 0);

SET @mod_sec  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Sistemas eléctricos en centrales'  LIMIT 1);
SET @mod_sub  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Subestaciones eléctricas'          LIMIT 1);
SET @mod_tel  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Telecontrol y automatismos'         LIMIT 1);
SET @mod_prev = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Prevención de riesgos eléctricos'   LIMIT 1);
SET @mod_ser  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Sistemas de energías renovables'    LIMIT 1);
SET @mod_cfv  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Configuración instalaciones FV'     LIMIT 1);
SET @mod_gfv  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Gestión montaje FV'                 LIMIT 1);
SET @mod_geo  = (SELECT id FROM modulos WHERE idcicloformativo = @ciclo_id AND nombre = 'Gestión montaje parques eólicos'    LIMIT 1);

-- ============================================================
-- 4. RESULTADOS DE APRENDIZAJE
-- ============================================================
INSERT INTO resultados_aprendizaje (idmodulo, ra)
VALUES
  (@mod_sec,  'RA1 Caracteriza sistemas eléctricos'),
  (@mod_sec,  'RA2 Clasifica materiales eléctricos'),
  (@mod_sec,  'RA3 Calcula circuitos eléctricos'),
  (@mod_sub,  'RA1 Caracteriza subestaciones'),
  (@mod_sub,  'RA2 Interpreta proyectos'),
  (@mod_tel,  'RA1 Caracteriza instrumentación'),
  (@mod_prev, 'RA1 Caracteriza efectos eléctricos'),
  (@mod_ser,  'RA1 Distingue energías renovables'),
  (@mod_cfv,  'RA1 Calcula potencial solar'),
  (@mod_gfv,  'RA1 Discrimina instalaciones'),
  (@mod_geo,  'RA1 Caracteriza instalaciones');

SET @ra_sec1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_sec  AND ra = 'RA1 Caracteriza sistemas eléctricos' LIMIT 1);
SET @ra_sec2  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_sec  AND ra = 'RA2 Clasifica materiales eléctricos'  LIMIT 1);
SET @ra_sec3  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_sec  AND ra = 'RA3 Calcula circuitos eléctricos'     LIMIT 1);
SET @ra_sub1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_sub  AND ra = 'RA1 Caracteriza subestaciones'        LIMIT 1);
SET @ra_sub2  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_sub  AND ra = 'RA2 Interpreta proyectos'             LIMIT 1);
SET @ra_tel1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_tel  AND ra = 'RA1 Caracteriza instrumentación'      LIMIT 1);
SET @ra_prev1 = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_prev AND ra = 'RA1 Caracteriza efectos eléctricos'   LIMIT 1);
SET @ra_ser1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_ser  AND ra = 'RA1 Distingue energías renovables'    LIMIT 1);
SET @ra_cfv1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_cfv  AND ra = 'RA1 Calcula potencial solar'          LIMIT 1);
SET @ra_gfv1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_gfv  AND ra = 'RA1 Discrimina instalaciones'         LIMIT 1);
SET @ra_geo1  = (SELECT id FROM resultados_aprendizaje WHERE idmodulo = @mod_geo  AND ra = 'RA1 Caracteriza instalaciones'        LIMIT 1);

-- ============================================================
-- 5. CRITERIOS DE EVALUACIÓN
-- ============================================================
INSERT INTO criterios_evaluacion (idmoduloRA, ce)
VALUES

  -- Sistemas eléctricos en centrales › RA1
  (@ra_sec1, 'a) Identifica características de sistemas eléctricos y tipos de redes'),
  (@ra_sec1, 'b) Distingue subsistema de generación'),
  (@ra_sec1, 'c) Distingue subsistema de transporte'),
  (@ra_sec1, 'd) Distingue subsistema de distribución'),
  (@ra_sec1, 'e) Identifica componentes del sistema eléctrico'),
  (@ra_sec1, 'f) Relaciona elementos con simbología en planos'),
  (@ra_sec1, 'g) Clasifica redes de distribución según conexión'),
  (@ra_sec1, 'h) Identifica esquemas BT y AT según puesta a tierra'),

  -- Sistemas eléctricos en centrales › RA2
  (@ra_sec2, 'a) Diferencia conductores de enlace e interior'),
  (@ra_sec2, 'b) Enumera conductores en redes aéreas'),
  (@ra_sec2, 'c) Categoriza conductores en redes subterráneas'),
  (@ra_sec2, 'd) Identifica tipos de condensadores'),
  (@ra_sec2, 'e) Clasifica aisladores'),
  (@ra_sec2, 'f) Selecciona aisladores según aislamiento'),
  (@ra_sec2, 'g) Reconoce propiedades de materiales ferromagnéticos'),
  (@ra_sec2, 'h) Diferencia elementos electromagnéticos'),

  -- Sistemas eléctricos en centrales › RA3
  (@ra_sec3, 'a) Reconoce valores de corriente alterna'),
  (@ra_sec3, 'b) Calcula tensión, intensidad y potencia monofásica'),
  (@ra_sec3, 'c) Reconoce ventajas de sistemas trifásicos'),
  (@ra_sec3, 'd) Identifica sistemas a tres y cuatro hilos'),
  (@ra_sec3, 'e) Diferencia sistemas equilibrados/desequilibrados'),
  (@ra_sec3, 'f) Calcula magnitudes en sistemas trifásicos equilibrados'),
  (@ra_sec3, 'g) Mejora factor de potencia'),
  (@ra_sec3, 'h) Calcula secciones de líneas'),
  (@ra_sec3, 'i) Selecciona protecciones eléctricas'),

  -- Subestaciones eléctricas › RA1
  (@ra_sub1, 'a) Reconoce tipos de subestaciones'),
  (@ra_sub1, 'b) Distingue función en sistema eléctrico'),
  (@ra_sub1, 'c) Distingue configuraciones'),
  (@ra_sub1, 'd) Identifica componentes'),
  (@ra_sub1, 'e) Interpreta reglamentos técnicos'),
  (@ra_sub1, 'f) Reconoce normativa aplicable'),

  -- Subestaciones eléctricas › RA2
  (@ra_sub2, 'a) Interpreta documentos del proyecto'),
  (@ra_sub2, 'b) Reconoce elementos en planos'),
  (@ra_sub2, 'c) Identifica fases de montaje'),
  (@ra_sub2, 'd) Dibuja esquemas y cronogramas'),
  (@ra_sub2, 'e) Elabora planos en CAD'),
  (@ra_sub2, 'f) Clasifica documentación técnica'),
  (@ra_sub2, 'g) Caracteriza elementos de subestación'),

  -- Telecontrol y automatismos › RA1
  (@ra_tel1, 'a) Clasifica sensores eléctricos'),
  (@ra_tel1, 'b) Reconoce funcionamiento de sensores'),
  (@ra_tel1, 'c) Identifica señales de transductores'),
  (@ra_tel1, 'd) Realiza conexión de sensores'),
  (@ra_tel1, 'e) Enumera circuitos de acondicionamiento'),
  (@ra_tel1, 'f) Reconoce instrumentos de medida'),
  (@ra_tel1, 'g) Determina valores eléctricos'),
  (@ra_tel1, 'h) Obtiene variables de red'),

  -- Prevención de riesgos eléctricos › RA1
  (@ra_prev1, 'a) Identifica factores del efecto eléctrico'),
  (@ra_prev1, 'b) Distingue umbrales eléctricos'),
  (@ra_prev1, 'c) Reconoce consecuencias de fibrilación'),
  (@ra_prev1, 'd) Explica asfixia eléctrica'),
  (@ra_prev1, 'e) Describe tetanización'),
  (@ra_prev1, 'f) Reconoce quemaduras eléctricas'),
  (@ra_prev1, 'g) Identifica efectos indirectos'),

  -- Sistemas de energías renovables › RA1
  (@ra_ser1, 'a) Define energía renovable'),
  (@ra_ser1, 'b) Define valorización energética'),
  (@ra_ser1, 'c) Enumera recursos energéticos'),
  (@ra_ser1, 'd) Valora reservas y consumos'),
  (@ra_ser1, 'e) Evalúa situación energética'),
  (@ra_ser1, 'f) Identifica energías renovables'),
  (@ra_ser1, 'g) Reconoce procesos energéticos'),
  (@ra_ser1, 'h) Identifica impactos ambientales'),

  -- Configuración instalaciones FV › RA1
  (@ra_cfv1, 'a) Define necesidades energéticas'),
  (@ra_cfv1, 'b) Cuantifica energía'),
  (@ra_cfv1, 'c) Valora energías convencionales'),
  (@ra_cfv1, 'd) Mide radiación solar'),
  (@ra_cfv1, 'e) Determina parámetros solares'),
  (@ra_cfv1, 'f) Evalúa viabilidad instalación'),
  (@ra_cfv1, 'g) Define criterios de configuración'),

  -- Gestión montaje FV › RA1
  (@ra_gfv1, 'a) Selecciona documentación'),
  (@ra_gfv1, 'b) Reconoce tipos de instalación'),
  (@ra_gfv1, 'c) Caracteriza instalación autónoma'),
  (@ra_gfv1, 'd) Reconoce instalación con apoyo'),
  (@ra_gfv1, 'e) Diferencia conexión a red'),
  (@ra_gfv1, 'f) Identifica seguimiento solar'),
  (@ra_gfv1, 'g) Reconoce telecontrol'),

  -- Gestión montaje parques eólicos › RA1
  (@ra_geo1, 'a) Identifica sistemas eólicos'),
  (@ra_geo1, 'b) Clasifica instalaciones'),
  (@ra_geo1, 'c) Describe funcionamiento'),
  (@ra_geo1, 'd) Reconoce elementos'),
  (@ra_geo1, 'e) Especifica torres y góndolas'),
  (@ra_geo1, 'f) Reconoce palas y rotor'),
  (@ra_geo1, 'g) Clasifica generadores'),
  (@ra_geo1, 'h) Reconoce transformadores'),
  (@ra_geo1, 'i) Interpreta esquemas');

COMMIT;