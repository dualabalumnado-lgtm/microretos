# DuaLab — Security Fixes Backlog (SAST/DAST)

> Formato optimizado para Claude Code. Cada ítem incluye archivo(s), acción concreta y criterio de done.
> Prioridades: 🔴 CRÍTICA → 🟠 ALTA → 🟡 MEDIA → 🟢 BAJA

---

## 🔴 CRÍTICAS

### C1 — Bypass de roles: empresa puede crear/editar centros, familias y ciclos
- **Archivo:** `routes/api.php`
- **Fix:** Envolver todas las rutas de escritura/edición/borrado de centros, familias y ciclos en `Route::middleware('admin')` (o middleware `EnsureIsDocenteOrAdmin`), igual que ya se hace en gestión de usuarios.
- **Done cuando:** Un token de rol `empresa` recibe 403 al hacer POST/PUT/DELETE sobre esos endpoints.

### C2 — Frontend y backend no comparten la misma matriz de acceso
- **Archivos:** `frontend-microretos/src/stores/auth.js`, `routes/api.php`
- **Fix:** Auditar cada endpoint de escritura/borrado contra la matriz de roles real y aplicar el middleware de rol en el backend (no solo ocultar en el frontend).
- **Done cuando:** Cada endpoint de escritura tiene middleware de rol y hay un test que lo verifica.

### C3 — MicroretoIAController escribe en BD sin validar campos
- **Archivo:** `app/Http/Controllers/MicroretoIAController.php`
- **Fix:** Añadir `$request->validate([...])` o un Form Request antes de cualquier `save()` / `create()`. Tratar campos generados por IA como no confiables.
- **Done cuando:** Un payload con `uuid` ajeno es rechazado con 422.

### C4 — PapeleraController: TypeError 500 con `$id` string malformado
- **Archivo:** `app/Http/Controllers/PapeleraController.php`
- **Fix:** Añadir `$request->validate(['id' => 'required|integer'])` al inicio de `restaurar()` y `destruir()`.
- **Done cuando:** Una petición con `id=aaaaa` devuelve 422, no 500.

### C5 — AdminUserController no valida longitud de email/password (excepción SQL)
- **Archivos:** `app/Http/Controllers/AdminUserController.php`, `frontend-microretos/src/views/gestionusuarios.vue`
- **Fix:** Añadir `'email' => 'required|email|max:255'` y `'password' => 'required|max:255'` en backend; replicar `maxlength="255"` en frontend.
- **Done cuando:** Un email de 300 chars devuelve 422 sin tocar la BD.

### C6 — DemoController usa `$familia` sin validar (posible abuso/inyección)
- **Archivos:** `app/Http/Controllers/DemoController.php`, `routes/api.php`
- **Fix:** Añadir en las rutas `/demos`, `/demos/{familia}`, `/demos/{familia}/microretos`:
  ```php
  ->where('familia', '^[a-zA-Z0-9\s\-_%,.áéíóúÁÉÍÓÚñÑ]{1,100}$')
  ```
- **Done cuando:** Un string de 200 chars en `{familia}` devuelve 404/422.

### C7 — Axios 1.14.0 / 1.15.0 — múltiples CVEs críticos
- **Archivo:** `package.json`, `package-lock.json`
- **Fix:** `npm install axios@^1.15.2` + `npm audit fix`. Regenerar `package-lock.json`.
- **Done cuando:** `npm audit` no reporta CVEs críticos en Axios.

### C8 — PHPUnit / php-file-iterator con CVEs altos
- **Archivos:** `composer.json`, `composer.lock`
- **Fix:** `composer update phpunit/phpunit` (y familia auxiliar). Verificar compatibilidad.
- **Done cuando:** `composer audit` limpio en dependencias de PHPUnit.

### C9 — Login genera hasta 10 tokens simultáneos (sesiones concurrentes)
- **Archivo:** `app/Http/Controllers/AdminAuthController.php`
- **Fix:** Al inicio del método `login()`, añadir `$user->tokens()->delete()` antes de crear el nuevo token.
- **Done cuando:** Un segundo login invalida todos los tokens anteriores del mismo usuario.

