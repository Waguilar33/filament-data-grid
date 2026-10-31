@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\ComponentAttributeBag;
    use Filament\Tables\View\TablesIconAlias;
    use Illuminate\Support\HtmlString;

    $heading = $entry->getHeading();
    $description = $entry->getDescription();
    $icon = $entry->getIcon();
    $hasHeader = filled($heading) || filled($description);
    $hasToolbar = $entry->isSearchable() || filled($payload['filters']);
    $isSection = $hasHeader && $entry->isCompact();
@endphp

<div
    x-load
    x-load-src="{{ $alpineSrc }}"
    x-data="filamentDataGridEntry({{ $config }})"
    wire:key="{{ $entry->getLivewireKey() }}.data-grid-entry"
    id="{{ $entry->getId() }}"
    class="fi-data-grid fi-data-grid-entry-grid"
>
    <div x-ref="payload" hidden data-payload="{{ json_encode($payload, JSON_THROW_ON_ERROR) }}"></div>

    @if ($isSection)
        <section class="fi-section fi-compact fi-section-has-header fi-data-grid-section">
            <header class="fi-section-header">
                {{ \Filament\Support\generate_icon_html($icon, attributes: (new ComponentAttributeBag)->color(\Filament\Support\View\Components\SectionComponent\IconComponent::class, 'gray'), size: \Filament\Support\Enums\IconSize::Large) }}

                <div class="fi-section-header-content fi-section-header-content-has-heading">
                    <div class="fi-section-header-text-ctn">
                        @if (filled($heading))
                            <h3 class="fi-section-header-heading">{{ $heading }}</h3>
                        @endif

                        @if (filled($description))
                            <p class="fi-section-header-description">{{ $description }}</p>
                        @endif
                    </div>
                </div>
            </header>

            <div class="fi-section-content-ctn">
    @endif

    <div @class(['fi-ta-ctn', 'fi-ta-ctn-with-header' => ($hasHeader && ! $isSection) || $hasToolbar])>
        <div class="fi-ta-main">
            @if ($hasHeader && ! $isSection)
                <div class="fi-ta-header">
                    <div class="fi-data-grid-entry-header">
                        {{ \Filament\Support\generate_icon_html($icon, attributes: (new ComponentAttributeBag)->class(['fi-data-grid-entry-header-icon']), size: \Filament\Support\Enums\IconSize::Large) }}

                        <div>
                            @if (filled($heading))
                                <h3 class="fi-ta-header-heading">{{ $heading }}</h3>
                            @endif

                            @if (filled($description))
                                <p class="fi-ta-header-description">{{ $description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasToolbar)
                <div class="fi-ta-header-toolbar">
                    <div></div>

                    <div>
                        @if ($entry->isSearchable())
                            <div x-id="['input']" class="fi-ta-search-field">
                                <label x-bind:for="$id('input')" class="fi-sr-only">
                                    {{ __('filament-tables::table.fields.search.label') }}
                                </label>

                                <x-filament::input.wrapper
                                    inline-prefix
                                    :prefix-icon="Heroicon::MagnifyingGlass"
                                    :prefix-icon-alias="TablesIconAlias::SEARCH_FIELD"
                                >
                                    <x-filament::input
                                        :attributes="
                                            (new ComponentAttributeBag)->merge([
                                                'autocomplete' => 'off',
                                                'inlinePrefix' => true,
                                                'maxlength' => 255,
                                                'placeholder' => __('filament-tables::table.fields.search.placeholder'),
                                                'type' => 'search',
                                                'x-model' => 'search',
                                                'x-bind:id' => '$id(\'input\')',
                                            ], escape: false)
                                        "
                                    />
                                </x-filament::input.wrapper>
                            </div>
                        @endif

                        @if (filled($payload['filters']))
                            <x-filament::dropdown
                                placement="bottom-end"
                                shift
                                :flip="false"
                                width="xs"
                                class="fi-ta-filters-dropdown"
                            >
                                <x-slot name="trigger">
                                    <x-filament::icon-button
                                        color="gray"
                                        :icon="Heroicon::Funnel"
                                        :icon-alias="TablesIconAlias::ACTIONS_FILTER"
                                        :label="__('filament-tables::table.actions.filter.label')"
                                        :badge="new HtmlString('<span x-text=activeFiltersCount>0</span>')"
                                        class="fi-data-grid-filters-trigger"
                                    />
                                </x-slot>

                                <div class="fi-ta-filters">
                                    <div class="fi-ta-filters-header">
                                        <h3 class="fi-ta-filters-heading">
                                            {{ __('filament-tables::table.filters.heading') }}
                                        </h3>

                                        <div>
                                            <x-filament::link
                                                tag="button"
                                                color="danger"
                                                x-on:click="resetFilters(); close()"
                                                class="fi-data-grid-filters-reset"
                                            >
                                                {{ __('filament-tables::table.filters.actions.reset.label') }}
                                            </x-filament::link>
                                        </div>
                                    </div>

                                    <div class="fi-data-grid-filters-form">
                                        @foreach ($payload['filters'] as $filter)
                                            <x-filament-forms::field-wrapper
                                                :id="$entry->getId() . '-filter-' . $filter['name']"
                                                :label="$filter['label']"
                                                :label-tag="$filter['type'] === 'select' && $filter['multiple'] ? 'div' : 'label'"
                                            >
                                                @if ($filter['type'] === 'checkbox')
                                                    <label class="fi-data-grid-filter-checkbox">
                                                        <x-filament::input.checkbox
                                                            :id="$entry->getId() . '-filter-' . $filter['name']"
                                                            x-model="draftFilters[{{ \Illuminate\Support\Js::from($filter['name']) }}]"
                                                        />
                                                    </label>
                                                @elseif ($filter['type'] === 'select' && $filter['multiple'])
                                                    <div class="fi-data-grid-filter-options">
                                                        @foreach ($filter['options'] as $option)
                                                            <label class="fi-data-grid-filter-option">
                                                                <x-filament::input.checkbox
                                                                    :value="$option['value']"
                                                                    x-model="draftFilters[{{ \Illuminate\Support\Js::from($filter['name']) }}]"
                                                                />

                                                                <span>{{ $option['label'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <x-filament::input.wrapper>
                                                        <x-filament::input.select
                                                            :id="$entry->getId() . '-filter-' . $filter['name']"
                                                            x-model="draftFilters[{{ \Illuminate\Support\Js::from($filter['name']) }}]"
                                                        >
                                                            <option value="">{{ $filter['placeholder'] }}</option>

                                                            @foreach ($filter['options'] as $option)
                                                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                                            @endforeach
                                                        </x-filament::input.select>
                                                    </x-filament::input.wrapper>
                                                @endif
                                            </x-filament-forms::field-wrapper>
                                        @endforeach
                                    </div>

                                    <div class="fi-ta-filters-actions-ctn">
                                        <x-filament::button x-on:click="applyFilters(); close()" class="fi-data-grid-filters-apply">
                                            {{ __('filament-tables::table.filters.actions.apply.label') }}
                                        </x-filament::button>
                                    </div>
                                </div>
                            </x-filament::dropdown>
                        @endif
                    </div>
                </div>
            @endif

            <div class="fi-data-grid-entry-body">
            <div
                wire:ignore
                class="fi-data-grid-entry-content"
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
                                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronLeft, \Filament\Support\View\SupportIconAlias::PAGINATION_PREVIOUS_BUTTON, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-pagination-item-icon'])) }}
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
                                {{ \Filament\Support\generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronRight, \Filament\Support\View\SupportIconAlias::PAGINATION_NEXT_BUTTON, attributes: (new \Filament\Support\View\ComponentAttributeBag)->class(['fi-pagination-item-icon'])) }}
                            </button>
                        </li>
                    </ol>
                </nav>
            @endif
            </div>

            @include('filament-data-grid::child-tables')
            </div>
        </div>
    </div>

    @if ($isSection)
            </div>
        </section>
    @endif
</div>
