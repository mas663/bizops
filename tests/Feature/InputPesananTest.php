<?php

namespace Tests\Feature;

use App\Enums\SelectionType;
use App\Enums\UserRole;
use App\Filament\Pages\InputPesanan;
use App\Models\Category;
use App\Models\Channel;
use App\Models\ModifierGroup;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InputPesananTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private Store $store;

    private Channel $channel;

    private User $user;

    private Category $category;

    private Product $product;

    private ProductVariant $variantS;

    private ProductVariant $variantM;

    private ModifierGroup $sweetness;

    private ModifierOption $noSugar;

    private ModifierOption $lessSugar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create(['name' => 'Test Organization']);
        $this->store = Store::create(['organization_id' => $this->organization->id, 'name' => 'Toko Utama']);
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
            'is_active' => true,
        ]);

        $this->actingAs($this->user);

        $this->category = Category::create([
            'organization_id' => $this->organization->id,
            'name' => 'Fresh Juice',
        ]);

        $this->product = Product::create([
            'organization_id' => $this->organization->id,
            'category_id' => $this->category->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $this->variantS = $this->product->variants()->create(['name' => 'S', 'price' => 18000, 'cost_price' => 8000]);
        $this->variantM = $this->product->variants()->create(['name' => 'M', 'price' => 22000, 'cost_price' => 10000]);

        $this->sweetness = ModifierGroup::create([
            'organization_id' => $this->organization->id,
            'name' => 'Sweetness',
            'is_required' => true,
            'selection_type' => SelectionType::Single,
        ]);
        $this->noSugar = $this->sweetness->options()->create(['name' => 'No Sugar', 'price_delta' => 0]);
        $this->lessSugar = $this->sweetness->options()->create(['name' => 'Less Sugar', 'price_delta' => 1000]);
        $this->product->modifierGroups()->attach($this->sweetness->id);
    }

    public function test_page_is_reachable_from_the_sidebar(): void
    {
        $this->get('/admin')->assertOk();

        Livewire::test(InputPesanan::class)->assertOk();
    }

    public function test_grid_only_shows_active_products_with_at_least_one_active_variant(): void
    {
        $inactiveProduct = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Produk Nonaktif',
            'receipt_name' => 'Produk Nonaktif',
            'is_active' => false,
        ]);
        $inactiveProduct->variants()->create(['name' => 'Reguler', 'price' => 10000, 'cost_price' => 5000]);

        $noActiveVariantProduct = Product::create([
            'organization_id' => $this->organization->id,
            'name' => 'Tanpa Varian Aktif',
            'receipt_name' => 'Tanpa Varian Aktif',
        ]);
        $noActiveVariantProduct->variants()->create(['name' => 'Reguler', 'price' => 10000, 'cost_price' => 5000, 'is_active' => false]);

        $products = Livewire::test(InputPesanan::class)->get('products');

        $this->assertTrue($products->contains('id', $this->product->id));
        $this->assertFalse($products->contains('id', $inactiveProduct->id));
        $this->assertFalse($products->contains('id', $noActiveVariantProduct->id));
    }

    public function test_saving_an_order_with_two_items_one_with_a_modifier_produces_correct_snapshots_and_totals(): void
    {
        $component = Livewire::test(InputPesanan::class);

        // Item 1: Jus Mangga (S), no modifier group required check passes
        // because Sweetness is required — select No Sugar.
        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        // Item 2: Jus Mangga (M) with Less Sugar (+1000), quantity 2.
        $component->call('selectProduct', $this->product->id);
        $component->call('selectVariant', $this->variantM->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->lessSugar->id, 'single');
        $component->set('modalQuantity', 2);
        $component->call('addToCart');

        $this->assertCount(2, $component->get('cart'));

        $component->set('data.channel_id', $this->channel->id);
        $component->set('data.discount_amount', 5000);
        $component->call('save');

        $order = Order::sole();

        $expectedSubtotal = 18000 + ((22000 + 1000) * 2);
        $this->assertSame($expectedSubtotal, $order->subtotal);
        $this->assertSame(5000, $order->discount_amount);
        $this->assertSame($expectedSubtotal - 5000, $order->total);
        $this->assertSame($this->channel->id, $order->channel_id);
        $this->assertSame($this->user->id, $order->created_by);
        $this->assertSame($this->store->id, $order->store_id);
        $this->assertSame($this->organization->id, $order->organization_id);
        $this->assertSame('manual', $order->entry_mode->value);
        $this->assertSame('completed', $order->status->value);
        $this->assertNotNull($order->order_number);

        $this->assertSame(2, $order->items()->count());

        $itemS = $order->items()->where('variant_name', 'S')->sole();
        $this->assertSame('Jus Mangga', $itemS->product_name);
        $this->assertSame(18000, $itemS->unit_price);
        $this->assertSame(8000, $itemS->unit_cost);
        $this->assertSame(1, $itemS->quantity);
        $this->assertSame(0, $itemS->modifiers_total);
        $this->assertSame(18000, $itemS->line_total);
        $modifierS = $itemS->modifiers()->sole();
        $this->assertSame('Sweetness', $modifierS->group_name);
        $this->assertSame('No Sugar', $modifierS->option_name);
        $this->assertSame(0, $modifierS->price_delta);

        $itemM = $order->items()->where('variant_name', 'M')->sole();
        $this->assertSame('Jus Mangga', $itemM->product_name);
        $this->assertSame(22000, $itemM->unit_price);
        $this->assertSame(10000, $itemM->unit_cost);
        $this->assertSame(2, $itemM->quantity);
        $this->assertSame(1000, $itemM->modifiers_total);
        $this->assertSame((22000 + 1000) * 2, $itemM->line_total);
        $modifierM = $itemM->modifiers()->sole();
        $this->assertSame('Sweetness', $modifierM->group_name);
        $this->assertSame('Less Sugar', $modifierM->option_name);
        $this->assertSame(1000, $modifierM->price_delta);
    }

    public function test_manual_price_override_is_saved_as_unit_price_but_unit_cost_stays_from_the_variant(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->set('modalUnitPrice', 15000); // overridden below the variant's actual price of 18000
        $component->call('addToCart');

        $component->set('data.channel_id', $this->channel->id);
        $component->call('save');

        $order = Order::sole();
        $item = $order->items()->sole();

        $this->assertSame(15000, $item->unit_price);
        $this->assertSame(8000, $item->unit_cost); // always the variant's real cost_price, never the override
        $this->assertSame(15000, $item->line_total);
    }

    public function test_add_to_cart_is_blocked_when_a_required_modifier_group_is_not_filled(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        // Sweetness is required but nothing selected.
        $component->call('addToCart');

        $this->assertCount(0, $component->get('cart'));
    }

    public function test_a_failure_partway_through_saving_leaves_no_orphaned_order_or_order_item_rows(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        $component->call('selectProduct', $this->product->id);
        $component->call('selectVariant', $this->variantM->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->lessSugar->id, 'single');
        $component->call('addToCart');

        $cartKeys = array_keys($component->get('cart'));

        // Force the second item's unit_price to null — this bypasses the UI
        // entirely (which always fills a numeric value) and simulates a
        // failure partway through the save transaction: the first item's
        // row would already be written before the second item's INSERT
        // hits order_items' NOT NULL constraint on unit_price.
        $component->set("cart.{$cartKeys[1]}.unit_price", null);

        $component->set('data.channel_id', $this->channel->id);

        try {
            $component->call('save');
            $this->fail('Expected a database exception for the missing unit_price.');
        } catch (\Throwable) {
            // Expected: the transaction must roll back.
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
    }

    public function test_channel_default_follows_each_logged_in_users_own_last_order(): void
    {
        $grabFood = Channel::create([
            'organization_id' => $this->organization->id,
            'name' => 'GrabFood',
            'code' => 'grabfood',
        ]);

        $otherUser = User::create([
            'organization_id' => $this->organization->id,
            'name' => 'Other Owner',
            'email' => 'other@test.local',
            'password' => 'password',
            'role' => UserRole::Owner,
            'is_active' => true,
        ]);

        // This user's last order used WhatsApp.
        Order::create([
            'organization_id' => $this->organization->id,
            'store_id' => $this->store->id,
            'channel_id' => $this->channel->id,
            'order_number' => '260805-001',
            'occurred_at' => now(),
            'created_by' => $this->user->id,
            'subtotal' => 10000,
            'discount_amount' => 0,
            'total' => 10000,
        ]);

        // The other user's last order used GrabFood.
        Order::create([
            'organization_id' => $this->organization->id,
            'store_id' => $this->store->id,
            'channel_id' => $grabFood->id,
            'order_number' => '260805-002',
            'occurred_at' => now(),
            'created_by' => $otherUser->id,
            'subtotal' => 20000,
            'discount_amount' => 0,
            'total' => 20000,
        ]);

        $thisUserComponent = Livewire::test(InputPesanan::class);
        $this->assertSame($this->channel->id, (int) $thisUserComponent->get('data')['channel_id']);

        $this->actingAs($otherUser);
        $otherUserComponent = Livewire::test(InputPesanan::class);
        $this->assertSame($grabFood->id, (int) $otherUserComponent->get('data')['channel_id']);
    }

    public function test_discount_exceeding_subtotal_is_rejected(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart'); // subtotal will be 18000

        $component->set('data.channel_id', $this->channel->id);
        $component->set('data.discount_amount', 999999);
        $component->call('save');

        $component->assertHasErrors(['data.discount_amount']);
        $this->assertSame(0, Order::count());
    }

    public function test_cannot_save_without_any_cart_item(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->set('data.channel_id', $this->channel->id);
        $component->call('save');

        $this->assertSame(0, Order::count());
    }

    public function test_cannot_save_without_a_channel(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        $component->set('data.channel_id', null);
        $component->call('save');

        $component->assertHasErrors(['data.channel_id']);
        $this->assertSame(0, Order::count());
    }

    public function test_cart_quantity_can_be_updated_and_items_removed(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        $key = array_key_first($component->get('cart'));

        $component->call('updateCartQuantity', $key, 3);
        $this->assertSame(3, $component->get('cart')[$key]['quantity']);
        $this->assertSame(18000 * 3, $component->get('cart')[$key]['line_total']);

        $component->call('updateCartQuantity', $key, 0); // must be ignored, quantity min is 1
        $this->assertSame(3, $component->get('cart')[$key]['quantity']);

        $component->call('removeFromCart', $key);
        $this->assertCount(0, $component->get('cart'));
    }

    public function test_occurred_at_can_be_backdated(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        $yesterday = now()->subDay();

        $component->set('data.channel_id', $this->channel->id);
        $component->set('data.occurred_at', $yesterday->toDateTimeString());
        $component->call('save');

        $order = Order::sole();
        $this->assertSame($yesterday->format('Y-m-d H:i'), $order->occurred_at->format('Y-m-d H:i'));
    }

    public function test_cart_and_form_reset_after_a_successful_save_while_channel_stays_defaulted(): void
    {
        $component = Livewire::test(InputPesanan::class);

        $component->call('selectProduct', $this->product->id);
        $component->call('toggleModifierOption', $this->sweetness->id, $this->noSugar->id, 'single');
        $component->call('addToCart');

        $component->set('data.channel_id', $this->channel->id);
        $component->set('data.customer_name', 'Budi');
        $component->call('save');

        $this->assertCount(0, $component->get('cart'));
        $this->assertSame($this->channel->id, (int) $component->get('data')['channel_id']);
        $this->assertNull($component->get('data')['customer_name']);
    }
}
