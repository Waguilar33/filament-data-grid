<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Js;
use Waguilar33\FilamentDataGrid\Payload\BuildGridPayload;
use Waguilar33\FilamentDataGrid\Rows\IsRowBlank;

class DataGrid extends Repeater
{
    protected bool|Closure $hasTrailingEmptyRow = false;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $trailingEmptyRowFilledBy = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reorderable(false);

        $this->afterStateHydrated(static function (DataGrid $component): void {
            $component->hydrateItems();
            $component->ensureTrailingEmptyRow();
        });
    }

    /**
     * @param  list<string>|string|Closure|null  $filledBy
     */
    public function trailingEmptyRow(bool|Closure $condition = true, array|string|Closure|null $filledBy = null): static
    {
        $this->hasTrailingEmptyRow = $condition;
        $this->trailingEmptyRowFilledBy = is_string($filledBy) ? [$filledBy] : $filledBy;

        return $this;
    }

    public function hasTrailingEmptyRow(): bool
    {
        return (bool) $this->evaluate($this->hasTrailingEmptyRow);
    }

    /**
     * @return list<string>
     */
    public function getTrailingEmptyRowFilledBy(): array
    {
        $fields = $this->evaluate($this->trailingEmptyRowFilledBy);

        if (is_array($fields)) {
            return array_values(array_filter($fields, is_string(...)));
        }

        $fields = [];

        foreach ($this->getChildSchema()?->getComponents(withHidden: true) ?? [] as $component) {
            if ($this->marksRowAsFilled($component)) {
                $fields[] = $component->getName();
            }
        }

        return $fields;
    }

    /**
     * @phpstan-assert-if-true Field $component
     */
    private function marksRowAsFilled(mixed $component): bool
    {
        return $component instanceof Field
            && ! $component instanceof Hidden
            && ! $component instanceof Toggle
            && ! $component instanceof Checkbox
            && $component->getDefaultState() === null;
    }

    public function isTrailingBlankRow(string|int $key): bool
    {
        if (! $this->hasTrailingEmptyRow()) {
            return false;
        }

        $rows = $this->getRawState();

        if (! is_array($rows) || array_key_last($rows) !== $key) {
            return false;
        }

        $row = $rows[$key];

        return is_array($row) && app(IsRowBlank::class)->handle($row, $this->getTrailingEmptyRowFilledBy());
    }

    public function ensureTrailingEmptyRow(): void
    {
        if (! $this->hasTrailingEmptyRow()) {
            return;
        }

        $rows = $this->getRawState();
        $rows = is_array($rows) ? $rows : [];
        $lastKey = array_key_last($rows);

        if ($lastKey !== null && $this->isTrailingBlankRow($lastKey)) {
            return;
        }

        $newKey = $this->generateUuid() ?? count($rows);
        $rows[$newKey] = [];

        $this->rawState($rows);
        $this->getChildSchema($newKey)?->fill();
    }

    /**
     * @return array<Schema>
     */
    public function getDefaultChildSchemas(): array
    {
        $schemas = parent::getDefaultChildSchemas();

        if (! $this->hasTrailingEmptyRow()) {
            return $schemas;
        }

        foreach ($schemas as $key => $schema) {
            $schema->hidden(fn (): bool => $this->isTrailingBlankRow($key));
        }

        return $schemas;
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $state
     * @return array<int, array<string, mixed>>
     */
    public function dehydrateItems(?array $state): array
    {
        $state ??= [];
        $lastKey = array_key_last($state);

        if ($lastKey !== null && $this->isTrailingBlankRow($lastKey)) {
            unset($state[$lastKey]);
        }

        return parent::dehydrateItems($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGridPayload(): array
    {
        return app(BuildGridPayload::class)->handle($this);
    }

    public function toEmbeddedHtml(): string
    {
        $this->ensureTrailingEmptyRow();

        $html = view('filament-data-grid::data-grid', [
            'grid' => $this,
            'payload' => $this->getGridPayload(),
            'alpineSrc' => FilamentAsset::getAlpineComponentSrc('data-grid', FilamentDataGridServiceProvider::PACKAGE),
            'styleHref' => FilamentAsset::getStyleHref('data-grid', FilamentDataGridServiceProvider::PACKAGE),
            'config' => Js::from([
                'statePath' => $this->getStatePath(),
                'translations' => __('filament-data-grid::data-grid'),
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html, labelTag: 'div');
    }
}
