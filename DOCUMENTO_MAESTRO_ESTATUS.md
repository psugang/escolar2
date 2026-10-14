# Sistema Web de Control Escolar — Documento Maestro, Capacidades y Estatus de Desarrollo

> **Fecha de actualización:** 13 de Octubre de 2026  
> **Ubicación del proyecto:** `/home/aaron/Sistema-escolar/escolar`  
> **Estatus general de calidad:** 83 pruebas automatizadas en Pest (81 aprobadas, 2 omitidas de registro Fortify), 0 errores en PHPStan Nivel 7 (Larastan estricto), Laravel Pint 100% compliant. Base de datos PostgreSQL 17 migrada y sincronizada.

---

## 1. Visión del Producto y Filosofía Arquitectónica
Sistema web integral de control escolar y asistencia para escuelas secundarias (con diseño extensible a primaria y bachillerato).
* **Multi-tenant / Multi-escuela real:** Una sola instalación administra múltiples planteles escolares manteniendo los datos estrictamente aislados mediante llaves foráneas indexadas y middlewares (`school.access`, `school.role`, `global.admin`).
* **Consistencia e integridad histórica:** No se destruye información de ciclos pasados, asistencias anteriores o cambios de grupo/turno. Toda modificación genera trazabilidad.
* **Agilidad operativa sin fricción:** Interfaz reactiva en tiempo real donde directivos y profesores realizan tareas comunes (inscribir alumnos, importar listas masivas por CSV, pasar lista en un clic, justificar faltas con comprobantes y emitir reportes) de forma inmediata.

---

## 2. Stack Tecnológico Real

| Capa | Tecnología | Justificación y Propósito |
| :--- | :--- | :--- |
| **Backend & Framework** | **Laravel 12/13 (PHP 8.3+)** | Eloquent ORM, UUIDs v4/v7 nativos, validación declarativa, transacciones y middlewares de aislamiento. |
| **Frontend & Reactividad** | **Livewire 4 + Flux UI** | SPA reactiva con componentes Blade declarativos, sin necesidad de compilar frontend separado ni desincronizar APIs. |
| **Estilos & UI** | **Tailwind CSS v4** | Diseño responsivo moderno, paleta visual accesible y soporte nativo de modo oscuro. |
| **Base de Datos** | **PostgreSQL 17** (Docker) / SQLite (tests en memoria) | Almacenamiento transaccional robusto, UUIDs nativos, índices compuestos e integridad referencial estricta. |
| **Autenticación & Seguridad**| **Laravel Fortify** | Sesiones seguras en base de datos, 2FA (Two-Factor Authentication), expiración y hash de contraseñas con Bcrypt. |
| **Testing & Calidad** | **Pest PHP 5, PHPStan (Nivel 7), Pint** | Tipado estricto, análisis estático de tipos, formateo PSR-12 y 83 pruebas de cobertura. |

---

## 3. Capacidades del Sistema e Implementaciones Realizadas

### 👑 1. Administración Global y Multi-Tenant (Secciones 4, 5 y 6)
* **Gestión de Escuelas (`/admin/escuelas`):** Alta de planteles, clave de centro de trabajo (C.C.T.), búsqueda en tiempo real y edición.
* **Protección contra borrado accidental:** Bloqueo automático que impide eliminar escuelas con ciclos o alumnos registrados.
* **Asignación de Administradores:** Asignación directa de usuarios existentes o nuevos con rol `ADMIN_ESCOLAR`.
* **Selector Dinámico de Plantel (`/dashboard`):** Menú para que usuarios con membresía en múltiples escuelas seleccionen el plantel deseado con insignias visuales de su rol activo.

### 📅 2. Ciclos Escolares, Turnos y Grupos (Secciones 8, 9 y 10)
* **Gestión de Ciclos (`/escuela/{school}/ciclos`):** Periodos fechados con detección automática del ciclo activo.
* **Generador Rápido de Grupos:** Creación ágil por grado (1°, 2°, 3°) y sección ('A', 'B', 'C'...) con asignación de turno (`MATUTINO` / `VESPERTINO`).

