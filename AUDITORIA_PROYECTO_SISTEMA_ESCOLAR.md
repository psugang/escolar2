# Auditoría de Proyecto y Documento Maestro — Sistema Escolar (Baseline v2.0)

> **Fecha de Certificación:** 15 de Octubre de 2026  
> **Ubicación del Proyecto:** `/home/aaron/Sistema-escolar/escolar`  
> **Estatus de Certificación:** **100 pruebas aprobadas en Pest PHP (360 aserciones)**, 2 omitidas (pruebas base de registro Fortify), **0 errores en PHPStan (Nivel 7 / Larastan Estricto)**, `Model::preventLazyLoading` activo y **Laravel Pint 100% compliant**.

---

## 1. Resumen Ejecutivo de la Versión 2.0

El **Sistema Web de Control Escolar** ha alcanzado el estado de **Baseline v2.0**, consolidando una arquitectura multi-plantel de alto rendimiento pensada para operar en hardware propio (Intel Core i5-12400 con NVMe y almacenamiento SSD SATA secundario) atendiendo entre 3 y 10 escuelas (100 a 800 alumnos por plantel) con cero sobre-ingeniería y código limpio, tipado y mantenible.

### Métricas de Calidad
- **Pest PHP:** 100 pruebas exitosas, 360 aserciones, 0 fallos (`98 passed / 2 skipped`).
- **PHPStan:** 0 errores ejecutado con `--memory-limit=512M`.
- **Laravel Pint:** 100% validado bajo estándares PSR-12 / Laravel.
- **Prevención de N+1:** `Model::preventLazyLoading(! app()->isProduction())` activo en `AppServiceProvider`.

---

## 2. Seguridad, Privacidad y Aislamiento Multi-Tenant

1. **Aislamiento Estricto por Plantel (IDOR Prevention):**
   - Todas las consultas Eloquent y mutaciones Livewire están ancladas de forma obligatoria al identificador del plantel activo (`$this->school->id`).
   - Los identificadores sensibles y modelos de sesión cuentan con el atributo `#[Locked]` de Livewire para evitar manipulación indebida de estado en el cliente.
2. **Control de Acceso y Bloqueo de Rutas por Rol:**
   - Middleware `EnsureSchoolAccess` (`school.access`) que valida la membresía del usuario en la escuela solicitada (retornando `404 Not Found` ante intentos de acceso cruzado).
   - Middleware `EnsureSchoolRole` (`school.role`) que protege las rutas administrativas (`/ciclos`, `/alumnos`, `/profesores`, `/clases`, `/reportes`, `/auditoria`).
   - Defensa en profundidad: cada componente Livewire administrativo implementa `abort_if(auth()->user()?->isTeacherOnly($school), 403);` en su método `mount()`. Un usuario con rol exclusivo `PROFESOR` tiene denegado el acceso URL directo a módulos que no le competen.
3. **Almacenamiento Privado de Justificantes (SSD SATA + Privacidad):**
   - Configuración del disco `'justifications'` en `config/filesystems.php` apuntando a `env('JUSTIFICATIONS_DISK_PATH', storage_path('app/private/justifications'))` con visibilidad privada, documentado en `.env.example`.
   - Limpieza de metadatos EXIF/GPS en comprobantes fotográficos: procesamiento nativo con PHP GD (`imagepalettetotruecolor` + `imagewebp(..., 80)`), almacenando imágenes en WebP y documentos PDF fuera de la carpeta pública `storage/`.
   - Controlador protegido `AttendanceJustificationDocumentController` que valida la pertenencia del justificante a la escuela antes de servir el archivo binario.
4. **Protección de Invitaciones:**
   - Rate limiting activo en `AppServiceProvider` (`Limit::perMinute(10)` para `invitations.accept`).

---

## 3. Automatización de Ciclo y Gestión de Horarios

1. **Días de Suspensión Oficial (`SchoolSuspension`):**
   - Modelo y migración UUID vinculados a la escuela y ciclo escolar.
   - Interfaz en `SchoolYears/Index.php` para alta y baja de fechas inhábiles oficiales.
   - Sincronización reactiva: al registrar un día inhábil, el sistema elimina automáticamente cualquier `ClassSession` futura de esa fecha que aún no cuente con asistencias registradas (`whereDoesntHave('attendances')->delete()`).
