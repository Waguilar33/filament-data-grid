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
        <div wire:ignore x-ref="content" class="fi-data-grid-entry-content">
            @if ($entry->hasLoadingSkeleton() && filled($payload['rows']))
                @include('filament-data-grid::loading-skeleton')
            @endif

            <div
                x-ref="grid"
                class="fi-data-grid-canvas"
                style="min-height: {{ $entry->getReservedHeight($payload) }}px"
            ></div>
        </div>

        @include('filament-data-grid::child-tables')
    </div>
</div>
