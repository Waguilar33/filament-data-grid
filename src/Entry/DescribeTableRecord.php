<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Waguilar\FilamentDataGrid\View\DataGridIconAlias;

use function Filament\Support\generate_href_html;
use function Filament\Support\generate_icon_html;

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
        $url = $table->getRecordUrl($record);

        if (blank($url)) {
            return null;
        }

        $href = generate_href_html($url, $table->shouldOpenRecordUrlInNewTab($record))->toHtml();
        $icon = generate_icon_html(Heroicon::ChevronRight, DataGridIconAlias::ROW_OPEN)?->toHtml() ?? '';
        $label = e(__('filament-data-grid::data-grid.entry.open_record'));

        return sprintf('<a %s class="fi-data-grid-record-link" aria-label="%s" title="%s">%s</a>', $href, $label, $label, $icon);
    }
}
