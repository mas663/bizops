<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_item_id',
        'modifier_option_id',
        'group_name',
        'option_name',
        'price_delta',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function modifierOption(): BelongsTo
    {
        return $this->belongsTo(ModifierOption::class);
    }
}
