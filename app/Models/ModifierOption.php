<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierOption extends Model
{
    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_delta',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_delta' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }

    public function orderItemModifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }
}
