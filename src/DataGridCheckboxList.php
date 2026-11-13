<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid;

use Closure;
use Filament\Forms\Components\Concerns\HasNestedRecursiveValidationRules;
use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules as HasNestedRecursiveValidationRulesContract;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Schema;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Traversable;
use Waguilar\FilamentDataGrid\Support\SelectedKeysStateCast;

class DataGridCheckboxList extends Field implements HasEmbeddedView, HasNestedRecursiveValidationRulesContract
{
    use HasNestedRecursiveValidationRules;

    protected DataGridEntry $grid;

    /**
     * @var array<array-key, mixed>|Collection<array-key, mixed>|Closure
     */
    protected array|Collection|Closure $records = [];

    protected ?Closure $checkIfRecordIsSelectableUsing = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $cachedRecords = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
        $this->bindGrid(DataGridEntry::make('records')->hiddenLabel());
        $this->childComponents(fn (DataGridCheckboxList $component): array => [$component->getGrid()]);
    }

    private function bindGrid(DataGridEntry $grid): void
    {
        $this->grid = $grid
            ->id(fn (): ?string => $this->getId())
            ->state(fn (): array => $this->getRecords())
            ->selectRowsWith($this);
    }

    /**
     * @param  array<array-key, mixed>|Collection<array-key, mixed>|Closure  $records
     */
    public function records(array|Collection|Closure $records): static
    {
        $this->records = $records;
        $this->cachedRecords = null;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecords(): array
    {
        if ($this->cachedRecords !== null) {
            return $this->cachedRecords;
        }

        $records = $this->evaluate($this->records);
        $records = match (true) {
            $records instanceof Collection => $records->all(),
            $records instanceof Traversable => iterator_to_array($records),
            is_array($records) => $records,
            default => [],
        };
        $keyed = [];

        foreach ($records as $key => $record) {
            $recordKey = $record instanceof Model ? $record->getKey() : $key;

            if (is_string($recordKey) || is_int($recordKey)) {
                $keyed[(string) $recordKey] = $record;
            }
        }

        return $this->cachedRecords = $keyed;
    }

    public function checkIfRecordIsSelectableUsing(?Closure $callback): static
    {
        $this->checkIfRecordIsSelectableUsing = $callback;

        return $this;
    }

    public function isRecordSelectable(string $key): bool
    {
        $record = $this->getRecords()[$key] ?? null;

        if ($record === null) {
            return false;
        }

        if ($this->checkIfRecordIsSelectableUsing === null) {
            return true;
        }

        return (bool) $this->evaluate(
            $this->checkIfRecordIsSelectableUsing,
            namedInjections: ['record' => $record, 'key' => $key],
            typedInjections: $record instanceof Model ? [Model::class => $record, $record::class => $record] : [],
        );
    }

    /**
     * @return list<string>
     */
    public function getSelectableKeys(): array
    {
        return array_values(array_filter(
            array_map(strval(...), array_keys($this->getRecords())),
            fn (string $key): bool => $this->isRecordSelectable($key),
        ));
    }

    /**
     * @return array<string>
     */
    public function getInValidationRuleValues(): array
    {
        return parent::getInValidationRuleValues() ?? $this->getSelectableKeys();
    }

    public function hasInValidationOnMultipleValues(): bool
    {
        return true;
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [...parent::getDefaultStateCasts(), new SelectedKeysStateCast];
    }

    public function getGrid(): DataGridEntry
    {
        return $this->grid;
    }

    /**
     * @param  array<TableColumn>|Closure|null  $columns
     */
    public function table(array|Closure|null $columns): static
    {
        $this->grid->table($columns);

        return $this;
    }

    /**
     * @param  array<Component|Htmlable|string>|Schema|Closure  $components
     */
    public function schema(array|Schema|Closure $components): static
    {
        $this->grid->schema($components);

        return $this;
    }

    /**
     * @param  bool|list<string>|Closure  $condition
     */
    public function searchable(bool|array|Closure $condition = true): static
    {
        $this->grid->searchable($condition);

        return $this;
    }

    /**
     * @param  list<BaseFilter>|Closure  $filters
     */
    public function filters(array|Closure $filters): static
    {
        $this->grid->filters($filters);

        return $this;
    }

    /**
     * @param  bool|int|list<int|string>|Closure  $condition
     */
    public function paginated(bool|int|array|Closure $condition = true): static
    {
        $this->grid->paginated($condition);

        return $this;
    }

    public function defaultPaginationPageOption(int|string|Closure|null $option): static
    {
        $this->grid->defaultPaginationPageOption($option);

        return $this;
    }

    public function defaultGroup(string|Group|Closure|null $group): static
    {
        $this->grid->defaultGroup($group);

        return $this;
    }

    public function collapsedGroupsByDefault(bool|Closure $condition = true): static
    {
        $this->grid->collapsedGroupsByDefault($condition);

        return $this;
    }

    public function compact(bool|Closure $condition = true): static
    {
        $this->grid->compact($condition);

        return $this;
    }

    public function dense(bool|Closure|null $condition = true): static
    {
        $this->grid->dense($condition);

        return $this;
    }

    public function wrapCells(bool|Closure $condition = true): static
    {
        $this->grid->wrapCells($condition);

        return $this;
    }

    public function getClone(): static
    {
        $clone = parent::getClone();
        $clone->bindGrid($this->grid->getClone());
        $clone->cachedRecords = null;

        return $clone;
    }

    public function toEmbeddedHtml(): string
    {
        $this->cachedRecords = null;
        // Filament gives a child component its container only once its schema lists its components.
        $this->getChildSchema()?->getComponents(withHidden: true);

        return $this->wrapEmbeddedHtml($this->grid->renderSelectionGrid(), labelTag: 'div');
    }
}
