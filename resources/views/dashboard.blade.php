<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();
        $isGlobalAdmin = $user->isGlobalAdmin();
        $schools = $isGlobalAdmin
            ? \App\Models\School::withCount(['schoolYears'])->orderBy('name')->get()
            : $user->schoolMemberships()->with(['school' => fn ($q) => $q->withCount('schoolYears'), 'roles'])->get();
    @endphp

    <div class="p-6 max-w-7xl mx-auto space-y-6">
        <!-- Saludo -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    @if ($isGlobalAdmin)
                        <flux:badge size="sm" color="amber">ADMIN_GLOBAL</flux:badge>
                    @else
                        <flux:badge size="sm" color="zinc">Personal Escolar</flux:badge>
                    @endif
                </div>
                <flux:heading size="xl" level="1" class="mt-1">
                    Bienvenido, {{ $user->name }}
                </flux:heading>
                <flux:subheading>
                    Sistema comercial de control escolar y asistencia para escuelas secundarias.
                </flux:subheading>
            </div>

            @if ($isGlobalAdmin)
                <div>
                    <flux:button variant="primary" icon="building-library" :href="route('admin.schools.index')" wire:navigate>
                        Administrar Escuelas
                    </flux:button>
                </div>
            @endif
        </div>

        <!-- Sección de Planteles Disponibles -->
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Planteles Educativos Disponibles</flux:heading>
                <flux:subheading>
                    Selecciona una escuela para acceder a su panel operativo y pase de lista.
                </flux:subheading>
            </div>

            @if ($isGlobalAdmin)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($schools as $school)
                        <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <flux:badge size="xs" color="indigo">Plantel</flux:badge>
                                    @if ($school->code)
                                        <span class="text-xs font-mono text-zinc-400">C.C.T. {{ $school->code }}</span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 mt-2">
                                    {{ $school->name }}
                                </h3>
                                <div class="text-xs text-zinc-500 mt-1">
                                    {{ $school->school_years_count }} ciclo(s) escolar(es) registrado(s)
                                </div>
                            </div>

                            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    class="w-full"
                                    icon-trailing="arrow-right"
                                    :href="route('school.dashboard', $school)"
                                    wire:navigate
                                >
                                    Ingresar al Plantel
                                </flux:button>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-10 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                            <flux:icon name="building-library" class="size-8 mx-auto text-zinc-400" />
                            <p class="font-medium mt-2">Aún no hay escuelas registradas en el sistema.</p>
                            <div class="mt-3">
                                <flux:button size="sm" variant="primary" :href="route('admin.schools.index')" wire:navigate>
                                    Registrar Primer Plantel
                                </flux:button>
                            </div>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($schools as $membership)
                        @php $school = $membership->school; @endphp
                        @if ($school)
                            <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($membership->roles as $role)
                                                <flux:badge size="xs" color="teal">{{ $role->role }}</flux:badge>
                                            @endforeach
                                        </div>
                                        @if ($school->code)
                                            <span class="text-xs font-mono text-zinc-400">C.C.T. {{ $school->code }}</span>
                                        @endif
                                    </div>
                                    <h3 class="font-bold text-base text-zinc-900 dark:text-zinc-100 mt-2">
                                        {{ $school->name }}
                                    </h3>
                                    <div class="text-xs text-zinc-500 mt-1">
                                        {{ $school->school_years_count }} ciclo(s) escolar(es)
                                    </div>
                                </div>

                                <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                    <flux:button
                                        size="sm"
                                        variant="primary"
                                        class="w-full"
                                        icon-trailing="arrow-right"
                                        :href="route('school.dashboard', $school)"
                                        wire:navigate
                                    >
                                        Ingresar al Plantel
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="col-span-full py-10 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                            <flux:icon name="building-office-2" class="size-8 mx-auto text-zinc-400" />
                            <p class="font-medium mt-2">No tienes planteles asignados actualmente.</p>
                            <p class="text-xs text-zinc-400 mt-1">Comunícate con el administrador central para que te asigne a tu escuela.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
