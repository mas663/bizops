# TASK 03 — Order Entry (Input Pesanan)

## Peran dan Batasan

Sama seperti task sebelumnya: implementasikan, jangan mengambil keputusan arsitektur. Kalau ambigu, tulis pertanyaan di laporan, jangan menebak.

Task ini lebih kompleks dari Task 01/02 — ambil waktu untuk membaca seluruh dokumen sebelum mulai menulis kode.

---

## Konteks

Task 01 (fondasi, skema, auth) dan Task 02 (Kelola Produk, lengkap 4 follow-up) sudah selesai dan diterima. Skema `orders`, `order_items`, `order_item_modifiers`, `channels` sudah ada sejak Task 01 — **jangan ubah strukturnya**.

Task ini membangun layar tempat Owner mencatat pesanan yang masuk dari WhatsApp, GoFood, GrabFood, ShopeeFood — semuanya dicatat manual di akhir shift, bukan real-time per pesanan (konteks bisnis: 100% online, tidak ada walk-in).

## Kenapa Ini BUKAN Filament Resource Biasa

Sudah diputuskan sejak awal proyek (Topik 10): layar ini adalah **halaman kustom Filament** (`Filament\Pages\Page`), bukan Resource CRUD standar. Alasannya: mengisi satu pesanan melibatkan alur "pilih produk → pilih varian → pilih modifier → atur jumlah → tambah ke keranjang → ulangi untuk item lain → isi info pesanan → simpan sekali" — pola keranjang yang tidak cocok dengan form Filament standar satu-record-satu-form.

Gunakan Livewire (terintegrasi dalam Filament Page) untuk mengelola state keranjang, dengan Alpine.js untuk interaksi UI yang instan (buka modal pilih varian, dsb) tanpa round-trip server yang tidak perlu.

---

## Ruang Lingkup

### Yang DIKERJAKAN

1. Satu halaman kustom Filament: "Input Pesanan" (buat, bukan lihat/edit pesanan lama)
2. Grid pemilihan produk (reuse tampilan kartu dari Task 02: foto portrait, nama, kategori, harga)
3. Modal/panel pemilihan varian + modifier + jumlah per produk yang diklik
4. Tampilan keranjang: daftar item ditambahkan, dengan hapus dan ubah jumlah
5. Form info pesanan: channel, waktu terjadi, nama pelanggan, diskon, catatan
6. Aksi simpan: satu transaksi database menulis `orders` + `order_items` + `order_item_modifiers`
7. Setelah simpan sukses: reset keranjang, tampilkan notifikasi sukses, form siap untuk pesanan berikutnya (channel tetap ter-default ke pilihan sebelumnya)

### Yang TIDAK DIKERJAKAN

- Melihat, mengedit, atau membatalkan pesanan yang sudah tersimpan (halaman Order List — menyusul di Task 04)
- Pencetakan nota, ESC/POS, Bluetooth apa pun
- Laporan atau ringkasan omzet
- Resource untuk Channel (data channel sudah ada dari seeder Task 01 — cukup dipakai, jangan buat CRUD untuk mengelolanya)
- Import pesanan dari file apa pun

---

## Alur Kerja Pengguna (Referensi)

1. Owner membuka halaman Input Pesanan
2. Melihat grid produk aktif (dengan foto, nama, kategori, harga mulai dari — gunakan tampilan yang sama seperti list Produk di Task 02)
3. Klik satu produk → muncul panel/modal: pilih varian (wajib), pilih modifier per grup (yang `is_required` wajib dipilih sebelum bisa ditambah), atur jumlah, harga per item **bisa ditimpa manual**
4. Klik "Tambah ke Keranjang" → item masuk daftar keranjang di sisi halaman, modal tertutup, kembali ke grid produk (bisa klik produk lain)
5. Ulangi untuk seluruh item dalam satu pesanan
6. Isi info pesanan: channel (wajib), waktu terjadi (default sekarang, bisa diundur), nama pelanggan (opsional), diskon (opsional), catatan (opsional)
7. Klik "Simpan Pesanan" → tersimpan, keranjang kosong, siap untuk pesanan berikutnya

