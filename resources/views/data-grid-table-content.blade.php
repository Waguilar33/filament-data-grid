<div
    x-load
    x-load-src="{{ $alpineSrc }}"
    x-data="filamentDataGridEntry({{ $config }})"
    wire:key="{{ $entry->getLivewireKey() }}.data-grid-entry"
    id="{{ $entry->getId() }}"
    @class(['fi-data-grid fi-data-grid-entry-grid fi-data-grid-in-table', 'fi-data-grid-compact' => $entry->isCompact()])
>
    <div x-ref="payload" hidden data-payload="{{ json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) }}"></div>

    <div class="fi-data-grid-entry-body">
        <div wire:ignore class="fi-data-grid-entry-content">
            <div
                x-ref="grid"
                class="fi-data-grid-canvas"
                style="min-height: calc(2.75rem + 1px + {{ count($payload['rows']) * 44 }}px)"
            ></div>
        </div>

        @include('filament-data-grid::child-tables')
    </div>
</div>
