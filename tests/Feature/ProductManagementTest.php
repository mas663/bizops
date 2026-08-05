<?php

namespace Tests\Feature;

use App\Enums\SelectionType;
use App\Enums\UserRole;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\ModifierGroups\Pages\CreateModifierGroup;
use App\Filament\Resources\ModifierGroups\Pages\ListModifierGroups;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::create(['name' => 'Test Organization']);

        $this->user = User::create([
            'organization_id' => $organization->id,
            'name' => 'Owner',
            'email' => 'owner@test.local',
            'password' => 'password',
            'role' => UserRole::Owner,
            'is_active' => true,
        ]);

        $this->actingAs($this->user);
    }

    public function test_all_three_resources_are_reachable_from_the_sidebar(): void
    {
        $this->get('/admin')->assertOk();

        Livewire::test(ListCategories::class)->assertOk();
        Livewire::test(ListProducts::class)->assertOk();
        Livewire::test(ListModifierGroups::class)->assertOk();
    }

    public function test_a_category_can_be_created_from_scratch(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Fresh Juice',
                'sort_order' => 0,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Fresh Juice',
        ]);
    }

    public function test_a_product_can_be_created_with_at_least_one_variant_in_one_form(): void
    {
        $category = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Jus Mangga',
                'receipt_name' => 'Jus Mangga',
                'category_id' => $category->id,
                'variants' => [
                    ['name' => 'S', 'price' => 18000, 'cost_price' => 8000, 'sort_order' => 0, 'is_active' => true],
                    ['name' => 'M', 'price' => 22000, 'cost_price' => 10000, 'sort_order' => 1, 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Jus Mangga')->sole();

        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(18000, $product->variants()->where('name', 'S')->sole()->price);
    }

    public function test_saving_a_product_without_any_variant_is_rejected(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Produk Tanpa Varian',
                'receipt_name' => 'Tanpa Varian',
                'variants' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['variants']);

        $this->assertDatabaseMissing('products', [
            'name' => 'Produk Tanpa Varian',
        ]);
    }

    public function test_saving_a_product_where_every_variant_is_inactive_is_rejected(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Produk Varian Nonaktif',
                'receipt_name' => 'Varian Nonaktif',
                'variants' => [
                    ['name' => 'Reguler', 'price' => 10000, 'cost_price' => 5000, 'sort_order' => 0, 'is_active' => false],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['variants']);

        $this->assertDatabaseMissing('products', [
            'name' => 'Produk Varian Nonaktif',
        ]);
    }

    public function test_a_modifier_group_can_be_created_with_options_and_price_delta_stays_hidden_and_zero(): void
    {
        Livewire::test(CreateModifierGroup::class)
            ->fillForm([
                'name' => 'Sweetness',
                'selection_type' => SelectionType::Single->value,
                'is_required' => true,
                'options' => [
                    ['name' => 'No Sugar', 'sort_order' => 0, 'is_active' => true],
                    ['name' => 'Less Sugar', 'sort_order' => 1, 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $modifierGroup = ModifierGroup::where('name', 'Sweetness')->sole();

        $this->assertSame(2, $modifierGroup->options()->count());
        $this->assertSame(0, $modifierGroup->options()->where('name', 'No Sugar')->sole()->price_delta);
    }

    public function test_no_resource_has_a_permanent_delete_action(): void
    {
        $category = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
        ]);

        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $product->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);

        $modifierGroup = ModifierGroup::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Sweetness',
            'selection_type' => SelectionType::Single,
        ]);

        Livewire::test(ListCategories::class)
            ->assertTableActionDoesNotExist('delete', record: $category)
            ->assertTableBulkActionDoesNotExist('delete');

        Livewire::test(ListProducts::class)
            ->assertTableActionDoesNotExist('delete', record: $product)
            ->assertTableBulkActionDoesNotExist('delete');

        Livewire::test(ListModifierGroups::class)
            ->assertTableActionDoesNotExist('delete', record: $modifierGroup)
            ->assertTableBulkActionDoesNotExist('delete');
    }

    public function test_multiple_modifier_groups_can_be_checked_on_one_product(): void
    {
        $sweetness = ModifierGroup::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Sweetness',
            'selection_type' => SelectionType::Single,
        ]);

        $base = ModifierGroup::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Base',
            'selection_type' => SelectionType::Single,
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Smoothie Mangga Yogurt',
                'receipt_name' => 'Smoothie Mangga',
                'modifierGroups' => [$sweetness->id, $base->id],
                'variants' => [
                    ['name' => 'S', 'price' => 25000, 'cost_price' => 12000, 'sort_order' => 0, 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Smoothie Mangga Yogurt')->sole();

        $this->assertSame(2, $product->modifierGroups()->count());
    }

    public function test_product_image_is_uploaded_to_the_public_disk_and_is_web_accessible(): void
    {
        Storage::fake('public');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'image' => UploadedFile::fake()->image('mangga.jpg'),
                'name' => 'Jus Mangga',
                'receipt_name' => 'Jus Mangga',
                'variants' => [
                    ['name' => 'S', 'price' => 18000, 'cost_price' => 8000, 'sort_order' => 0, 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Jus Mangga')->sole();

        $this->assertNotNull($product->image);
        $this->assertStringStartsWith('products/', $product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_receipt_name_is_auto_filled_from_name_until_the_user_edits_it_manually(): void
    {
        Livewire::test(CreateProduct::class)
            ->set('data.name', 'Jus Mangga Segar Sekali Banget')
            ->assertSet('data.receipt_name', mb_substr('Jus Mangga Segar Sekali Banget', 0, 20))
            ->set('data.receipt_name', 'Nota Custom')
            ->set('data.name', 'Nama Produk Sudah Berubah Total')
            ->assertSet('data.receipt_name', 'Nota Custom');
    }

    public function test_starting_price_is_the_minimum_price_among_active_variants_only(): void
    {
        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $product->variants()->create(['name' => 'S', 'price' => 18000, 'cost_price' => 8000, 'is_active' => true]);
        $product->variants()->create(['name' => 'M', 'price' => 22000, 'cost_price' => 10000, 'is_active' => true]);
        $product->variants()->create(['name' => 'L', 'price' => 12000, 'cost_price' => 5000, 'is_active' => false]);

        $this->assertSame(18000, $product->refresh()->starting_price);
    }

    public function test_starting_price_is_null_when_there_is_no_active_variant(): void
    {
        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $product->variants()->create(['name' => 'S', 'price' => 18000, 'cost_price' => 8000, 'is_active' => false]);

        $this->assertNull($product->refresh()->starting_price);
    }

    public function test_product_sort_order_defaults_to_zero_when_not_provided(): void
    {
        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);

        $this->assertSame(0, $product->sort_order);
    }

    public function test_products_list_defaults_to_alphabetical_order_by_name(): void
    {
        $mangga = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Zambrud Mangga',
            'receipt_name' => 'Zambrud Mangga',
        ]);
        $mangga->variants()->create(['name' => 'Reguler', 'price' => 20000, 'cost_price' => 9000]);

        $apel = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Apel Segar',
            'receipt_name' => 'Apel Segar',
        ]);
        $apel->variants()->create(['name' => 'Reguler', 'price' => 15000, 'cost_price' => 7000]);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$apel, $mangga], inOrder: true);
    }

    public function test_product_grid_card_has_no_toggle_active_action(): void
    {
        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $product->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);

        Livewire::test(ListProducts::class)
            ->assertTableActionDoesNotExist('toggleActive', record: $product)
            ->assertTableActionExists('edit', record: $product);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $freshJuice = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
        ]);

        $smoothies = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Smoothies',
        ]);

        $jusMangga = Product::create([
            'organization_id' => $this->user->organization_id,
            'category_id' => $freshJuice->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $jusMangga->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);

        $smoothieMangga = Product::create([
            'organization_id' => $this->user->organization_id,
            'category_id' => $smoothies->id,
            'name' => 'Smoothie Mangga',
            'receipt_name' => 'Smoothie Mangga',
        ]);
        $smoothieMangga->variants()->create(['name' => 'Reguler', 'price' => 25000, 'cost_price' => 12000]);

        Livewire::test(ListProducts::class)
            ->filterTable('category_id', $freshJuice->id)
            ->assertCanSeeTableRecords([$jusMangga])
            ->assertCanNotSeeTableRecords([$smoothieMangga]);
    }
}
