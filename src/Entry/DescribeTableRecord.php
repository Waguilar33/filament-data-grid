<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use Filament\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final readonly class DescribeTableRecord
{
    /**
     * @param  Model|array<string, mixed>  $record
     * @return array{actions: string|null, link: string|null}
     */
    public function handle(Table $table, Model|array $record): array
    {
        return [
            'actions' => $this->actions($table, $record),
            'link' => $this->link($table, $record),
        ];
    }

    /**
     * @param  Model|array<string, mixed>  $record
     */
    private function actions(Table $table, Model|array $record): ?string
    {
        $html = '';

        foreach ($table->getRecordActions() as $action) {
            $action = $action->getClone();

            if (! $action instanceof BulkAction) {
                $action->record($record);
            }

            if ($action->isHidden()) {
                continue;
            }

            $html .= $action->toHtml();
        }

        return $html === '' ? null : '<div class="fi-ta-actions fi-data-grid-record-actions">'.$html.'</div>';
    }

    /**
     * @param  Model|array<string, mixed>  $record
     */
    private function link(Table $table, Model|array $record): ?string
    {
        return (new RenderRecordLink)->handle($table->getRecordUrl($record), $table->shouldOpenRecordUrlInNewTab($record));
    }
}
