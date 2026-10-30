<div
    x-load
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
                    x-model="search"
                    :placeholder="__('filament-data-grid::data-grid.entry.search')"
                    :aria-label="__('filament-data-grid::data-grid.entry.search')"
                />
            </x-filament::input.wrapper>
        </div>
    @endif

    <div
        wire:ignore
        class="fi-data-grid-viewport"
        x-bind:class="{ 'fi-data-grid-loading': isLoading }"
        x-bind:aria-busy="isLoading"
    >
        <div
            x-ref="grid"
            class="fi-data-grid-canvas"
            style="min-height: calc(2.75rem + 1px + {{ is_int($payload['recordsPerPage']) ? min(count($payload['rows']), $payload['recordsPerPage']) * 44 : count($payload['rows']) * 44 }}px)"
        ></div>

        <p x-cloak x-show="isMounted && total === 0" class="fi-data-grid-empty">
            {{ __('filament-data-grid::data-grid.entry.empty') }}
        </p>

        @if ($entry->isPaginated())
            @php
                $pageOptions = $entry->getPaginationPageOptions();
                $optionLabel = fn (int | string $option): string => $option === \Waguilar\FilamentDataGrid\DataGridEntry::ALL_RECORDS
                    ? __('filament::components/pagination.fields.records_per_page.options.all')
                    : (string) $option;
            @endphp

            <nav
                x-cloak
                x-show="isMounted && total > 0"
                aria-label="{{ __('filament::components/pagination.label') }}"
                class="fi-pagination"
            >
                <x-filament::button
                    color="gray"
                    rel="prev"
                    x-show="page > 1"
                    x-on:click="goToPage(page - 1)"
                    class="fi-pagination-previous-btn"
                >
                    {{ __('filament::components/pagination.actions.previous.label') }}
                </x-filament::button>

                <span class="fi-pagination-overview" x-text="overview"></span>

                @if (count($pageOptions) > 1)
                    <div class="fi-pagination-records-per-page-select-ctn">
                        @foreach ([true, false] as $isCompact)
                            <label @class(['fi-pagination-records-per-page-select', 'fi-compact' => $isCompact])>
                                <x-filament::input.wrapper
                                    :prefix="$isCompact ? null : __('filament::components/pagination.fields.records_per_page.label')"
                                >
                                    <x-filament::input.select x-on:change="changeRecordsPerPage($event.target.value)">
                                        @foreach ($pageOptions as $option)
                                            <option
                                                value="{{ $option }}"
                                                x-bind:selected="String(recordsPerPage) === @js((string) $option)"
                                            >
                                                {{ $optionLabel($option) }}
                                            </option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>

                                @if ($isCompact)
                                    <span class="fi-sr-only">
                                        {{ __('filament::components/pagination.fields.records_per_page.label') }}
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

                <x-filament::button
                    color="gray"
                    rel="next"
                    x-show="page < pageCount"
                    x-on:click="goToPage(page + 1)"
                    class="fi-pagination-next-btn"
                >
                    {{ __('filament::components/pagination.actions.next.label') }}
                </x-filament::button>

                <ol x-show="pageCount > 1" class="fi-pagination-items">
                    <li x-show="page > 1" class="fi-pagination-item">
                        <button
                            type="button"
                            rel="prev"
                            aria-label="{{ __('filament::components/pagination.actions.previous.label') }}"
                            x-on:click="goToPage(page - 1)"
                            class="fi-pagination-item-btn"
                        >
                            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronLeft, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-pagination-item-icon'])) }}
                        </button>
                    </li>

                    <template x-for="(item, index) in pages" x-bind:key="index">
                        <li
                            class="fi-pagination-item"
                            x-bind:class="{ 'fi-active': item === page, 'fi-disabled': item === '...' }"
                        >
                            <button
                                type="button"
                                class="fi-pagination-item-btn"
                                x-bind:disabled="item === '...'"
                                x-bind:aria-current="item === page ? 'page' : null"
                                x-bind:aria-label="item === '...' ? null : goToPageLabel(item)"
                                x-on:click="item !== '...' && goToPage(item)"
                            >
                                <span
                                    class="fi-pagination-item-label"
                                    x-text="item === '...' ? item : item.toLocaleString()"
                                ></span>
                            </button>
                        </li>
                    </template>

                    <li x-show="page < pageCount" class="fi-pagination-item">
                        <button
                            type="button"
                            rel="next"
                            aria-label="{{ __('filament::components/pagination.actions.next.label') }}"
                            x-on:click="goToPage(page + 1)"
                            class="fi-pagination-item-btn"
                        >
                            {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronRight, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-pagination-item-icon'])) }}
                        </button>
                    </li>
                </ol>
            </nav>
        @endif
    </div>
</div>
