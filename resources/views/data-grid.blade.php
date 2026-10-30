@php
    $columnLabels = collect($payload['columns'])->pluck('label', 'field');
    $cellErrors = collect($payload['rows'])
        ->flatMap(fn (array $row, int $index): array => collect($row['cells'])
            ->filter(fn (array $cell): bool => filled($cell['error'] ?? null))
            ->map(fn (array $cell, string $field): array => [
                'row' => $index + 1,
                'column' => $columnLabels[$field] ?? $field,
                'message' => $cell['error'],
            ])
            ->values()
            ->all());
    $addAction = $grid->getAction($grid->getAddActionName());
@endphp

<div
    wire:key="{{ $grid->getLivewireKey() }}.data-grid"
    id="{{ $grid->getId() }}"
    role="group"
    aria-labelledby="{{ $grid->getId() }}-label"
    class="fi-data-grid fi-fixed-positioning-context"
>
    <div
        x-load
        x-load-src="{{ $alpineSrc }}"
        x-data="filamentDataGrid({{ $config }})"
        x-on:keydown.capture="rememberEditingState()"
        x-on:keydown.escape="stopEscapeWhileEditing($event)"
    >
        <div x-ref="payload" hidden data-payload="{{ json_encode($payload, JSON_THROW_ON_ERROR) }}"></div>

        <div wire:ignore class="fi-data-grid-viewport">
            <div
                x-ref="grid"
                class="fi-data-grid-canvas"
                style="min-height: calc(2.75rem + 1px + {{ count($payload['rows']) * 44 }}px)"
            ></div>
        </div>
    </div>

    @if ($cellErrors->isNotEmpty())
        <ul class="fi-data-grid-errors" role="alert">
            @foreach ($cellErrors as $error)
                <li class="fi-fo-field-wrp-error-message">
                    {{ __('filament-data-grid::data-grid.errors.row', ['row' => $error['row']]) }} · {{ $error['column'] }}: {{ $error['message'] }}
                </li>
            @endforeach
        </ul>
    @endif

    @if ($grid->isAddable() && ! $grid->hasTrailingEmptyRow() && $addAction->isVisible())
        <div class="fi-data-grid-add">
            {{ $addAction }}
        </div>
    @endif
</div>
