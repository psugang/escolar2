# Sistema Web de Control Escolar

Sistema integral multi-plantel de control escolar, asistencias, gestión de alumnos, grupos y profesores para escuelas secundarias.

## Stack Tecnológico

* **Backend:** Laravel 13 (PHP 8.3+)
* **Frontend reactivo:** Livewire 4 + Flux UI + Tailwind CSS v4
* **Base de datos:** PostgreSQL 17 (Docker) / SQLite para pruebas
* **Autenticación y Seguridad:** Laravel Fortify con soporte 2FA y sesiones en base de datos
* **Pruebas y Calidad:** Pest PHP (100 pruebas aprobadas), PHPStan (Nivel 7 estricto), Laravel Pint

---

## Puesta en Marcha Rápida (Local)

### 1. Iniciar Base de Datos y Servicios
```bash
docker compose up -d
```
Esto iniciará:
* **PostgreSQL 17:** puerto `5432` (`DB_DATABASE=escolar`, `DB_USERNAME=escolar_dev`)
* **Adminer:** gestor web de base de datos en `http://localhost:8080`

### 2. Migraciones y Respaldo
```bash
php artisan migrate
```

Para generar un snapshot SQL completo de la base de datos en cualquier momento:
```bash
php artisan app:backup-database
```

### 3. Iniciar el Servidor de Desarrollo
```bash
composer dev
```
*(Inicia en simultáneo el servidor Laravel y el compilador en caliente de Vite en `http://localhost:8000`)*.

---

## Suite de Pruebas y Certificación

```bash
# Ejecutar suite de pruebas Pest
./vendor/bin/pest

# Análisis estático de tipos
./vendor/bin/phpstan analyse --memory-limit=512M

# Verificación de estilos de código
./vendor/bin/pint --test

# O ejecutar todo junto:
composer test
```

---

## Documentación Detallada
* [Documento Maestro y Estatus de Desarrollo](DOCUMENTO_MAESTRO_ESTATUS.md)
* [Auditoría de Arquitectura y Rendimiento Baseline v2.0](AUDITORIA_PROYECTO_SISTEMA_ESCOLAR.md)
