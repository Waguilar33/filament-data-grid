<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Waguilar\FilamentDataGrid\DataGridEntry;

final class ActionsInsetDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    public bool $compact = false;

    public bool $dense = false;

    public function mount(): void
    {
        $this->compact = request()->boolean('compact');
        $this->dense = request()->boolean('dense');
    }

    public function content(Schema $schema): Schema
    {
        $places = [
            ['place' => 'Dock A', 'status' => 'Ready', 'loads' => [['load' => 'L-1', 'weight' => '120 kg'], ['load' => 'L-2', 'weight' => '80 kg']]],
            ['place' => 'Dock B', 'status' => 'Waiting', 'loads' => [['load' => 'L-3', 'weight' => '45 kg']]],
        ];

        return $schema->components([
            DataGridEntry::make('places')
                ->hiddenLabel()
                ->compact($this->compact)
                ->dense($this->dense)
                ->state($places)
                ->table([TableColumn::make('Place'), TableColumn::make('Status')])
                ->schema([TextEntry::make('place'), TextEntry::make('status')])
                ->recordActions([Action::make('dispatch')->label('Dispatch')->icon(Heroicon::OutlinedTruck)->action(fn (): null => null)]),
            DataGridEntry::make('loads')
                ->hiddenLabel()
                ->compact($this->compact)
                ->dense($this->dense)
                ->state($places)
                ->table([TableColumn::make('Place'), TableColumn::make('Status')])
                ->schema([
                    TextEntry::make('place'),
                    TextEntry::make('status'),
                    RepeatableEntry::make('loads')
                        ->table([TableColumn::make('Load'), TableColumn::make('Weight')])
                        ->schema([TextEntry::make('load'), TextEntry::make('weight')]),
                ])
                ->childRecordActions([Action::make('unload')->label('Unload')->icon(Heroicon::OutlinedArrowDownTray)->action(fn (): null => null)]),
        ]);
    }
}