### 🎓 3. Control de Alumnos, Historial e Importación Masiva (Secciones 11, 12 y 13)
* **Padrón Estudiantil (`/escuela/{school}/alumnos`):** Búsqueda por nombre, CURP o matrícula, con filtros por ciclo, grupo y estado.
* **Inscripción Individual:** Alta de expedientes con asignación de grupo y fecha de inicio.
* **Importación Masiva vía CSV:**
  * Carga rápida de listas de alumnos con columnas: `grado,grupo,apellido_paterno,apellido_materno,nombre`.
  * Detección y creación automática del grupo si no existe en el ciclo.
  * Descarga directa de plantilla de ejemplo (`plantilla_alumnos.csv`).
  * Validación de filas con reporte de incidencias y registro en auditoría.
* **Cambio de Grupo/Turno Fechado:** Cierra la inscripción previa (`CAMBIO_GRUPO`, con fecha `ends_on`) y abre la nueva inscripción manteniendo el historial escolar intacto.
* **Baja Escolar Segura:** Marcado de estado `BAJA` y cierre de inscripción sin destruir asistencias ni calificaciones pasadas.
* **Expediente de Tutores:** Vinculación o registro de hasta 2 tutores por alumno (con validación de parentesco, teléfono y correo electrónico).

### 👨‍🏫 4. Directorio Docente, Materias y Clases/Talleres (Secciones 7, 15, 16, 17 y 18)
* **Profesores Multi-Escuela (`/escuela/{school}/profesores`):** Directorio docente, número de empleado, teléfono, correo y creación simultánea de cuenta de usuario con rol `PROFESOR`. Un mismo docente puede laborar en varios planteles.
* **Materias y Clases (`/escuela/{school}/clases`):** Catálogo de materias por escuela.
* **Clases Regulares vs Talleres:**
  * *Clases Regulares:* Asignadas a un grupo específico (ej. Matemáticas 1° A).
  * *Talleres Multigrado:* Marcadas con `is_workshop = true` y padrón manual de inscripción de alumnos de diferentes grupos o grados.

### 📋 5. Pase de Lista y Suplencias (Secciones 19, 20, 21, 24, 27 y 28)
* **Vista Diaria del Profesor (`/escuela/{school}/asistencia`):** El docente visualiza exclusivamente las sesiones correspondientes a la fecha seleccionada.
* **Reglas de Integridad:** Bloqueo estricto para impedir pases de lista en sesiones de fechas futuras.
* **Sustituciones Docentes:** Asignación de profesores suplentes (`substitute_teacher_id`) que conservan al profesor original en el historial y facultan al sustituto para pasar lista.
* **Marcado Rápido:** Botón para marcar a todos presentes y selector de estados `PRESENTE`, `AUSENTE` y `RETARDO`.
* **Bitácora de Modificaciones:** Todo cambio posterior sobre una lista guardada registra en `attendance_logs` el estado previo, estado nuevo, fecha y usuario responsable.

### 📎 6. Justificantes de Inasistencia (Sección 23)
* **Gestión de Comprobantes (`/escuela/{school}/justificantes`):** Consulta de ausencias registradas y carga de justificantes médicos o escolares.
* **Soporte de Archivos:** Carga de documentos PDF, JPG, JPEG y PNG de hasta 10 MB.
* **Integridad de Falta:** El justificante se vincula a la falta sin borrar el estado `AUSENTE` (garantiza que el alumno realmente faltó pero su ausencia cuenta con respaldo legal/escolar).

### ✉️ 7. Notificaciones a Tutores por Inasistencia (Sección 14)
* **Flujo de Confirmación:** Tras concluir el pase de lista, un modal resume los alumnos ausentes y permite confirmar el despacho de correos electrónicos.
* **Plantilla Profesional:** Correo responsivo (`StudentAbsenceNotificationMail`) con fecha, materia, horario y datos del plantel.
* **Envío Multi-Tutor:** Si el alumno tiene 1 tutor se envía a 1; si tiene 2, se envía a ambos tutores registrados.
* **Registro de Auditoría:** Cada correo despachado genera un evento `AVISO_AUSENCIA_ENVIADO` en la bitácora del sistema.

### 🛡️ 8. Bitácora Forense de Auditoría (Secciones 29 y 30)
* **Registro Inmutable (`/escuela/{school}/auditoria`):** Trazabilidad append-only de eventos críticos.
* **Datos Capturados:** Tipo de operación, usuario, marca de tiempo, entidad afectada, dirección IP, navegador y valores anteriores/nuevos en formato JSON.
* **Filtros Avanzados:** Búsqueda textual, filtro por acción (altas, bajas, cambios de grupo, asistencias, justificantes, correos) y rango de fechas.

