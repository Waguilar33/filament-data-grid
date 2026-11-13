<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Support;

use BackedEnum;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Stringable;

final class SelectedKeysStateCast implements StateCast
{
    /**
     * @return list<string>
     */
    public function get(mixed $state): array
    {
        return $this->keys($state);
    }

    /**
     * @return list<string>
     */
    public function set(mixed $state): array
    {
        return $this->keys($state);
    }

    /**
     * @return list<string>
     */
    private function keys(mixed $state): array
    {
        if (! is_array($state)) {
            return [];
        }

        $keys = [];

        foreach ($state as $key) {
            $key = $key instanceof BackedEnum ? $key->value : $key;

            if ((is_scalar($key) || $key instanceof Stringable) && (string) $key !== '') {
                $keys[] = (string) $key;
            }
        }

        return array_values(array_unique($keys));
    }
}
