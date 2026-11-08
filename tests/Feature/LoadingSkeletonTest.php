<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Livewire\Livewire;
use Waguilar\FilamentDataGrid\DataGridEntry;
use Workbench\App\Filament\Pages\CashShiftDemo;
use Workbench\App\Filament\Pages\DeliveryTableDemo;

beforeEach(function (): void {
    Filament::setCurrentPanel('demo');
});

it('is off until a grid asks for it', function (): void {
    expect(DataGridEntry::make('lines')->hasLoadingSkeleton())->toBeFalse()
        ->and(DataGridEntry::make('lines')->loadingSkeleton()->hasLoadingSkeleton())->toBeTrue()
        ->and(DataGridEntry::make('lines')->loadingSkeleton(fn (): bool => false)->hasLoadingSkeleton())->toBeFalse();
});

it('turns on for every grid from configureUsing, and a grid can still opt out', function (): void {
    DataGridEntry::configureUsing(
        fn (DataGridEntry $entry): DataGridEntry => $entry->loadingSkeleton(),
        during: fn () => expect(DataGridEntry::make('lines')->hasLoadingSkeleton())->toBeTrue()
            ->and(DataGridEntry::make('lines')->loadingSkeleton(false)->hasLoadingSkeleton())->toBeFalse(),
    );

    expect(DataGridEntry::make('lines')->hasLoadingSkeleton())->toBeFalse();
});

it('waits for Filament\'s Livewire loading delay', function (?string $delay, int $milliseconds): void {
    config(['filament.livewire_loading_delay' => $delay]);

    expect(DataGridEntry::make('lines')->getLoadingSkeletonDelay())->toBe($milliseconds);
})->with([
    ['default', 200],
    ['none', 0],
    ['shortest', 50],
    ['long', 300],
    ['longest', 1000],
    ['unknown', 200],
    [null, 200],
]);

it('follows the loadingSkeleton() of the Filament table it is drawn in', function (): void {
    expect(Livewire::test(CashShiftDemo::class)->html())->toContain('fi-data-grid-skeleton fi-ta-content-loading')
        ->and(Livewire::test(DeliveryTableDemo::class)->html())->not->toContain('fi-data-grid-skeleton');
});
