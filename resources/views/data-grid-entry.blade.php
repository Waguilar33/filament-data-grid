@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\ComponentAttributeBag;
    use Filament\Tables\View\TablesIconAlias;
    use Illuminate\Support\HtmlString;

    $heading = $entry->getHeading();
    $description = $entry->getDescription();
    $icon = $entry->getIcon();
    $hasHeader = filled($heading) || filled($description);
    $hasToolbar = ($entry->isSearchable() || filled($payload['filters'])) && ! $payload['isEmpty'];
    $isSection = $hasHeader && $entry->isCompact();
    $showsEmptyState = $payload['hasEmptyState'] && $payload['isEmpty'];
@endphp

<div
    x-load
    x-load-src="{{ $alpineSrc }}"
    x-data="filamentDataGridEntry({{ $config }}@if ($selectionState !== null), {{ $selectionState }}@endif)"
    wire:key="{{ $entry->getLivewireKey() }}.data-grid-entry"
    id="{{ $entry->getId() }}"
    @class(['fi-data-grid fi-data-grid-entry-grid', 'fi-data-grid-compact' => $entry->isCompact(), 'fi-data-grid-dense' => $entry->hasDenseRows(), 'fi-data-grid-heading-has-icon' => $hasHeader && filled($icon)])
>
    <div x-ref="payload" hidden data-payload="{{ json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) }}"></div>

    @if ($isSection)
        <section class="fi-section fi-compact fi-section-has-header fi-data-grid-section">
            @include('filament-data-grid::section-header', ['heading' => $heading, 'description' => $description, 'icon' => $icon])

            <div class="fi-section-content-ctn">
    @endif

    <div @class(['fi-ta-ctn', 'fi-ta-ctn-with-header' => ($hasHeader && ! $isSection) || $hasToolbar])>
        <div class="fi-ta-main">
            @if ($hasHeader && ! $isSection)
                <div class="fi-ta-header fi-data-grid-icon-header">
                    @include('filament-data-grid::section-header', ['heading' => $heading, 'description' => $description, 'icon' => $icon])
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
                                                :label-tag="$filter['type'] === 'select' && ($filter['multiple'] || $filter['searchable']) ? 'div' : 'label'"
                                            >
                                                @if ($filter['type'] === 'checkbox')
                                                    <label class="fi-data-grid-filter-checkbox">
                                                        <x-filament::input.checkbox
                                                            :id="$entry->getId() . '-filter-' . $filter['name']"
                                                            x-model="draftFilters[{{ \Illuminate\Support\Js::from($filter['name']) }}]"
                                                        />
                                                    </label>
                                                @elseif ($filter['type'] === 'select' && ($filter['multiple'] || $filter['searchable']))
                                                    <x-filament::input.wrapper>
                                                        <div
                                                            x-load
                                                            x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('select', 'filament/forms') }}"
                                                            x-data="selectFormComponent({
                                                                canSelectPlaceholder: true,
                                                                clearButtonLabel: @js(__('filament-forms::components.select.actions.clear.label')),
                                                                errorMessage: @js(__('filament-forms::components.select.error_message')),
                                                                hasDynamicOptions: false,
                                                                hasDynamicSearchResults: false,
                                                                id: @js($entry->getId() . '-filter-' . $filter['name']),
                                                                initialOptionLabel: null,
                                                                initialOptionLabels: [],
                                                                initialState: draftFilters[@js($filter['name'])],
                                                                isMultiple: @js($filter['multiple']),
                                                                isSearchable: @js($filter['searchable']),
                                                                loadingMessage: @js(__('filament-forms::components.select.loading_message')),
                                                                noOptionsMessage: @js(__('filament-forms::components.select.no_options_message')),
                                                                noSearchResultsMessage: @js(__('filament-forms::components.select.no_search_results_message')),
                                                                options: @js($filter['options']),
                                                                optionsLimit: @js($filter['optionsLimit']),
                                                                placeholder: @js($filter['placeholder']),
                                                                removeButtonLabel: @js(__('filament-forms::components.select.actions.remove_option.label')),
                                                                searchingMessage: @js(__('filament-forms::components.select.searching_message')),
                                                                searchLabel: @js(__('filament-forms::components.select.search_label')),
                                                                searchPrompt: @js(__('filament-forms::components.select.search_prompt')),
                                                                state: draftFilters[@js($filter['name'])],
                                                            })"
                                                            x-init="
                                                                $watch('state', (value) => (draftFilters[@js($filter['name'])] = value))
                                                                $watch(() => draftFilters[@js($filter['name'])], (value) => JSON.stringify(value) !== JSON.stringify(state) && (state = value))
                                                            "
                                                            wire:ignore
                                                            class="fi-select-input fi-data-grid-filter-select"
                                                        >
                                                            <div x-ref="select"></div>
                                                        </div>
                                                    </x-filament::input.wrapper>
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

            @if ($payload['selection'] !== null)
                <div x-cloak x-show="selectedCount > 0" class="fi-ta-selection-indicator fi-data-grid-selection-indicator">
                    <div>
                        <span x-text="window.pluralize(@js(__('filament-tables::table.selection_indicator.selected_count')), selectedCount, { count: new Intl.NumberFormat(@js(str_replace('_', '-', app()->getLocale()))).format(selectedCount) })"></span>
                    </div>

                    @if (! $payload['selection']['disabled'])
                        <div>
                            <div class="fi-ta-selection-indicator-actions-ctn">
                                <x-filament::link
                                    color="primary"
                                    tag="button"
                                    x-on:click="selectAllMatching()"
                                    x-show="unselectedMatchingCount > 0"
                                    class="fi-data-grid-select-all-matching"
                                >
                                    <span x-text="window.pluralize(@js(__('filament-tables::table.selection_indicator.actions.select_all.label')), selectableMatchingCount, { count: new Intl.NumberFormat(@js(str_replace('_', '-', app()->getLocale()))).format(selectableMatchingCount) })"></span>
                                </x-filament::link>

                                <x-filament::link color="danger" tag="button" x-on:click="deselectAll()" class="fi-data-grid-deselect-all">
                                    {{ __('filament-tables::table.selection_indicator.actions.deselect_all.label') }}
                                </x-filament::link>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="fi-data-grid-entry-body">
            <div
                wire:ignore
                x-ref="content"
                class="fi-data-grid-entry-content"
                x-bind:class="{ 'fi-data-grid-loading': isLoading && ! hasLoadingSkeleton }"
                x-bind:aria-busy="isLoading"
            >
                @if ($entry->hasLoadingSkeleton() && ! $showsEmptyState && filled($payload['rows']))
                    @include('filament-data-grid::loading-skeleton')
                @endif

                <div
                    x-ref="grid"
                    x-show="! showsEmptyState"
                    class="fi-data-grid-canvas"
                    style="min-height: {{ $entry->getReservedHeight($payload) }}px{{ $showsEmptyState ? '; display: none' : '' }}"
                ></div>

                <p x-cloak x-show="isMounted && total === 0 && ! showsEmptyState" class="fi-data-grid-empty">
                    {{ __('filament-data-grid::data-grid.entry.empty') }}
                </p>

            @if ($entry->isPaginated())
                @php
                    $pageOptions = $entry->getPaginationPageOptions();
                    $allRecordsLabel = __('filament::components/pagination.fields.records_per_page.options.all');
                @endphp

                <nav
                    x-cloak
                    x-show="isMounted && total > 0 && ! fitsOnSmallestPage"
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
                        <div x-show="shownPageOptions.length > 1" class="fi-pagination-records-per-page-select-ctn">
                            @foreach ([true, false] as $isCompact)
                                <label @class(['fi-pagination-records-per-page-select', 'fi-compact' => $isCompact])>
                                    <x-filament::input.wrapper
                                        :prefix="$isCompact ? null : __('filament::components/pagination.fields.records_per_page.label')"
                                    >
                                        <x-filament::input.select x-on:change="changeRecordsPerPage($event.target.value)">
                                            <template x-for="option in shownPageOptions" x-bind:key="option">
                                                <option
                                                    x-bind:value="option"
                                                    x-bind:selected="String(recordsPerPage) === String(option)"
                                                    x-text="option === @js(\Waguilar\FilamentDataGrid\DataGridEntry::ALL_RECORDS) ? @js($allRecordsLabel) : option"
                                                ></option>
                                            </template>
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

            @if ($showsEmptyState)
                <div class="fi-ta-empty-state fi-data-grid-empty-state" role="status">
                    <div class="fi-ta-empty-state-content">
                        <div class="fi-ta-empty-state-icon-bg">
                            {{ \Filament\Support\generate_icon_html($entry->getEmptyStateIcon(), size: \Filament\Support\Enums\IconSize::Large) }}
                        </div>

                        <{{ filled($heading) ? 'h4' : 'h3' }} class="fi-ta-empty-state-heading">
                            {{ $entry->getEmptyStateHeading() }}
                        </{{ filled($heading) ? 'h4' : 'h3' }}>

                        @if (filled($emptyStateDescription = $entry->getEmptyStateDescription()))
                            <p class="fi-ta-empty-state-description">
                                {{ $emptyStateDescription }}
                            </p>
                        @endif

                        @if ($emptyStateActions = array_filter($entry->getEmptyStateActions(), fn (\Filament\Actions\Action | \Filament\Actions\ActionGroup $action): bool => $action->isVisible()))
                            <div class="fi-ta-actions fi-align-center fi-wrapped">
                                @foreach ($emptyStateActions as $action)
                                    {{ $action }}
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @include('filament-data-grid::child-tables')
            </div>
        </div>
    </div>

    @if ($isSection)
            </div>
        </section>
    @endif

    @if ($entry->rendersActionModals($this))
        {{-- Pages draw the modals in their layout; a relation manager's content() has nowhere else to draw them. --}}
        <x-filament-actions::modals />
    @endif
</div>
