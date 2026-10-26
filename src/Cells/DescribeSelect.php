<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Cells;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Contracts\Support\Htmlable;

final readonly class DescribeSelect
{
    public function __construct(private DescribeOptions $describeOptions) {}

    /**
     * @return array<string, mixed>
     */
    public function column(Select|Radio|ToggleButtons $component): array
    {
        $shared = [
            'canOptionLabelsWrap' => true,
            'clearButtonLabel' => __('filament-forms::components.select.actions.clear.label'),
            'errorMessage' => __('filament-forms::components.select.error_message'),
            'removeButtonLabel' => __('filament-forms::components.select.actions.remove_option.label'),
            'searchLabel' => __('filament-forms::components.select.search_label'),
            'isReorderable' => false,
        ];

        if (! $component instanceof Select) {
            return [
                ...$shared,
                'hasDynamicOptions' => false,
                'hasDynamicSearchResults' => false,
                'hasInitialNoOptionsMessage' => false,
                'isHtmlAllowed' => false,
                'isMultiple' => $component instanceof ToggleButtons && $component->isMultiple(),
                'isSearchable' => false,
                'loadingMessage' => __('filament-forms::components.select.loading_message'),
                'maxItems' => null,
                'maxItemsMessage' => null,
                'noOptionsMessage' => __('filament-forms::components.select.no_options_message'),
                'noSearchResultsMessage' => __('filament-forms::components.select.no_search_results_message'),
                'optionsLimit' => null,
                'placeholder' => __('filament-forms::components.select.placeholder'),
                'position' => null,
                'searchDebounce' => 0,
                'searchingMessage' => __('filament-forms::components.select.searching_message'),
                'searchPrompt' => __('filament-forms::components.select.search_prompt'),
                'searchableOptionFields' => ['label'],
            ];
        }

        return [
            ...$shared,
            'hasDynamicOptions' => $component->hasDynamicOptions(),
            'hasDynamicSearchResults' => $component->hasDynamicSearchResults(),
            'hasInitialNoOptionsMessage' => $component->hasInitialNoOptionsMessage(),
            'isHtmlAllowed' => $component->isHtmlAllowed(),
            'isMultiple' => $component->isMultiple(),
            'isSearchable' => $component->isSearchable(),
            'loadingMessage' => $component->getLoadingMessage(),
            'maxItems' => $component->getMaxItems(),
            'maxItemsMessage' => $component->getMaxItemsMessage(),
            'noOptionsMessage' => $this->text($component->getNoOptionsMessage()),
            'noSearchResultsMessage' => $this->text($component->getNoSearchResultsMessage()),
            'optionsLimit' => $component->getOptionsLimit(),
            'placeholder' => $component->getPlaceholder(),
            'position' => $component->getPosition(),
            'searchDebounce' => $component->getSearchDebounce(),
            'searchingMessage' => $component->getSearchingMessage(),
            'searchPrompt' => $this->text($component->getSearchPrompt()),
            'searchableOptionFields' => $component->getSearchableOptionFields(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cell(Select|Radio|ToggleButtons $component): array
    {
        $isMultiple = $component instanceof Select || $component instanceof ToggleButtons ? $component->isMultiple() : false;
        $options = $component instanceof Select ? $component->getOptionsForJs() : $this->describeOptions->handle($component);

        return [
            'options' => $options,
            'canSelectPlaceholder' => $component instanceof Select ? $component->canSelectPlaceholder() : ! $component->isRequired(),
            'label' => $isMultiple ? null : $this->describeOptions->label($component),
            'labels' => $isMultiple ? $this->describeOptions->labels($component) : null,
        ];
    }

    private function text(string|Htmlable $text): string
    {
        return $text instanceof Htmlable ? $text->toHtml() : $text;
    }
}