---

## 🟠 ALTAS

### A1 — Sin bloqueo de cuenta por intentos fallidos de login
- **Archivo:** `app/Http/Controllers/AdminAuthController.php`
- **Fix:** Combinar el throttle por IP con un contador `failed_logins` por `email+IP`; bloquear temporalmente tras N intentos y resetear en login correcto.
- **Done cuando:** Tras 10 intentos fallidos la cuenta queda bloqueada X minutos.

### A2 — Sin longitud mínima/máxima de contraseña en login
- **Archivos:** `frontend-microretos/src/components/LoginModal.vue`, `app/Http/Controllers/AdminAuthController.php`
- **Fix:** Aplicar `minlength="16"` y `maxlength="128"` en frontend; `'password' => 'min:16|max:128'` en backend.
- **Done cuando:** Contraseña de 4 chars es rechazada con mensaje claro.

### A3 — Autocompletado de contraseña habilitado en LoginModal
- **Archivo:** `frontend-microretos/src/components/LoginModal.vue`
- **Fix:** Añadir `autocomplete="new-password"` (o `"off"`) al input de contraseña.
- **Done cuando:** El navegador no ofrece autocompletar la contraseña de login.

### A4 — Sesión puede renovarse indefinidamente sin expiración absoluta
- **Archivo:** `frontend-microretos/src/App.vue`
- **Fix:** Implementar un timestamp `session_started_at` en `localStorage`; forzar logout si supera X horas aunque el usuario esté activo.
- **Done cuando:** Tras el tiempo máximo absoluto se fuerza logout independientemente de la actividad.

### A5 — Cambio de rol no se refleja en sesiones activas
- **Archivos:** `frontend-microretos/src/stores/auth.js`, `app/Http/Controllers/AdminAuthController.php`
- **Fix:** En el método `refresh()`, revalidar rol real en BD; si difiere del token, llamar `logout()` y forzar reautenticación.
- **Done cuando:** Si un admin cambia el rol de un usuario logueado, su sesión expira en el siguiente refresh.

### A6 — SESSION_SECURE_COOKIE desactivado
- **Archivo:** `.env`
- **Fix:** Añadir `SESSION_SECURE_COOKIE=true` en `.env` de producción.

### A7 — SESSION_ENCRYPT desactivado
- **Archivo:** `.env`
- **Fix:** Añadir `SESSION_ENCRYPT=true` en `.env` de producción.

### A8 — HSTS no configurado
- **Archivo:** `public/.htaccess`
- **Fix:** Añadir:
  ```apache
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
  ```

### A9 — Cabecera X-Powered-By expone versión de PHP
- **Archivo:** Configuración del servidor (`php.ini` / Apache)
- **Fix:** Añadir `expose_php = Off` en `php.ini` o `Header unset X-Powered-By` en Apache.

### A10 — Content-Security-Policy ausente
- **Archivo:** Configuración del servidor
- **Fix:** Definir CSP ajustada a orígenes reales de la app (Cloudinary, OpenAI, etc.) en todas las respuestas.

### A11 — Cookie XSRF-TOKEN sin flag HttpOnly
- **Archivo:** `config/session.php`
- **Fix:** Verificar `'http_only' => true` en la configuración de cookies de Laravel.

### A12 — Cabeceras COEP / COOP / CORP ausentes
- **Archivo:** Configuración del servidor
- **Fix:** Añadir:
  ```
  Cross-Origin-Embedder-Policy: require-corp
  Cross-Origin-Opener-Policy: same-origin
  Cross-Origin-Resource-Policy: same-origin
  ```

### A13 — X-Content-Type-Options ausente
- **Archivo:** Configuración del servidor
- **Fix:** Añadir `X-Content-Type-Options: nosniff` en todas las respuestas.

### A14 — No se registran intentos de login fallidos ni fallos de control de acceso
- **Archivo:** (crear servicio de log centralizado)
- **Fix:** Implementar logging en: logins fallidos, accesos denegados por rol, tokens inválidos/expirados. Sin guardar contraseñas ni tokens en claro.

