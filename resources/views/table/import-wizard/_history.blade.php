{{-- History panel --}}
@php
    /** @var list<array<string, mixed>> $batches */
@endphp

@if (count($batches) === 0)
    <div class="text-center py-5 text-gray-500 dark:text-gray-400">
        <i class="fas fa-inbox fa-3x mb-3"></i>
        <p class="mb-0">No imports yet for this table.</p>
    </div>
@else
    <p class="text-gray-500 dark:text-gray-400 text-sm">
        Showing the most recent {{ count($batches) }} imports for this table.
        Reversal undoes the records created by an import (archives them when the
        table supports archiving, otherwise hard-deletes).
    </p>
    <div class="table-responsive">
        <table class="arch-table arch-table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Filename</th>
                    <th>When</th>
                    <th class="text-right">Imported</th>
                    <th class="text-right">Failed</th>
                    <th>Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batches as $b)
                    <tr wire:key="batch-{{ $b['id'] }}">
                        <td>#{{ $b['id'] }}</td>
                        <td><span class="truncate inline-block" style="max-width: 200px;">{{ $b['filename'] }}</span></td>
                        <td class="text-sm text-gray-500 dark:text-gray-400">{{ $b['created_at'] }}</td>
                        <td class="text-right">{{ $b['imported_rows'] }}</td>
                        <td class="text-right">{{ $b['failed_rows'] }}</td>
                        <td>
                            @switch($b['status'])
                                @case('complete')
                                    <span class="arch-badge bg-green-600">Complete</span>
                                    @break
                                @case('partial')
                                    <span class="arch-badge bg-amber-500 text-gray-900 dark:text-white">Partial</span>
                                    @break
                                @case('reversed')
                                    <span class="arch-badge bg-gray-500">Reversed</span>
                                    @if (!empty($b['reversed_at']))
                                        <small class="text-gray-500 dark:text-gray-400 block">{{ $b['reversed_at'] }}</small>
                                    @endif
                                    @break
                                @default
                                    <span class="arch-badge bg-gray-100 dark:bg-gray-700/50 text-gray-900 dark:text-white">{{ $b['status'] }}</span>
                            @endswitch
                        </td>
                        <td class="text-right">
                            @if ($b['can_reverse'])
                                <button type="button" class="arch-btn arch-btn-sm arch-btn-outline-danger"
                                    wire:click="reverseBatch({{ $b['id'] }})"
                                    wire:confirm="Reverse this import? Records created by it will be archived or deleted."
                                >
                                    <i class="fas fa-undo ml-1"></i>Reverse
                                </button>
                            @elseif ($b['status'] !== 'reversed')
                                <span class="text-gray-500 dark:text-gray-400 text-sm" title="Reversal window expired or not authorised">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
