{{--
    Date range filter partial — Flatpickr range input wired to DashboardEngine.

    Variables:
        $dateFrom  string — Y-m-d
        $dateTo    string — Y-m-d
--}}
<div
    x-data="{
        init() {
            flatpickr(this.$refs.dateInput, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: ['{{ $dateFrom }}', '{{ $dateTo }}'],
                onChange: (selectedDates) => {
                    if (selectedDates.length === 2) {
                        const fmt = d => d.toISOString().slice(0, 10);
                        $wire.set('dateFrom', fmt(selectedDates[0]));
                        $wire.set('dateTo',   fmt(selectedDates[1]));
                    }
                },
            });
        }
    }"
    class="flex items-center gap-2"
>
    <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Date range:</label>
    <input
        x-ref="dateInput"
        type="text"
        placeholder="Select date range…"
        class="arch-input arch-input-sm w-56"
        readonly
    />
</div>
