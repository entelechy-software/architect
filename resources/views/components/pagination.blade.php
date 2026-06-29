{{--
    Props:
      paginator                    — LengthAwarePaginator instance
      pageOptions                  — array of per-page option integers (e.g. [10, 25, 50, 100])
      currentPageOptionProperty    — Livewire property name for per-page (default 'perPage')
--}}
@props([
    'paginator',
    'pageOptions'               => [],
    'currentPageOptionProperty' => 'perPage',
])
<div class="arch-pagination-bar">

    {{-- Per-page selector --}}
    @if (count($pageOptions) > 0)
        <div class="arch-pagination-bar__per-page">
            <span class="arch-pagination-bar__label">{{ __('Rows per page:') }}</span>
            <select
                wire:model.live="{{ $currentPageOptionProperty }}"
                class="arch-select"
            >
                @foreach ($pageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Showing X–Y of Z --}}
    @if ($paginator->total() > 0)
        <span class="arch-pagination-bar__summary">
            {{ __('Showing') }} <strong>{{ number_format($paginator->firstItem()) }}</strong>–<strong>{{ number_format($paginator->lastItem()) }}</strong>
            {{ __('of') }} <strong>{{ number_format($paginator->total()) }}</strong>
        </span>
    @else
        <span class="arch-pagination-bar__summary">{{ __('No results') }}</span>
    @endif

    {{-- Page navigation --}}
    @if ($paginator->hasPages())
        <nav aria-label="Pagination">
            <ul class="arch-pagination">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li class="arch-pagination__item" data-disabled="true"><span class="arch-pagination__btn" aria-hidden="true">&lsaquo;</span></li>
                @else
                    <li class="arch-pagination__item">
                        <button wire:click="previousPage" class="arch-pagination__btn" aria-label="{{ __('Previous page') }}">&lsaquo;</button>
                    </li>
                @endif

                {{-- Page numbers --}}
                @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="arch-pagination__item" aria-current="page"><span class="arch-pagination__btn" data-active="true">{{ $page }}</span></li>
                    @else
                        <li class="arch-pagination__item">
                            <button wire:click="gotoPage({{ $page }})" class="arch-pagination__btn">{{ $page }}</button>
                        </li>
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li class="arch-pagination__item">
                        <button wire:click="nextPage" class="arch-pagination__btn" aria-label="{{ __('Next page') }}">&rsaquo;</button>
                    </li>
                @else
                    <li class="arch-pagination__item" data-disabled="true"><span class="arch-pagination__btn" aria-hidden="true">&rsaquo;</span></li>
                @endif
            </ul>
        </nav>
    @endif
</div>
