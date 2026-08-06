<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_380px]">
        {{-- Kolom produk --}}
        <div class="space-y-4">
            {{-- Tabs kategori --}}
            <div class="flex flex-wrap gap-2 overflow-x-auto pb-1">
                <button
                    type="button"
                    wire:click="filterByCategory(null)"
                    @class([
                        'rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap transition',
                        'bg-primary-600 text-white' => $activeCategoryId === null,
                        'bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10' => $activeCategoryId !== null,
                    ])
                >
                    Semua
                </button>
                @foreach ($this->categories as $category)
                    <button
                        type="button"
                        wire:click="filterByCategory({{ $category->id }})"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap transition',
                            'bg-primary-600 text-white' => $activeCategoryId === $category->id,
                            'bg-white text-gray-700 ring-1 ring-gray-950/10 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10' => $activeCategoryId !== $category->id,
                        ])
                    >
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            {{-- Grid produk --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                @forelse ($this->products as $product)
                    <button
                        type="button"
                        wire:click="selectProduct({{ $product->id }})"
                        class="overflow-hidden rounded-xl bg-white text-left shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10"
                    >
                        <img
                            src="{{ $product->image ? Illuminate\Support\Facades\Storage::disk('public')->url($product->image) : asset('images/product-placeholder.svg') }}"
                            alt="{{ $product->name }}"
                            class="aspect-[3/4] w-full object-cover"
                        />
                        <div class="space-y-1 p-3">
                            <p class="text-sm font-bold text-gray-950 dark:text-white">{{ $product->name }}</p>
                            <div class="flex items-center justify-between gap-2">
                                @if ($product->category)
                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span></span>
                                @endif
                                @php $startingPrice = $product->variants->where('is_active', true)->min('price'); @endphp
                                <span class="text-base font-bold text-gray-950 dark:text-white">
                                    @if ($startingPrice === null)
                                        Belum ada varian aktif
                                    @else
                                        Rp {{ number_format($startingPrice, 0, ',', '.') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-xl bg-white p-8 text-center text-sm text-gray-500 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10">
                        Tidak ada produk aktif untuk ditampilkan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Kolom keranjang --}}
        <div
            x-data="{ cartOpen: window.innerWidth >= 1024 }"
            x-on:resize.window="if (window.innerWidth >= 1024) cartOpen = true"
        >
            {{-- Tombol buka keranjang di layar sempit --}}
            <button
                type="button"
                x-on:click="cartOpen = true"
                class="fixed bottom-4 right-4 z-30 flex items-center gap-2 rounded-full bg-primary-600 px-4 py-3 text-sm font-semibold text-white shadow-lg lg:hidden"
                x-show="!cartOpen"
            >
                Keranjang ({{ count($cart) }})
            </button>

            <div
                x-show="cartOpen"
                x-transition
                class="fixed inset-0 z-40 flex items-end bg-gray-950/50 lg:static lg:inset-auto lg:z-auto lg:block lg:bg-transparent"
                x-cloak
            >
                <div class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-4 shadow-lg ring-1 ring-gray-950/5 lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)] lg:rounded-2xl dark:bg-gray-900 dark:ring-white/10">
                    <div class="mb-3 flex items-center justify-between lg:hidden">
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">Keranjang</h2>
                        <button type="button" x-on:click="cartOpen = false" class="text-gray-500">Tutup</button>
                    </div>
                    <h2 class="mb-3 hidden text-base font-bold text-gray-950 lg:block dark:text-white">Keranjang</h2>

                    <div class="space-y-3">
                        @forelse ($cart as $key => $item)
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5" wire:key="cart-item-{{ $key }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ $item['product_name'] }} ({{ $item['variant_name'] }})
                                        </p>
                                        @foreach ($item['modifiers'] as $modifier)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $modifier['option_name'] }}</p>
                                        @endforeach
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $item['quantity'] }}x @ Rp {{ number_format($item['unit_price'] + $item['modifiers_total'], 0, ',', '.') }}
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removeFromCart('{{ $key }}')" class="text-xs font-medium text-danger-600">
                                        Hapus
                                    </button>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="updateCartQuantity('{{ $key }}', {{ $item['quantity'] - 1 }})"
                                            class="h-6 w-6 rounded bg-white text-sm font-bold ring-1 ring-gray-950/10 dark:bg-white/10 dark:ring-white/10"
                                        >-</button>
                                        <span class="w-6 text-center text-sm">{{ $item['quantity'] }}</span>
                                        <button
                                            type="button"
                                            wire:click="updateCartQuantity('{{ $key }}', {{ $item['quantity'] + 1 }})"
                                            class="h-6 w-6 rounded bg-white text-sm font-bold ring-1 ring-gray-950/10 dark:bg-white/10 dark:ring-white/10"
                                        >+</button>
                                    </div>
                                    <span class="text-sm font-bold text-gray-950 dark:text-white">
                                        Rp {{ number_format($item['line_total'], 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Keranjang masih kosong.</p>
                        @endforelse
                    </div>

                    <div class="my-4 border-t border-gray-950/10 dark:border-white/10"></div>

                    {{ $this->form }}

                    <div class="my-4 border-t border-gray-950/10 dark:border-white/10"></div>

                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="text-gray-950 dark:text-white">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Diskon</span>
                            <span class="text-gray-950 dark:text-white">-Rp {{ number_format((int) ($data['discount_amount'] ?? 0), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold">
                            <span class="text-gray-950 dark:text-white">Total</span>
                            <span class="text-gray-950 dark:text-white">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="save"
                        class="mt-4 w-full rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        Simpan Pesanan
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal pilih varian + modifier + jumlah --}}
    <div
        x-data="{ open: false }"
        x-on:open-modal.window="if ($event.detail.id === 'product-picker-modal') open = true"
        x-on:close-modal.window="if ($event.detail.id === 'product-picker-modal') open = false"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4"
    >
        <div
            x-show="open"
            x-transition
            x-on:click.outside="open = false"
            class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-xl dark:bg-gray-900"
        >
            @if ($this->modalProduct)
                <h2 class="text-base font-bold text-gray-950 dark:text-white">{{ $this->modalProduct->name }}</h2>

                {{-- Varian --}}
                <div class="mt-4">
                    <p class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">Varian</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->modalProduct->variants as $variant)
                            <button
                                type="button"
                                wire:click="selectVariant({{ $variant->id }})"
                                @class([
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                    'bg-primary-600 text-white' => $modalVariantId === $variant->id,
                                    'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' => $modalVariantId !== $variant->id,
                                ])
                            >
                                {{ $variant->name }} — Rp {{ number_format($variant->price, 0, ',', '.') }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Modifier groups --}}
                @foreach ($this->modalProduct->modifierGroups as $group)
                    <div class="mt-4">
                        <p class="mb-2 text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $group->name }}
                            @if ($group->is_required)
                                <span class="text-danger-600">*wajib</span>
                            @endif
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($group->options as $option)
                                @php
                                    $isSelected = in_array($option->id, $modalSelectedModifierOptionIds[$group->id] ?? [], true);
                                @endphp
                                <button
                                    type="button"
                                    wire:click="toggleModifierOption({{ $group->id }}, {{ $option->id }}, '{{ $group->selection_type->value }}')"
                                    @class([
                                        'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                        'bg-primary-600 text-white' => $isSelected,
                                        'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200' => ! $isSelected,
                                    ])
                                >
                                    {{ $option->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Jumlah dan harga --}}
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-950 dark:text-white">Jumlah</label>
                        <input
                            type="number"
                            min="1"
                            wire:model="modalQuantity"
                            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/10"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-950 dark:text-white">Harga Satuan</label>
                        <input
                            type="number"
                            min="0"
                            wire:model="modalUnitPrice"
                            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/10"
                        />
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        x-on:click="open = false"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        wire:click="addToCart"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        Tambah ke Keranjang
                    </button>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