---

## Aturan Wajib

### 1. Modifier wajib memblokir penambahan ke keranjang

Kalau sebuah produk terhubung ke satu atau lebih `ModifierGroup` dengan `is_required = true`, item tidak bisa ditambahkan ke keranjang sebelum grup itu punya pilihan. Modifier yang tidak wajib boleh dikosongkan.

Selection type `single` — hanya satu opsi bisa dipilih dalam grup itu. Selection type `multiple` — boleh lebih dari satu.

### 2. Harga bisa ditimpa manual per item

Field harga satuan pada saat menambah ke keranjang **default terisi dari harga varian**, tapi Owner bisa mengubahnya secara manual sebelum menambah ke keranjang. Ini disengaja — harga di setiap channel bisa berbeda dan promo sering terjadi (sudah diputuskan sejak Topik 2).

### 3. Snapshot wajib benar

Saat pesanan disimpan, `order_items` menyalin `product_name`, `variant_name`, `unit_price` (harga final setelah ditimpa jika ada), `unit_cost` (HPP dari varian saat itu — **bukan** harga yang ditimpa), `quantity`. `order_item_modifiers` menyalin `group_name`, `option_name`, `price_delta`.

**Penting:** `unit_cost` selalu ikut harga HPP asli dari varian, tidak terpengaruh oleh override harga jual. Override hanya mengubah `unit_price`.

### 4. Perhitungan total

- `line_total` per item = `(unit_price + total price_delta modifier terpilih) × quantity`
- `subtotal` pesanan = jumlah seluruh `line_total`
- `total` = `subtotal - discount_amount`
- Seluruh perhitungan dilakukan di server saat submit (jangan percaya angka dari klien mentah-mentah) — Livewire component menghitung ulang dari data produk/varian/modifier asli di database berdasarkan ID yang dipilih, bukan dari angka yang dikirim browser, **kecuali** untuk `unit_price` yang memang sengaja bisa ditimpa (override ini yang disimpan, bukan dihitung ulang)

### 5. Transaksi database

Satu `DB::transaction()` untuk seluruh proses simpan: buat `Order`, lalu seluruh `OrderItem` dan `OrderItemModifier`-nya. Gagal di tengah jalan → seluruhnya batal, tidak ada baris tertinggal. Ini persis pola yang sudah diuji di Task 01 — pertahankan level integritas yang sama.

### 6. Field yang diisi otomatis, bukan oleh pengguna

- `organization_id`, `store_id` — dari user yang login (lewat trait `BelongsToOrganization` atau setara)
- `entry_mode` — selalu `manual`
- `status` — selalu `completed`
- `created_by` — user yang login
- `order_number` — buat nomor unik yang mudah dibaca (mis. format tanggal + urutan, seperti `260805-001`), pastikan unik per organisasi

### 7. Channel default: mengingat pilihan terakhir

Saat halaman dibuka atau setelah pesanan tersimpan, field channel di-default ke channel yang dipakai pada pesanan terakhir yang dibuat oleh user yang sedang login (bukan channel terakhir siapa pun). Kalau belum pernah ada pesanan sama sekali, kosongkan (wajib dipilih manual pertama kali).

### 8. Waktu terjadi (`occurred_at`)

Default ke waktu saat ini (`now()`), tapi **wajib bisa diundur** ke waktu sebelumnya — karena pencatatan dilakukan di akhir shift, bukan real-time. Gunakan date-time picker yang membolehkan input mundur, tidak boleh membatasi ke "hari ini saja".

### 9. Validasi minimum

- Pesanan wajib punya minimal satu item sebelum bisa disimpan
- Channel wajib dipilih
- Quantity per item minimal 1
- Diskon tidak boleh membuat total menjadi negatif

---

## Desain Tampilan

Ikuti gaya visual yang sudah ditetapkan di Task 02: kartu produk dengan foto portrait di atas, nama-kategori-harga di bawah. Gunakan komponen/partial yang sama kalau memungkinkan, supaya tidak ada dua versi kartu produk yang berbeda gaya di aplikasi yang sama.

