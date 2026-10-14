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
            <flux:heading size="xl" level="1" class="mt-1">Materias, Clases y Talleres</flux:heading>
            <flux:subheading>
                Asignación académica de materias por profesor, grupos regulares y talleres multiserie.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if ($activeTab === 'classes')
                <flux:button variant="subtle" icon="wrench-screwdriver" wire:click="openCreateWorkshopModal">
                    Nuevo Taller
                </flux:button>
                <flux:button variant="primary" icon="plus" wire:click="openCreateClassModal">
                    Nueva Clase Normal
                </flux:button>
            @else
                <flux:button variant="primary" icon="plus" wire:click="openSubjectModal">
                    Nueva Materia
                </flux:button>
            @endif

            <flux:button variant="ghost" icon="arrow-left" :href="route('school.dashboard', $school)" wire:navigate>
                Panel de Escuela
            </flux:button>
        </div>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
        <button
            type="button"
            wire:click="$set('activeTab', 'classes')"
            class="py-2.5 px-4 font-medium text-sm border-b-2 transition-colors {{ $activeTab === 'classes' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}"
        >
            Clases y Talleres
        </button>
        <button
            type="button"
            wire:click="$set('activeTab', 'subjects')"
            class="py-2.5 px-4 font-medium text-sm border-b-2 transition-colors {{ $activeTab === 'subjects' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}"
        >
            Catálogo de Materias ({{ $subjects->count() }})
        </button>
    </div>

    @if ($activeTab === 'classes')
        <!-- Barra de Filtros de Clases -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 bg-white dark:bg-zinc-900 p-4 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
            <div>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por clase, materia o profesor..."
                    icon="magnifying-glass"
                    clearable
                />
            </div>

            <div>
                <flux:select wire:model.live="filterYearId" placeholder="Selecciona ciclo escolar">
                    @foreach ($schoolYears as $year)
                        <flux:select.option value="{{ $year->id }}">Ciclo {{ $year->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="filterType">
                    <flux:select.option value="all">Todas las clases y talleres</flux:select.option>
                    <flux:select.option value="normal">Solo Clases Regulares</flux:select.option>
                    <flux:select.option value="workshop">Solo Talleres</flux:select.option>
                </flux:select>
            </div>
        </div>

        <!-- Grid de Clases y Talleres -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($classes as $class)
                <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between">
                            @if ($class->is_workshop)
                                <flux:badge size="xs" color="purple">Taller Multiserie</flux:badge>
                            @else
                                <flux:badge size="xs" color="indigo">
                                    {{ $class->schoolGroup?->grade }}°{{ $class->schoolGroup?->section }} ({{ $class->schoolGroup?->shift?->name }})
                                </flux:badge>
                            @endif

                            <flux:badge size="xs" color="zinc">{{ $class->subject->name }}</flux:badge>
                        </div>

                        <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 mt-2">
                            {{ $class->display_name }}
                        </h3>

                        <div class="text-xs text-zinc-500 mt-2 flex items-center gap-1.5">
                            <flux:icon name="user" class="size-4 text-zinc-400" />
                            <span>Profesor: <strong class="text-zinc-800 dark:text-zinc-200">{{ $class->teacher->full_name }}</strong></span>
                        </div>

                        @if ($class->is_workshop)
                            <div class="mt-2 text-xs text-purple-700 dark:text-purple-300 font-medium flex items-center gap-1">
                                <flux:icon name="user-group" class="size-4" />
                                <span>{{ $class->workshopStudents->count() }} alumnos inscritos manualmente</span>
                            </div>
                        @else
                            <div class="mt-2 text-xs text-zinc-400">
                                Alumnos derivados automáticamente del grupo
                            </div>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                        @if ($class->is_workshop)
                            <flux:button
                                size="xs"
                                variant="subtle"
                                icon="user-plus"
                                wire:click="openWorkshopStudentsModal('{{ $class->id }}')"
                            >
                                Gestionar Alumnos
                            </flux:button>
                        @else
                            <div></div>
                        @endif

                        <flux:button
                            size="xs"
                            variant="subtle"
                            icon="trash"
                            class="text-red-600 hover:text-red-700"
                            wire:click="openDeleteClassModal('{{ $class->id }}')"
                            title="Eliminar clase"
                        />
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                    <flux:icon name="book-open" class="size-8 mx-auto text-zinc-400" />
                    <p class="font-medium mt-2">No hay clases o talleres registrados con los filtros seleccionados.</p>
                </div>
            @endforelse
        </div>

        @if ($classes->hasPages())
            <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl">
                {{ $classes->links() }}
            </div>
        @endif
    @else
        <!-- Catálogo de Materias -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-3 px-4">Materia / Asignatura</th>
                            <th class="py-3 px-4">Clave</th>
                            <th class="py-3 px-4">Clases que la Imparten</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($subjects as $subject)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="py-3.5 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $subject->name }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $subject->code ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <flux:badge size="sm" color="zinc">
                                        {{ $subject->classes_count }} clase(s)
                                    </flux:badge>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="pencil-square"
                                        wire:click="openSubjectModal('{{ $subject->id }}')"
                                        title="Editar materia"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-zinc-500">
                                    No hay materias registradas aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Modal: Crear/Editar Materia -->
    <flux:modal wire:model="showSubjectModal" class="md:w-md">
        <form wire:submit="saveSubject" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingSubjectId ? 'Editar Materia' : 'Nueva Materia' }}
                </flux:heading>
                <flux:subheading>
                    Catálogo de asignaturas de la escuela.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="subjectName"
                    label="Nombre de la Materia *"
                    placeholder="Ej. Matemáticas"
                    required
                />

                <flux:input
                    wire:model="subjectCode"
                    label="Código / Clave de Asignatura"
                    placeholder="Ej. MAT-SEC"
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showSubjectModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Materia
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Crear Clase Normal -->
    <flux:modal wire:model="showCreateClassModal" class="md:w-lg">
        <form wire:submit="createNormalClass" class="space-y-6">
            <div>
                <flux:heading size="lg">Nueva Clase Regular</flux:heading>
                <flux:subheading>
                    Asigna una materia a un profesor y grupo escolar.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:select wire:model="classSubjectId" label="Materia *" required>
                    <flux:select.option value="">Selecciona una materia</flux:select.option>
                    @foreach ($subjects as $sb)
                        <flux:select.option value="{{ $sb->id }}">{{ $sb->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="classTeacherId" label="Profesor Asignado *" required>
                    <flux:select.option value="">Selecciona un profesor</flux:select.option>
                    @foreach ($teachers as $tc)
                        <flux:select.option value="{{ $tc->id }}">{{ $tc->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="classGroupId" label="Grupo Escolar *" required>
                    <flux:select.option value="">Selecciona el grupo</flux:select.option>
                    @foreach ($groups as $gp)
                        <flux:select.option value="{{ $gp->id }}">
                            {{ $gp->grade }}°{{ $gp->section }} — {{ $gp->shift->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateClassModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Crear Clase
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Crear Taller -->
    <flux:modal wire:model="showCreateWorkshopModal" class="md:w-lg">
        <form wire:submit="createWorkshop" class="space-y-6">
            <div>
                <flux:heading size="lg">Nuevo Taller Multiserie</flux:heading>
                <flux:subheading>
                    Crea un taller que permite mezclar alumnos de distintos grados y grupos.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="workshopName"
                    label="Nombre del Taller *"
                    placeholder="Ej. Taller de Robótica y Programación"
                    required
                />

                <flux:select wire:model="workshopSubjectId" label="Materia Base *" required>
                    <flux:select.option value="">Selecciona materia</flux:select.option>
                    @foreach ($subjects as $sb)
                        <flux:select.option value="{{ $sb->id }}">{{ $sb->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="workshopTeacherId" label="Profesor Encargado *" required>
                    <flux:select.option value="">Selecciona profesor</flux:select.option>
                    @foreach ($teachers as $tc)
                        <flux:select.option value="{{ $tc->id }}">{{ $tc->full_name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateWorkshopModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Crear Taller
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Gestionar Alumnos de Taller -->
    <flux:modal wire:model="showWorkshopStudentsModal" class="md:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Alumnos del Taller</flux:heading>
                <flux:subheading>
                    Taller: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedWorkshop?->name }}</span>
                </flux:subheading>
            </div>

            <!-- Inscribir alumno -->
            <form wire:submit="enrollStudentInWorkshop" class="flex gap-2 items-end">
                <div class="flex-1">
                    <flux:select wire:model="studentToEnrollId" label="Seleccionar Alumno *" required>
                        <flux:select.option value="">Selecciona un alumno para inscribir</flux:select.option>
                        @foreach ($allStudents as $st)
                            <flux:select.option value="{{ $st->id }}">
                                {{ $st->full_name }} ({{ $st->currentEnrollment?->schoolGroup ? $st->currentEnrollment->schoolGroup->grade . '°' . $st->currentEnrollment->schoolGroup->section : 'Sin grupo' }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button type="submit" variant="primary">
                    Inscribir al Taller
                </flux:button>
            </form>

            <flux:separator />

            <!-- Lista de inscritos -->
            <div class="space-y-3">
                <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                    Alumnos Inscritos ({{ $selectedWorkshop?->workshopStudents->count() ?? 0 }})
                </flux:heading>

                @if ($selectedWorkshop && $selectedWorkshop->workshopStudents->isNotEmpty())
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden max-h-72 overflow-y-auto">
                        @foreach ($selectedWorkshop->workshopStudents as $wsStudent)
                            <div class="p-3 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-800/40">
                                <div>
                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $wsStudent->full_name }}
                                    </div>
                                    <div class="text-xs text-zinc-400">
                                        Grupo regular: {{ $wsStudent->currentEnrollment?->schoolGroup ? $wsStudent->currentEnrollment->schoolGroup->grade . '°' . $wsStudent->currentEnrollment->schoolGroup->section : 'Sin grupo' }}
                                    </div>
                                </div>

                                <flux:button
                                    size="xs"
                                    variant="subtle"
                                    class="text-red-600 hover:text-red-700"
                                    wire:click="removeStudentFromWorkshop('{{ $wsStudent->id }}')"
                                    title="Quitar del taller"
                                >
                                    Quitar
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500 italic p-3 bg-zinc-50 dark:bg-zinc-800/30 rounded-lg">
                        Aún no hay alumnos inscritos en este taller.
                    </p>
                @endif
            </div>

            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showWorkshopStudentsModal', false)">
                    Cerrar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Confirmar Eliminación de Clase -->
    <flux:modal wire:model="showDeleteClassModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Confirmar Eliminación
                </flux:heading>
                <flux:subheading>
                    ¿Deseas eliminar la clase <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $deletingClassName }}</span>?
                </flux:subheading>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteClassModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteClass">
                    Sí, Eliminar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

