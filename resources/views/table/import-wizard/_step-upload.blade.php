{{-- Step 1: Upload --}}
@php
    /** @var array<string, \Entelechy\Architect\Table\Column> $importColumns */
    /** @var list<string> $globalErrors */
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    {{-- Left: column reference --}}
    <div class="">
        <h6 class="mb-2">Importable columns</h6>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-2">
            Your CSV must have these column headers in this exact order.
        </p>
        <div class="overflow-x-auto border rounded-md">
            <table class="arch-table arch-table-sm mb-0">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>Column</th>
                        <th>Example</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($importColumns as $key => $col)
                        <tr>
                            <td class="text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</td>
                            <td>{{ $col->getLabel() }}</td>
                            <td class="text-gray-500 dark:text-gray-400 text-sm">{{ $col->getPlaceholder() ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            <button type="button" class="arch-btn arch-btn-outline-primary arch-btn-sm" wire:click="downloadTemplate">
                <i class="fas fa-download ml-1"></i>Download CSV template
            </button>
        </div>
    </div>

    {{-- Right: file picker --}}
    <div class="">
        <h6 class="mb-2">Choose your file</h6>
        @if ($globalErrors !== [])
            <div class="arch-alert arch-alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($globalErrors as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <label class="arch-label" for="import-file">CSV file (UTF-8)</label>
        <input
            type="file"
            id="import-file"
            class="arch-input @error('file') is-invalid @enderror"
            wire:model="file"
            accept=".csv,text/csv"
        >
        @error('file')<div class="invalid-feedback block">{{ $message }}</div>@enderror

        <div class="arch-form-hint mt-2">
            <ul class="mb-0 pl-3">
                <li>UTF-8 encoded</li>
                <li>First row must match the template headers exactly</li>
                <li>Cells starting with <code>=</code>, <code>+</code>, <code>-</code> or <code>@</code> are sanitised</li>
            </ul>
        </div>

        <div wire:loading wire:target="file" class="mt-2 text-gray-500 dark:text-gray-400 text-sm">
            <i class="fas fa-spinner fa-spin ml-1"></i>Uploading…
        </div>
    </div>
</div>
