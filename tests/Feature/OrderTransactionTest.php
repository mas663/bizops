<?php

namespace Tests\Feature;

use App\Enums\SelectionType;
use App\Enums\UserRole;
use App\Models\Channel;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderTransactionTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Store $store;

    private Channel $channel;

    private User $user;

    private Product $product;

    private ProductVariant $variantMedium;

    private ProductVariant $variantLarge;

    private ModifierGroup $modifierGroup;

    private ModifierOption $optionLessSugar;

    private ModifierOption $optionNoSugar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Test Organization',
        ]);

        $this->store = Store::create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Store',
        ]);

        $this->channel = Channel::create([
            'organization_id' => $this->organization->id,
            'name' => 'WhatsApp',
            'code' => 'whatsapp',
        ]);

        $this->user = User::create([
            'organization_id' => $this->organization->id,
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'password' => 'password',
            'role' => UserRole::Owner,
        ]);

        $this->actingAs($this->user);

        $this->product = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);

        $this->variantMedium = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Medium',
            'price' => 15000,
            'cost_price' => 6000,
        ]);

        $this->variantLarge = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Large',
            'price' => 20000,
            'cost_price' => 8000,
        ]);

        $this->modifierGroup = ModifierGroup::create([
            'organization_id' => $this->organization->id,
            'name' => 'Level Gula',
            'selection_type' => SelectionType::Single,
        ]);

        $this->optionLessSugar = ModifierOption::create([
            'modifier_group_id' => $this->modifierGroup->id,
            'name' => 'Less Sugar',
            'price_delta' => 0,
        ]);

        $this->optionNoSugar = ModifierOption::create([
            'modifier_group_id' => $this->modifierGroup->id,
            'name' => 'No Sugar',
            'price_delta' => 3000,
        ]);
    }

    public function test_order_with_multiple_items_and_a_modifier_is_stored_with_correct_totals_and_snapshots(): void
    {
        DB::transaction(function () {
            $order = Order::create([
                'organization_id' => $this->organization->id,
                'store_id' => $this->store->id,
                'channel_id' => $this->channel->id,
                'order_number' => 'ORD-0001',
                'occurred_at' => now(),
                'created_by' => $this->user->id,
                'subtotal' => 38000,
                'discount_amount' => 2000,
                'total' => 36000,
            ]);

            $itemOne = OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $this->variantMedium->id,
                'product_name' => $this->product->name,
                'variant_name' => $this->variantMedium->name,
                'unit_price' => $this->variantMedium->price,
                'unit_cost' => $this->variantMedium->cost_price,
                'quantity' => 1,
                'modifiers_total' => 0,
                'line_total' => 15000,
            ]);

            $itemTwo = OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $this->variantLarge->id,
                'product_name' => $this->product->name,
                'variant_name' => $this->variantLarge->name,
                'unit_price' => $this->variantLarge->price,
                'unit_cost' => $this->variantLarge->cost_price,
                'quantity' => 1,
                'modifiers_total' => 3000,
                'line_total' => 23000,
            ]);

            OrderItemModifier::create([
                'order_item_id' => $itemTwo->id,
                'modifier_option_id' => $this->optionNoSugar->id,
                'group_name' => $this->modifierGroup->name,
                'option_name' => $this->optionNoSugar->name,
                'price_delta' => $this->optionNoSugar->price_delta,
            ]);

            $this->assertNotNull($itemOne);
        });

        $order = Order::sole();

        $this->assertSame(38000, $order->subtotal);
        $this->assertSame(2000, $order->discount_amount);
        $this->assertSame(36000, $order->total);

        $this->assertSame(2, OrderItem::count());

        $itemOne = OrderItem::where('product_variant_id', $this->variantMedium->id)->sole();
        $this->assertSame('Jus Mangga', $itemOne->product_name);
        $this->assertSame('Medium', $itemOne->variant_name);
        $this->assertSame(15000, $itemOne->unit_price);
        $this->assertSame(6000, $itemOne->unit_cost);
        $this->assertSame(15000, $itemOne->line_total);

        $itemTwo = OrderItem::where('product_variant_id', $this->variantLarge->id)->sole();
        $this->assertSame('Jus Mangga', $itemTwo->product_name);
        $this->assertSame('Large', $itemTwo->variant_name);
        $this->assertSame(20000, $itemTwo->unit_price);
        $this->assertSame(8000, $itemTwo->unit_cost);
        $this->assertSame(3000, $itemTwo->modifiers_total);
        $this->assertSame(23000, $itemTwo->line_total);

        $modifier = OrderItemModifier::sole();
        $this->assertSame('Level Gula', $modifier->group_name);
        $this->assertSame('No Sugar', $modifier->option_name);
        $this->assertSame(3000, $modifier->price_delta);
    }

    public function test_failed_item_save_rolls_back_the_whole_order(): void
    {
        try {
            DB::transaction(function () {
                $order = Order::create([
                    'organization_id' => $this->organization->id,
                    'store_id' => $this->store->id,
                    'channel_id' => $this->channel->id,
                    'order_number' => 'ORD-0002',
                    'occurred_at' => now(),
                    'created_by' => $this->user->id,
                    'subtotal' => 35000,
                    'discount_amount' => 0,
                    'total' => 35000,
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $this->variantMedium->id,
                    'product_name' => $this->product->name,
                    'variant_name' => $this->variantMedium->name,
                    'unit_price' => $this->variantMedium->price,
                    'unit_cost' => $this->variantMedium->cost_price,
                    'quantity' => 1,
                    'modifiers_total' => 0,
                    'line_total' => 15000,
                ]);

                // Second item is missing the mandatory unit_cost snapshot,
                // which must trigger a NOT NULL violation and abort the
                // whole transaction — an order can never be left without
                // all of its items.
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $this->variantLarge->id,
                    'product_name' => $this->product->name,
                    'variant_name' => $this->variantLarge->name,
                    'unit_price' => $this->variantLarge->price,
                    'quantity' => 1,
                    'modifiers_total' => 0,
                    'line_total' => 20000,
                ]);
            });

            $this->fail('Expected a QueryException to be thrown for the missing unit_cost column.');
        } catch (QueryException) {
            // Expected: the transaction must roll back.
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
    }
}
