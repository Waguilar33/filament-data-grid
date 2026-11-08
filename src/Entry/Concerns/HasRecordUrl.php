<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry\Concerns;

use Closure;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Waguilar\FilamentDataGrid\Entry\RenderRecordLink;

trait HasRecordUrl
{
    protected string|Closure|null $recordUrl = null;

    protected bool|Closure $shouldOpenRecordUrlInNewTab = false;

    public function recordUrl(string|Closure|null $url, bool|Closure|null $shouldOpenInNewTab = null): static
    {
        if ($shouldOpenInNewTab !== null) {
            $this->openRecordUrlInNewTab($shouldOpenInNewTab);
        }

        $this->recordUrl = $url;

        return $this;
    }

    public function openRecordUrlInNewTab(bool|Closure $condition = true): static
    {
        $this->shouldOpenRecordUrlInNewTab = $condition;

        return $this;
    }

    /**
     * @param  Model|array<string, mixed>  $record
     */
    public function getRecordUrl(Model|array $record): ?string
    {
        $url = $this->evaluate($this->recordUrl, ...$this->recordUrlInjections($record));

        return is_string($url) ? $url : null;
    }

    /**
     * @param  Model|array<string, mixed>  $record
     */
    public function shouldOpenRecordUrlInNewTab(Model|array $record): bool
    {
        return (bool) $this->evaluate($this->shouldOpenRecordUrlInNewTab, ...$this->recordUrlInjections($record));
    }

    public function renderRecordLink(string $key, Schema $item): ?string
    {
        if ($this->recordUrl === null) {
            return null;
        }

        $record = $this->getRecordActionRowRecord($this->getRecordActionRowKey($key, $item), $item);

        return (new RenderRecordLink)->handle($this->getRecordUrl($record), $this->shouldOpenRecordUrlInNewTab($record));
    }

    /**
     * @param  Model|array<string, mixed>  $record
     * @return array{namedInjections: array<string, mixed>, typedInjections: array<class-string, Model>}
     */
    private function recordUrlInjections(Model|array $record): array
    {
        return [
            'namedInjections' => ['record' => $record],
            'typedInjections' => $record instanceof Model ? [Model::class => $record, $record::class => $record] : [],
        ];
    }
}
