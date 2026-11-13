<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Filters\SelectFilter;
use Waguilar\FilamentDataGrid\DataGridCheckboxList;

/**
 * @property-read Schema $form
 */
final class OrderJobsDemo extends Page
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

    /**
     * Orders keyed 1..n and jobs keyed by their own ids from 1, so order and job keys overlap.
     *
     * @return array<int, array{number: string, customer: string, jobs: array<int, array{product: string, worker: string|null}>}>
     */
    public static function orders(int $count = 6): array
    {
        $orders = [];
        $jobId = 0;

        for ($order = 1; $order <= $count; $order++) {
            $jobs = [];

            for ($job = 0; $job <= $order % 3; $job++) {
                $jobId++;
                $jobs[$jobId] = [
                    'product' => self::PRODUCTS[$jobId % count(self::PRODUCTS)],
                    'worker' => $jobId % 4 === 0 ? null : sprintf('Worker %d', $jobId % 3 + 1),
                ];
            }

            $orders[$order] = ['number' => sprintf('ORD-%03d', $order), 'customer' => sprintf('Customer %s', chr(64 + $order)), 'jobs' => $jobs];
        }

        return $orders;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pick jobs by order')->schema([
                    $this->picker()->live(),
                    Text::make(fn (Get $get): string => sprintf('%d selected', count((array) $get('job_ids'))))->key('selected-count'),
                ]),
            ])
            ->statePath('data');
    }

    public function mount(): void
    {
        $this->count = (int) request()->query('count', '6');
        $this->perPage = request()->has('perPage') ? (int) request()->query('perPage') : null;
        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addJobs')
                ->label('Add jobs')
                ->slideOver()
                ->schema([$this->picker()])
                ->action(fn (array $data) => $this->savedState = ['added' => $data['job_ids']]),
        ];
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }

    private function picker(): DataGridCheckboxList
    {
        $count = $this->count;

        return DataGridCheckboxList::make('job_ids')
            ->hiddenLabel()
            ->records(fn (): array => self::orders($count))
            ->selectChildRows()
            ->checkIfRecordIsSelectableUsing(fn (array $record): bool => $record['worker'] !== null)
            ->searchable()
            ->filters([SelectFilter::make('product')->options(array_combine(self::PRODUCTS, self::PRODUCTS))->multiple()->searchable()])
            ->paginated($this->perPage ? [$this->perPage, 50, 100] : false)
            ->table([TableColumn::make('Order'), TableColumn::make('Customer')])
            ->schema([
                TextEntry::make('number'),
                TextEntry::make('customer'),
                RepeatableEntry::make('jobs')
                    ->table([TableColumn::make('Product'), TableColumn::make('Worker')])
                    ->schema([
                        TextEntry::make('product')
                            ->belowContent(fn (TextEntry $component): ?Text => data_get($component->getContainer()->getConstantState(), 'worker') === null ? Text::make('Waiting for a worker')->color('gray') : null),
                        TextEntry::make('worker')->placeholder('—'),
                    ]),
            ]);
    }
}
