<?php

namespace App\Models;

use App\Enums\SelectionType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'is_required',
        'selection_type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'selection_type' => SelectionType::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ModifierGroup $modifierGroup) {
            $modifierGroup->sort_order ??= 0;
        });
    }

    public function options(): HasMany
    {
        return $this->hasMany(ModifierOption::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group')
            ->using(ProductModifierGroup::class)
            ->withPivot('sort_order');
    }
}
