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
            <flux:heading size="xl" level="1" class="mt-1">Plantilla Docente (Profesores)</flux:heading>
            <flux:subheading>
                Gestión de profesores, adscripción multi-plantel y cuentas de pase de lista.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="envelope" wire:click="openInviteModal">
                Invitar Docente
            </flux:button>
            <flux:button variant="subtle" icon="plus" wire:click="openCreateModal">
                Registrar Profesor
            </flux:button>
            <flux:button variant="ghost" icon="arrow-left" :href="route('school.dashboard', $school)" wire:navigate>
                Panel de Escuela
            </flux:button>
        </div>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="flex items-center gap-4">
        <div class="w-full sm:w-96">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar profesor por nombre, matrícula o correo..."
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    <!-- Tabla de Profesores -->
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="py-3 px-4">Profesor</th>
                        <th class="py-3 px-4">Clave / Matrícula</th>
                        <th class="py-3 px-4">Contacto</th>
                        <th class="py-3 px-4">Planteles Adscritos</th>
                        <th class="py-3 px-4">Clases en este Plantel</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($teachers as $teacher)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    <span>{{ $teacher->full_name }}</span>
                                    @if ($teacher->user_id)
                                        <flux:badge size="xs" color="emerald">Con Acceso</flux:badge>
                                    @endif
                                </div>
                                <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                    UUID: {{ Str::limit($teacher->id, 13) }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-xs">
                                {{ $teacher->employee_number ?? '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                @if ($teacher->email) <div>{{ $teacher->email }}</div> @endif
                                @if ($teacher->phone) <div class="text-zinc-400">Tel: {{ $teacher->phone }}</div> @endif
                                @if (! $teacher->email && ! $teacher->phone) <span class="text-zinc-400 italic">Sin datos</span> @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($teacher->schools as $sch)
                                        <flux:badge size="xs" color="{{ $sch->id === $school->id ? 'indigo' : 'zinc' }}">
                                            {{ Str::limit($sch->name, 15) }}
                                        </flux:badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <flux:badge size="sm" color="zinc">
                                    {{ $teacher->classes->count() }} clase(s)
                                </flux:badge>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="pencil-square"
                                        wire:click="openEditModal('{{ $teacher->id }}')"
                                        title="Editar datos del profesor"
                                    />

                                    <flux:button
                                        size="sm"
                                        variant="subtle"
                                        icon="arrow-right-start-on-rectangle"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        wire:click="openUnlinkModal('{{ $teacher->id }}')"
                                        title="Desvincular de este plantel"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 px-4 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="users" class="size-8 mx-auto text-zinc-400" />
                                <p class="font-medium mt-2">No se encontraron profesores adscritos a este plantel.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($teachers->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $teachers->links() }}
            </div>
        @endif
    </div>

    <!-- Sección: Invitaciones Pendientes -->
    @if ($pendingInvitations->isNotEmpty())
        <div class="border border-amber-200 dark:border-amber-900/50 rounded-xl overflow-hidden bg-white dark:bg-zinc-900 shadow-xs">
            <div class="p-4 bg-amber-50/50 dark:bg-amber-950/20 border-b border-amber-200 dark:border-amber-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div class="flex items-center gap-2">
                    <flux:icon name="envelope" class="size-5 text-amber-600 dark:text-amber-400" />
                    <flux:heading size="base" class="text-zinc-900 dark:text-zinc-100">
                        Invitaciones Pendientes de Activación ({{ $pendingInvitations->count() }})
                    </flux:heading>
                </div>
                <span class="text-xs text-zinc-500">Los docentes invitados deben abrir el enlace recibido por correo para completar su registro.</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="py-2.5 px-4">Docente</th>
                            <th class="py-2.5 px-4">Correo</th>
                            <th class="py-2.5 px-4">Clave</th>
                            <th class="py-2.5 px-4">Estado</th>
                            <th class="py-2.5 px-4">Vence</th>
                            <th class="py-2.5 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($pendingInvitations as $invitation)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="py-3 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $invitation->full_name }}
                                </td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300 font-mono text-xs">
                                    {{ $invitation->email }}
                                </td>
                                <td class="py-3 px-4 text-zinc-500 font-mono text-xs">
                                    {{ $invitation->employee_number ?? '—' }}
                                </td>
                                <td class="py-3 px-4">
                                    <flux:badge size="xs" color="amber">Pendiente</flux:badge>
                                </td>
                                <td class="py-3 px-4 text-xs text-zinc-500">
                                    {{ $invitation->expires_at->diffForHumans() }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <flux:button
                                            size="sm"
                                            variant="subtle"
                                            wire:click="resendInvitation('{{ $invitation->id }}')"
                                            title="Reenviar invitación por correo"
                                        >
                                            Reenviar
                                        </flux:button>
                                        <flux:button
                                            size="sm"
                                            variant="ghost"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            wire:click="revokeInvitation('{{ $invitation->id }}')"
                                            wire:confirm="¿Deseas revocar la invitación para {{ $invitation->email }}?"
                                            title="Revocar invitación"
                                        >
                                            Revocar
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Modal: Registrar / Vincular Profesor -->
    <flux:modal wire:model="showCreateModal" class="md:w-2xl">
        <form wire:submit="createTeacher" class="space-y-6">
            <div>
                <flux:heading size="lg">Adscripción de Profesor</flux:heading>
                <flux:subheading>
                    Plantel: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $school->name }}</span>
                </flux:subheading>
            </div>

            <!-- Selector de Modo -->
            <div class="flex gap-2 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg text-sm">
                <button
                    type="button"
                    wire:click="$set('teacherMode', 'new')"
                    class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $teacherMode === 'new' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}"
                >
                    Registrar Nuevo Profesor
                </button>
                <button
                    type="button"
                    wire:click="$set('teacherMode', 'existing')"
                    class="flex-1 py-1.5 px-3 rounded-md font-medium transition-colors {{ $teacherMode === 'existing' ? 'bg-white dark:bg-zinc-700 shadow-xs text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400' }}"
                >
                    Vincular Profesor de Otra Escuela
                </button>
            </div>

            @if ($teacherMode === 'existing')
                <div class="space-y-4">
                    <p class="text-xs text-zinc-500">
                        Selecciona un profesor ya registrado en el sistema para permitirle laborar también en este plantel (multi-escuela).
                    </p>

                    <flux:select wire:model="existingTeacherId" label="Profesor Disponible *" required>
                        <flux:select.option value="">Selecciona un profesor</flux:select.option>
                        @foreach ($otherTeachers as $ot)
                            <flux:select.option value="{{ $ot->id }}">
                                {{ $ot->full_name }} ({{ $ot->employee_number ?? $ot->email ?? 'Sin matrícula' }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @else
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <flux:input
                            wire:model="newFirstName"
                            label="Nombre(s) *"
                            placeholder="Ej. Roberto"
                            required
                        />

                        <flux:input
                            wire:model="newLastName"
                            label="Apellidos *"
                            placeholder="Ej. Gómez Bolaños"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <flux:input
                            wire:model="newEmployeeNumber"
                            label="Clave / Matrícula"
                            placeholder="Ej. PROF-01"
                        />

                        <flux:input
                            wire:model="newPhone"
                            label="Teléfono"
                            placeholder="Ej. 3312345678"
                        />

                        <flux:input
                            wire:model="newEmail"
                            label="Correo Electrónico"
                            type="email"
                            placeholder="profesor@escuela.edu"
                        />
                    </div>

                    <div class="pt-3 border-t border-zinc-200 dark:border-zinc-700 space-y-3">
                        <flux:checkbox
                            wire:model.live="createUserAccount"
                            label="Crear cuenta de acceso al sistema para este profesor"
                            description="Permite que el profesor inicie sesión para ver sus sesiones diarias y pasar asistencia."
                        />

                        @if ($createUserAccount)
                            <flux:input
                                wire:model="newPassword"
                                label="Contraseña Temporal *"
                                type="password"
                                placeholder="Mínimo 12 caracteres"
                                viewable
                                required
                            />
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Completar Registro
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Editar Profesor -->
    <flux:modal wire:model="showEditModal" class="md:w-lg">
        <form wire:submit="updateTeacher" class="space-y-6">
            <div>
                <flux:heading size="lg">Editar Profesor</flux:heading>
                <flux:subheading>
                    Actualiza los datos personales y de contacto del docente.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="editFirstName"
                        label="Nombre(s) *"
                        required
                    />

                    <flux:input
                        wire:model="editLastName"
                        label="Apellidos *"
                        required
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <flux:input
                        wire:model="editEmployeeNumber"
                        label="Clave / Matrícula"
                    />

                    <flux:input
                        wire:model="editPhone"
                        label="Teléfono"
                    />

                    <flux:input
                        wire:model="editEmail"
                        label="Correo Electrónico"
                        type="email"
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showEditModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Actualizar Datos
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Desvincular Profesor -->
    <flux:modal wire:model="showUnlinkModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Desvincular Profesor del Plantel
                </flux:heading>
                <flux:subheading>
                    ¿Deseas desvincular al docente <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $unlinkingTeacherName }}</span> de esta escuela?
                </flux:subheading>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                El docente dejará de aparecer en este plantel pero conservará su registro global si labora en otras escuelas.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showUnlinkModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="unlinkTeacher">
                    Sí, Desvincular
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal: Invitar Docente vía Correo Transaccional -->
    <flux:modal wire:model="showInviteModal" class="md:w-lg">
        <form wire:submit="sendInvitation" class="space-y-6">
            <div>
                <flux:heading size="lg">Invitar Docente por Correo</flux:heading>
                <flux:subheading>
                    Se enviará un correo seguro con enlace de activación temporal (3 días) para que el docente configure su propia contraseña.
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="inviteFirstName"
                        label="Nombre(s) *"
                        placeholder="Ej. Roberto"
                        required
                    />

                    <flux:input
                        wire:model="inviteLastName"
                        label="Apellidos *"
                        placeholder="Ej. Gómez Bolaños"
                        required
                    />
                </div>

                <flux:input
                    wire:model="inviteEmail"
                    label="Correo Electrónico Institucional / Personal *"
                    type="email"
                    placeholder="docente@escuela.edu"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <flux:input
                        wire:model="inviteEmployeeNumber"
                        label="Clave / Matrícula (opcional)"
                        placeholder="Ej. PROF-01"
                    />

                    <flux:input
                        wire:model="invitePhone"
                        label="Teléfono (opcional)"
                        placeholder="Ej. 3312345678"
                    />
                </div>

                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-800/60 p-3 text-xs text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                    <div class="font-medium text-zinc-900 dark:text-zinc-200 mb-1 flex items-center gap-1.5">
                        <flux:icon name="shield-check" class="size-4 text-emerald-600 dark:text-emerald-400" />
                        Seguridad de la invitación
                    </div>
                    El enlace utiliza un token criptográfico único con hash SHA-256. Ninguna contraseña es enviada por correo ni almacenada en texto plano.
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showInviteModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary" icon="paper-airplane">
                    Enviar Invitación
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