Tata letak yang disarankan (boleh disesuaikan menurut kebiasaan Filament, jelaskan kalau berbeda):

```
┌─────────────────────────────┬──────────────────┐
│  [Tabs kategori, sama        │   KERANJANG       │
│   seperti di Kelola Produk]  │                   │
│                              │  Jus Mangga (M)   │
│  [Grid kartu produk]         │   No Sugar        │
│                              │   2x @22.000       │
│                              │           44.000  │
│                              │  [Hapus] [Qty: 2] │
│                              │                   │
│                              │  ─────────────    │
│                              │  Channel: [___]   │
│                              │  Waktu: [_______] │
│                              │  Nama: [________] │
│                              │  Diskon: [______] │
│                              │  Catatan:[______] │
│                              │                   │
│                              │  Subtotal  85.000 │
│                              │  Diskon    -5.000 │
│                              │  TOTAL     80.000 │
│                              │                   │
│                              │ [Simpan Pesanan]  │
└─────────────────────────────┴──────────────────┘
```

Mobile-first: di layar sempit (HP), keranjang bisa jadi panel yang bisa dibuka/tutup (bottom sheet atau collapsible), bukan dua kolom berdampingan yang memaksa scroll horizontal.

---

## Kriteria Selesai

- [ ] Halaman Input Pesanan bisa diakses dari sidebar
- [ ] Grid produk hanya menampilkan produk aktif dengan minimal satu varian aktif
- [ ] Klik produk membuka pemilihan varian + modifier + jumlah
- [ ] Modifier wajib memblokir tombol tambah ke keranjang sampai terisi
- [ ] Harga bisa ditimpa manual sebelum ditambah ke keranjang
- [ ] Keranjang menampilkan item yang ditambahkan, bisa dihapus dan diubah jumlahnya
- [ ] Total dihitung benar termasuk modifier dan diskon
- [ ] Channel default mengikuti pesanan terakhir milik user yang login
- [ ] Waktu terjadi bisa diundur, tidak dibatasi hari ini saja
- [ ] Simpan berhasil menulis satu Order + seluruh OrderItem + OrderItemModifier dalam satu transaksi
- [ ] Snapshot (`product_name`, `variant_name`, `unit_price`, `unit_cost`, `group_name`, `option_name`, `price_delta`) terisi benar, bukan null
- [ ] `unit_cost` selalu dari HPP varian asli, tidak terpengaruh override harga jual
- [ ] Gagal simpan di tengah jalan (uji dengan skenario serupa test Task 01) tidak meninggalkan baris parsial
- [ ] Setelah simpan sukses, keranjang kosong dan siap untuk pesanan berikutnya, channel tetap ter-default
- [ ] Validasi: tidak bisa simpan tanpa item, tanpa channel, dengan quantity < 1, atau diskon melebihi subtotal
- [ ] Tidak ada Resource/CRUD baru untuk Channel
- [ ] `php artisan test` lulus seluruhnya termasuk test regresi Task 01 & 02

## Test yang Wajib Ditulis

1. Simpan pesanan dengan dua item berbeda, salah satunya punya modifier — pastikan snapshot dan total benar (mirip test Task 01, tapi lewat alur halaman ini)
2. Item dengan harga ditimpa manual — pastikan `unit_price` tersimpan sesuai override, tapi `unit_cost` tetap dari HPP varian asli
3. Modifier wajib tidak terisi — tombol tambah ke keranjang harus terblokir/tervalidasi
4. Kegagalan di tengah proses simpan (simulasikan) — pastikan tidak ada baris `orders` atau `order_items` yang tertinggal
5. Channel default mengikuti pesanan terakhir user yang login — buat dua user berbeda, pastikan default tidak tertukar antar user
6. Diskon melebihi subtotal ditolak validasi

## Laporan yang Harus Dikirim

1. Pendekatan yang dipilih untuk state keranjang (Livewire component properties, session, dll) dan alasannya
2. Bagaimana modal/panel pemilihan varian-modifier diimplementasikan
3. Keputusan yang terpaksa diambil karena dokumen kurang eksplisit, beserta alasan
4. Hasil `php artisan test`