### A15 — Sin validación de entrada centralizada
- **Fix:** Crear Form Requests reutilizables con reglas comunes: charset UTF-8, rechazo de bytes nulos, saltos de línea, path traversal, longitudes máximas.

---

## 🟡 MEDIAS

### M1 — UploadController sin límite de tamaño validado
- **Archivo:** `app/Http/Controllers/UploadController.php` línea 30
- **Fix:** Añadir `'file' => 'max:10240'` (o el límite definido) en la validación. Configurar también en `php.ini` (`upload_max_filesize`) y en el formulario (`maxlength`).

### M2 — Hash débil en UploadController (líneas 69 y 172)
- **Archivo:** `app/Http/Controllers/UploadController.php`
- **Fix:** Sustituir por `bcrypt` / `Hash::make()` si es para contraseñas, o `hash('sha256', ...)` con sal si es para integridad.

### M3 — Regex con riesgo de ReDoS en LoginModal (línea 101)
- **Archivo:** `frontend-microretos/src/components/LoginModal.vue`
- **Fix:** Simplificar la expresión regular o reemplazarla por validación por pasos sin backtracking superlineal.

### M4 — Complejidad cognitiva alta
- **Archivos:** `MicroretoIAController.php`, `DatosFPController.php`, `MicroproyectoController.php`, `StartupDayWizard.vue`
- **Fix:** Dividir métodos largos, extraer lógica a helpers/servicios. Objetivo: complejidad cognitiva < 15 por método (SonarQube).

### M5 — Problemas de accesibilidad en componentes Vue
- **Archivos:** Varios componentes Vue
- **Fix:** Asociar cada `<label>` con su `<input>` vía `for`/`id`, corregir contrastes, ajustar `autocomplete`.

### M6 — Código muerto, imports duplicados y TODOs pendientes
- **Fix:** Eliminar código comentado, limpiar imports duplicados, resolver o eliminar TODOs. Puede hacerse con ESLint + `no-unused-vars`.

### M7 — `window` en lugar de `globalThis` en frontend
- **Fix:** Buscar y reemplazar usos de `window` por `globalThis` donde aplique.

### M8 — Bloques `if` sin llaves en backend
- **Fix:** Añadir llaves `{}` a todos los bloques condicionales. Configurable con PHP-CS-Fixer.

### M9 — Literales duplicados en rutas y seeders
- **Archivos:** `routes/api.php`, seeders
- **Fix:** Extraer constantes o variables para strings repetidos.

### M10 — DatosFPController con demasiadas responsabilidades
- **Archivo:** `app/Http/Controllers/DatosFPController.php`
- **Fix:** Dividir en controladores más pequeños por responsabilidad. Reducir métodos con múltiples `return`.

### M11 — follow-redirects 1.15.11 — fuga de cabeceras al redirigir
- **Archivo:** `package-lock.json`
- **Fix:** `npm install follow-redirects@^1.16.0`.

### M12 — postcss 8.5.9 — riesgo XSS en CSS dinámico
- **Archivo:** `package-lock.json`
- **Fix:** `npm update postcss`. Revisar dónde se genera CSS desde datos no confiables.

### M13 — Stack Symfony por debajo de versión segura
- **Archivo:** `composer.lock`
- **Fix:** `composer update symfony/*`. Verificar compatibilidad entre componentes.

### M14 — nette/schema 1.3.5 con vulnerabilidad media
- **Archivo:** `composer.lock`
- **Fix:** Actualizar la dependencia que arrastra `nette/schema` o la dependencia directa.

### M15 — Límites de caracteres desalineados entre frontend, backend y BD
- **Archivos:** Varios controladores y vistas
- **Fix:** Inventariar límites reales de cada campo en BD y replicarlos como `max:N` en backend y `maxlength="N"` en frontend.

### M16 — Canonicalización incompleta de nombres de empresa (duplicados posibles)
- **Fix:** Implementar listado centralizado de empresas, normalizar nombre antes de comparar, validar duplicados antes de `INSERT`.

