<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Livewire\Attributes\Renderless;
use Workbench\App\Catalog\FakeProducts;

final class LibrarySpike extends Page
{
    protected string $view = 'spike.page';

    /**
     * @return list<array{value: string, label: string, code: string}>
     */
    #[Renderless]
    public function searchProducts(string $search): array
    {
        $search = mb_strtolower(trim($search));

        return array_values(collect(FakeProducts::all())
            ->filter(fn (array $product): bool => $search === ''
                || str_contains(mb_strtolower($product['name']), $search)
                || mb_strtolower($product['sku']) === $search)
            ->take(20)
            ->map(fn (array $product): array => [
                'value' => "product:{$product['id']}",
                'label' => $product['name'],
                'code' => $product['sku'],
            ])
            ->all());
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('slideOver')
                ->slideOver()
                ->modalWidth('5xl')
                ->schema([View::make('spike.grids')])
                ->action(fn () => null),
            Action::make('modal')
                ->modalWidth('5xl')
                ->schema([View::make('spike.grids')])
                ->action(fn () => null),
        ];
    }
}
