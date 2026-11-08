@php
    $rows = array_slice($payload['rows'], 0, is_int($payload['recordsPerPage']) ? $payload['recordsPerPage'] : null);
    $rows = filled($payload['totals']) && filled($rows) ? [...$rows, $rows[0]] : $rows;
    $columns = array_map(fn (array $column): array => ['field' => $column['field'], 'label' => $column['headerHidden'] ? '' : $column['label'], 'alignment' => $column['alignment'] ?? 'start'], $payload['columns']);
    $isExpandable = $payload['childTable'] || collect($payload['rows'])->contains(fn (array $row): bool => filled($row['children']) || filled($row['above']) || filled($row['below']));

    if (collect($payload['rows'])->contains(fn (array $row): bool => filled($row['actions'] ?? null))) {
        $columns[] = ['field' => null, 'label' => '', 'bar' => __('filament-data-grid::data-grid.columns.actions'), 'alignment' => 'end'];
    }
@endphp

{{-- The rows hold their real content, hidden by Filament's skeleton styles, so every bar and row has its drawn size. --}}
<div x-ignore inert aria-hidden="true" class="fi-data-grid-skeleton fi-ta-content-loading">
    <table class="fi-ta-table">
        <thead>
            <tr>
                @if ($isExpandable)
                    <th class="fi-ta-header-cell fi-data-grid-skeleton-toggle"></th>
                @endif

                @foreach ($columns as $column)
                    <th class="fi-ta-header-cell fi-align-{{ $column['alignment'] }}">{{ $column['label'] }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody class="fi-ta-records">
            @foreach ($rows as $row)
                <tr class="fi-ta-row">
                    @if ($isExpandable)
                        <td class="fi-ta-cell fi-data-grid-skeleton-toggle"></td>
                    @endif

                    @foreach ($columns as $column)
                        <td class="fi-ta-cell">
                            <div class="fi-ta-col">
                                <div class="fi-ta-text fi-align-{{ $column['alignment'] }}">
                                    @if ($column['field'] === null)
                                        <div class="fi-ta-text-item fi-size-sm">{{ $column['bar'] }}</div>
                                    @else
                                        {!! $row['cells'][$column['field']]['html'] ?? '' !!}
                                    @endif
                                </div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