### M17 — Ruta `/startup/proyectos/{uuid}` muestra página en blanco con UUID inválido
- **Fix:** Añadir guard en el controlador/router para devolver 404 controlado cuando el UUID no existe.

### M18 — Lógica de autorización dispersa (sin Gates/Policies)
- **Archivo:** `app/Providers/AppServiceProvider.php` + controladores varios
- **Fix:** Migrar comprobaciones manuales de rol a `Gate::define()` en `AppServiceProvider` y usar `Gate::authorize()` en los controladores.

### M19 — Base de datos usa usuario `root` en producción
- **Archivo:** `.env` (producción)
- **Fix:** Crear usuario MySQL con privilegios mínimos (`SELECT, INSERT, UPDATE, DELETE`) solo sobre la BD del proyecto. Actualizar `.env`.

### M20 — Sin auditoría automática de cuentas inactivas
- **Archivo:** `app/Console/Kernel.php`
- **Fix:** Crear comando `php artisan users:deactivate-inactive` y programarlo diariamente en el Scheduler. Bloquear cuentas sin actividad en 30 días.

### M21 — APP_DEBUG puede estar activo en producción
- **Archivo:** `.env` (producción)
- **Fix:** Confirmar `APP_DEBUG=false`. Incluir en checklist de despliegue.

### M22 — Sin política documentada de gestión de claves criptográficas
- **Fix:** Documentar ciclo de vida de `APP_KEY` y credenciales externas: generación, rotación, revocación, almacenamiento.

### M23 — Contraseña de BD sin política de robustez documentada
- **Archivo:** `.env` (producción)
- **Fix:** Generar contraseña robusta (≥20 chars, aleatoria). Documentar política y rotación periódica.

### M24 — Sin proceso recurrente de actualización de dependencias
- **Fix:** Configurar GitHub Dependabot o equivalente. Establecer revisión mensual de `npm audit` y `composer audit`.

### M25 — Revisar configuración del servidor: listado de dirs, métodos HTTP, WebDAV
- **Archivo:** Configuración del servidor (producción)
- **Fix:** Deshabilitar listado de directorios, restringir a métodos HTTP necesarios (GET, POST, PUT, DELETE), desactivar WebDAV si no se usa.

---

## 🟢 BAJAS / MEJORAS

### B1 — Cambios en panel empresa no persisten al navegar adelante/atrás
- **Archivo:** Panel Generador (wizard)
- **Fix:** Revisar gestión de estado del wizard para que persista entre pasos (Pinia store o `sessionStorage`).

### B2 — Se puede reasignar centro educativo a empresa ya asignada sin aviso
- **Fix:** Bloquear o mostrar aviso de confirmación al intentar reasignar centro ya vinculado.

### B3 — Avisos de validación solo aparecen al final del formulario
- **Archivos:** Panel de proyectos / Panel Generador
- **Fix:** Mover validación a evento `blur`/`change` de cada campo, no solo al submit.

### B4 — Campos autocompletados (centro, empresa, reto) son editables libremente
- **Archivos:** Panel de proyectos (crear proyecto)
- **Fix:** Marcar campos como `readonly` tras autocompletar, o mostrar aviso si se editan.

### B5 — Se puede avanzar sin añadir ningún objetivo al proyecto
- **Archivo:** Panel de proyectos (paso objetivos)
- **Fix:** Bloquear el botón "continuar" hasta que haya al menos un objetivo. Mostrar aviso inmediato.

### B6 — Publicar/archivar proyecto se puede pulsar varias veces
- **Archivo:** Panel de proyectos (paso publicar)
- **Fix:** Deshabilitar el botón tras la primera acción o mostrar spinner + estado.

### B7 — Estado "validado" incorrecto aunque empresa indique que no es viable
- **Archivo:** Panel de proyectos (paso publicar/validación)
- **Fix:** Corregir lógica de estado para reflejar la respuesta real de la empresa.

