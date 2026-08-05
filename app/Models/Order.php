<?php

namespace App\Models;

use App\Enums\EntryMode;
use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'store_id',
        'channel_id',
        'order_number',
        'external_order_id',
        'customer_name',
        'occurred_at',
        'entry_mode',
        'status',
        'cancelled_reason',
        'cancelled_at',
        'subtotal',
        'discount_amount',
        'total',
        'note',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'entry_mode' => EntryMode::class,
            'status' => OrderStatus::class,
            'cancelled_at' => 'datetime',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'total' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
