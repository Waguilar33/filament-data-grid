<?php

declare(strict_types=1);

namespace Waguilar33\FilamentDataGrid\Rows;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class ResolveRowFilledFields
{
    /**
     * @return list<string>
     */
    public function handle(?Schema $rowSchema): array
    {
        $fields = [];

        foreach ($rowSchema?->getComponents(withHidden: true) ?? [] as $component) {
            if ($this->marksRowAsFilled($component)) {
                $fields[] = $component->getName();
            }
        }

        return $fields;
    }

    /**
     * @phpstan-assert-if-true Field $component
     */
    private function marksRowAsFilled(mixed $component): bool
    {
        return $component instanceof Field
            && ! $component instanceof Hidden
            && ! $component instanceof Toggle
            && ! $component instanceof Checkbox
            && $component->getDefaultState() === null;
    }
}
