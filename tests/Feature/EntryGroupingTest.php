<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Grouping\Group;
use Waguilar\FilamentDataGrid\DataGridEntry;

function groupedEntry(Group|string|null $group): DataGridEntry
{
    return serverEntry(fn (DataGridEntry $entry): DataGridEntry => $entry
        ->state([
            'a' => ['name' => 'Tea', 'sale' => 'S-1'],
            'b' => ['name' => 'Coffee', 'sale' => 'S-2'],
            'c' => ['name' => 'Milk', 'sale' => 'S-1'],
            'd' => ['name' => 'Sugar', 'sale' => null],
        ])
        ->defaultGroup($group)
        ->schema([TextEntry::make('name')]));
}

it('describes each row group with Filament group titles and keeps groups together in first-seen order', function (): void {
    $rows = groupedEntry(Group::make('sale'))->getGridPayload()['rows'];

    expect(array_column($rows, 'key'))->toBe(['a', 'c', 'b', 'd'])
        ->and(array_column($rows, 'group'))->toBe([
            ['key' => 'S-1', 'title' => 'Sale: S-1', 'description' => null],
            ['key' => 'S-1', 'title' => 'Sale: S-1', 'description' => null],
            ['key' => 'S-2', 'title' => 'Sale: S-2', 'description' => null],
            ['key' => null, 'title' => '', 'description' => null],
        ]);
});

it('groups by a key and title computed from each record', function (): void {
    $group = Group::make('sale')
        ->getKeyFromRecordUsing(fn (array $record): string => $record['sale'] ?? 'none')
        ->getTitleFromRecordUsing(fn (array $record): string => $record['sale'] ?? 'No sale <b>')
        ->getDescriptionFromRecordUsing(fn (string $title): string => "About {$title}")
        ->titlePrefixedWithLabel(false);

    $rows = groupedEntry($group)->getGridPayload()['rows'];

    expect($rows[3]['group'])->toBe(['key' => 'none', 'title' => 'No sale &lt;b&gt;', 'description' => 'About No sale &lt;b&gt;']);
});

it('leaves rows ungrouped without a default group', function (): void {
    $rows = groupedEntry(null)->getGridPayload()['rows'];

    expect(array_column($rows, 'key'))->toBe(['a', 'b', 'c', 'd'])
        ->and(array_column($rows, 'group'))->toBe([null, null, null, null]);
});
