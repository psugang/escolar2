<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">Bitácora de Auditoría</flux:heading>
                <flux:badge color="zinc" size="sm">Trazabilidad</flux:badge>
            </div>
            <flux:subheading>
                Registro inmutable de acciones, modificaciones y eventos de control escolar en {{ $school->name }}.
            </flux:subheading>
        </div>
    </div>

    <!-- Filtros -->
    <div class="p-4 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="lg:col-span-2">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="Buscar por descripción, acción o IP..."
                    clearable
                />
            </div>

            <div>
                <flux:select wire:model.live="filterAction" placeholder="Todas las acciones">
                    <flux:select.option value="">Todas las operaciones</flux:select.option>
                    @foreach ($actions as $act)
                        <flux:select.option value="{{ $act }}">{{ $act }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div>
                <flux:select wire:model.live="filterUserId" placeholder="Todos los usuarios">
                    <flux:select.option value="">Todos los usuarios</flux:select.option>
                    @foreach ($users as $u)
                        <flux:select.option value="{{ $u->id }}">{{ $u->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="flex items-center gap-2">
                <flux:input type="date" wire:model.live="dateFrom" title="Desde" class="w-full" />
                <span class="text-zinc-400 text-xs">a</span>
                <flux:input type="date" wire:model.live="dateTo" title="Hasta" class="w-full" />
            </div>
        </div>

        @if ($search !== '' || $filterAction !== '' || $filterUserId !== '' || $dateFrom !== '' || $dateTo !== '')
            <div class="flex justify-end pt-1">
                <flux:button size="xs" variant="ghost" wire:click="clearFilters" icon="x-mark">
                    Limpiar Filtros
                </flux:button>
            </div>
        @endif
    </div>

    <!-- Tabla de Registros -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Fecha y Hora</th>
                        <th class="px-4 py-3">Usuario Responsable</th>
                        <th class="px-4 py-3">Operación</th>
                        <th class="px-4 py-3">Detalle del Evento</th>
                        <th class="px-4 py-3">IP</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($logs as $log)
                        @php
                            $badgeColor = match ($log->action) {
                                'ALUMNO_CREADO', 'CLASE_CREADA', 'JUSTIFICANTE_CREADO' => 'emerald',
                                'ALUMNO_BAJA', 'JUSTIFICANTE_ELIMINADO' => 'red',
                                'CAMBIO_GRUPO', 'ASISTENCIA_MODIFICADA' => 'amber',
                                'AVISO_AUSENCIA_ENVIADO' => 'purple',
                                'PASE_ASISTENCIA' => 'blue',
                                default => 'zinc',
                            };
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-500 font-mono">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($log->user)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" :name="$log->user->name" />
                                        <span class="text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $log->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Sistema / Anónimo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <flux:badge size="xs" :color="$badgeColor">
                                    {{ $log->action }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-xs text-zinc-800 dark:text-zinc-200 max-w-md truncate">
                                {{ $log->description ?: '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-zinc-400 font-mono">
                                {{ $log->ip_address ?: '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <flux:button size="xs" variant="ghost" icon="eye" wire:click="viewDetails('{{ $log->id }}')">
                                    Detalles
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="shield-check" class="size-8 mx-auto text-zinc-400 mb-2" />
                                <p class="font-medium">No se encontraron eventos de auditoría con los filtros seleccionados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Detalles de Auditoría -->
    <flux:modal wire:model="showDetailModal" class="md:w-xl">
        @if ($selectedLog)
            <div class="space-y-6">
                <div>
                    <div class="flex items-center justify-between">
                        <flux:heading size="lg">Detalle del Registro de Auditoría</flux:heading>
                        <flux:badge size="sm" color="zinc">{{ $selectedLog->action }}</flux:badge>
                    </div>
                    <flux:subheading>
                        Información técnica y valores registrados en el evento.
                    </flux:subheading>
                </div>

                <div class="p-4 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200 dark:border-zinc-700 space-y-2 text-xs">
                    <div class="grid grid-cols-3 gap-2">
                        <span class="text-zinc-500 font-medium">Fecha y hora:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100 font-mono">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</span>

                        <span class="text-zinc-500 font-medium">Usuario:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100 font-medium">{{ $selectedLog->user?->name ?? 'Sistema' }} ({{ $selectedLog->user?->email ?? 'N/A' }})</span>

                        <span class="text-zinc-500 font-medium">Descripción:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100">{{ $selectedLog->description }}</span>

                        <span class="text-zinc-500 font-medium">Entidad afectada:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100 font-mono text-[11px]">
                            {{ $selectedLog->auditable_type ? class_basename($selectedLog->auditable_type) . ' #' . $selectedLog->auditable_id : 'N/A' }}
                        </span>

                        <span class="text-zinc-500 font-medium">Dirección IP:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100 font-mono">{{ $selectedLog->ip_address ?: 'N/A' }}</span>

                        <span class="text-zinc-500 font-medium">Navegador/Agente:</span>
                        <span class="col-span-2 text-zinc-900 dark:text-zinc-100 break-words text-[11px]">{{ $selectedLog->user_agent ?: 'N/A' }}</span>
                    </div>
                </div>

                @if ($selectedLog->old_values || $selectedLog->new_values)
                    <div class="space-y-3">
                        <flux:text size="sm" class="font-semibold">Cambios en los datos:</flux:text>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                            @if ($selectedLog->old_values)
                                <div class="p-3 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 rounded-lg">
                                    <div class="font-bold text-red-700 dark:text-red-400 mb-1">Valores Anteriores:</div>
                                    <pre class="overflow-x-auto text-[11px] text-zinc-800 dark:text-zinc-200 font-mono">{{ json_encode($selectedLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif

                            @if ($selectedLog->new_values)
                                <div class="p-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 rounded-lg">
                                    <div class="font-bold text-emerald-700 dark:text-emerald-400 mb-1">Valores Nuevos:</div>
                                    <pre class="overflow-x-auto text-[11px] text-zinc-800 dark:text-zinc-200 font-mono">{{ json_encode($selectedLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <flux:button variant="ghost" wire:click="$set('showDetailModal', false)">
                        Cerrar
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>