### B8 — Selector de proyectos a validar no filtra por empresa
- **Archivo:** Panel directorio de empresas (enviar validación)
- **Fix:** Filtrar la lista para mostrar solo proyectos asociados a esa empresa/instituto.

### B9 — Dashboard docente permite guardar sesiones duplicadas sin aviso
- **Archivo:** Dashboard docente (creación de sesión)
- **Fix:** Detectar sesión con datos idénticos antes de guardar y mostrar confirmación.

### B10 — Validación de longitud en dashboard docente solo en backend
- **Archivos:** `app/Http/Controllers/SesionController.php`, `frontend-microretos/src/views/dashboarddocente.vue`
- **Fix:** Añadir `maxlength="255"` en frontend para nombre del instituto.

### B11 — Mensaje confuso en "crear nueva empresa"
- **Archivo:** Panel de base de datos
- **Fix:** Reescribir el texto del mensaje para mayor claridad.

### B12 — Se puede añadir empresa con estado "descartada"
- **Fix:** Definir regla de negocio. Si no debe permitirse, añadir validación explícita.

### B13 — Rol empresa puede ver secciones de docente
- **Fix:** Revisar y restringir en frontend y backend todas las acciones no permitidas al rol empresa.

### B14 — Login de empresa no filtra proyectos por su empresa concreta
- **Fix:** Asociar cuenta empresa a una empresa concreta al autenticar; filtrar todos los listados.

### B15 — Barra lateral muestra vista de administrador antes del login
- **Archivo:** Frontend (layout principal)
- **Fix:** Mostrar sidebar neutro/bienvenida cuando no hay sesión activa.

### B16 — Archivos CSV/Excel quedan en servidor tras importación
- **Archivo:** Rutas de importación (si se reactivan)
- **Fix:** Añadir `File::delete($path)` tras procesar el archivo.

### B17 — Sin escaneo antivirus en archivos subidos
- **Archivo:** `app/Http/Controllers/UploadController.php`
- **Fix:** Evaluar integrar ClamAV (o similar) antes de mover el archivo a almacenamiento definitivo.

### B18 — Contraseñas de docente/empresa no confirmadas en `.env`
- **Archivo:** `.env`
- **Fix:** Confirmar que están en `.env` / GitHub Secrets, no hardcodeadas en el código.

### B19 — Módulo de contacto empresas sin límite de intentos
- **Archivo:** `app/Http/Controllers/EmpresaContactoController.php`
- **Fix:** Añadir rate limiting en `verificarAcceso()`. Revisar autorización en `contactar()` y `enviarValidacion()`.

### B20 — Sin tabla de auditoría inmutable
- **Fix:** Verificar existencia; si no existe, implementar tabla append-only para acciones administrativas sensibles.

### B21 — Datos sensibles / menores sin anonimización
- **Fix:** Aplicar minimización de datos, cifrado donde corresponda y anonimizar identificadores de alumnado menor de edad.

### B22 — Falta documentar flujos de datos y autenticación
- **Archivo:** Documentación del proyecto
- **Fix:** Elaborar: flujo de usuario, autenticación/autorización, flujo de negocio con casos de abuso, DFD, diagrama de estado, trust boundaries y modelo STRIDE.

### B23 — Sin auditoría de cumplimiento RGPD/LOPDGDD
- **Fix:** Realizar checklist AEPD. Valorar EIPD si se tratan datos de menores.

### B24 — Sin autoevaluación ENS
- **Fix:** Realizar autoevaluación ENS según categoría de los datos tratados.

### B25 — Pruebas funcionales, de rendimiento y pentest pendientes
- **Fix:** Planificar tras aplicar fixes críticos y altos: tests funcionales, pruebas de carga y revisión manual OWASP ASVS completa.

---

## Notas

- El análisis DAST (OWASP ZAP) está **aún en curso**. Repetir escaneo tras aplicar los fixes de cabeceras (A8–A13).
- Orden de trabajo recomendado: **C → A → M → B**.
- Cada fix marcado con "Done cuando" tiene criterio verificable para CI o tests manuales.
