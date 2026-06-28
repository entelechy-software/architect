{{-- Step 4: Result --}}
<div class="text-center py-4">
    @if ($lastFailed === 0)
        <i class="fas fa-check-circle fa-4x text-green-600 dark:text-green-400 mb-3"></i>
        <h4>Import complete</h4>
    @else
        <i class="fas fa-exclamation-circle fa-4x text-amber-600 dark:text-amber-400 mb-3"></i>
        <h4>Import completed with errors</h4>
    @endif

    <div class="inline-block text-left mt-3">
        <table class="arch-table arch-table-sm arch-table-borderless mb-0">
            <tbody>
                <tr>
                    <th class="pr-4">Batch ID:</th>
                    <td>#{{ $lastBatchId }}</td>
                </tr>
                <tr>
                    <th class="pr-4">Imported:</th>
                    <td><span class="arch-badge bg-green-600">{{ $lastImported }}</span></td>
                </tr>
                @if ($lastFailed > 0)
                    <tr>
                        <th class="pr-4">Failed:</th>
                        <td><span class="arch-badge bg-red-600">{{ $lastFailed }}</span></td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
