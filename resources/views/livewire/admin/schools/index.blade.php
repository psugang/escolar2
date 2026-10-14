<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">Planteles Escolares</flux:heading>
            <flux:subheading>
                Administración global de escuelas secundarias y sus administradores de plantel.
            </flux:subheading>
        </div>

        <div>
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                Registrar Escuela
            </flux:button>
        </div>
    </div>

    <!-- Barra de búsqueda y filtros -->
    <div class="flex items-center gap-4">
        <div class="w-full sm:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre o clave..."
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    <!-- Tabla de Escuelas -->
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="py-3 px-4">Escuela / Plantel</th>
                        <th class="py-3 px-4">Clave C.C.T.</th>
                        <th class="py-3 px-4">Ciclos Escolares</th>
                        <th class="py-3 px-4">Administradores Escolares</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($schools as $school)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $school->name }}
                                </div>
                                <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                    UUID: {{ $school->id }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-zinc-700 dark:text-zinc-300">
                                {{ $school->code ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <flux:badge size="sm" color="zinc">
                                    {{ $school->school_years_count }} ciclo(s)
                                </flux:badge>
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($school->memberships->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($school->memberships as $membership)
                                            <flux:badge size="sm" color="teal">
                                                {{ $membership->user->name }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">
                                        Sin administrador asignado
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="user-group"
                                        wire:click="openAdminsModal('{{ $school->id }}')"
                                        title="Gestionar Administradores"
                                    >
                                        Admins
                                    </flux:button>

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="pencil-square"
                                        wire:click="openEditModal('{{ $school->id }}')"
                                        title="Editar Escuela"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="trash"
                                        wire:click="openDeleteModal('{{ $school->id }}')"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        title="Eliminar Escuela"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center text-zinc-500 dark:text-zinc-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <flux:icon name="building-library" class="size-8 text-zinc-400" />
                                    <p class="font-medium">No se encontraron escuelas registradas.</p>
                                    @if ($search !== '')
                                        <p class="text-xs text-zinc-400">Prueba con otro término de búsqueda.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schools->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $schools->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Crear Escuela -->
    <flux:modal wire:model="showCreateModal" class="md:w-lg">
        <form wire:submit="createSchool" class="space-y-6">
            <div>
                <flux:heading size="lg">Registrar Nueva Escuela</flux:heading>
                <flux:subheading>
                    Crea un nuevo plantel educativo en la plataforma.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="createName"
                    label="Nombre de la Escuela *"
                    placeholder="Ej. Escuela Secundaria Técnica No. 12"
                    required
                />

                <flux:input
                    wire:model="createCode"
                    label="Clave de Centro de Trabajo / Código"
                    placeholder="Ej. 14DST0012A"
                    description="Opcional. Identificador único oficial de la escuela."
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Escuela
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Editar Escuela -->
    <flux:modal wire:model="showEditModal" class="md:w-lg">
        <form wire:submit="updateSchool" class="space-y-6">
            <div>
                <flux:heading size="lg">Editar Escuela</flux:heading>
                <flux:subheading>
                    Actualiza la información general del plantel.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input
                    wire:model="editName"
                    label="Nombre de la Escuela *"
                    placeholder="Ej. Escuela Secundaria Técnica No. 12"
                    required
                />

                <flux:input
                    wire:model="editCode"
                    label="Clave de Centro de Trabajo / Código"
                    placeholder="Ej. 14DST0012A"
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showEditModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Actualizar
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Gestionar Administradores Escolares -->
    <flux:modal wire:model="showAdminsModal" class="md:w-2xl">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    Administradores Escolares (ADMIN_ESCOLAR)
                </flux:heading>
                <flux:subheading>
                    Plantel: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedSchool?->name }}</span>
                </flux:subheading>
            </div>

            <!-- Lista de Administradores Actuales -->
            <div class="space-y-3">
                <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                    Administradores Asignados
                </flux:heading>

                @if ($selectedSchool && $selectedSchool->memberships->isNotEmpty())
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                        @foreach ($selectedSchool->memberships as $membership)
                            <div class="p-3 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-800/40">
                                <div>
                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $membership->user->name }}
                                    </div>
                                    <div class="text-xs text-zinc-500">
                                        {{ $membership->user->email }}
                                    </div>
                                </div>

                                <flux:button
                                    size="xs"
                                    variant="danger"
                                    wire:click="revokeAdminRole('{{ $membership->id }}')"
                                    wire:confirm="¿Estás seguro de revocar los privilegios de administrador escolar a este usuario?"
                                >
                                    Revocar Rol
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-zinc-500 italic p-3 bg-zinc-50 dark:bg-zinc-800/30 rounded-lg">
                        Este plantel todavía no tiene administradores escolares asignados.
                    </p>
                @endif
            </div>

            <flux:separator />

            <!-- Asignar / Crear Administrador -->
            <div class="space-y-4">
                <flux:heading level="3" size="sm" class="font-semibold text-zinc-700 dark:text-zinc-300">
                    Asignar Administrador Escolar
                </flux:heading>

                <!-- Selector de Modo -->
                <div class="flex gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-sm">
                    <button
                        type="button"
                        wire:click="$set('adminMode', 'existing')"
                        class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $adminMode === 'existing' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                    >
                        Vincular Usuario Existente
                    </button>
                    <button
                        type="button"
                        wire:click="$set('adminMode', 'new')"
                        class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $adminMode === 'new' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900' }}"
                    >
                        Registrar Nuevo Usuario
                    </button>
                </div>

                @if ($adminMode === 'existing')
                    <form wire:submit="assignExistingAdmin" class="space-y-3">
                        <flux:input
                            wire:model="adminEmail"
                            label="Correo Electrónico del Usuario *"
                            type="email"
                            placeholder="usuario@ejemplo.com"
                            description="El usuario ya debe estar registrado en el sistema."
                        />

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" size="sm">
                                Asignar como Administrador Escolar
                            </flux:button>
                        </div>
                    </form>
                @else
                    <form wire:submit="registerAndAssignNewAdmin" class="space-y-3">
                        <flux:input
                            wire:model="newAdminName"
                            label="Nombre Completo *"
                            placeholder="Ej. Profesor Juan Pérez"
                        />

                        <flux:input
                            wire:model="newAdminEmail"
                            label="Correo Electrónico *"
                            type="email"
                            placeholder="juan.perez@escuela.edu.mx"
                        />

                        <flux:input
                            wire:model="newAdminPassword"
                            label="Contraseña Temporal *"
                            type="password"
                            placeholder="Mínimo 12 caracteres"
                            viewable
                            description="Se requiere una contraseña segura de al menos 12 caracteres."
                        />

                        <div class="flex justify-end">
                            <flux:button type="submit" variant="primary" size="sm">
                                Crear Usuario y Asignar
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>

            <div class="flex justify-end pt-2">
                <flux:button variant="ghost" wire:click="$set('showAdminsModal', false)">
                    Cerrar
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Eliminar Escuela -->
    <flux:modal wire:model="showDeleteModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Confirmar Eliminación de Plantel
                </flux:heading>
                <flux:subheading>
                    ¿Estás seguro de que deseas eliminar la escuela <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $deletingSchoolName }}</span>?
                </flux:subheading>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Esta acción eliminará el registro de la escuela y sus membresías asignadas. No se podrá eliminar si la escuela cuenta con ciclos escolares registrados para proteger la integridad histórica.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteSchool">
                    Sí, Eliminar Escuela
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

