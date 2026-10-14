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
            <flux:heading size="xl" level="1" class="mt-1">Ciclos Escolares y Grupos</flux:heading>
            <flux:subheading>
                Configura el calendario del ciclo escolar y la estructura de grupos por grado y turno.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreateYearModal">
                Nuevo Ciclo
            </flux:button>
            <flux:button variant="ghost" icon="arrow-left" :href="route('school.dashboard', $school)" wire:navigate>
                Panel de Escuela
            </flux:button>
        </div>
    </div>

    <!-- Layout: Ciclos a la izquierda, Grupos a la derecha -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Columna Izquierda: Lista de Ciclos Escolares (4 columnas) -->
        <div class="lg:col-span-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-700">
                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                    Historial de Ciclos
                </span>
                <flux:badge size="xs" color="zinc">{{ $schoolYears->count() }} ciclo(s)</flux:badge>
            </div>

            <div class="space-y-2">
                @forelse ($schoolYears as $year)
                    @php
                        $isSelected = $selectedYearId === $year->id;
                        $isCurrent = today()->between($year->starts_on, $year->ends_on);
                    @endphp
                    <div
                        wire:click="selectYear('{{ $year->id }}')"
                        class="p-3 rounded-lg border cursor-pointer transition-all {{ $isSelected ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 bg-zinc-50/40 dark:bg-zinc-800/30' }}"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                Ciclo {{ $year->name }}
                            </span>
                            @if ($isCurrent)
                                <flux:badge size="xs" color="emerald">Vigente</flux:badge>
                            @endif
                        </div>

                        <div class="mt-1 text-xs text-zinc-500">
                            {{ $year->starts_on->format('d/m/Y') }} — {{ $year->ends_on->format('d/m/Y') }}
                        </div>

                        <div class="mt-2 flex items-center justify-between pt-2 border-t border-zinc-200/60 dark:border-zinc-700/60 text-xs">
                            <span class="text-zinc-500">
                                {{ $year->school_groups_count }} grupo(s)
                            </span>

                            <button
                                type="button"
                                wire:click.stop="openEditYearModal('{{ $year->id }}')"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                            >
                                Editar Ciclo
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-zinc-400">
                        No hay ciclos registrados aún.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Columna Derecha: Grupos del Ciclo Seleccionado (8 columnas) -->
        <div class="lg:col-span-8 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 shadow-xs space-y-5">
            @if ($selectedYear)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <flux:heading size="lg">Grupos del Ciclo {{ $selectedYear->name }}</flux:heading>
                        <flux:subheading>
                            Periodo escolar del {{ $selectedYear->starts_on->format('d/m/Y') }} al {{ $selectedYear->ends_on->format('d/m/Y') }}
                        </flux:subheading>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="subtle" icon="sparkles" wire:click="openBatchModal">
                            Generar Grupos
                        </flux:button>
                        <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreateGroupModal">
                            Nuevo Grupo
                        </flux:button>
                    </div>
                </div>

                <!-- Filtros de Grupos -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="w-44">
                        <flux:select wire:model.live="filterShiftId" placeholder="Todos los turnos">
                            <flux:select.option value="">Todos los turnos</flux:select.option>
                            @foreach ($shifts as $shift)
                                <flux:select.option value="{{ $shift->id }}">{{ $shift->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div class="w-36">
                        <flux:select wire:model.live="filterGrade" placeholder="Todos los grados">
                            <flux:select.option value="">Todos los grados</flux:select.option>
                            <flux:select.option value="1">1° Grado</flux:select.option>
                            <flux:select.option value="2">2° Grado</flux:select.option>
                            <flux:select.option value="3">3° Grado</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <!-- Cuadrícula de Grupos -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @forelse ($groups as $group)
                        <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/60 dark:bg-zinc-800/40 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-base text-zinc-900 dark:text-zinc-100">
                                        {{ $group->grade }}°{{ $group->section }}
                                    </span>
                                    <flux:badge size="xs" color="{{ $group->shift->code === 'MATUTINO' ? 'amber' : 'purple' }}">
                                        {{ $group->shift->name }}
                                    </flux:badge>
                                </div>
                                <div class="text-xs text-zinc-400 mt-0.5">
                                    {{ $group->grade }}° Grado, Grupo {{ $group->section }}
                                </div>
                            </div>

                            <flux:button
                                size="xs"
                                variant="subtle"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                                wire:click="openDeleteGroupModal('{{ $group->id }}')"
                                title="Eliminar grupo"
                            />
                        </div>
                    @empty
                        <div class="col-span-full py-10 text-center text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="user-group" class="size-8 mx-auto text-zinc-400" />
                            <p class="font-medium mt-2">No hay grupos registrados en este ciclo con los filtros seleccionados.</p>
                            <p class="text-xs text-zinc-400 mt-1">Usa "Generar Grupos" para crearlos en lote rápidamente.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="py-12 text-center text-zinc-500 dark:text-zinc-400">
                    <p class="font-medium">Selecciona un ciclo escolar o registra uno nuevo para ver sus grupos.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Crear Ciclo Escolar -->
    <flux:modal wire:model="showCreateYearModal" class="md:w-lg">
        <form wire:submit="createSchoolYear" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo Ciclo Escolar</flux:heading>
                <flux:subheading>
                    Plantel: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $school->name }}</span>
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="createYearName"
                    label="Nombre del Ciclo Escolar *"
                    placeholder="Ej. 2026-2027"
                    required
                />

                <div class="grid grid-cols-2 gap-3">
                    <flux:input
                        wire:model="createStartsOn"
                        label="Fecha de Inicio *"
                        type="date"
                        required
                    />

                    <flux:input
                        wire:model="createEndsOn"
                        label="Fecha de Término *"
                        type="date"
                        required
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateYearModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Ciclo
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Editar Ciclo Escolar -->
    <flux:modal wire:model="showEditYearModal" class="md:w-lg">
        <form wire:submit="updateSchoolYear" class="space-y-6">
            <div>
                <flux:heading size="lg">Editar Ciclo Escolar</flux:heading>
                <flux:subheading>
                    Modifica las fechas o nombre del ciclo escolar.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="editYearName"
                    label="Nombre del Ciclo Escolar *"
                    placeholder="Ej. 2026-2027"
                    required
                />

                <div class="grid grid-cols-2 gap-3">
                    <flux:input
                        wire:model="editStartsOn"
                        label="Fecha de Inicio *"
                        type="date"
                        required
                    />

                    <flux:input
                        wire:model="editEndsOn"
                        label="Fecha de Término *"
                        type="date"
                        required
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showEditYearModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Actualizar
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Crear Grupo Individual -->
    <flux:modal wire:model="showCreateGroupModal" class="md:w-md">
        <form wire:submit="createGroup" class="space-y-6">
            <div>
                <flux:heading size="lg">Registrar Grupo</flux:heading>
                <flux:subheading>
                    Ciclo: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedYear?->name }}</span>
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:select wire:model="createGroupShiftId" label="Turno *" required>
                    @foreach ($shifts as $shift)
                        <flux:select.option value="{{ $shift->id }}">{{ $shift->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <div class="grid grid-cols-2 gap-3">
                    <flux:select wire:model="createGroupGrade" label="Grado *" required>
                        <flux:select.option value="1">1° Grado</flux:select.option>
                        <flux:select.option value="2">2° Grado</flux:select.option>
                        <flux:select.option value="3">3° Grado</flux:select.option>
                    </flux:select>

                    <flux:input
                        wire:model="createGroupSection"
                        label="Grupo / Sección *"
                        placeholder="Ej. A"
                        maxlength="1"
                        required
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateGroupModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Crear Grupo
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Generación Automática de Grupos -->
    <flux:modal wire:model="showBatchModal" class="md:w-lg">
        <form wire:submit="generateStandardGroups" class="space-y-6">
            <div>
                <flux:heading size="lg">Generador Rápido de Grupos</flux:heading>
                <flux:subheading>
                    Crea automáticamente los grupos para 1°, 2° y 3° grado de este ciclo.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:select wire:model="batchShiftId" label="Turno para los grupos *" required>
                    @foreach ($shifts as $shift)
                        <flux:select.option value="{{ $shift->id }}">{{ $shift->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="batchMaxSection" label="Secciones a generar por grado *" required>
                    <flux:select.option value="A">Solo sección A (1A, 2A, 3A)</flux:select.option>
                    <flux:select.option value="B">Secciones A y B (1A, 1B, 2A, 2B, 3A, 3B)</flux:select.option>
                    <flux:select.option value="C">Secciones A, B y C</flux:select.option>
                    <flux:select.option value="D">Secciones A a la D</flux:select.option>
                    <flux:select.option value="E">Secciones A a la E</flux:select.option>
                    <flux:select.option value="F">Secciones A a la F</flux:select.option>
                </flux:select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showBatchModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Generar Grupos
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Confirmar Eliminación de Grupo -->
    <flux:modal wire:model="showDeleteGroupModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Confirmar Eliminación de Grupo
                </flux:heading>
                <flux:subheading>
                    ¿Deseas eliminar el grupo <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $deletingGroupName }}</span>?
                </flux:subheading>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Esta acción borrará el grupo del ciclo escolar. Asegúrate de que no haya alumnos ni clases asignadas a este grupo.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteGroupModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteGroup">
                    Sí, Eliminar Grupo
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

