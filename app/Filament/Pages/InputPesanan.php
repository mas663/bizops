<?php

namespace App\Filament\Pages;

use App\Enums\EntryMode;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Channel;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class InputPesanan extends Page
{
    protected string $view = 'filament.pages.input-pesanan';

    protected static ?string $navigationLabel = 'Input Pesanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Pesanan';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Input Pesanan';

    /**
     * Order info form state (channel, occurred_at, customer_name,
     * discount_amount, note).
     *
     * @var array<string, mixed>
     */
    public ?array $data = [];

    /**
     * Cart lines, keyed by a UUID so they can be removed individually.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $cart = [];

    public ?int $activeCategoryId = null;

    public ?int $modalProductId = null;

    public ?int $modalVariantId = null;

    /**
     * @var array<int, array<int, int>> modifier_group_id => [modifier_option_id, ...]
     */
    public array $modalSelectedModifierOptionIds = [];

    public int $modalQuantity = 1;

    public ?int $modalUnitPrice = null;

    public function mount(): void
    {
        $this->form->fill([
            'channel_id' => $this->defaultChannelId(),
            'occurred_at' => now(),
            'customer_name' => null,
            'discount_amount' => null,
            'note' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('channel_id')
                    ->label('Channel')
                    ->options(fn () => Channel::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->required()
                    ->validationMessages([
                        'required' => 'Channel wajib dipilih.',
                    ]),
                DateTimePicker::make('occurred_at')
                    ->label('Waktu Terjadi')
                    ->native(false)
                    ->seconds(false)
                    ->required()
                    ->validationMessages([
                        'required' => 'Waktu terjadi wajib diisi.',
                    ]),
                TextInput::make('customer_name')
                    ->label('Nama Pelanggan')
                    ->maxLength(255),
                TextInput::make('discount_amount')
                    ->label('Diskon')
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2),
            ])
            ->statePath('data');
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategoriesProperty(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProductsProperty(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('variants', fn ($query) => $query->where('is_active', true))
            ->when($this->activeCategoryId, fn ($query) => $query->where('category_id', $this->activeCategoryId))
            ->with([
                'category',
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderBy('name')
            ->get();
    }

    public function getModalProductProperty(): ?Product
    {
        if (! $this->modalProductId) {
            return null;
        }

        return Product::query()
            ->with([
                'variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                'modifierGroups' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
                'modifierGroups.options' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->find($this->modalProductId);
    }

    public function getSubtotalProperty(): int
    {
        return collect($this->cart)->sum('line_total');
    }

    public function getTotalProperty(): int
    {
        $discount = (int) ($this->data['discount_amount'] ?? 0);

        return $this->subtotal - $discount;
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->activeCategoryId = $categoryId;
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::query()
            ->with(['variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->find($productId);

        if (! $product || $product->variants->isEmpty()) {
            return;
        }

        $firstVariant = $product->variants->first();

        $this->modalProductId = $productId;
        $this->modalVariantId = $firstVariant->id;
        $this->modalUnitPrice = $firstVariant->price;
        $this->modalSelectedModifierOptionIds = [];
        $this->modalQuantity = 1;

        $this->dispatch('open-modal', id: 'product-picker-modal');
    }

    public function selectVariant(int $variantId): void
    {
        $variant = ProductVariant::find($variantId);

        if (! $variant) {
            return;
        }

        $this->modalVariantId = $variantId;
        $this->modalUnitPrice = $variant->price;
    }

    public function toggleModifierOption(int $groupId, int $optionId, string $selectionType): void
    {
        $current = $this->modalSelectedModifierOptionIds[$groupId] ?? [];

        if ($selectionType === 'single') {
            $this->modalSelectedModifierOptionIds[$groupId] = in_array($optionId, $current, true) ? [] : [$optionId];

            return;
        }

        if (in_array($optionId, $current, true)) {
            $this->modalSelectedModifierOptionIds[$groupId] = array_values(array_diff($current, [$optionId]));
        } else {
            $current[] = $optionId;
            $this->modalSelectedModifierOptionIds[$groupId] = $current;
        }
    }

    public function addToCart(): void
    {
        $product = $this->modalProduct;

        if (! $product || ! $this->modalVariantId) {
            return;
        }

        $variant = $product->variants->firstWhere('id', $this->modalVariantId);

        if (! $variant) {
            return;
        }

        foreach ($product->modifierGroups as $group) {
            if ($group->is_required && empty($this->modalSelectedModifierOptionIds[$group->id] ?? [])) {
                Notification::make()
                    ->title('Pilih modifier wajib terlebih dahulu.')
                    ->body("Grup \"{$group->name}\" wajib diisi sebelum item bisa ditambahkan.")
                    ->danger()
                    ->send();

                return;
            }
        }

        if ($this->modalQuantity < 1) {
            Notification::make()->title('Jumlah minimal 1.')->danger()->send();

            return;
        }

        $selectedOptionIds = collect($this->modalSelectedModifierOptionIds)->flatten()->all();

        $options = ModifierOption::query()
            ->with('modifierGroup')
            ->whereIn('id', $selectedOptionIds)
            ->get();

        $unitPrice = $this->modalUnitPrice ?? $variant->price;
        $modifiersTotal = (int) $options->sum('price_delta');
        $quantity = $this->modalQuantity;

        $this->cart[(string) Str::uuid()] = [
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'modifier_option_ids' => $selectedOptionIds,
            'modifiers' => $options->map(fn (ModifierOption $option) => [
                'group_name' => $option->modifierGroup->name,
                'option_name' => $option->name,
                'price_delta' => $option->price_delta,
            ])->values()->all(),
            'modifiers_total' => $modifiersTotal,
            'line_total' => ($unitPrice + $modifiersTotal) * $quantity,
        ];

        $this->modalProductId = null;
        $this->dispatch('close-modal', id: 'product-picker-modal');

        Notification::make()->title('Ditambahkan ke keranjang.')->success()->send();
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function updateCartQuantity(string $key, int $quantity): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        if ($quantity < 1) {
            return;
        }

        $this->cart[$key]['quantity'] = $quantity;
        $this->cart[$key]['line_total'] = ($this->cart[$key]['unit_price'] + $this->cart[$key]['modifiers_total']) * $quantity;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (empty($this->cart)) {
            Notification::make()
                ->title('Keranjang masih kosong.')
                ->body('Tambahkan minimal satu item sebelum menyimpan pesanan.')
                ->danger()
                ->send();

            return;
        }

        $lines = $this->buildOrderLines();
        $subtotal = collect($lines)->sum('line_total');
        $discountAmount = (int) ($data['discount_amount'] ?? 0);

        if ($discountAmount > $subtotal) {
            $this->addError('data.discount_amount', 'Diskon tidak boleh melebihi subtotal.');

            return;
        }

        $total = $subtotal - $discountAmount;
        $organizationId = Auth::user()->organization_id;
        $storeId = Store::query()->value('id');

        DB::transaction(function () use ($data, $lines, $subtotal, $discountAmount, $total, $organizationId, $storeId) {
            $order = Order::create([
                'organization_id' => $organizationId,
                'store_id' => $storeId,
                'channel_id' => $data['channel_id'],
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => filled($data['customer_name'] ?? null) ? $data['customer_name'] : null,
                'occurred_at' => $data['occurred_at'],
                'entry_mode' => EntryMode::Manual,
                'status' => OrderStatus::Completed,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'note' => filled($data['note'] ?? null) ? $data['note'] : null,
                'created_by' => Auth::id(),
            ]);

            foreach ($lines as $sortOrder => $line) {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $line['product_variant_id'],
                    'product_name' => $line['product_name'],
                    'variant_name' => $line['variant_name'],
                    'unit_price' => $line['unit_price'],
                    'unit_cost' => $line['unit_cost'],
                    'quantity' => $line['quantity'],
                    'modifiers_total' => $line['modifiers_total'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $sortOrder,
                ]);

                foreach ($line['modifiers'] as $modifier) {
                    OrderItemModifier::create([
                        'order_item_id' => $orderItem->id,
                        ...$modifier,
                    ]);
                }
            }
        });

        $usedChannelId = $data['channel_id'];

        $this->cart = [];
        $this->form->fill([
            'channel_id' => $usedChannelId,
            'occurred_at' => now(),
            'customer_name' => null,
            'discount_amount' => null,
            'note' => null,
        ]);

        Notification::make()
            ->title('Pesanan berhasil disimpan.')
            ->success()
            ->send();
    }

    /**
     * Recompute every cart line from the database by ID — the only value
     * trusted verbatim from the cart is unit_price, which is intentionally
     * overridable. unit_cost always comes fresh from the variant's current
     * cost_price, never from the override.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildOrderLines(): array
    {
        $lines = [];

        foreach ($this->cart as $item) {
            $variant = ProductVariant::findOrFail($item['product_variant_id']);

            $modifierRows = [];
            $modifiersTotal = 0;

            foreach ($item['modifier_option_ids'] as $optionId) {
                $option = ModifierOption::with('modifierGroup')->findOrFail($optionId);

                $modifierRows[] = [
                    'modifier_option_id' => $option->id,
                    'group_name' => $option->modifierGroup->name,
                    'option_name' => $option->name,
                    'price_delta' => $option->price_delta,
                ];

                $modifiersTotal += $option->price_delta;
            }

            // Not force-cast to int: addToCart() already stores a proper
            // integer here, and leaving it untouched means a genuinely
            // missing/null override is passed straight through to the
            // unit_price NOT NULL column instead of being silently
            // coerced to 0.
            $unitPrice = $item['unit_price'];
            $quantity = (int) $item['quantity'];

            $lines[] = [
                'product_variant_id' => $variant->id,
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'unit_price' => $unitPrice,
                'unit_cost' => $variant->cost_price,
                'quantity' => $quantity,
                'modifiers_total' => $modifiersTotal,
                'line_total' => ($unitPrice + $modifiersTotal) * $quantity,
                'modifiers' => $modifierRows,
            ];
        }

        return $lines;
    }

    private function generateOrderNumber(): string
    {
        $prefix = now()->format('ymd');

        $count = Order::query()
            ->where('order_number', 'like', "{$prefix}-%")
            ->count();

        return sprintf('%s-%03d', $prefix, $count + 1);
    }

    private function defaultChannelId(): ?int
    {
        return Order::query()
            ->where('created_by', Auth::id())
            ->orderByDesc('id')
            ->value('channel_id');
    }
}
