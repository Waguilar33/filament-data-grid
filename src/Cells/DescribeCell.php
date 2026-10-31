<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Cells;

use Filament\Forms\Components\Contracts\HasAffixes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\RawJs;
use Illuminate\Support\MessageBag;

use function Filament\Support\generate_icon_html;

final readonly class DescribeCell
{
    public function __construct(
        private ResolveCellEditorType $resolveEditorType,
        private DescribeSelect $describeSelect,
        private DescribeDate $describeDate,
        private DescribeStateText $describeStateText,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(Component $component, MessageBag $errors): array
    {
        if ($component->isHidden()) {
            return ['visible' => false];
        }

        $type = $this->resolveEditorType->handle($component);

        if ($component instanceof Entry) {
            return ['visible' => true, 'html' => $this->entryHtml($component)];
        }

        $statePath = $component->getStatePath();
        $isLocked = $this->isLocked($component);

        return array_filter([
            'visible' => true,
            'key' => $component->getKey(),
            'disabled' => $isLocked,
            'required' => $component instanceof Field && $component->isRequired(),
            'live' => $component->isLive(),
            'error' => $errors->first($statePath) ?: null,
            'icon' => $isLocked ? null : $this->suffixIcon($component),
            ...$this->editorState($component, $type),
        ], fn (mixed $value): bool => $value !== null);
    }

    private function isLocked(Component $component): bool
    {
        return $component->isDisabled()
            || (($component instanceof TextInput || $component instanceof DateTimePicker) && $component->isReadOnly());
    }

    private function suffixIcon(Component $component): ?string
    {
        if (! $component instanceof HasAffixes) {
            return null;
        }

        $icon = $component->getSuffixIcon();

        return $icon === null ? null : generate_icon_html($icon)?->toHtml();
    }

    /**
     * @return array<string, mixed>
     */
    private function editorState(Component $component, CellEditorType $type): array
    {
        return match (true) {
            $component instanceof TextInput && $type === CellEditorType::Number => [
                'min' => $component->getMinValue(),
                'max' => $component->getMaxValue(),
                'step' => $component->getStep(),
                ...$this->mask($component),
            ],
            $component instanceof Select || $component instanceof Radio || $component instanceof ToggleButtons => $this->describeSelect->cell($component),
            $component instanceof DateTimePicker => $this->describeDate->handle($component),
            $component instanceof Field && $type === CellEditorType::Modal => [
                'label' => $this->describeStateText->handle($component),
            ],
            default => [],
        };
    }

    /**
     * @return array{mask?: string, maskDynamic?: bool, stripCharacters?: list<string>}
     */
    private function mask(TextInput $component): array
    {
        $mask = $component->getMask();

        if (blank($mask)) {
            return [];
        }

        return [
            'mask' => $mask instanceof RawJs ? $mask->toHtml() : $mask,
            'maskDynamic' => $mask instanceof RawJs,
            'stripCharacters' => array_values($component->getStripCharacters()),
        ];
    }

    private function entryHtml(Entry $entry): string
    {
        $entry->hiddenLabel();

        $html = $entry instanceof HasEmbeddedView ? $entry->toEmbeddedHtml() : $entry->toHtml();

        return trim((string) preg_replace('/>\s+</', '> <', $html));
    }
}
