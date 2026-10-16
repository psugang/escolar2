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
            <flux:heading size="xl" level="1" class="mt-1">Control de Alumnos</flux:heading>
            <flux:subheading>
                Padrón estudiantil, asignación histórica de grupos y administración de tutores.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="subtle" icon="arrow-up-tray" wire:click="openImportModal">
                Importar CSV
            </flux:button>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Inscribir Alumno
            </flux:button>
            <flux:button variant="ghost" icon="arrow-left" :href="route('school.dashboard', $school)" wire:navigate>
                Panel de Escuela
            </flux:button>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-white dark:bg-zinc-900 p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
        <div>
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar alumno, matrícula o CURP..."
                icon="magnifying-glass"
                clearable
            />
        </div>

        <div>
            <flux:select wire:model.live="filterYearId" placeholder="Filtrar por ciclo escolar">
                <flux:select.option value="">Todos los ciclos</flux:select.option>
                @foreach ($schoolYears as $year)
                    <flux:select.option value="{{ $year->id }}">Ciclo {{ $year->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select wire:model.live="filterGroupId" placeholder="Filtrar por grupo">
                <flux:select.option value="">Todos los grupos</flux:select.option>
                @foreach ($groups as $grp)
                    <flux:select.option value="{{ $grp->id }}">
                        {{ $grp->grade }}°{{ $grp->section }} — {{ $grp->shift->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <div>
            <flux:select wire:model.live="filterStatus" placeholder="Estado del alumno">
                <flux:select.option value="ACTIVO">Alumnos Activos</flux:select.option>
                <flux:select.option value="BAJA">Alumnos en Baja</flux:select.option>
                <flux:select.option value="ALL">Todos los estados</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Tabla de Alumnos -->
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="py-3 px-4">Alumno</th>
                        <th class="py-3 px-4">Matrícula / CURP</th>
                        <th class="py-3 px-4">Grupo Actual</th>
                        <th class="py-3 px-4">Tutores (Máx. 2)</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($students as $student)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $student->full_name }}
                                </div>
                                <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                    UUID: {{ Str::limit($student->id, 13) }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                <div>Matrícula: {{ $student->student_id_number ?? '—' }}</div>
                                <div class="text-zinc-400">CURP: {{ $student->curp ?? '—' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($student->currentEnrollment)
                                    @php $currentGrp = $student->currentEnrollment->schoolGroup; @endphp
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100">
                                            {{ $currentGrp->grade }}°{{ $currentGrp->section }}
                                        </span>
                                        <flux:badge size="xs" color="{{ $currentGrp->shift->code === 'MATUTINO' ? 'amber' : 'purple' }}">
                                            {{ $currentGrp->shift->name }}
                                        </flux:badge>
                                    </div>
                                    <div class="text-xs text-zinc-400 mt-0.5">
                                        Desde {{ $student->currentEnrollment->starts_on->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Sin grupo activo</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($student->guardians->isNotEmpty())
                                    <div class="flex flex-col gap-1">
                                        @foreach ($student->guardians as $guardian)
                                            <div class="text-xs flex items-center gap-1">
                                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $guardian->full_name }}</span>
                                                <flux:badge size="xs" color="zinc">{{ $guardian->pivot->relationship }}</flux:badge>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Sin tutores registrados</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($student->status === 'ACTIVO')
                                    <flux:badge size="sm" color="emerald">Activo</flux:badge>
                                @else
                                    <flux:badge size="sm" color="red">Baja</flux:badge>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="arrows-right-left"
                                        wire:click="openChangeGroupModal('{{ $student->id }}')"
                                        title="Cambiar de grupo o turno"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="users"
                                        wire:click="openGuardiansModal('{{ $student->id }}')"
                                        title="Gestionar Tutores"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="clock"
                                        wire:click="openHistoryModal('{{ $student->id }}')"
                                        title="Historial de Inscripciones"
                                    />

                                    @if ($student->status === 'ACTIVO')
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            icon="user-minus"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            wire:click="openBajaModal('{{ $student->id }}')"
                                            title="Dar de baja al alumno"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 px-4 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="academic-cap" class="size-8 mx-auto text-zinc-400" />
                                <p class="font-medium mt-2">No se encontraron alumnos con los criterios seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $students->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Inscribir Alumno -->
    <flux:modal wire:model="showCreateModal" class="md:w-2xl">
        <form wire:submit="createStudent" class="space-y-6">
            <div>
                <flux:heading size="lg">Inscribir Nuevo Alumno</flux:heading>
                <flux:subheading>
                    Registra los datos del estudiante y asígnalo a su grupo inicial.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="newFirstName"
                        label="Nombre(s) *"
                        placeholder="Ej. Juan"
                        required
                    />

                    <flux:input
                        wire:model="newLastName"
                        label="Apellidos *"
                        placeholder="Ej. Pérez López"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="newStudentIdNumber"
                        label="Matrícula"
                        placeholder="Ej. MAT-2026-001"
                        description="Opcional. Única si la escuela la utiliza."
                    />

                    <flux:input
                        wire:model="newCurp"
                        label="CURP"
                        placeholder="18 caracteres"
                        maxlength="18"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="newBirthDate"
                        label="Fecha de Nacimiento"
                        type="date"
                    />

                    <flux:select wire:model="newGender" label="Género">
                        <flux:select.option value="">No especificado</flux:select.option>
                        <flux:select.option value="M">Masculino</flux:select.option>
                        <flux:select.option value="F">Femenino</flux:select.option>
                        <flux:select.option value="X">Otro / No binario</flux:select.option>
                    </flux:select>
                </div>

                <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 space-y-3">
                    <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                        Inscripción Inicial
                    </flux:heading>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <flux:select wire:model="newGroupId" label="Grupo y Turno *" required>
                            <flux:select.option value="">Selecciona un grupo</flux:select.option>
                            @foreach ($allSchoolGroups as $group)
                                <flux:select.option value="{{ $group->id }}">
                                    Ciclo {{ $group->schoolYear->name }} · {{ $group->grade }}°{{ $group->section }} ({{ $group->shift->name }})
                                </flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input
                            wire:model="newStartsOn"
                            label="Fecha de Inicio *"
                            type="date"
                            required
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Completar Inscripción
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Cambiar de Grupo o Turno -->
    <flux:modal wire:model="showChangeGroupModal" class="md:w-lg">
        <form wire:submit="processChangeGroup" class="space-y-6">
            <div>
                <flux:heading size="lg">Cambio de Grupo / Turno</flux:heading>
                <flux:subheading>
                    Alumno: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $changeStudent?->full_name }}</span>
                </flux:subheading>
            </div>

            <p class="text-xs text-zinc-500">
                El cambio cerrará el periodo en el grupo actual y abrirá uno nuevo a partir de la fecha seleccionada, conservando todo el historial.
            </p>

            <div class="space-y-4">
                <flux:select wire:model="changeGroupId" label="Nuevo Grupo y Turno *" required>
                    <flux:select.option value="">Selecciona el nuevo grupo</flux:select.option>
                    @foreach ($allSchoolGroups as $group)
                        <flux:select.option value="{{ $group->id }}">
                            Ciclo {{ $group->schoolYear->name }} · {{ $group->grade }}°{{ $group->section }} ({{ $group->shift->name }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    wire:model="changeStartsOn"
                    label="Fecha de Aplicación del Cambio *"
                    type="date"
                    required
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showChangeGroupModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Registrar Cambio
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Gestionar Tutores (Máx 2) -->
    <flux:modal wire:model="showGuardiansModal" class="md:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Tutores del Alumno</flux:heading>
                <flux:subheading>
                    Estudiante: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $guardiansStudent?->full_name }}</span>
                </flux:subheading>
            </div>

            <!-- Tutores actuales -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                        Tutores Vinculados
                    </flux:heading>
                    <flux:badge size="xs" color="{{ ($guardiansStudent?->guardians->count() ?? 0) >= 2 ? 'amber' : 'zinc' }}">
                        {{ $guardiansStudent?->guardians->count() ?? 0 }} de 2 tutores
                    </flux:badge>
                </div>

                @if ($guardiansStudent && $guardiansStudent->guardians->isNotEmpty())
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        @foreach ($guardiansStudent->guardians as $g)
                            <div class="p-3 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-800/40">
                                <div>
                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                        <span>{{ $g->full_name }}</span>
                                        <flux:badge size="xs" color="teal">{{ $g->pivot->relationship }}</flux:badge>
                                    </div>
                                    <div class="text-xs text-zinc-500 mt-0.5">
                                        @if ($g->phone) Tel: {{ $g->phone }} @endif
                                        @if ($g->email) · Correo: {{ $g->email }} @endif
                                    </div>
                                </div>

                                <flux:button
                                    size="xs"
                                    variant="subtle"
                                    class="text-red-600 hover:text-red-700"
                                    wire:click="removeGuardian('{{ $g->id }}')"
                                    title="Quitar tutor"
                                >
                                    Quitar
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500 italic p-3 bg-zinc-50 dark:bg-zinc-800/30 rounded-lg">
                        El alumno no tiene tutores registrados actualmente.
                    </p>
                @endif
            </div>

            @if (($guardiansStudent?->guardians->count() ?? 0) < 2)
                <flux:separator />

                <!-- Formulario para agregar tutor -->
                <div class="space-y-4">
                    <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                        Agregar Tutor
                    </flux:heading>

                    <div class="flex gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-sm">
                        <button
                            type="button"
                            wire:click="$set('guardianMode', 'new')"
                            class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $guardianMode === 'new' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}"
                        >
                            Registrar Nuevo Tutor
                        </button>
                        <button
                            type="button"
                            wire:click="$set('guardianMode', 'existing')"
                            class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $guardianMode === 'existing' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}"
                        >
                            Vincular Tutor Existente
                        </button>
                    </div>

                    <form wire:submit="addGuardian" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <flux:input
                                wire:model="guardianRelationship"
                                label="Parentesco / Relación *"
                                placeholder="Ej. Padre, Madre, Tutor Legal"
                                required
                            />

                            @if ($guardianMode === 'existing')
                                <flux:select wire:model="existingGuardianId" label="Tutor Registrado *" required>
                                    <flux:select.option value="">Selecciona tutor</flux:select.option>
                                    @foreach ($existingGuardians as $eg)
                                        <flux:select.option value="{{ $eg->id }}">
                                            {{ $eg->full_name }} ({{ $eg->phone ?? $eg->email ?? 'Sin contacto' }})
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:input
                                    wire:model="guardianFirstName"
                                    label="Nombre(s) *"
                                    placeholder="Ej. María"
                                    required
                                />
                            @endif
                        </div>

                        @if ($guardianMode === 'new')
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <flux:input
                                    wire:model="guardianLastName"
                                    label="Apellidos *"
                                    placeholder="Ej. Sánchez Morales"
                                    required
                                />

                                <flux:input
                                    wire:model="guardianPhone"
                                    label="Teléfono"
                                    placeholder="Ej. 3312345678"
                                />

                                <flux:input
                                    wire:model="guardianEmail"
                                    label="Correo Electrónico"
                                    type="email"
                                    placeholder="maria@ejemplo.com"
                                />
                            </div>
                        @endif

                        <div class="flex justify-end pt-2">
                            <flux:button type="submit" variant="primary" size="sm">
                                Vincular Tutor al Alumno
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showGuardiansModal', false)">
                    Cerrar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Historial de Inscripciones -->
    <flux:modal wire:model="showHistoryModal" class="md:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Historial Completo de Inscripciones</flux:heading>
                <flux:subheading>
                    Estudiante: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $historyStudent?->full_name }}</span>
                </flux:subheading>
            </div>

            @if ($historyStudent && $historyStudent->enrollments->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($historyStudent->enrollments->sortByDesc('starts_on') as $enrollment)
                        <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">
                                        Ciclo {{ $enrollment->schoolYear->name }} · {{ $enrollment->grade }}°{{ $enrollment->schoolGroup->section }}
                                    </span>
                                    <flux:badge size="xs" color="{{ $enrollment->shift->code === 'MATUTINO' ? 'amber' : 'purple' }}">
                                        {{ $enrollment->shift->name }}
                                    </flux:badge>
                                </div>
                                <div class="text-xs text-zinc-500 mt-1 font-mono">
                                    Periodo: {{ $enrollment->starts_on->format('d/m/Y') }} → {{ $enrollment->ends_on ? $enrollment->ends_on->format('d/m/Y') : 'Actualidad' }}
                                </div>
                            </div>

                            <flux:badge size="sm" color="{{ $enrollment->status === 'ACTIVO' ? 'emerald' : ($enrollment->status === 'BAJA' ? 'red' : 'zinc') }}">
                                {{ $enrollment->status }}
                            </flux:badge>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-zinc-500 italic py-4">No hay historial de inscripciones registrado.</p>
            @endif

            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showHistoryModal', false)">
                    Cerrar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Confirmar Baja -->
    <flux:modal wire:model="showBajaModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Confirmar Baja Escolar
                </flux:heading>
                <flux:subheading>
                    ¿Deseas marcar como BAJA al alumno <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $bajaStudentName }}</span>?
                </flux:subheading>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                El alumno quedará en estado inactivo y su inscripción actual se cerrará con fecha de hoy. Su información y asistencias históricas permanecerán intactas en el sistema.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showBajaModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="processBaja">
                    Confirmar Baja
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Importar Alumnos desde CSV -->
    <flux:modal wire:model="showImportModal" class="md:w-3xl">
        @if ($importStep === 'upload')
            <form wire:submit="previewImportCsv" class="space-y-6">
                <div>
                    <flux:heading size="lg">Importación Rápida de Alumnos (.CSV)</flux:heading>
                    <flux:subheading>
                        Carga masiva de alumnos con detección inteligente de re-inscripciones y nuevos ingresos.
                    </flux:subheading>
                </div>

                <!-- Instrucciones de formato y botón de plantilla -->
                <div class="p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700 space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">Columnas requeridas en el archivo:</span>
                        <flux:button size="xs" variant="subtle" icon="arrow-down-tray" wire:click="downloadSampleCsv" type="button">
                            Descargar Plantilla CSV
                        </flux:button>
                    </div>

                    <div class="font-mono bg-white dark:bg-zinc-900 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 overflow-x-auto">
                        grado,grupo,apellido_paterno,apellido_materno,nombre,curp,tutor_nombre,tutor_correo,tutor_telefono
                    </div>

                    <p class="text-zinc-500">
                        Las columnas <strong class="text-zinc-700 dark:text-zinc-300">curp</strong> y datos de tutor (<strong class="text-zinc-700 dark:text-zinc-300">tutor_nombre, tutor_correo, tutor_telefono</strong>) son opcionales. Si incluyes datos del tutor, se vinculará automáticamente sin duplicados.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:select wire:model="importYearId" label="Ciclo Escolar *">
                        @foreach ($schoolYears as $year)
                            <flux:select.option value="{{ $year->id }}">Ciclo {{ $year->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="importShiftId" label="Turno Predeterminado *">
                        @foreach ($shifts as $sh)
                            <flux:select.option value="{{ $sh->id }}">{{ $sh->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div>
                    <flux:input
                        type="file"
                        wire:model="importFile"
                        label="Seleccionar Archivo .CSV *"
                        accept=".csv,text/csv,text/plain"
                    />
                </div>

                @if (! empty($importErrors))
                    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 space-y-1">
                        <div class="font-bold text-xs text-red-700 dark:text-red-400">Observaciones en la carga:</div>
                        <ul class="text-xs text-red-600 dark:text-red-300 list-disc list-inside max-h-32 overflow-y-auto">
                            @foreach ($importErrors as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-2">
                    <flux:button variant="ghost" wire:click="$set('showImportModal', false)">
                        Cancelar
                    </flux:button>
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="previewImportCsv">Analizar Archivo</span>
                        <span wire:loading wire:target="previewImportCsv">Analizando...</span>
                    </flux:button>
                </div>
            </form>
        @else
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Revisión y Pre-visualización</flux:heading>
                    <flux:subheading>
                        Verifica los alumnos detectados antes de aplicar los cambios en el sistema.
                    </flux:subheading>
                </div>

                <!-- Resumen de conteos -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 rounded-xl">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Re-inscripciones</div>
                        <div class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1">
                            {{ count($previewReEnrollments) }}
                        </div>
                        <div class="text-[11px] text-blue-600/80 dark:text-blue-400/80 mt-0.5">Alumnos existentes</div>
                    </div>

                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 rounded-xl">
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Nuevos Ingresos</div>
                        <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">
                            {{ count($previewNewStudents) }}
                        </div>
                        <div class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5">Nuevos en el plantel</div>
                    </div>

                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-xl col-span-2 sm:col-span-1">
                        <div class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">Omitidos / Errores</div>
                        <div class="text-2xl font-bold text-zinc-700 dark:text-zinc-300 mt-1">
                            {{ count($previewErrors) }}
                        </div>
                        <div class="text-[11px] text-zinc-500 mt-0.5">Filas no procesables</div>
                    </div>
                </div>

                @if (! empty($previewErrors))
                    <div class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 space-y-1">
                        <div class="font-bold text-xs text-amber-800 dark:text-amber-300">Filas que no se importarán:</div>
                        <ul class="text-xs text-amber-700 dark:text-amber-400 list-disc list-inside max-h-24 overflow-y-auto">
                            @foreach ($previewErrors as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Listado de Re-inscripciones -->
                @if (! empty($previewReEnrollments))
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Re-inscripciones (Pasan al nuevo grupo conservando tutores e historial):
                            </span>
                            <flux:badge size="xs" color="blue">{{ count($previewReEnrollments) }}</flux:badge>
                        </div>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700 text-xs">
                            @foreach ($previewReEnrollments as $item)
                                <div class="p-2.5 flex items-center justify-between bg-white dark:bg-zinc-900">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['name'] }}</div>
                                        <div class="text-zinc-500 text-[11px]">
                                            @if(!empty($item['curp'])) CURP: {{ $item['curp'] }} · @endif Grupo previo: {{ $item['current_group'] }}
                                            @if(!empty($item['tutor_nombre'])) · Tutor: {{ $item['tutor_nombre'] }} @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <flux:badge size="xs" color="blue">
                                            Nuevo: {{ $item['target_grade'] }}°{{ $item['target_section'] }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Listado de Alumnos Nuevos -->
                @if (! empty($previewNewStudents))
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Alumnos de Nuevo Ingreso:
                            </span>
                            <flux:badge size="xs" color="emerald">{{ count($previewNewStudents) }}</flux:badge>
                        </div>
                        <div class="max-h-48 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700 text-xs">
                            @foreach ($previewNewStudents as $item)
                                <div class="p-2.5 flex items-center justify-between bg-white dark:bg-zinc-900">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $item['name'] }}</div>
                                        <div class="text-zinc-500 text-[11px]">
                                            @if(!empty($item['curp'])) CURP: {{ $item['curp'] }} @else Sin CURP @endif
                                            @if(!empty($item['tutor_nombre'])) · Tutor: {{ $item['tutor_nombre'] }} @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <flux:badge size="xs" color="emerald">
                                            Asignar: {{ $item['target_grade'] }}°{{ $item['target_section'] }}
                                        </flux:badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="$set('importStep', 'upload')">
                        ← Cambiar Archivo
                    </flux:button>

                    <div class="flex items-center gap-2">
                        <flux:button variant="ghost" wire:click="$set('showImportModal', false)">
                            Cancelar
                        </flux:button>
                        <flux:button variant="primary" wire:click="confirmImport" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="confirmImport">Confirmar e Inscribir ({{ count($previewReEnrollments) + count($previewNewStudents) }})</span>
                            <span wire:loading wire:target="confirmImport">Inscribiendo...</span>
                        </flux:button>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>
</div>

