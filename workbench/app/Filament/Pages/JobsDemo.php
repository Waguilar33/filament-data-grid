<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Waguilar\FilamentDataGrid\DataGridCheckboxList;
use Waguilar\FilamentDataGrid\DataGridEntry;

/**
 * @property-read Schema $form
 */
final class JobsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    private const PRODUCTS = ['Fraser Fir', 'Garland', 'Wreath', 'Tree stand', 'Light string'];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public int $count = 6;

    public ?int $perPage = null;

    public bool $onlyPicker = false;

    public bool $compact = false;

    public bool $dense = false;

    /**
     * @return array<string, array{product: string, sale: string|null, order: string, quantity: int, unit: string|null}>
     */
    public static function jobs(int $count = 6): array
    {
        $products = self::PRODUCTS;
        $jobs = [];

        for ($index = 1; $index <= $count; $index++) {
            $sale = $index % 3 === 0 ? null : sprintf('S-%03d', intdiv($index + 1, 2));
            $jobs[sprintf('job-%d', $index)] = [
                'product' => $products[$index % count($products)],
                'sale' => $sale,
                'order' => sprintf('P-%03d', $index),
                'quantity' => $index,
                'unit' => $index % 4 === 0 ? null : sprintf('U-%d', $index),
            ];
        }

        return $jobs;
    }

    public static function jobGroup(): Group
    {
        return Group::make('sale')
            ->getKeyFromRecordUsing(fn (array $record): string => self::text($record['sale'] ?? $record['order']))
            ->getTitleFromRecordUsing(fn (array $record): string => isset($record['sale']) ? 'Sale '.self::text($record['sale']) : 'Order '.self::text($record['order']))
            ->titlePrefixedWithLabel(false);
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    public function form(Schema $schema): Schema
    {
        $count = $this->count;

        return $schema
            ->components([
                Section::make('Pick jobs')->schema([
                    DataGridCheckboxList::make('job_ids')
                        ->hiddenLabel()
                        ->records(fn (): array => self::jobs($count))
                        ->checkIfRecordIsSelectableUsing(fn (array $record): bool => $record['unit'] !== null)
                        ->defaultGroup(self::jobGroup())
                        ->searchable()
                        ->filters([SelectFilter::make('product')->options(array_combine(self::PRODUCTS, self::PRODUCTS))])
                        ->compact($this->compact)
                        ->dense($this->dense)
                        ->paginated($this->perPage === null ? false : [$this->perPage, 50, 100])
                        ->live()
                        ->table([TableColumn::make('Product'), TableColumn::make('Order'), TableColumn::make('Quantity')->alignEnd()])
                        ->schema([
                            TextEntry::make('product')
                                ->belowContent(fn (TextEntry $component): ?Text => data_get($component->getContainer()->getConstantState(), 'unit') === null ? Text::make('Waiting for its unit')->color('gray') : null),
                            TextEntry::make('order'),
                            TextEntry::make('quantity')->numeric(),
                        ]),
                    Text::make(fn (Get $get): string => sprintf('%d selected', count((array) $get('job_ids'))))->key('selected-count'),
                ]),
                Section::make('Jobs')->hidden($this->onlyPicker)->schema([
                    DataGridEntry::make('jobs')
                        ->hiddenLabel()
                        ->state(self::jobs($count))
                        ->defaultGroup(self::jobGroup())
                        ->table([TableColumn::make('Product'), TableColumn::make('Order'), TableColumn::make('Quantity')->alignEnd()])
                        ->schema([
                            TextEntry::make('product'),
                            TextEntry::make('order'),
                            TextEntry::make('quantity')->numeric(),
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->count = (int) request()->query('count', '6');
        $this->perPage = request()->has('perPage') ? (int) request()->query('perPage') : null;
        $this->onlyPicker = request()->boolean('onlyPicker');
        $this->compact = request()->boolean('compact');
        $this->dense = request()->boolean('dense');
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addJobs')
                ->label('Add jobs')
                ->slideOver()
                ->modalWidth('5xl')
                ->schema([
                    DataGridCheckboxList::make('job_ids')
                        ->records(fn (): array => self::jobs($this->count))
                        ->checkIfRecordIsSelectableUsing(fn (array $record): bool => $record['unit'] !== null)
                        ->defaultGroup(self::jobGroup())
                        ->searchable()
                        ->filters([SelectFilter::make('product')->options(array_combine(self::PRODUCTS, self::PRODUCTS))])
                        ->paginated([2, 50, 100])
                        ->table([TableColumn::make('Product'), TableColumn::make('Order')])
                        ->schema([TextEntry::make('product'), TextEntry::make('order')]),
                ])
                ->action(fn (array $data) => $this->savedState = ['added' => $data['job_ids']]),
        ];
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }
}