2. **Generador Automático de Sesiones (`ClassSessionGenerator`):**
   - Generación masiva de sesiones de clase (`ClassSession`) para todo el rango del ciclo escolar (`starts_on` a `ends_on`), respetando los horarios semanales (`ClassSchedule`).
   - Exclusión automática de fines de semana (sábados y domingos) y de todas las fechas registradas en `SchoolSuspension`.
   - Generación de UUIDs v7 (`Str::uuid7()`) con inserción por lotes mediante `insertOrIgnore` en bloques de 500 registros, garantizando ejecuciones idempotentes sin duplicar sesiones existentes.

---

## 4. Importador CSV Inteligente con Dry-Run y Re-inscripciones

1. **Flujo de Pre-visualización (*Dry-Run*):**
   - Carga y análisis en memoria de archivos `.csv` en dos pasos: análisis (`previewImportCsv`) y confirmación (`confirmImport`).
   - Detección automática por CURP o por nombre completo normalizado en $O(1)$.
   - Clasificación visual en **Re-inscripciones** y **Nuevos Ingresos** con desglose de filas con inconsistencias antes de alterar la base de datos.
2. **Re-inscripciones No Destructivas:**
   - Para alumnos existentes del ciclo anterior, cierra la inscripción previa registrando la fecha de corte y estado `COMPLETADO`.
   - Abre la nueva inscripción en el ciclo y grupo destino manteniendo el expediente del estudiante y todos sus tutores vinculados intactos.
3. **Carga Opcional de Tutores:**
   - Columnas admitidas: `grado,grupo,apellido_paterno,apellido_materno,nombre,curp,tutor_nombre,tutor_correo,tutor_telefono`.
   - Si se proporcionan datos de tutor, `syncCsvGuardian()` asocia o crea el registro `Guardian` y `StudentGuardian` evitando duplicados si el tutor ya está vinculado, con soporte para hermanos mediante coincidencia de correo.
   - Plantilla descargable en vivo (`plantilla_alumnos.csv`).

---

## 5. Experiencia de Usuario (UX) Mobile-First del Docente

1. **Auto-redirección Inteligente en `/dashboard`:**
   - Si el usuario autenticado pertenece a una sola escuela y no es administrador global, el sistema lo conduce directamente a su panel escolar sin clics intermedios.
2. **Navegación Focalizada (`sidebar.blade.php`):**
   - Mediante `User::isTeacherOnly($school)`, el menú lateral oculta módulos administrativos irrelevantes al docente, ofreciéndole una navegación limpia centrada en *Mis Clases e Inicio*, *Pase de Asistencia* y *Justificantes*.
3. **Sección "Mis Clases de Hoy" (`School/Dashboard.php`):**
   - Tarjetas táctiles ordenadas cronológicamente por hora de inicio, con información de materia, grupo, turno, badge de estatus (*Asistencia Lista* vs *Pendiente*) y botón táctil de 48px que abre la sesión automáticamente vía URL (`?session={id}`).
4. **Pase de Lista Instantáneo en Cliente (`DailyAttendance`):**
   - **Cero latencia de red:** Enlace de estado cliente-servidor mediante `@entangle` diferido (sin `.live`), permitiendo al docente marcar a los ausentes instantáneamente en pantallas móviles táctiles sin esperar roundtrips HTTP por cada toque.
   - **Botones ergonómicos:** Controles táctiles de 44px de altura (`min-h-[44px]`).
   - **Atajo inteligente sin ausentes:** Al pulsar *"Guardar y Confirmar Lista"*, si no hay ausencias, el sistema guarda la lista directamente sin abrir el modal de correos, mostrando un toast inmediato.
   - **Feedback visual de aviso a tutores:** Badge discreto `Notificado` en la lista y en el modal para identificar a los alumnos cuyos tutores ya recibieron notificación por correo (`absence_notified_at !== null`).
5. **Filtrado Estricto de Sesiones por Rol:**
   - Los docentes únicamente visualizan sus propias clases o suplencias en el pase de lista diario.
   - Administradores, directores y control escolar mantienen visibilidad global de todas las sesiones del día.

---

## 6. Rendimiento y Optimización de Base de Datos

