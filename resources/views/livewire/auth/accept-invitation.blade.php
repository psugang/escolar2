<div class="flex flex-col gap-6">
    <div class="text-center">
        <flux:heading size="xl" level="1">Activar Cuenta de Docente</flux:heading>
        <flux:subheading class="mt-1">
            Configura tu contraseña de acceso para el sistema escolar.
        </flux:subheading>
    </div>

    @if (! $isValid)
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl text-center space-y-3">
            <flux:icon name="exclamation-triangle" class="size-8 mx-auto text-red-500" />
            <div class="text-sm text-red-700 dark:text-red-300 font-medium">
                {{ $errorMessage }}
            </div>
            <div>
                <flux:button variant="subtle" size="sm" :href="route('login')" wire:navigate>
                    Ir al Inicio de Sesión
                </flux:button>
            </div>
        </div>
    @else
        <!-- Tarjeta informativa del Plantel y Docente -->
        <div class="p-3.5 bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 rounded-xl space-y-1 text-xs">
            <div class="flex justify-between items-center text-zinc-500">
                <span>Plantel:</span>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $schoolName }}</span>
            </div>
            <div class="flex justify-between items-center text-zinc-500">
                <span>Docente:</span>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $teacherName }}</span>
            </div>
            <div class="flex justify-between items-center text-zinc-500">
                <span>Correo:</span>
                <span class="font-mono text-zinc-900 dark:text-zinc-100">{{ $email }}</span>
            </div>
        </div>

        <form wire:submit="accept" class="flex flex-col gap-5">
            <!-- Password -->
            <flux:input
                wire:model="password"
                label="Nueva Contraseña"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Mínimo 12 caracteres (letras y números)"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                wire:model="password_confirmation"
                label="Confirmar Contraseña"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Repite tu nueva contraseña"
                viewable
            />

            <div class="pt-2">
                <flux:button type="submit" variant="primary" class="w-full">
                    Activar Cuenta e Ingresar
                </flux:button>
            </div>
        </form>
    @endif
</div>
