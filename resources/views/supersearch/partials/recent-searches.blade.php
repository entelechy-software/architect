{{--
    Supersearch — recent searches shown before the user has typed anything.
    Reads from localStorage key `architectSupersearch_recent_<key>`.
    Alpine renders this client-side; if localStorage is empty the pane is blank.
--}}
<div x-data="{ recent: [] }" x-init="recent = getRecent()">
    <template x-if="recent.length > 0">
        <div>
            <div class="px-4 pt-3 pb-1">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 select-none">
                    Recent searches
                </p>
            </div>
            <template x-for="(term, idx) in recent" :key="idx">
                <div
                    class="flex items-center gap-3 px-4 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                    @click="$dispatch('architect:supersearch:run-recent', { term })"
                >
                    <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800">
                        <i class="fas fa-clock-rotate-left text-sm text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1" x-text="term"></span>
                    <button
                        type="button"
                        @click.stop="removeRecent(term); recent = getRecent()"
                        class="text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400 text-xs"
                        title="Remove"
                    >
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </template>
        </div>
    </template>

    <template x-if="recent.length === 0">
        <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
            <i class="fas fa-magnifying-glass text-gray-300 dark:text-gray-600 text-2xl mb-2"></i>
            <p class="text-sm text-gray-400 dark:text-gray-500">Start typing to search…</p>
        </div>
    </template>
</div>
