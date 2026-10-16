<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50/60 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200/80 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.header class="px-2 pt-3 pb-2">
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php
                $activeSchool = request()->route('school');
                if (is_string($activeSchool)) {
                    $activeSchool = \App\Models\School::find($activeSchool);
                }
            @endphp

            <flux:sidebar.nav class="space-y-1">
                @if ($activeSchool)
                    <!-- Identificador del Plantel Activo -->
                    <div class="mx-1 mb-2 px-3 py-2 rounded-lg bg-zinc-100/80 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60">
                        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <span class="inline-block size-2 rounded-full bg-emerald-500"></span>
                            <span>Plantel Activo</span>
                        </div>
                        <div class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 truncate mt-0.5" title="{{ $activeSchool->name }}">
                            {{ $activeSchool->name }}
                        </div>
                        @if ($activeSchool->code)
                            <div class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400">
                                C.C.T. {{ $activeSchool->code }}
                            </div>
                        @endif
                    </div>

                    @php
                        $user = auth()->user();
                        $isTeacherOnly = $user && $user->isTeacherOnly($activeSchool);
                    @endphp

                    <flux:sidebar.group heading="Gestión Escolar" class="grid gap-0.5">
                        <flux:sidebar.item icon="chart-bar" :href="route('school.dashboard', $activeSchool)" :current="request()->routeIs('school.dashboard')" wire:navigate>
                            {{ $isTeacherOnly ? 'Mis Clases e Inicio' : 'Panel de Escuela' }}
                        </flux:sidebar.item>

                        @if (! $isTeacherOnly)
                            <flux:sidebar.item icon="calendar" :href="route('school.years.index', $activeSchool)" :current="request()->routeIs('school.years.*')" wire:navigate>
                                Ciclos y Grupos
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="academic-cap" :href="route('school.students.index', $activeSchool)" :current="request()->routeIs('school.students.*')" wire:navigate>
                                Alumnos
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="users" :href="route('school.teachers.index', $activeSchool)" :current="request()->routeIs('school.teachers.*')" wire:navigate>
                                Profesores
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="book-open" :href="route('school.classes.index', $activeSchool)" :current="request()->routeIs('school.classes.*')" wire:navigate>
                                Clases y Talleres
                            </flux:sidebar.item>
                        @endif

                        <flux:sidebar.item icon="clipboard-document-check" :href="route('school.attendance.daily', $activeSchool)" :current="request()->routeIs('school.attendance.*') && !request()->routeIs('school.attendance.justifications')" wire:navigate>
                            Pase de Asistencia
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('school.attendance.justifications', $activeSchool)" :current="request()->routeIs('school.attendance.justifications')" wire:navigate>
                            Justificantes
                        </flux:sidebar.item>

                        @if (! $isTeacherOnly)
                            <flux:sidebar.item icon="chart-pie" :href="route('school.reports.index', $activeSchool)" :current="request()->routeIs('school.reports.*')" wire:navigate>
                                Reportes
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="shield-check" :href="route('school.audit.index', $activeSchool)" :current="request()->routeIs('school.audit.*')" wire:navigate>
                                Auditoría
                            </flux:sidebar.item>
                        @endif
                    </flux:sidebar.group>

                    <flux:sidebar.group class="grid gap-0.5 mt-2 pt-2 border-t border-zinc-200/60 dark:border-zinc-800">
                        <flux:sidebar.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                            Cambiar Plantel
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @else
                    <flux:sidebar.group :heading="__('Platform')" class="grid gap-0.5">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if (auth()->user()?->isGlobalAdmin())
                    <flux:sidebar.group heading="Administración Global" class="grid gap-0.5 mt-2 pt-2 border-t border-zinc-200/60 dark:border-zinc-800">
                        <flux:sidebar.item icon="building-library" :href="route('admin.schools.index')" :current="request()->routeIs('admin.schools.*')" wire:navigate>
                            Escuelas
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block border-t border-zinc-200/60 dark:border-zinc-800 pt-3" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-zinc-200/80 bg-white/95 backdrop-blur-xs dark:border-zinc-800 dark:bg-zinc-900/95 px-4 py-2">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            @if ($activeSchool)
                <div class="flex items-center gap-2 max-w-[190px] sm:max-w-xs truncate">
                    <span class="inline-block size-2 shrink-0 rounded-full bg-emerald-500"></span>
                    <span class="font-semibold text-xs text-zinc-900 dark:text-zinc-100 truncate">
                        {{ $activeSchool->name }}
                    </span>
                </div>
            @else
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate class="scale-90" />
            @endif

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
