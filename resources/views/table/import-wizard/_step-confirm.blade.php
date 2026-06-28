{{-- Step 3: Confirm --}}
@php
    $importable = $validCount;
    if ($skipDuplicates) {
        $importable = $validCount - $duplicateCount;
        if ($importable < 0) { $importable = 0; }
    }
@endphp

<div class="text-center py-4">
    <i class="fas fa-cloud-upload-alt fa-3x text-primary-600 dark:text-primary-400 mb-3"></i>
    <h4>Ready to import</h4>
    <p class="text-gray-500 dark:text-gray-400">Please review the summary below before committing.</p>

    <div class="inline-block text-left mt-3">
        <table class="arch-table arch-table-sm arch-table-borderless mb-0">
            <tbody>
                <tr>
                    <th class="pr-4">Total rows in file:</th>
                    <td>{{ count($parsedRows) }}</td>
                </tr>
                <tr>
                    <th class="pr-4">Selected rows:</th>
                    <td><span class="arch-badge bg-primary-600">{{ $this->selectedCount }}</span></td>
                </tr>
                <tr>
                    <th class="pr-4">Selected valid rows:</th>
                    <td><span class="arch-badge bg-green-600">{{ $validCount }}</span></td>
                </tr>
                <tr>
                    <th class="pr-4">Selected invalid rows:</th>
                    <td><span class="arch-badge bg-red-600">{{ $invalidCount }}</span></td>
                </tr>
                @if ($duplicateCount > 0)
                    <tr>
                        <th class="pr-4">Duplicates:</th>
                        <td>
                            <span class="arch-badge bg-amber-500 text-gray-900 dark:text-white">{{ $duplicateCount }}</span>
                            <small class="text-gray-500 dark:text-gray-400 mr-2">
                                ({{ $skipDuplicates ? 'will be skipped' : 'will be imported' }})
                            </small>
                        </td>
                    </tr>
                @endif
                <tr class="border-t border-gray-200 dark:border-white/10">
                    <th class="pr-4 pt-2">Will be imported:</th>
                    <td class="pt-2">
                        <span class="arch-badge bg-primary-600 text-sm">{{ $importable }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="text-gray-500 dark:text-gray-400 text-sm mt-4 mb-0">
        <i class="fas fa-info-circle ml-1"></i>
        You may reverse this import within the configured reversal window from the History panel.
    </p>
</div>
