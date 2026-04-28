-- ============================================================
-- SEEDER: Completar familias profesionales desde fablab.sql
-- Fecha: 2026-04-28
--
-- Qué hace este script:
--   1. Elimina el duplicado roto de "Energía y Agua" (id=9,
--      encoding UTF-8 corrupto). id=16 es el correcto.
--   2. Inserta las 21 familias profesionales que fablab tiene
--      y nuestra BD no tiene. Usa INSERT IGNORE sobre el
--      unique de `nombre`, así es idempotente.
--   3. Corrige familia_id en los 2 ciclos donde fablab tiene
--      la clasificación oficial más precisa que la nuestra.
--
-- Qué NO hace:
--   - No toca ciclos, módulos, resultados ni criterios
--     (ya los tenemos todos, incluso con más datos que fablab).
--   - No usa los IDs de fablab para las familias nuevas
--     (conflictirían con nuestros IDs existentes 1-8, 16).
--
-- Seguridad:
--   - Toda la operación es una transacción única.
--   - Las familias nuevas solo se insertan si no existen por nombre.
--   - Los UPDATEs de ciclos tienen guarda WHERE para no aplicarse
--     si ya fueron corregidos en una ejecución anterior.
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. LIMPIAR DUPLICADO: Energía y Agua id=9 (encoding roto)
-- ============================================================
-- Verificación: ningún ciclo ni empresa_familia apunta a id=9
-- (confirmado en data_seed.sql — se ejecuta igual por seguridad).

-- ciclos_formativos: ON DELETE SET NULL, pero actualizamos
-- explícitamente para mantener coherencia con el campo texto.
UPDATE ciclos_formativos
SET    familia_id = 16,
       familia    = 'Energía y Agua'
WHERE  familia_id = 9;

-- empresa_familia: FK RESTRICT, hay que limpiar antes de borrar.
UPDATE empresa_familia
SET    familia_id = 16,
       familia    = 'Energía y Agua'
WHERE  familia_id = 9;

-- Borrar el duplicado con nombre mal codificado.
DELETE FROM familias WHERE id = 9;


-- ============================================================
-- 2. INSERTAR LAS 21 FAMILIAS FALTANTES
-- INSERT IGNORE respeta el UNIQUE en `nombre`, así que si
-- se vuelve a ejecutar el script no duplica nada.
-- imagen_url queda NULL; se puede rellenar más adelante.
-- ============================================================

INSERT IGNORE INTO familias (nombre, imagen_url, created_at, updated_at) VALUES
('Artes Gráficas',                            NULL, NOW(), NULL),
('Artes y Artesanías',                         NULL, NOW(), NULL),
('Edificación y Obra Civil',                   NULL, NOW(), NULL),
('Electricidad y Electrónica',                 NULL, NOW(), NULL),
('Hostelería y Turismo',                       NULL, NOW(), NULL),
('Imagen Personal',                            NULL, NOW(), NULL),
('Industrias Alimentarias',                    NULL, NOW(), NULL),
('Industrias Extractivas',                     NULL, NOW(), NULL),
('Instalación y Mantenimiento',                NULL, NOW(), NULL),
('Marítimo-Pesquera',                          NULL, NOW(), NULL),
('Química',                                    NULL, NOW(), NULL),
('Sanidad',                                    NULL, NOW(), NULL),
('Seguridad y Medio Ambiente',                 NULL, NOW(), NULL),
('Servicios Socioculturales y a la Comunidad', NULL, NOW(), NULL),
('Textil, Confección y Piel',                  NULL, NOW(), NULL),
('Vidrio y Cerámica',                          NULL, NOW(), NULL),
('Actividades Físicas y Deportivas',           NULL, NOW(), NULL),
('Aeronáutica',                                NULL, NOW(), NULL),
('Biotecnología',                              NULL, NOW(), NULL),
('Logística y Almacén',                        NULL, NOW(), NULL),
('Telecomunicaciones',                         NULL, NOW(), NULL);


-- ============================================================
-- 3. CORREGIR FAMILIA_ID EN DOS CICLOS MAL CLASIFICADOS
--
-- Solo actualizamos donde fablab tiene la clasificación
-- oficial del BOE claramente más precisa. El resto de
-- ciclos (82, 94, 113, 130, 134) ya tienen la asignación
-- correcta en nuestra BD según el BOE.
--
-- Guarda: la condición AND familia_id = 8 evita que el UPDATE
-- se aplique si el ciclo ya fue corregido anteriormente.
-- ============================================================

-- Ciclo 114: "Electromecánica de Maquinaria" (GM)
-- Nuestra BD: TMV (id=8)  |  BOE / fablab: Electricidad y Electrónica
UPDATE ciclos_formativos
SET    familia_id = (SELECT id FROM familias
                     WHERE nombre = 'Electricidad y Electrónica' LIMIT 1),
       familia    = 'Electricidad y Electrónica'
WHERE  id = 114
  AND  familia_id = 8;

-- Ciclo 115: "Mantenimiento de embarcaciones de recreo" (GM)
-- Nuestra BD: TMV (id=8)  |  BOE / fablab: Marítimo-Pesquera
UPDATE ciclos_formativos
SET    familia_id = (SELECT id FROM familias
                     WHERE nombre = 'Marítimo-Pesquera' LIMIT 1),
       familia    = 'Marítimo-Pesquera'
WHERE  id = 115
  AND  familia_id = 8;


COMMIT;
