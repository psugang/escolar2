<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">Reportes y Estadísticas</flux:heading>
                <flux:badge color="zinc" size="sm">Métricas Escolares</flux:badge>
            </div>
            <flux:subheading>
                Generación y exportación de reportes de asistencia e historial para {{ $school->name }}.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="subtle" icon="arrow-down-tray" wire:click="exportCsv">
                Exportar CSV / Excel
            </flux:button>
            <flux:button variant="primary" icon="printer" onclick="window.print()">
                Imprimir
            </flux:button>
        </div>
    </div>

    <!-- Selector de Tipo de Reporte -->
    <div class="flex border-b border-zinc-200 dark:border-zinc-700 space-x-4">
        <button
            type="button"
            wire:click="$set('reportType', 'ALUMNOS_ASISTENCIA')"
            class="pb-3 text-sm font-medium transition-colors border-b-2 {{ $reportType === 'ALUMNOS_ASISTENCIA' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
        >
            Asistencia por Alumno
        </button>
        <button
            type="button"
            wire:click="$set('reportType', 'GRUPOS_RESUMEN')"
            class="pb-3 text-sm font-medium transition-colors border-b-2 {{ $reportType === 'GRUPOS_RESUMEN' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
        >
            Resumen por Grupo
        </button>
        <button
            type="button"
            wire:click="$set('reportType', 'PROFESORES_SESIONES')"
            class="pb-3 text-sm font-medium transition-colors border-b-2 {{ $reportType === 'PROFESORES_SESIONES' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
        >
            Sesiones de Profesores
        </button>
    </div>

    <!-- Filtros -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <flux:select wire:model.live="schoolYearId" placeholder="Seleccionar ciclo escolar">
                    @foreach ($schoolYears as $year)
                        <flux:select.option value="{{ $year->id }}">{{ $year->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            @if ($reportType === 'ALUMNOS_ASISTENCIA' || $reportType === 'GRUPOS_RESUMEN')
                <div>
                    <flux:select wire:model.live="groupId" placeholder="Todos los grupos">
                        <flux:select.option value="">Todos los grupos</flux:select.option>
                        @foreach ($groups as $g)
                            <flux:select.option value="{{ $g->id }}">{{ $g->name_with_shift }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif

            <div>
                <flux:input type="date" wire:model.live="dateFrom" label="Fecha Inicio" />
            </div>

            <div>
                <flux:input type="date" wire:model.live="dateTo" label="Fecha Fin" />
            </div>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs overflow-hidden">
        @if ($reportType === 'ALUMNOS_ASISTENCIA')
            <!-- Tabla Alumnos -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Matrícula</th>
                            <th class="px-4 py-3">Alumno</th>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3 text-center">Presentes</th>
                            <th class="px-4 py-3 text-center">Faltas</th>
                            <th class="px-4 py-3 text-center">Retardos</th>
                            <th class="px-4 py-3 text-center">Justificados</th>
                            <th class="px-4 py-3 text-right">% Asistencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($reportData as $row)
                            @php
                                $pct = $row['percentage'];
                                $badgeColor = $pct >= 85 ? 'emerald' : ($pct >= 70 ? 'amber' : 'red');
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-zinc-500">
                                    {{ $row['student_id_number'] ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $row['name'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $row['group'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-emerald-600">
                                    {{ $row['present'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-red-600">
                                    {{ $row['absent'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-amber-600">
                                    {{ $row['retardo'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-blue-600">
                                    {{ $row['justified'] }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:badge size="sm" :color="$badgeColor">
                                        {{ $pct }}%
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-zinc-500">
                                    No se encontraron registros de alumnos para este filtro.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif ($reportType === 'GRUPOS_RESUMEN')
            <!-- Tabla Grupos -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Turno</th>
                            <th class="px-4 py-3 text-center">Inscritos</th>
                            <th class="px-4 py-3 text-center">Sesiones Realizadas</th>
                            <th class="px-4 py-3 text-center">Total Asistencias</th>
                            <th class="px-4 py-3 text-center">Total Faltas</th>
                            <th class="px-4 py-3 text-center">Total Retardos</th>
                            <th class="px-4 py-3 text-right">% Promedio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($reportData as $row)
                            @php
                                $pct = $row['percentage'];
                                $badgeColor = $pct >= 85 ? 'emerald' : ($pct >= 70 ? 'amber' : 'red');
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $row['name'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $row['shift'] }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    {{ $row['students_count'] }} alumnos
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    {{ $row['sessions_count'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-emerald-600">
                                    {{ $row['total_present'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-red-600">
                                    {{ $row['total_absent'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-amber-600">
                                    {{ $row['total_retardo'] }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <flux:badge size="sm" :color="$badgeColor">
                                        {{ $pct }}%
                                    </flux:badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-zinc-500">
                                    No hay datos registrados para los grupos en este periodo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <!-- Tabla Profesores -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">N° Empleado</th>
                            <th class="px-4 py-3">Profesor</th>
                            <th class="px-4 py-3 text-center">Clases Asignadas</th>
                            <th class="px-4 py-3 text-center">Sesiones Programadas</th>
                            <th class="px-4 py-3 text-center">Impartidas</th>
                            <th class="px-4 py-3 text-center">Canceladas</th>
                            <th class="px-4 py-3 text-center">Sustituciones Realizadas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($reportData as $row)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-zinc-500">
                                    {{ $row['employee_number'] ?: '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $row['name'] }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs">
                                    {{ $row['classes_count'] }}
                                </td>
                                <td class="px-4 py-3 text-center text-xs font-medium">
                                    {{ $row['scheduled_sessions'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-emerald-600">
                                    {{ $row['completed_sessions'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-red-600">
                                    {{ $row['cancelled_sessions'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-purple-600">
                                    {{ $row['substitute_sessions'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500">
                                    No se encontraron profesores registrados en este plantel.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

