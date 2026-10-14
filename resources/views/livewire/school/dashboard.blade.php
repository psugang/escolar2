<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Encabezado de la Escuela -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-200 dark:border-zinc-700 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="indigo">Plantel Activo</flux:badge>
                @if ($school->code)
                    <span class="text-xs font-mono text-zinc-400">C.C.T. {{ $school->code }}</span>
                @endif
            </div>
            <flux:heading size="xl" level="1" class="mt-1">{{ $school->name }}</flux:heading>
            <flux:subheading>
                Panel de control operativo y gestión escolar.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="subtle" icon="calendar" :href="route('school.years.index', $school)" wire:navigate>
                Ciclos y Grupos
            </flux:button>
            @if (auth()->user()?->isGlobalAdmin())
                <flux:button variant="ghost" icon="arrow-left" :href="route('admin.schools.index')" wire:navigate>
                    Volver a Escuelas
                </flux:button>
            @endif
        </div>
    </div>

    <!-- Tarjetas de métricas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Ciclo Actual -->
        <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Ciclo Escolar</span>
                <flux:icon name="calendar-days" class="size-5 text-indigo-500" />
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $activeYear?->name ?? 'Sin ciclo activo' }}
                </div>
                <div class="text-xs text-zinc-500 mt-0.5">
                    @if ($activeYear)
                        {{ $activeYear->starts_on->format('d/m/Y') }} — {{ $activeYear->ends_on->format('d/m/Y') }}
                    @else
                        Configura un ciclo para comenzar
                    @endif
                </div>
            </div>
        </div>

        <!-- Total de Grupos -->
        <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Grupos Activos</span>
                <flux:icon name="user-group" class="size-5 text-emerald-500" />
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $totalGroups }}
                </div>
                <div class="text-xs text-zinc-500 mt-0.5">
                    En el ciclo {{ $activeYear?->name ?? 'vigente' }}
                </div>
            </div>
        </div>

        <!-- Historial de Ciclos -->
        <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Historial de Ciclos</span>
                <flux:icon name="clock" class="size-5 text-amber-500" />
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $totalYears }}
                </div>
                <div class="text-xs text-zinc-500 mt-0.5">
                    Ciclos conservados históricamente
                </div>
            </div>
        </div>

        <!-- Personal / Membresías -->
        <div class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Personal Asignado</span>
                <flux:icon name="users" class="size-5 text-sky-500" />
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
                    {{ $totalMembers }}
                </div>
                <div class="text-xs text-zinc-500 mt-0.5">
                    Directores, admins y profesores
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose de Grupos por Grado -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl p-5 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">Estructura de Grupos por Grado</flux:heading>
                <flux:subheading>
                    Ciclo Escolar: {{ $activeYear?->name ?? 'No configurado' }}
                </flux:subheading>
            </div>
            <flux:button size="sm" variant="subtle" icon="plus" :href="route('school.years.index', $school)" wire:navigate>
                Administrar Grupos
            </flux:button>
        </div>

        @if ($groupsByGrade->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ([1, 2, 3] as $gradeNum)
                    <div class="p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/30">
                        <div class="flex items-center justify-between pb-2 border-b border-zinc-200 dark:border-zinc-700">
                            <span class="font-bold text-zinc-900 dark:text-zinc-100 text-base">
                                {{ $gradeNum }}° Grado
                            </span>
                            <flux:badge size="xs" color="zinc">
                                {{ isset($groupsByGrade[$gradeNum]) ? count($groupsByGrade[$gradeNum]) : 0 }} grupo(s)
                            </flux:badge>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if (isset($groupsByGrade[$gradeNum]))
                                @foreach ($groupsByGrade[$gradeNum] as $grp)
                                    <div class="flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-md text-xs font-medium">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100">
                                            {{ $grp->grade }}{{ $grp->section }}
                                        </span>
                                        <span class="text-zinc-400 font-normal">
                                            · {{ $grp->shift->name }}
                                        </span>
                                    </div>
                                @endforeach
                            @else
                                <span class="text-xs text-zinc-400 italic">Sin grupos registrados</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                <flux:icon name="academic-cap" class="size-8 mx-auto text-zinc-400" />
                <p class="font-medium mt-2">Aún no se han configurado grupos para este ciclo.</p>
                <div class="mt-3">
                    <flux:button size="sm" variant="primary" :href="route('school.years.index', $school)" wire:navigate>
                        Configurar Ciclos y Grupos
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    <!-- Acceso Rápido Operativo -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('school.attendance.daily', $school) }}" wire:navigate class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors group flex items-start gap-3 shadow-xs">
            <div class="p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400">
                <flux:icon name="clipboard-document-check" class="size-5" />
            </div>
            <div>
                <h4 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-indigo-600 transition-colors">Pase de Lista</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Sesiones del día y asistencia reactiva</p>
            </div>
        </a>

        <a href="{{ route('school.attendance.justifications', $school) }}" wire:navigate class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors group flex items-start gap-3 shadow-xs">
            <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400">
                <flux:icon name="document-text" class="size-5" />
            </div>
            <div>
                <h4 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-amber-600 transition-colors">Justificantes</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Carga de comprobantes (hasta 10MB)</p>
            </div>
        </a>

        <a href="{{ route('school.reports.index', $school) }}" wire:navigate class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors group flex items-start gap-3 shadow-xs">
            <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400">
                <flux:icon name="chart-pie" class="size-5" />
            </div>
            <div>
                <h4 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-emerald-600 transition-colors">Reportes</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Estadísticas y exportación Excel/CSV</p>
            </div>
        </a>

        <a href="{{ route('school.audit.index', $school) }}" wire:navigate class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors group flex items-start gap-3 shadow-xs">
            <div class="p-2.5 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400">
                <flux:icon name="shield-check" class="size-5" />
            </div>
            <div>
                <h4 class="font-bold text-sm text-zinc-900 dark:text-zinc-100 group-hover:text-purple-600 transition-colors">Auditoría</h4>
                <p class="text-xs text-zinc-500 mt-0.5">Historial inmutable de operaciones</p>
            </div>
        </a>
    </div>
</div>

