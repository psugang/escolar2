<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="indigo">{{ $school->name }}</flux:badge>
                @if ($school->code)
                    <span class="text-xs font-mono text-zinc-400">C.C.T. {{ $school->code }}</span>
                @endif
            </div>
            <flux:heading size="xl" level="1" class="mt-1">Pase de Asistencia Diario</flux:heading>
            <flux:subheading>
                Sesiones programadas y registro ágil de asistencia por clase.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="ghost" icon="arrow-left" :href="route('school.dashboard', $school)" wire:navigate>
                Panel de Escuela
            </flux:button>
        </div>
    </div>

    <!-- Barra de Selección de Fecha -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-zinc-900 p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
        <div class="flex items-center gap-3">
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Fecha de Consulta:</span>
            <flux:input
                wire:model.live="selectedDate"
                type="date"
                class="w-48"
            />
            @if ($selectedDate === today()->format('Y-m-d'))
                <flux:badge size="sm" color="emerald">Hoy</flux:badge>
            @endif
        </div>

        <div class="text-xs text-zinc-500">
            {{ $sessions->count() }} sesión(es) programada(s) para este día
        </div>
    </div>

    @if ($activeSession)
        <!-- Panel Activo de Pase de Lista -->
        <div class="bg-white dark:bg-zinc-900 border-2 border-indigo-500/50 dark:border-indigo-400/50 rounded-xl p-5 shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                <div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" color="indigo">Pase en Curso</flux:badge>
                        <span class="text-xs font-mono text-zinc-500">
                            {{ substr($activeSession->start_time, 0, 5) }} — {{ substr($activeSession->end_time, 0, 5) }}
                        </span>
                    </div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 mt-1">
                        {{ $activeSession->schoolClass->display_name }}
                    </h2>
                    <div class="text-xs text-zinc-500">
                        Profesor: {{ $activeSession->effective_teacher->full_name }}
                        @if ($activeSession->substitute_teacher_id)
                            <flux:badge size="xs" color="purple">Sustituto</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <flux:button size="sm" variant="subtle" icon="check-circle" wire:click="markAllPresent">
                        Marcar Todos Presentes
                    </flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="$set('activeSessionId', null)">
                        Cerrar Lista
                    </flux:button>
                </div>
            </div>

            <!-- Resumen en Vivo -->
            <div class="flex gap-4 p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg text-xs font-semibold">
                <span class="text-emerald-700 dark:text-emerald-400">✓ Presentes: {{ $presentCount }}</span>
                <span class="text-red-700 dark:text-red-400">✗ Ausentes: {{ $absentCount }}</span>
                <span class="text-amber-700 dark:text-amber-400">⏰ Retardos: {{ $retardoCount }}</span>
                <span class="text-zinc-500">Total Alumnos: {{ count($attendanceStates) }}</span>
            </div>

            <!-- Lista de Alumnos para Pase de Lista -->
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                @forelse ($students as $student)
                    @php $currentState = $attendanceStates[$student->id] ?? 'PRESENTE'; @endphp
                    <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                        <div>
                            <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $student->full_name }}
                            </div>
                            <div class="text-xs text-zinc-400 font-mono">
                                {{ $student->student_id_number ?? $student->curp ?? 'Sin matrícula' }}
                            </div>
                        </div>

                        <!-- Selector Rápido de Estado -->
                        <div class="inline-flex rounded-lg border border-zinc-200 dark:border-zinc-700 p-0.5 bg-zinc-100 dark:bg-zinc-800 text-xs">
                            <button
                                type="button"
                                wire:click="$set('attendanceStates.{{ $student->id }}', 'PRESENTE')"
                                class="px-3 py-1.5 rounded-md font-semibold transition-all {{ $currentState === 'PRESENTE' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Presente
                            </button>
                            <button
                                type="button"
                                wire:click="$set('attendanceStates.{{ $student->id }}', 'AUSENTE')"
                                class="px-3 py-1.5 rounded-md font-semibold transition-all {{ $currentState === 'AUSENTE' ? 'bg-red-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Ausente
                            </button>
                            <button
                                type="button"
                                wire:click="$set('attendanceStates.{{ $student->id }}', 'RETARDO')"
                                class="px-3 py-1.5 rounded-md font-semibold transition-all {{ $currentState === 'RETARDO' ? 'bg-amber-600 text-white shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                            >
                                Retardo
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-zinc-500">
                        No hay alumnos asignados a esta clase o taller.
                    </div>
                @endforelse
            </div>

            <div class="flex items-center justify-between pt-2">
                <flux:button variant="ghost" wire:click="$set('activeSessionId', null)">
                    Cancelar
                </flux:button>
                <flux:button variant="primary" icon="check" wire:click="openConfirmModal">
                    Guardar y Confirmar Lista
                </flux:button>
            </div>
        </div>
    @endif

    <!-- Lista de Sesiones del Día -->
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Sesiones Programadas</flux:heading>
            <flux:subheading>
                Selecciona una sesión para iniciar o actualizar el pase de lista.
            </flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($sessions as $session)
                @php
                    $isCompleted = $session->status === 'COMPLETADA';
                    $isCancelled = $session->status === 'CANCELADA';
                    $sessionAbsent = $session->attendances->where('status', 'AUSENTE')->count();
                @endphp
                <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                {{ substr($session->start_time, 0, 5) }} — {{ substr($session->end_time, 0, 5) }}
                            </span>

                            @if ($isCompleted)
                                <flux:badge size="xs" color="emerald">Pase Realizado</flux:badge>
                            @elseif ($isCancelled)
                                <flux:badge size="xs" color="red">Cancelada</flux:badge>
                            @else
                                <flux:badge size="xs" color="amber">Pendiente</flux:badge>
                            @endif
                        </div>

                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 mt-2">
                            {{ $session->schoolClass->display_name }}
                        </h3>

                        <div class="text-xs text-zinc-500 mt-1">
                            Profesor: <strong class="text-zinc-800 dark:text-zinc-200">{{ $session->effective_teacher->full_name }}</strong>
                            @if ($session->substitute_teacher_id)
                                <span class="text-purple-600 font-medium">(Sustituto)</span>
                            @endif
                        </div>

                        @if ($isCompleted && $sessionAbsent > 0)
                            <div class="mt-2 text-xs text-red-600 font-medium">
                                ⚠ {{ $sessionAbsent }} alumno(s) ausente(s)
                            </div>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        @if ($isCancelled)
                            <div class="text-xs text-red-500 italic">Sesión cancelada: {{ $session->cancellation_reason }}</div>
                        @else
                            <flux:button
                                size="sm"
                                variant="{{ $isCompleted ? 'subtle' : 'primary' }}"
                                class="w-full"
                                wire:click="selectSession('{{ $session->id }}')"
                            >
                                {{ $isCompleted ? 'Editar Lista' : 'Tomar Asistencia' }}
                            </flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:icon name="clock" class="size-8 mx-auto text-zinc-400" />
                    <p class="font-medium mt-2">No hay sesiones de clase registradas para esta fecha.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal de Confirmación de Asistencia -->
    <flux:modal wire:model="showConfirmModal" class="md:w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirmación de Asistencia</flux:heading>
                <flux:subheading>
                    Revisa el resumen antes de registrar definitivamente la lista.
                </flux:subheading>
            </div>

            <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Presentes:</span>
                    <strong class="text-emerald-600">{{ $presentCount }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Ausentes:</span>
                    <strong class="text-red-600">{{ $absentCount }}</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-600 dark:text-zinc-400">Retardos:</span>
                    <strong class="text-amber-600">{{ $retardoCount }}</strong>
                </div>
            </div>

            @if ($absentCount > 0)
                <div class="space-y-3">
                    <flux:text size="sm" class="font-medium text-red-600 dark:text-red-400">
                        Alumnos ausentes a notificar:
                    </flux:text>

                    <div class="max-h-36 overflow-y-auto divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700 p-2 bg-zinc-50 dark:bg-zinc-800/50 text-xs">
                        @foreach ($students as $stu)
                            @if (($attendanceStates[$stu->id] ?? null) === 'AUSENTE')
                                <div class="py-1 px-1 flex items-center justify-between">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $stu->full_name }}</span>
                                    <span class="text-zinc-500">
                                        {{ $stu->guardians->count() }} tutor(es)
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <flux:checkbox
                        wire:model="sendGuardianNotifications"
                        label="Enviar notificación por correo a los tutores de los alumnos ausentes"
                        description="Se enviará un aviso inmediato con los detalles de la inasistencia."
                    />
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showConfirmModal', false)">
                    Seguir Editando
                </flux:button>
                <flux:button variant="primary" wire:click="saveAttendance">
                    Confirmar y Guardar Asistencia
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
