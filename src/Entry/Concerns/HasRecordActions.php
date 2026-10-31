<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Schemas\Schema;
use Filament\Support\ArrayRecord;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Waguilar\FilamentDataGrid\Exceptions\DuplicateRecordActionName;

trait HasRecordActions
{
    /**
     * @var array<Action|ActionGroup>|Closure
     */
    protected array|Closure $recordActions = [];

    /**
     * @var list<Action|ActionGroup>|null
     */
    protected ?array $cachedRecordActions = null;

    /**
     * @var array<Action|ActionGroup>|Closure
     */
    protected array|Closure $childRecordActions = [];

    /**
     * @var list<Action|ActionGroup>|null
     */
    protected ?array $cachedChildRecordActions = null;

    /**
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function recordActions(array|Closure $actions): static
    {
        $this->recordActions = $actions;
        $this->cachedRecordActions = null;
        $this->cachedActions = null;

        return $this;
    }

    /**
     * @param  array<Action|ActionGroup>|Closure  $actions
     */
    public function childRecordActions(array|Closure $actions): static
    {
        $this->childRecordActions = $actions;
        $this->cachedChildRecordActions = null;
        $this->cachedActions = null;

        return $this;
    }

    /**
     * @return list<Action|ActionGroup>
     */
    public function getRecordActions(): array
    {
        return $this->cachedRecordActions ??= $this->prepareRecordActions($this->recordActions, $this->resolveRecordActionRecordFor(...));
    }

    /**
     * @return list<Action|ActionGroup>
     */
    public function getChildRecordActions(): array
    {
        return $this->cachedChildRecordActions ??= $this->prepareRecordActions($this->childRecordActions, $this->resolveChildRecordActionRecordFor(...));
    }

    public function hasRecordActions(): bool
    {
        return $this->getRecordActions() !== [];
    }

    public function hasChildRecordActions(): bool
    {
        return $this->getChildRecordActions() !== [];
    }

    /**
     * @return array<Action>
     */
    public function getDefaultActions(): array
    {
        $actions = parent::getDefaultActions();
        $parentNames = [];

        foreach ($this->getRecordActions() as $action) {
            foreach ($this->flatten($action) as $flatAction) {
                $parentNames[(string) $flatAction->getName()] = true;
                $actions[] = $flatAction;
            }
        }

        foreach ($this->getChildRecordActions() as $action) {
            foreach ($this->flatten($action) as $flatAction) {
                if (isset($parentNames[(string) $flatAction->getName()])) {
                    throw DuplicateRecordActionName::make((string) $this->getName(), (string) $flatAction->getName());
                }

                $actions[] = $flatAction;
            }
        }

        return $actions;
    }

    public function renderRecordActions(string $key, Schema $item): ?string
    {
        $rowKey = $this->getRecordActionRowKey($key, $item);

        return $this->renderActionsFor($this->getRecordActions(), [self::RECORD_ACTION_ROW_ARGUMENT => $rowKey], $this->getRecordActionRowRecord($rowKey, $item));
    }

    public function renderChildRecordActions(string $parentKey, Schema $parent, string $key, Schema $child): ?string
    {
        $childKey = $this->getRecordActionRowKey($key, $child);

        return $this->renderActionsFor(
            $this->getChildRecordActions(),
            [self::RECORD_ACTION_ROW_ARGUMENT => $this->getRecordActionRowKey($parentKey, $parent), self::CHILD_RECORD_ACTION_ARGUMENT => $childKey],
            $this->getRecordActionRowRecord($childKey, $child),
        );
    }

    /**
     * @param  list<Action|ActionGroup>  $actions
     * @param  array<string, string>  $arguments
     * @param  Model|array<string, mixed>  $record
     */
    private function renderActionsFor(array $actions, array $arguments, Model|array $record): ?string
    {
        $html = '';

        foreach ($actions as $action) {
            $action = $this->recordActionForRow($action, $arguments, $record);

            if ($action->isHidden()) {
                continue;
            }

            $html .= $action->toHtml();
        }

        return $html === '' ? null : '<div class="fi-ta-actions fi-data-grid-record-actions">'.$html.'</div>';
    }

    /**
     * @return Model|array<string, mixed>
     */
    public function resolveRecordActionRecord(mixed $rowKey): Model|array
    {
        if (is_string($rowKey) || is_int($rowKey)) {
            foreach ($this->getItems() as $key => $item) {
                if ($this->getRecordActionRowKey((string) $key, $item) === (string) $rowKey) {
                    return $this->getRecordActionRowRecord((string) $rowKey, $item);
                }
            }
        }

        throw new ActionNotResolvableException(sprintf('Row [%s] is no longer in DataGridEntry [%s].', is_scalar($rowKey) ? $rowKey : '', $this->getName()));
    }

