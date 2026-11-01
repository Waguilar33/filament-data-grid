<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconSize;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use Waguilar\FilamentDataGrid\Actions\BuildEditCellAction;
use Waguilar\FilamentDataGrid\Concerns\HasExpandedContent;
use Waguilar\FilamentDataGrid\Concerns\HasExpandIconSize;
use Waguilar\FilamentDataGrid\Concerns\HasGridHeader;
use Waguilar\FilamentDataGrid\Payload\BuildGridPayload;
use Waguilar\FilamentDataGrid\Rows\EnsureTrailingEmptyRow;
use Waguilar\FilamentDataGrid\Rows\IsRowBlank;
use Waguilar\FilamentDataGrid\Rows\LimitFilledRows;
use Waguilar\FilamentDataGrid\Rows\ResolveRowFilledFields;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

use function Filament\Support\generate_icon_html;

class DataGrid extends Repeater
{
    use HasExpandedContent;
    use HasExpandIconSize;
    use HasGridHeader;

    protected bool|Closure $hasTrailingEmptyRow = false;

    /**
     * @var list<string>|Closure|null
     */
    protected array|Closure|null $trailingEmptyRowFilledBy = null;

    protected int|Closure $frozenColumns = 0;

    protected int|Closure $childTablePadding = 15;

    protected bool|Closure $shouldExpandRows = false;

    /**
     * @var bool|list<string>|Closure
     */
    protected bool|array|Closure $highlightedEditableColumns = false;

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

    public function maxItems(int|Closure|null $count): static
    {
        $this->maxItems = $count;

        $this->rule(
            static fn (DataGrid $component): Closure => app(LimitFilledRows::class)->max($component),
            static fn (DataGrid $component): bool => $component->getMaxItems() !== null,
        );

        return $this;
    }

    public function minItems(int|Closure|null $count): static
    {
        $this->minItems = $count;

        $this->rule(
            static fn (DataGrid $component): Closure => app(LimitFilledRows::class)->min($component),
            static fn (DataGrid $component): bool => $component->getMinItems() !== null,
        );

        return $this;
    }

    public function getItemsCount(): int
    {
        $rows = $this->getRawState();

        if (! is_array($rows)) {
            return 0;
        }

        $lastKey = array_key_last($rows);

        return count($rows) - ($lastKey !== null && $this->isTrailingBlankRow($lastKey) ? 1 : 0);
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

    public function childTablePadding(int|Closure $pixels): static
    {
        $this->childTablePadding = $pixels;

        return $this;
    }

    public function getChildTablePadding(): int
    {
        $pixels = $this->evaluate($this->childTablePadding);

        return is_int($pixels) ? max(0, $pixels) : 15;
    }

    /**
     * @param  bool|list<string>|Closure  $columns
     */
    public function highlightEditableColumns(bool|array|Closure $columns = true): static
    {
        $this->highlightedEditableColumns = $columns;

        return $this;
    }

    /**
     * @return bool|list<string>
     */
    public function getHighlightedEditableColumns(): bool|array
    {
        $columns = $this->evaluate($this->highlightedEditableColumns);

        if (is_array($columns)) {
            return array_values(array_filter($columns, is_string(...)));
        }

        return $columns === true;
    }

    public function expandRowsWhen(bool|Closure $condition = true): static
    {
        $this->shouldExpandRows = $condition;

        return $this;
    }

    /**
     * @param  array<mixed>  $state
     */
    public function shouldExpandRow(string $key, array $state): bool
    {
        [$named, $typed] = $this->rowInjections($key, $state);

        return (bool) $this->evaluate($this->shouldExpandRows, $named, $typed);
    }

    /**
     * @param  array<mixed>  $state
     */
    public function renderExpandedAbove(string $key, Schema $item, array $state): ?string
    {
        return $this->renderExpandedSlot($this->expandedAbove, $item, ...$this->rowInjections($key, $state));
    }

    /**
     * @param  array<mixed>  $state
     */
    public function renderExpandedBelow(string $key, Schema $item, array $state): ?string
    {
        return $this->renderExpandedSlot($this->expandedBelow, $item, ...$this->rowInjections($key, $state));
    }

    /**
     * @param  array<mixed>  $state
     * @return array{array<string, mixed>, array<string, mixed>}
     */
    private function rowInjections(string $key, array $state): array
    {
        $rowComponent = $this->getChildSchema($key)?->getComponents(withHidden: true)[0] ?? null;

        if (! $rowComponent instanceof Component) {
            return [['state' => $state], []];
        }

        $get = $rowComponent->makeGetUtility();

        return [['state' => $state, 'get' => $get], [Get::class => $get]];
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

    /**
     * @return array{statePath: ?string, schemaComponent: ?string, actions: array{editCell: string, reorder: string}, expandIcon: ?string}
     */
    public function getGridConfig(): array
    {
        return [
            'statePath' => $this->getStatePath(),
            'schemaComponent' => $this->getKey(),
            'actions' => [
                'editCell' => $this->getEditCellActionName(),
                'reorder' => $this->getReorderActionName(),
            ],
            'expandIcon' => $this->getExpandIconHtml(),
        ];
    }

    public function toEmbeddedHtml(): string
    {
        $this->ensureTrailingEmptyRow();

        $html = view('filament-data-grid::data-grid', [
            'grid' => $this,
            'payload' => $this->getGridPayload(),
            'alpineSrc' => FilamentAsset::getAlpineComponentSrc('data-grid', FilamentDataGridServiceProvider::PACKAGE),
            'config' => Js::from([
                ...$this->getGridConfig(),
                'translations' => __('filament-data-grid::data-grid'),
                'icons' => [
                    'reorder' => generate_icon_html(Heroicon::Bars2, DataGridIconAlias::CELL_REORDER, size: IconSize::Small)?->toHtml(),
                    'date' => generate_icon_html(Heroicon::Calendar, DataGridIconAlias::CELL_DATE, size: IconSize::Small)?->toHtml(),
                    'edit' => generate_icon_html(Heroicon::PencilSquare, DataGridIconAlias::CELL_EDIT, size: IconSize::Small)?->toHtml(),
                    'headerEdit' => generate_icon_html(Heroicon::PencilSquare, DataGridIconAlias::CELL_EDIT, size: IconSize::ExtraSmall)?->toHtml(),
                    'stepUp' => generate_icon_html(Heroicon::ChevronUp, DataGridIconAlias::CELL_STEP_UP, size: IconSize::ExtraSmall)?->toHtml(),
                    'stepDown' => generate_icon_html(Heroicon::ChevronDown, DataGridIconAlias::CELL_STEP_DOWN, size: IconSize::ExtraSmall)?->toHtml(),
                ],
            ]),
        ])->render();

        return $this->wrapEmbeddedHtml($html, labelTag: 'div');
    }
}
