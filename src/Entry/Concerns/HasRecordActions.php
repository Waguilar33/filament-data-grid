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
     * @return list<Action|ActionGroup>
     */
    public function getRecordActions(): array
    {
        if ($this->cachedRecordActions !== null) {
            return $this->cachedRecordActions;
        }

        $actions = array_filter(
            Arr::wrap($this->evaluate($this->recordActions)),
            fn (mixed $action): bool => $action instanceof Action || $action instanceof ActionGroup,
        );

        return $this->cachedRecordActions = array_values(array_map($this->prepareRecordAction(...), $actions));
    }

    public function hasRecordActions(): bool
    {
        return $this->getRecordActions() !== [];
    }

    /**
     * @return array<Action>
     */
    public function getDefaultActions(): array
    {
        $actions = parent::getDefaultActions();

        foreach ($this->getRecordActions() as $action) {
            array_push($actions, ...($action instanceof ActionGroup ? array_values($action->getFlatActions()) : [$action]));
        }

        return $actions;
    }

    public function renderRecordActions(string $key, Schema $item): ?string
    {
        $rowKey = $this->getRecordActionRowKey($key, $item);
        $record = $this->getRecordActionRowRecord($rowKey, $item);
        $html = '';

        foreach ($this->getRecordActions() as $action) {
            $action = $this->recordActionForRow($action, [self::RECORD_ACTION_ROW_ARGUMENT => $rowKey], $record);

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

    private function prepareRecordAction(Action|ActionGroup $action): Action|ActionGroup
    {
        if ($action instanceof Action) {
            return $this->prepareRecordRowAction($action->defaultSize(Size::Small)->defaultView(Action::LINK_VIEW));
        }

        foreach ($action->getFlatActions() as $flatAction) {
            $this->prepareRecordRowAction($flatAction);
        }

        return $this->prepareActionGroup($action->defaultDropdownPlacement('bottom-end')->defaultDropdownTeleport(true));
    }

    private function prepareRecordRowAction(Action $action): Action
    {
        return $this->prepareAction($action)
            ->record(fn (Action $action): Model|array => $this->resolveRecordActionRecord($this->getRecordActionRowArgument($action)))
            // Filament only unmounts quietly on a resolution error thrown up to boot(), so a missing row must fail here.
            ->bootUsing(fn (Action $action): Model|array => $this->resolveRecordActionRecord($this->getRecordActionRowArgument($action)));
    }

    private function getRecordActionRowArgument(Action $action): mixed
    {
        foreach ([$action, ...array_values($action->getRootGroup()?->getFlatActions() ?? [])] as $candidate) {
            $row = $candidate->getArguments()[self::RECORD_ACTION_ROW_ARGUMENT] ?? null;

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
