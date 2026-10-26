<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use Waguilar\FilamentDataGrid\Actions\BuildEditCellAction;
use Waguilar\FilamentDataGrid\Payload\BuildGridPayload;
use Waguilar\FilamentDataGrid\Rows\EnsureTrailingEmptyRow;
use Waguilar\FilamentDataGrid\Rows\IsRowBlank;
use Waguilar\FilamentDataGrid\Rows\ResolveRowFilledFields;

use function Filament\Support\generate_icon_html;

class DataGrid extends Repeater
{
    protected bool|Closure $hasTrailingEmptyRow = false;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $trailingEmptyRowFilledBy = null;

    protected int|Closure $frozenColumns = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            fn (DataGrid $component): Action => $component->getEditCellAction(),
        ]);

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

    public function frozenColumns(int|Closure $count = 1): static
    {
        $this->frozenColumns = $count;

        return $this;
    }

    public function getFrozenColumns(): int
    {
        $count = $this->evaluate($this->frozenColumns);

        return is_int($count) ? max(0, $count) : 0;
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

        return app(ResolveRowFilledFields::class)->handle($this->getChildSchema());
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
        app(EnsureTrailingEmptyRow::class)->handle($this);
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

    public function getEditCellAction(): Action
    {
        return app(BuildEditCellAction::class)->handle($this->getEditCellActionName());
    }

    public function getEditCellActionName(): string
    {
        return 'editCell';
    }

    public function getRowField(string $key, string $field): ?Field
    {
        foreach ($this->getChildSchema($key)?->getComponents(withHidden: true) ?? [] as $component) {
            if ($component instanceof Field && $component->getName() === $field) {
                return $component;
            }
        }

        return null;
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
                'schemaComponent' => $this->getKey(),
                'actions' => [
                    'editCell' => $this->getEditCellActionName(),
                    'reorder' => $this->getReorderActionName(),
                ],
                'translations' => __('filament-data-grid::data-grid'),
                'icons' => [
                    'reorder' => generate_icon_html(Heroicon::Bars2)?->toHtml(),
                    'date' => generate_icon_html(Heroicon::Calendar)?->toHtml(),
                    'edit' => generate_icon_html(Heroicon::PencilSquare)?->toHtml(),
                ],
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html, labelTag: 'div');
    }
}
