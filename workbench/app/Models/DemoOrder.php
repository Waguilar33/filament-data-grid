<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DemoOrder extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<DemoOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DemoOrderLine::class)->orderBy('sort');
    }
}
