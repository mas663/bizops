<?php

namespace Tests\Feature;

use App\Enums\SelectionType;
use App\Enums\UserRole;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\ModifierGroups\ModifierGroupResource;
use App\Filament\Resources\ModifierGroups\Pages\CreateModifierGroup;
use App\Filament\Resources\ModifierGroups\Pages\ListModifierGroups;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
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

    public function test_clicking_a_row_opens_edit_with_no_separate_edit_or_toggle_buttons(): void
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

        $categories = Livewire::test(ListCategories::class);
        $categories->assertTableActionDoesNotExist('edit', record: $category);
        $categories->assertTableActionDoesNotExist('toggleActive', record: $category);
        $this->assertSame(
            CategoryResource::getUrl('edit', ['record' => $category]),
            $categories->instance()->getTable()->getRecordUrl($category),
        );

        $products = Livewire::test(ListProducts::class);
        $products->assertTableActionDoesNotExist('edit', record: $product);
        $products->assertTableActionDoesNotExist('toggleActive', record: $product);
        $this->assertSame(
            ProductResource::getUrl('edit', ['record' => $product]),
            $products->instance()->getTable()->getRecordUrl($product),
        );

        $modifierGroups = Livewire::test(ListModifierGroups::class);
        $modifierGroups->assertTableActionDoesNotExist('edit', record: $modifierGroup);
        $modifierGroups->assertTableActionDoesNotExist('toggleActive', record: $modifierGroup);
        $this->assertSame(
            ModifierGroupResource::getUrl('edit', ['record' => $modifierGroup]),
            $modifierGroups->instance()->getTable()->getRecordUrl($modifierGroup),
        );
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

    public function test_inactive_records_are_hidden_by_default_but_visible_via_the_status_filter(): void
    {
        $activeCategory = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
            'is_active' => true,
        ]);
        $inactiveCategory = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Discontinued',
            'is_active' => false,
        ]);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$activeCategory])
            ->assertCanNotSeeTableRecords([$inactiveCategory])
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactiveCategory])
            ->assertCanNotSeeTableRecords([$activeCategory]);

        $activeProduct = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
            'is_active' => true,
        ]);
        $activeProduct->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);
        $inactiveProduct = Product::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Produk Lama',
            'receipt_name' => 'Produk Lama',
            'is_active' => false,
        ]);
        $inactiveProduct->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);

        Livewire::test(ListProducts::class)
            ->assertCanSeeTableRecords([$activeProduct])
            ->assertCanNotSeeTableRecords([$inactiveProduct])
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactiveProduct])
            ->assertCanNotSeeTableRecords([$activeProduct]);

        $activeModifierGroup = ModifierGroup::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Sweetness',
            'selection_type' => SelectionType::Single,
            'is_active' => true,
        ]);
        $inactiveModifierGroup = ModifierGroup::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Old Group',
            'selection_type' => SelectionType::Single,
            'is_active' => false,
        ]);

        Livewire::test(ListModifierGroups::class)
            ->assertCanSeeTableRecords([$activeModifierGroup])
            ->assertCanNotSeeTableRecords([$inactiveModifierGroup])
            ->filterTable('is_active', false)
            ->assertCanSeeTableRecords([$inactiveModifierGroup])
            ->assertCanNotSeeTableRecords([$activeModifierGroup]);
    }

    public function test_status_filter_options_are_labelled_in_indonesian(): void
    {
        Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
        ]);

        $html = Livewire::test(ListCategories::class)->html();

        $this->assertStringContainsString('Aktif', $html);
        $this->assertStringContainsString('Nonaktif', $html);
        $this->assertStringNotContainsString('>Yes<', $html);
        $this->assertStringNotContainsString('>No<', $html);
    }

    public function test_file_upload_placeholder_is_localized(): void
    {
        $html = Livewire::test(CreateProduct::class)->html();

        // The placeholder is embedded as a JSON string inside an Alpine
        // x-data attribute, so "&" is rendered as the JSON-hex-escaped
        // & rather than a literal ampersand — assert around it.
        $this->assertStringContainsString('Seret', $html);
        $this->assertStringContainsString('lepas file di sini', $html);
        $this->assertStringContainsString('Jelajahi', $html);
    }

    public function test_variant_repeater_has_no_manual_sort_order_input(): void
    {
        $html = Livewire::test(CreateProduct::class)->html();

        // The repeater's own field labels ("Nama Varian", "Harga Jual") must
        // remain, but the standalone "Urutan" number input for variants must
        // be gone now that only drag-and-drop reordering is used — and since
        // Product's own top-level "Urutan" field was already removed in
        // Follow-up 2, "Urutan" should not appear anywhere in this form.
        $this->assertStringContainsString('Nama Varian', $html);
        $this->assertStringContainsString('Harga Jual', $html);
        $this->assertStringNotContainsString('Urutan', $html);
    }

    public function test_product_list_has_a_dynamic_tab_per_active_category(): void
    {
        $freshJuice = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
            'is_active' => true,
        ]);
        Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Discontinued Category',
            'is_active' => false,
        ]);

        $jusMangga = Product::create([
            'organization_id' => $this->user->organization_id,
            'category_id' => $freshJuice->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $jusMangga->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000]);

        $tabs = Livewire::test(ListProducts::class)->instance()->getTabs();

        $this->assertSame(['all', $freshJuice->id], array_keys($tabs));
        $this->assertSame('Semua', $tabs['all']->getLabel());
        $this->assertSame('Fresh Juice', $tabs[(string) $freshJuice->id]->getLabel());
    }

    public function test_product_card_layout_has_a_portrait_photo_and_price_row(): void
    {
        $category = Category::create([
            'organization_id' => $this->user->organization_id,
            'name' => 'Fresh Juice',
        ]);

        $product = Product::create([
            'organization_id' => $this->user->organization_id,
            'category_id' => $category->id,
            'name' => 'Jus Mangga',
            'receipt_name' => 'Jus Mangga',
        ]);
        $product->variants()->create(['name' => 'Reguler', 'price' => 18000, 'cost_price' => 8000, 'is_active' => true]);

        $html = Livewire::test(ListProducts::class)->html();

        $this->assertStringContainsString('aspect-[3/4]', $html);
        $this->assertStringContainsString('Rp 18.000', $html);
        $this->assertStringNotContainsString('aspect-square', $html);
    }

    public function test_pagination_and_confirmation_modal_strings_are_localized(): void
    {
        // These come from scoped app-level overrides of vendor language
        // files (lang/vendor/filament*/en/...), since Filament has no
        // fluent PHP method for pagination or generic confirmation-modal
        // text. Asserting the translator resolution directly is more
        // reliable than string-matching rendered HTML for content that
        // may live inside lazy-rendered modal partials.
        $this->assertSame(
            'Menampilkan 1 hasil',
            trans_choice('filament::components/pagination.overview', 1),
        );
        $this->assertSame('Per halaman', __('filament::components/pagination.fields.records_per_page.label'));
        $this->assertSame('Apakah Anda yakin ingin melakukan ini?', __('filament-actions::modal.confirmation'));
        $this->assertSame('Batal', __('filament-actions::modal.actions.cancel.label'));
        $this->assertSame('Konfirmasi', __('filament-actions::modal.actions.confirm.label'));
        $this->assertSame('Cari', __('filament-tables::table.fields.search.placeholder'));
        $this->assertSame('Pilih opsi', __('filament-forms::components.select.placeholder'));
    }

    public function test_category_drag_reordering_still_persists_sort_order_despite_alphabetical_default_sort(): void
    {
        $organization = $this->user->organization_id;

        $apple = Category::create(['organization_id' => $organization, 'name' => 'Apple']);
        $banana = Category::create(['organization_id' => $organization, 'name' => 'Banana']);

        Livewire::test(ListCategories::class)
            ->call('reorderTable', [$banana->getKey(), $apple->getKey()]);

        $this->assertSame(1, $banana->refresh()->sort_order);
        $this->assertSame(2, $apple->refresh()->sort_order);
    }
}