### 📊 9. Reportes y Exportación a Excel/CSV (Secciones 31 y 32)
* **Centro de Reportes (`/escuela/{school}/reportes`):**
  * *Reporte de Alumnos:* Presentes, ausencias, retardos, justificantes y porcentaje ponderado de asistencia.
  * *Resumen Grupal:* Asistencia promedio, total de faltas y métricas por grado y turno.
  * *Sesiones Docentes:* Sesiones programadas, impartidas, canceladas y suplencias.
* **Exportación Excel / CSV:** Generación en tiempo real de archivos CSV con codificación UTF-8 BOM para apertura nativa en Microsoft Excel sin problemas de tildes o caracteres especiales.
* **Impresión / PDF:** Formato listo para impresión física o guardado en PDF desde el navegador.

---

## 4. Mapa de Rutas y Puntos de Acceso

```text
/
├── login                          -> Formulario de inicio de sesión
├── dashboard                      -> Selector de planteles escolares del usuario
├── settings/profile               -> Perfil de usuario, contraseñas y 2FA
├── admin/
│   └── escuelas                   -> Gestión global de planteles (Superadmin)
└── escuela/{school}/
    ├── dashboard                  -> Métricas del ciclo activo y accesos directos
    ├── ciclos                     -> Ciclos escolares y grupos por grado/turno
    ├── alumnos                    -> Padrón de alumnos, tutores, bajas e importación CSV
    ├── profesores                 -> Directorio docente y usuarios de profesores
    ├── clases                     -> Materias, clases regulares y talleres multigrado
    ├── asistencia                 -> Pase de lista diario y confirmación de avisos
    ├── justificantes              -> Carga y consulta de comprobantes médicos/legales
    ├── reportes                   -> Estadísticas exportables a Excel/CSV e impresión
    └── auditoria                  -> Bitácora inmutable de eventos y operaciones
```

---

## 5. Garantía de Calidad y Suite de Pruebas (83 Pruebas)

```bash
# Ejecutar suite completa de pruebas
./vendor/bin/pest

# Ejecutar análisis estático estricto Nivel 7
./vendor/bin/phpstan analyse --memory-limit=512M

# Comprobar estándares de estilo Laravel
./vendor/bin/pint --test
```

### Distribución de pruebas:
* `tests/Feature/Admin/SchoolManagementTest.php` (11 pruebas)
* `tests/Feature/School/StudentManagementTest.php` (10 pruebas: incluye importación masiva CSV y plantilla)
* `tests/Feature/School/SchoolYearAndGroupTest.php` (8 pruebas)
* `tests/Feature/School/AttendanceAndJustificationTest.php` (6 pruebas)
* `tests/Feature/School/TeacherAndClassTest.php` (5 pruebas)
* `tests/Feature/School/AuditAndReportsTest.php` (4 pruebas)
* `tests/Feature/SchoolAccessMiddlewareTest.php` (4 pruebas)
* Pruebas base de autenticación, perfil y seguridad Fortify (35 pruebas).

---

## 6. Posibles Mejoras y Extensiones a Futuro (Roadmap Recomendado)

1. **Generación Automatizada Masiva de Sesiones del Semestre:**
   * Crear un comando de consola (`php artisan sessions:generate {school_year}`) o un botón en la interfaz de ciclos para proyectar automáticamente todas las sesiones del semestre a partir de las reglas de horarios recurrentes (`class_schedules`).
2. **Calendario Escolar y Días Inhábiles (Sección 26):**
   * Módulo para dar de alta días festivos oficiales, consejos técnicos escolares y suspensiones, evitando que el generador de sesiones cree clases en días no laborables.
3. **Canal de Notificaciones vía WhatsApp / SMS (Sección 14):**
   * Integración de un servicio como Twilio o Meta WhatsApp Business Cloud API para complementar el envío de avisos de inasistencia por mensaje de texto o WhatsApp además del correo electrónico.
4. **Sistema de Alertas por Ausentismo Crítico (Sección 33):**
   * Monitoreo programado diario para detectar alumnos con menos del 80% de asistencia o más de 3 faltas consecutivas y emitir alertas prioritarias a la dirección y trabajo social.
5. **Portal de Consulta Presencial o Tótem para Tutores:**
   * En caso de que en una etapa comercial posterior se autorice acceso a tutores, habilitar un módulo de consulta restringida con código QR o credencial de tutor.
