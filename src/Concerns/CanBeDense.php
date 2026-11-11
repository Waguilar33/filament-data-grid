<?php

declare(strict_types=1);

namespace Waguilar\FilamentDataGrid\Concerns;

trait CanBeDense
{
    // Only an explicit dense() on the grid counts; Filament's isDense() would also inherit it from a dense parent schema.
    public function hasDenseRows(): bool
    {
        return (bool) $this->evaluate($this->isDense);
    }
}
