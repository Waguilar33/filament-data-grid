<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Workbench\App\Catalog\FakeDispatch;

/**
 * @property-read Schema $form
 */
final class AllocationDemo extends Page
{
    protected static ?int $navigationSort = 6;

    protected string $view = 'demo.data-grid';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $savedState = null;

    public static function getNavigationLabel(): string
    {
        return __('demo::demo.allocation.navigation');
    }

    public function getTitle(): string
    {
        return __('demo::demo.allocation.navigation');
    }

    public function mount(): void
    {
        $this->form->fill(['products' => FakeDispatch::products(longNames: true)]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DispatchDemo::productsGrid(detailed: true)
                    ->heading(__('demo::demo.allocation.heading'))
                    ->description(__('demo::demo.allocation.description'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->compact(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->savedState = $this->form->getState();
    }
}
