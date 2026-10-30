@php
    $rowErrors = fn (array $row, array $columns, string $location): array => collect($row['cells'])
        ->filter(fn (array $cell): bool => filled($cell['error'] ?? null))
        ->map(fn (array $cell, string $field): array => [
            'location' => $location,
            'column' => collect($columns)->pluck('label', 'field')[$field] ?? $field,
            'message' => $cell['error'],
        ])
        ->values()
        ->all();
    $childErrors = function (array $child, string $parentLocation) use ($grid, $rowErrors): array {
        $hasErrors = collect($child['payload']['rows'])
            ->contains(fn (array $row): bool => collect($row['cells'])->contains(fn (array $cell): bool => filled($cell['error'] ?? null)));

        if (! $hasErrors) {
            return [];
        }

        $component = $grid->getLivewire()->getSchemaComponent($child['config']['schemaComponent']);
        $label = $component !== null && method_exists($component, 'getLabel') ? $component->getLabel() : null;

        return collect($child['payload']['rows'])
            ->flatMap(fn (array $row, int $index): array => $rowErrors(
                $row,
                $child['payload']['columns'],
                $parentLocation.' · '.(filled($label)
                    ? __('filament-data-grid::data-grid.errors.child_row', ['label' => $label, 'row' => $index + 1])
                    : __('filament-data-grid::data-grid.errors.row', ['row' => $index + 1])),
            ))
            ->all();
    };
    $cellErrors = collect($payload['rows'])
        ->flatMap(function (array $row, int $index) use ($payload, $rowErrors, $childErrors): array {
            $location = __('filament-data-grid::data-grid.errors.row', ['row' => $index + 1]);

            return [
                ...$rowErrors($row, $payload['columns'], $location),
                ...(filled($row['child'] ?? null) ? $childErrors($row['child'], $location) : []),
            ];
        });
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
                    {{ $error['location'] }} · {{ $error['column'] }}: {{ $error['message'] }}
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
