<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry\Concerns;

use Closure;
use Filament\Schemas\Schema;
use Filament\Tables\Grouping\Group;
use Illuminate\Contracts\Support\Htmlable;

trait HasGrouping
{
    protected string|Group|Closure|null $defaultGroup = null;

    protected bool|Closure $areGroupsCollapsedByDefault = false;

    public function defaultGroup(string|Group|Closure|null $group): static
    {
        $this->defaultGroup = $group;

        return $this;
    }

    public function collapsedGroupsByDefault(bool|Closure $condition = true): static
    {
        $this->areGroupsCollapsedByDefault = $condition;

        return $this;
    }

    public function areGroupsCollapsedByDefault(): bool
    {
        return (bool) $this->evaluate($this->areGroupsCollapsedByDefault);
    }

    /**
     * @return array{collapsible: bool, collapsedByDefault: bool}|null
     */
    public function describeGrouping(): ?array
    {
        $collapsible = $this->getGrouping()?->isCollapsible();

        return $collapsible === null ? null : ['collapsible' => $collapsible, 'collapsedByDefault' => $collapsible && $this->areGroupsCollapsedByDefault()];
    }

    public function getGrouping(): ?Group
    {
        $group = $this->evaluate($this->defaultGroup);

        return match (true) {
            $group instanceof Group => $group,
            is_string($group) && $group !== '' => Group::make($group),
            default => null,
        };
    }

    /**
     * @return array{key: string|null, title: string, description: string|null}|null
     */
    public function describeRowGroup(string $key, Schema $item): ?array
    {
        $group = $this->getGrouping();

        if ($group === null) {
            return null;
        }

        $record = $this->getRecordActionRowRecord($this->getRecordActionRowKey($key, $item), $item);
        $groupKey = $group->getStringKey($record);
        $rawTitle = $group->getTitle($record, $groupKey);
        $title = $this->groupHtml($rawTitle);

        if ($group->isTitlePrefixedWithLabel() && $title !== '') {
            $title = e($group->getLabel()).': '.$title;
        }

        $description = $this->groupHtml($group->getDescription($record, $rawTitle));

        return ['key' => $groupKey, 'title' => $title, 'description' => $description === '' ? null : $description];
    }

    private function groupHtml(string|Htmlable|null $text): string
    {
        return $text instanceof Htmlable ? $text->toHtml() : e((string) $text);
    }
}
