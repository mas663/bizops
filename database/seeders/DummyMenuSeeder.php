<?php

namespace Database\Seeders;

use App\Enums\SelectionType;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Data karangan untuk menguji struktur skema — bukan menu final.
 * Jalankan manual: php artisan db:seed --class=DummyMenuSeeder
 */
class DummyMenuSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::firstOrFail();

        $categories = collect([
            'Fresh Juice',
            'Smoothies',
            'Seasonal',
        ])->mapWithKeys(function (string $name, int $sortOrder) use ($organization) {
            return [$name => Category::create([
                'organization_id' => $organization->id,
                'name' => $name,
                'sort_order' => $sortOrder,
            ])];
        });

        $sweetness = ModifierGroup::create([
            'organization_id' => $organization->id,
            'name' => 'Sweetness',
            'is_required' => true,
            'selection_type' => SelectionType::Single,
        ]);
        $this->createOptions($sweetness, ['No Sugar', 'Less Sugar', 'Normal']);

        $base = ModifierGroup::create([
            'organization_id' => $organization->id,
            'name' => 'Base',
            'is_required' => false,
            'selection_type' => SelectionType::Single,
        ]);
        $this->createOptions($base, ['Water Based', 'Fresh Milk', 'Oat Milk', 'Coconut Water']);

        $extra = ModifierGroup::create([
            'organization_id' => $organization->id,
            'name' => 'Extra',
            'is_required' => false,
            'selection_type' => SelectionType::Multiple,
        ]);
        $this->createOptions($extra, ['Chia Seed', 'Oat', 'Protein', 'Extra Fruit']);

        $jusMangga = Product::create([
            'organization_id' => $organization->id,
            'category_id' => $categories['Fresh Juice']->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $this->createVariants($jusMangga, [
            ['S', 18000, 8000],
            ['M', 22000, 10000],
            ['L', 26000, 12000],
        ]);
        $jusMangga->modifierGroups()->attach([$sweetness->id]);

        $smoothie = Product::create([
            'organization_id' => $organization->id,
            'category_id' => $categories['Smoothies']->id,
            'name' => 'Smoothie Mangga Yogurt',
            'receipt_name' => 'Smoothie Mangga',
        ]);
        $this->createVariants($smoothie, [
            ['S', 25000, 12000],
            ['M', 29000, 14000],
            ['L', 33000, 16000],
        ]);
        $smoothie->modifierGroups()->attach([$sweetness->id, $base->id, $extra->id]);

        $kunafa = Product::create([
            'organization_id' => $organization->id,
            'category_id' => $categories['Seasonal']->id,
            'name' => 'Kunafa Pistachio Fruit',
            'receipt_name' => 'Kunafa Pistachio',
        ]);
        $this->createVariants($kunafa, [
            ['Reguler', 35000, 18000],
        ]);
    }

    /**
     * @param  array<string>  $names
     */
    private function createOptions(ModifierGroup $modifierGroup, array $names): void
    {
        foreach ($names as $sortOrder => $name) {
            $modifierGroup->options()->create([
                'name' => $name,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    /**
     * @param  array<array{0: string, 1: int, 2: int}>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $sortOrder => [$name, $price, $costPrice]) {
            $product->variants()->create([
                'name' => $name,
                'price' => $price,
                'cost_price' => $costPrice,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
