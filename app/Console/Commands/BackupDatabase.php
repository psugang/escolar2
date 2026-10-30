<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:backup-database {--filename= : Nombre del archivo de respaldo}')]
#[Description('Genera un respaldo SQL completo de los datos de la base de datos')]
class BackupDatabase extends Command
{
    public function handle(): int
    {
        $tables = [
            'users',
            'schools',
            'school_years',
            'shifts',
            'school_groups',
            'school_memberships',
            'school_membership_roles',
            'students',
            'student_enrollments',
            'guardians',
            'student_guardians',
            'teachers',
            'teacher_schools',
            'subjects',
            'school_classes',
            'class_enrollments',
            'class_schedules',
            'class_sessions',
            'attendances',
            'attendance_logs',
            'attendance_justifications',
            'audit_logs',
            'school_suspensions',
        ];

        $backupDir = database_path('backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = $this->option('filename') ?: 'respaldo_db_escolar_'.date('Y_m_d_His').'.sql';
        $filepath = $backupDir.'/'.$filename;

        $sql = "-- Respaldo de Base de Datos: Sistema Escolar\n";
        $sql .= '-- Fecha: '.date('Y-m-d H:i:s')."\n";
        $sql .= "SET statement_timeout = 0;\n";
        $sql .= "SET lock_timeout = 0;\n";
        $sql .= "SET client_encoding = 'UTF8';\n\n";

        $pdo = DB::getPdo();
        $totalRows = 0;

        foreach ($tables as $table) {
            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $sql .= "-- Datos para la tabla: {$table}\n";
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $cols = array_keys($rowArray);
                $escapedCols = array_map(fn ($c) => "\"$c\"", $cols);
                $escapedVals = array_map(function ($v) use ($pdo) {
                    if (is_null($v)) {
                        return 'NULL';
                    }
                    if (is_bool($v)) {
                        return $v ? 'TRUE' : 'FALSE';
                    }
                    if (is_int($v) || is_float($v)) {
                        return (string) $v;
                    }

                    return $pdo->quote((string) $v);
                }, array_values($rowArray));

                $sql .= "INSERT INTO \"{$table}\" (".implode(', ', $escapedCols).') VALUES ('.implode(', ', $escapedVals).") ON CONFLICT DO NOTHING;\n";
                $totalRows++;
            }
            $sql .= "\n";
        }

        file_put_contents($filepath, $sql);

        $this->info("Respaldo creado con éxito en: {$filepath}");
        $this->info("Total de registros exportados: {$totalRows}");

        return self::SUCCESS;
    }
}
