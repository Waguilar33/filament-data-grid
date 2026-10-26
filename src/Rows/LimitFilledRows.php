<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Rows;

use Closure;
use Waguilar\FilamentDataGrid\DataGrid;

final class LimitFilledRows
{
    public function max(DataGrid $grid): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($grid): void {
            $limit = $grid->getMaxItems();

            if ($limit !== null && $grid->getItemsCount() > $limit) {
                $fail($this->message('validation.max.array', ['max' => $limit]));
            }
        };
    }

    public function min(DataGrid $grid): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($grid): void {
            $limit = $grid->getMinItems();

            if ($limit !== null && $grid->getItemsCount() < $limit) {
                $fail($this->message('validation.min.array', ['min' => $limit]));
            }
        };
    }

    /**
     * @param  array<string, int>  $replace
     */
    private function message(string $key, array $replace): string
    {
        $message = __($key, $replace);

        return is_string($message) ? $message : $key;
    }
}
