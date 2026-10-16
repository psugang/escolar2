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
            <flux:heading size="xl" level="1" class="mt-1">Justificantes de Inasistencia</flux:heading>
            <flux:subheading>
                Control y registro de justificaciones médicas o administrativas para ausencias de alumnos.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="subtle" icon="clipboard-document-check" :href="route('school.attendance.daily', $school)" wire:navigate>
                Pase de Asistencia
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
                placeholder="Buscar alumno ausente..."
                icon="magnifying-glass"
                clearable
            />
        </div>
    </div>

    <!-- Tabla de Ausencias y Justificantes -->
    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden bg-white dark:bg-zinc-900 shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-600 dark:text-zinc-300 font-medium border-b border-zinc-200 dark:border-zinc-700">
                    <tr>
                        <th class="py-3 px-4">Alumno Ausente</th>
                        <th class="py-3 px-4">Fecha y Clase</th>
                        <th class="py-3 px-4">Estado de Asistencia</th>
                        <th class="py-3 px-4">Justificante</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($attendances as $att)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition-colors">
                            <td class="py-3.5 px-4 font-medium text-zinc-900 dark:text-zinc-100">
                                <div>{{ $att->student->full_name }}</div>
                                <div class="text-xs text-zinc-400 font-mono mt-0.5">
                                    {{ $att->student->student_id_number ?? 'Sin matrícula' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div class="font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ $att->classSession->date->format('d/m/Y') }} · {{ substr($att->classSession->start_time, 0, 5) }}
                                </div>
                                <div class="text-zinc-500">
                                    {{ $att->classSession->schoolClass->display_name }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <flux:badge size="sm" color="red">Ausente</flux:badge>
                            </td>
                            <td class="py-3.5 px-4">
                                @if ($att->justification)
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <flux:badge size="xs" color="emerald">Justificada</flux:badge>
                                            @if ($att->justification->document_path)
                                                <a
                                                    href="{{ route('school.justifications.document', [$school, $att->justification]) }}"
                                                    target="_blank"
                                                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5"
                                                >
                                                    <flux:icon name="document-arrow-down" class="size-3.5" />
                                                    Comprobante
                                                </a>
                                            @endif
                                        </div>
                                        <div class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 max-w-xs truncate" title="{{ $att->justification->reason }}">
                                            Motivo: {{ $att->justification->reason }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400 italic">Sin justificar</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if (! $att->justification)
                                    <flux:button
                                        size="sm"
                                        variant="primary"
                                        icon="document-plus"
                                        wire:click="openJustifyModal('{{ $att->id }}')"
                                    >
                                        Justificar
                                    </flux:button>
                                @else
                                    <flux:button
                                        size="xs"
                                        variant="subtle"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700"
                                        wire:click="openDeleteModal('{{ $att->justification->id }}')"
                                        title="Eliminar justificante"
                                    />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 px-4 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="check-circle" class="size-8 mx-auto text-emerald-500" />
                                <p class="font-medium mt-2">No hay inasistencias registradas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($attendances->hasPages())
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Registrar Justificante -->
    <flux:modal wire:model="showJustifyModal" class="md:w-lg">
        <form wire:submit="saveJustification" class="space-y-6">
            <div>
                <flux:heading size="lg">Registrar Justificante</flux:heading>
                <flux:subheading>
                    Alumno: <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedAttendance?->student->full_name }}</span>
                </flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:textarea
                    wire:model="reason"
                    label="Motivo o Causa de la Justificación *"
                    placeholder="Ej. Cita médica en el IMSS, receta presentada..."
                    rows="3"
                    required
                />

                <flux:input
                    wire:model="document"
                    label="Documento Comprobatorio (PDF o Imagen)"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    description="Opcional. Formatos aceptados: PDF, JPG, PNG (máx. 10 MB)."
                />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showJustifyModal', false)">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Guardar Justificante
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal: Eliminar Justificante -->
    <flux:modal wire:model="showDeleteModal" class="md:w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-600 dark:text-red-400">
                    Eliminar Justificante
                </flux:heading>
                <flux:subheading>
                    ¿Estás seguro de que deseas eliminar este justificante?
                </flux:subheading>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                La inasistencia permanecerá registrada como AUSENTE sin justificación.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <flux:button variant="ghost" wire:click="$set('showDeleteModal', false)">
                    Cancelar
                </flux:button>
                <flux:button variant="danger" wire:click="deleteJustification">
                    Sí, Eliminar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