1. **Eliminación de Consultas N+1:**
   - `Reports/Index.php`: Consultas de estadísticas de asistencia de alumnos y grupos migradas a `withCount` y agregaciones directas en SQL (`SUM(CASE ...)`), eliminando bucles iterativos `Attendance::where()->get()`.
   - `Classes/Index.php`: Colección `$allStudents` diferida condicionalmente a `$this->showWorkshopStudentsModal === true`.
   - `DailyAttendance::saveAttendance()`: Precarga de asistencias existentes con `->keyBy('student_id')` antes de la transacción, usando `Attendance::create()` para nuevos registros con soporte total para UUIDs.
2. **Índices Compuestos de Rendimiento:**
   - Índices en `attendances(class_session_id, status)`, `class_sessions(school_class_id, date, start_time)` y `audit_logs(school_id, created_at)`.

---

## 7. Mapa de Rutas de la Aplicación

```text
/
├── login                                           -> Inicio de sesión Fortify
├── dashboard                                       -> Selector de planteles (o auto-redirección de profesor)
├── settings/profile                                -> Perfil de usuario y seguridad
├── invitacion/{token}                              -> Aceptación de invitación docente
├── admin/
│   └── escuelas                                    -> Gestión global de planteles (ADMIN_GLOBAL)
└── escuela/{school}/
    ├── dashboard                                   -> Panel de escuela / "Mis Clases de Hoy"
    ├── asistencia                                  -> Pase de lista diario (filtrado por rol)
    ├── justificantes                               -> Consulta de inasistencias y carga de justificantes
    ├── justificantes/{justification}/documento     -> Descarga privada protegida de justificante
    └── [school.role: ADMIN_ESCOLAR, DIRECTOR, CONTROL_ESCOLAR]
        ├── ciclos                                  -> Ciclos escolares, grupos y días de suspensión
        ├── alumnos                                 -> Padrón escolar, importador CSV con dry-run y tutores
        ├── profesores                              -> Directorio docente e invitaciones por correo
        ├── clases                                  -> Materias, clases, talleres multigrado y horarios
        ├── reportes                                -> Reportes agregados y exportación CSV con UTF-8 BOM
        └── auditoria                               -> Bitácora inmutable de eventos del plantel
```

---

## 8. Certificación de la Suite de Pruebas (100 Pruebas Pest)

```bash
# Ejecución integral de pruebas y análisis
php artisan test
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test
```

### Detalle de Cobertura por Archivo de Prueba:
- `tests/Feature/School/AttendanceAndJustificationTest.php` (9 pruebas: pase de lista, auditoría, sustitutos, justificantes WebP, aislamiento de documentos privados, filtro por rol de profesor y atajo 0 ausentes).
- `tests/Feature/School/SessionAutomationAndCsvReEnrollmentTest.php` (4 pruebas: generador de sesiones con exclusión de fines de semana y suspensiones, borrado reactivo de sesiones por día inhábil, importador CSV dry-run con re-inscripción y vinculación de tutores).
- `tests/Feature/School/StudentManagementTest.php` (10 pruebas: expedientes, matrículas, cambio de grupo histórico, bajas y plantilla CSV).
- `tests/Feature/School/TeacherInvitationTest.php` (6 pruebas: invitaciones firmadas, aceptación de tokens, rate limiting y auditoría).
- `tests/Feature/School/TeacherAndClassTest.php` (5 pruebas: catálogo de materias, talleres multigrado y asignación docente).
- `tests/Feature/School/SchoolYearAndGroupTest.php` (8 pruebas: ciclos escolares y generación por grado/turno).
- `tests/Feature/School/AuditAndReportsTest.php` (4 pruebas: bitácora inmutable, reportes agregados y exportación CSV).
- `tests/Feature/SchoolAccessMiddlewareTest.php` (5 pruebas: prevención de IDOR entre planteles y bloqueo 403 Forbidden para profesor en rutas administrativas).
- `tests/Feature/Admin/SchoolManagementTest.php` (11 pruebas: gestión multi-escuela y control global).
- Pruebas base de autenticación y seguridad Fortify (38 pruebas).

---
*Fin del Baseline v2.0 — Sistema Escolar preparado para despliegue y puesta en marcha operativa.*
