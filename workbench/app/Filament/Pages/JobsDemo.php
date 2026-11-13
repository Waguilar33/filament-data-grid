<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Grouping\Group;
use Waguilar\FilamentDataGrid\DataGridEntry;

/**
 * @property-read Schema $form
 */
final class JobsDemo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    /**
     * @return array<string, array{product: string, sale: string|null, order: string, quantity: int, unit: string|null}>
     */
    public static function jobs(int $count = 6): array
    {
        $products = ['Fraser Fir', 'Garland', 'Wreath', 'Tree stand', 'Light string'];
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
        $count = (int) request()->query('count', '6');

        return $schema
            ->components([
                Section::make('Jobs')->schema([
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

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }
}
