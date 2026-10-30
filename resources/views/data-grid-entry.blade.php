<div
    x-load
    x-load-css="@js([$styleHref])"
    x-load-src="{{ $alpineSrc }}"
    x-data="filamentDataGridEntry({{ $config }})"
    wire:key="{{ $entry->getLivewireKey() }}.data-grid-entry"
    id="{{ $entry->getId() }}"
    class="fi-data-grid fi-data-grid-entry-grid"
>
    <div x-ref="payload" hidden data-payload="{{ json_encode($payload, JSON_THROW_ON_ERROR) }}"></div>

    @if ($entry->isSearchable())
        <div class="fi-data-grid-toolbar">
            <x-filament::input.wrapper
                inline-prefix
                prefix-icon="heroicon-m-magnifying-glass"
                class="fi-data-grid-search"
            >
                <x-filament::input
                    type="search"
                    autocomplete="off"
                    x-model.debounce.150ms="search"
                    :placeholder="__('filament-data-grid::data-grid.entry.search')"
                    :aria-label="__('filament-data-grid::data-grid.entry.search')"
                />
            </x-filament::input.wrapper>
        </div>
    @endif

    <div wire:ignore class="fi-data-grid-viewport" x-show="! isMounted || total > 0">
        <div x-ref="grid" class="fi-data-grid-canvas"></div>
    </div>

    <p x-cloak x-show="isMounted && total === 0" class="fi-data-grid-empty">
        {{ __('filament-data-grid::data-grid.entry.empty') }}
    </p>

    @if ($entry->getRecordsPerPage() !== null)
        <nav
            x-cloak
            x-show="isMounted && total > 0"
            class="fi-data-grid-pagination"
            aria-label="{{ __('filament-data-grid::data-grid.entry.pagination.label') }}"
        >
            <span class="fi-data-grid-pagination-overview" x-text="overview"></span>

            <div x-show="pageCount > 1" class="fi-data-grid-pagination-pages">
                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-m-chevron-left"
                    :label="__('filament-data-grid::data-grid.entry.pagination.previous')"
                    x-on:click="previousPage()"
                    x-bind:disabled="page <= 1"
                    class="fi-data-grid-pagination-previous"
                />

                <span class="fi-data-grid-pagination-page" x-text="`${page} / ${pageCount}`"></span>

                <x-filament::icon-button
                    color="gray"
                    icon="heroicon-m-chevron-right"
                    :label="__('filament-data-grid::data-grid.entry.pagination.next')"
                    x-on:click="nextPage()"
                    x-bind:disabled="page >= pageCount"
                    class="fi-data-grid-pagination-next"
                />
            </div>
        </nav>
    @endif
</div>