    /**
     * @return Model|array<string, mixed>
     */
    public function resolveChildRecordActionRecord(mixed $rowKey, mixed $childKey): Model|array
    {
        if ((is_string($rowKey) || is_int($rowKey)) && (is_string($childKey) || is_int($childKey))) {
            foreach ($this->getItems() as $key => $item) {
                if ($this->getRecordActionRowKey((string) $key, $item) !== (string) $rowKey) {
                    continue;
                }

                foreach ($this->getChildRowsEntry($item)?->getItems() ?? [] as $itemKey => $child) {
                    if ($this->getRecordActionRowKey((string) $itemKey, $child) === (string) $childKey) {
                        return $this->getRecordActionRowRecord((string) $childKey, $child);
                    }
                }
            }
        }

        throw new ActionNotResolvableException(sprintf(
            'Child row [%s] of row [%s] is no longer in DataGridEntry [%s].',
            is_scalar($childKey) ? $childKey : '',
            is_scalar($rowKey) ? $rowKey : '',
            $this->getName(),
        ));
    }

    /**
     * @return Model|array<string, mixed>
     */
    private function resolveRecordActionRecordFor(Action $action): Model|array
    {
        return $this->resolveRecordActionRecord($this->getRecordActionArgument($action, self::RECORD_ACTION_ROW_ARGUMENT));
    }

    /**
     * @return Model|array<string, mixed>
     */
    private function resolveChildRecordActionRecordFor(Action $action): Model|array
    {
        return $this->resolveChildRecordActionRecord(
            $this->getRecordActionArgument($action, self::RECORD_ACTION_ROW_ARGUMENT),
            $this->getRecordActionArgument($action, self::CHILD_RECORD_ACTION_ARGUMENT),
        );
    }

    /**
     * @param  array<Action|ActionGroup>|Closure  $actions
     * @param  Closure(Action): (Model|array<string, mixed>)  $resolveRecord
     * @return list<Action|ActionGroup>
     */
    private function prepareRecordActions(array|Closure $actions, Closure $resolveRecord): array
    {
        $actions = array_filter(
            Arr::wrap($this->evaluate($actions)),
            fn (mixed $action): bool => $action instanceof Action || $action instanceof ActionGroup,
        );

        return array_values(array_map(fn (Action|ActionGroup $action): Action|ActionGroup => $this->prepareRecordAction($action, $resolveRecord), $actions));
    }

    /**
     * @return list<Action>
     */
    private function flatten(Action|ActionGroup $action): array
    {
        return $action instanceof ActionGroup ? array_values($action->getFlatActions()) : [$action];
    }

    /**
     * @param  array<string, string>  $arguments
     * @param  Model|array<string, mixed>  $record
     */
    private function recordActionForRow(Action|ActionGroup $action, array $arguments, Model|array $record): Action|ActionGroup
    {
        if ($action instanceof Action) {
            return $action($arguments)->record($record);
        }

        $group = $action->getClone();

        return $group
            ->actions(array_map(fn (Action|ActionGroup $child): Action|ActionGroup => $this->recordActionForRow($child, $arguments, $record), $group->getActions()))
            ->record($record);
    }

    /**
     * @param  Closure(Action): (Model|array<string, mixed>)  $resolveRecord
     */
    private function prepareRecordAction(Action|ActionGroup $action, Closure $resolveRecord): Action|ActionGroup
    {
        if ($action instanceof Action) {
            return $this->prepareRecordRowAction($action->defaultSize(Size::Small)->defaultView(Action::LINK_VIEW), $resolveRecord);
        }

        foreach ($action->getFlatActions() as $flatAction) {
            $this->prepareRecordRowAction($flatAction, $resolveRecord);
        }

        return $this->prepareActionGroup($action->defaultDropdownPlacement('bottom-end')->defaultDropdownTeleport(true));
    }

    /**
     * @param  Closure(Action): (Model|array<string, mixed>)  $resolveRecord
     */
    private function prepareRecordRowAction(Action $action, Closure $resolveRecord): Action
    {
        return $this->prepareAction($action)
            ->record($resolveRecord)
            // Filament only unmounts quietly on a resolution error thrown up to boot(), so a missing row must fail here.
            ->bootUsing($resolveRecord);
    }

    private function getRecordActionArgument(Action $action, string $argument): mixed
    {
        foreach ([$action, ...array_values($action->getRootGroup()?->getFlatActions() ?? [])] as $candidate) {
            $row = $candidate->getArguments()[$argument] ?? null;

            if ($row !== null) {
                return $row;
            }
        }

        return null;
    }

    private function getRecordActionRowKey(string $key, Schema $item): string
    {
        $record = $item->getRecord(withParentComponentRecord: false);

        $recordKey = $record instanceof Model ? $record->getKey() : null;

        return is_string($recordKey) || is_int($recordKey) ? (string) $recordKey : $key;
    }

    /**
     * @return Model|array<string, mixed>
     */
    private function getRecordActionRowRecord(string $rowKey, Schema $item): Model|array
    {
        $record = $item->getRecord(withParentComponentRecord: false);

        if ($record instanceof Model) {
            return $record;
        }

        $state = $item->getConstantState();
        $state = is_object($state) ? get_object_vars($state) : (array) $state;

        return [...array_combine(array_map(strval(...), array_keys($state)), $state), ArrayRecord::getKeyName() => $rowKey];
    }
}
