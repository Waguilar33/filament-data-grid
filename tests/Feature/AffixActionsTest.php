<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Waguilar\FilamentDataGrid\DataGrid;

function stockWarning(): Action
{
    return Action::make('stockWarning')
        ->icon('heroicon-o-exclamation-triangle')
        ->visible(fn (Get $get): bool => (int) $get('quantity') > 5)
        ->action(fn (Set $set) => $set('quantity', 5));
}

function affixGrid(bool $disabled = false): Closure
{
    return fn (): array => [
        DataGrid::make('lines')->schema([
            TextInput::make('quantity')->numeric()->disabled($disabled)->suffixIcon('heroicon-o-cube')->suffixAction(stockWarning())->prefixAction(Action::make('help')->icon('heroicon-o-question-mark-circle')),
        ]),
    ];
}

it('renders the visible prefix and suffix actions of a field in its cell', function (): void {
    $component = mountLines([['quantity' => 9], ['quantity' => 1]], affixGrid());

    $payload = grid($component)->getGridPayload();
    $keys = rowKeys($component);

    expect($payload['rows'][0]['cells']['quantity']['actions'])->toContain("mountAction('help'")->toContain("mountAction('stockWarning'")->toContain("lines.{$keys[0]}.quantity")
        ->and($payload['rows'][1]['cells']['quantity']['actions'])->toContain("mountAction('help'")->not->toContain("mountAction('stockWarning'")
        ->and($payload['rows'][0]['cells']['quantity']['icon'])->not->toBeNull();
});

it('shows the actions of a locked cell but not its icon', function (): void {
    $component = mountLines([['quantity' => 9]], affixGrid(disabled: true));

    $cell = grid($component)->getGridPayload()['rows'][0]['cells']['quantity'];

    expect($cell['disabled'])->toBeTrue()
        ->and($cell)->not->toHaveKey('icon')
        ->and($cell['actions'])->toContain("mountAction('stockWarning'");
});

it('runs a suffix action for its row', function (): void {
    $component = mountLines([['quantity' => 9], ['quantity' => 7]], affixGrid());

    $keys = rowKeys($component);

    $component->callAction(TestAction::make('stockWarning')->schemaComponent("lines.{$keys[0]}.quantity"));

    expect($component->get("data.lines.{$keys[0]}.quantity"))->toEqual(5)
        ->and($component->get("data.lines.{$keys[1]}.quantity"))->toEqual(7);
});
