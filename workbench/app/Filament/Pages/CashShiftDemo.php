<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Waguilar\FilamentDataGrid\Support\IconHeading;
use Waguilar\FilamentDataGrid\Totals\Total;
use Workbench\App\Catalog\FakeCashShift;
use Workbench\App\Livewire\ShiftMovementsTable;

final class CashShiftDemo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.shift.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.shift.navigation');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
            self::methods('standalone_methods')
                ->heading(__('demo::demo.shift.sections.standalone'))
                ->description(__('demo::demo.shift.descriptions.standalone'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->searchable()
                ->totalsLabelAlignment(Alignment::End)
                ->state(FakeCashShift::methods()),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(IconHeading::make(__('demo::demo.shift.sections.table'), Heroicon::OutlinedBanknotes))
            ->description(__('demo::demo.shift.descriptions.table'))
            ->records(fn (?string $search): array => array_filter(
                FakeCashShift::methods(),
                fn (string $key): bool => blank($search) || str_contains(mb_strtolower(__("demo::demo.shift.methods.{$key}")), mb_strtolower($search)),
                ARRAY_FILTER_USE_KEY,
            ))
            ->columns([TextColumn::make('key')->searchable()])
            ->paginated(false)
            ->content(self::methods('methods')->compact()->toTableContent());
    }

    private static function methods(string $name): DataGridEntry
    {
        return DataGridEntry::make($name)
            ->hiddenLabel()
            ->childTable(ShiftMovementsTable::class, fn (array $record): array => ['method' => $record['key']])
            ->totals([
                Total::make('movements'),
                Total::make('money_in')->money('USD'),
                Total::make('money_out')->money('USD'),
                Total::make('balance')->money('USD'),
            ])
            ->totalsLabel(__('demo::demo.shift.total'))
            ->table([
                TableColumn::make(__('demo::demo.shift.columns.method')),
                TableColumn::make(__('demo::demo.shift.columns.movements'))->alignEnd(),
                TableColumn::make(__('demo::demo.shift.columns.money_in'))->alignEnd(),
                TableColumn::make(__('demo::demo.shift.columns.money_out'))->alignEnd(),
                TableColumn::make(__('demo::demo.shift.columns.balance'))->alignEnd(),
            ])
            ->schema([
                TextEntry::make('key')
                    ->weight('medium')
                    ->formatStateUsing(fn (string $state): string => __("demo::demo.shift.methods.{$state}")),
                TextEntry::make('movements')->numeric(),
                TextEntry::make('money_in')->money('USD'),
                TextEntry::make('money_out')->money('USD'),
                TextEntry::make('balance')->money('USD')->weight('medium'),
            ]);
    }
}
