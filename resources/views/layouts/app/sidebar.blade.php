<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @php
                $activeSchool = request()->route('school');
                if (is_string($activeSchool)) {
                    $activeSchool = \App\Models\School::find($activeSchool);
                }
            @endphp

            <flux:sidebar.nav>
                @if ($activeSchool)
                    <flux:sidebar.group :heading="'Plantel: ' . Str::limit($activeSchool->name, 22)" class="grid">
                        <flux:sidebar.item icon="chart-bar" :href="route('school.dashboard', $activeSchool)" :current="request()->routeIs('school.dashboard')" wire:navigate>
                            Panel de Escuela
                        </flux:sidebar.item>
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
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('school.attendance.daily', $activeSchool)" :current="request()->routeIs('school.attendance.*') && !request()->routeIs('school.attendance.justifications')" wire:navigate>
                            Pase de Asistencia
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('school.attendance.justifications', $activeSchool)" :current="request()->routeIs('school.attendance.justifications')" wire:navigate>
                            Justificantes
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chart-pie" :href="route('school.reports.index', $activeSchool)" :current="request()->routeIs('school.reports.*')" wire:navigate>
                            Reportes
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="shield-check" :href="route('school.audit.index', $activeSchool)" :current="request()->routeIs('school.audit.*')" wire:navigate>
                            Auditoría
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="arrow-left" :href="route('dashboard')" wire:navigate>
                            Cambiar Plantel
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @else
                    <flux:sidebar.group :heading="__('Platform')" class="grid">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if (auth()->user()?->isGlobalAdmin())
                    <flux:sidebar.group heading="Administración Global" class="grid">
                        <flux:sidebar.item icon="building-library" :href="route('admin.schools.index')" :current="request()->routeIs('admin.schools.*')" wire:navigate>
                            Escuelas
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

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
