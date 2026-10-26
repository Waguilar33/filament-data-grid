<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DemoOrderLine extends Model
{
    protected $guarded = [];

    /**
     * @return BelongsTo<DemoOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(DemoOrder::class, 'demo_order_id');
    }
}
