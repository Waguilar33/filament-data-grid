@php
    use Filament\Actions\Action;
    use Filament\Actions\ActionGroup;
    use Filament\Tables\Actions\HeaderActionsPosition;

    $isReordering = $table->isReordering();
    $headerActions = $isReordering ? [] : array_filter(
        $table->getHeaderActions(),
        fn (Action | ActionGroup $action): bool => $action->isVisible(),
    );
@endphp

<div
    @class([
        'fi-ta-header fi-data-grid-icon-header',
        'fi-ta-header-adaptive-actions-position' => $headerActions && ($table->getHeaderActionsPosition() === HeaderActionsPosition::Adaptive),
    ])
>
    @include('filament-data-grid::section-header', [
        'heading' => $table->getHeading(),
        'description' => $table->getDescription(),
        'icon' => $icon,
        'headingTag' => $table->getHeadingTag(),
    ])

    @if ($headerActions)
        <div class="fi-ta-actions fi-align-start fi-wrapped">
            @foreach ($headerActions as $action)
                {{ $action }}
            @endforeach
        </div>
    @endif
</div>
