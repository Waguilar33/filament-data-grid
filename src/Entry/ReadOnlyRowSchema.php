<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Entry;

use Filament\Schemas\Schema;

/**
 * Entry rows hold constant state, so Filament's form-state walks have nothing to read or write in them.
 */
class ReadOnlyRowSchema extends Schema
{
    public function getValidationRules(): array
    {
        return [];
    }

    public function getValidationMessages(): array
    {
        return [];
    }

    public function getValidationAttributes(): array
    {
        return [];
    }

    public function mutateStateForValidation(array &$state = []): array
    {
        return $state;
    }

    public function callBeforeStateDehydrated(array &$state = []): void {}

    public function saveRelationships(): void {}

    public function loadStateFromRelationships(bool $shouldHydrate = false): void {}

    /**
     * Filament before 5.11 walks every row here on submit; later versions no longer call it.
     *
     * @return array<string, bool>
     */
    protected function buildDehydratedComponentsCache(): array
    {
        return [];
    }
}
