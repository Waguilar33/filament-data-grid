@if ($childTables !== [])
    <div x-ref="childTables" class="fi-data-grid-child-table-layer">
        @foreach ($childTables as $rowKey => $childTable)
            <div
                wire:key="{{ $entry->getLivewireKey() }}.child-table.{{ $rowKey }}"
                wire:ignore.self
                data-row-key="{{ $rowKey }}"
                class="fi-data-grid-child-table-host"
                style="display: none"
            >
                {!! $childTable !!}
            </div>
        @endforeach
    </div>
@endif
