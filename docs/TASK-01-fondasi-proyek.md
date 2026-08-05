# TASK 01 — Fondasi Proyek, Skema Database, dan Autentikasi

## Peran dan Batasan

Kamu bertugas **mengimplementasikan**, bukan mengambil keputusan arsitektur.

Seluruh keputusan teknis dalam dokumen ini sudah final dan disepakati bersama Project Owner. Jika ada bagian yang ambigu, tidak lengkap, atau menurutmu keliru:

- **Jangan berimprovisasi.**
- Hentikan pekerjaan pada bagian itu.
- Tulis pertanyaan atau keberatanmu di laporan akhir.
- Lanjutkan bagian lain yang sudah jelas.

Jangan menambah fitur, tabel, kolom, paket, atau halaman yang tidak diminta dokumen ini.

---

## Konteks Proyek

Sistem operasional bisnis berbasis web untuk brand minuman buah personalisasi yang berjualan **100% online** (WhatsApp, GoFood, GrabFood, ShopeeFood).

Sistem mencatat seluruh pesanan dari semua kanal sebagai satu sumber kebenaran, dan dirancang untuk berkembang menjadi POS, manajemen stok bahan baku, dan pelaporan keuangan tanpa dibangun ulang.

Task ini adalah **fondasi**. Belum ada satu pun layar fungsional yang dibangun.

---

## Technology Stack (final, jangan diubah)

| Lapisan        | Pilihan                                         |
| -------------- | ----------------------------------------------- |
| Framework      | Laravel — versi stabil terbaru                  |
| Panel admin    | Filament — versi stabil terbaru yang kompatibel |
| Interaktivitas | Livewire + Alpine.js (bawaan Filament)          |
| Basis data     | PostgreSQL                                      |
| Akses data     | Eloquent                                        |
| Autentikasi    | Laravel bawaan, melalui panel Filament          |

Laporkan versi persis Laravel, Filament, dan PHP yang terpasang di laporan akhir.

---

## Ruang Lingkup Task Ini

### Yang DIKERJAKAN

1. Inisialisasi proyek Laravel baru
2. Instalasi dan konfigurasi Filament (satu panel: `admin`)
3. Seluruh migrasi database sesuai skema di bawah
4. Seluruh model Eloquent beserta relasi dan casting
5. Trait scoping organisasi
6. Berkas otorisasi terpusat
7. Seeder data awal
8. Satu feature test untuk transaksi penyimpanan pesanan

### Yang TIDAK DIKERJAKAN

- Filament Resource apa pun (menyusul di Task 02)
- Layar input pesanan
- Laporan, grafik, ekspor
- Pencetakan nota, ESC/POS, Bluetooth
- Stok, bahan baku, resep/BOM
- Impor file laporan platform
- Integrasi eksternal apa pun
- Fitur lupa kata sandi / pengiriman email

---

## Konvensi Wajib

### 1. Uang disimpan sebagai bilangan bulat rupiah

Semua kolom uang bertipe `bigInteger`, menyimpan nilai penuh dalam rupiah tanpa desimal.

Contoh: harga Rp 25.000 disimpan sebagai `25000`.

**Dilarang** memakai `float`, `double`, atau `decimal` untuk kolom uang. Pembulatan biner pada tipe pecahan menyebabkan selisih yang menumpuk dan membuat laporan keuangan tidak pernah balance.

Persentase komisi **bukan** uang dan memakai `decimal(5,2)`.

### 2. Tidak ada penghapusan data

- Master data (produk, varian, modifier, kategori, channel) dinonaktifkan lewat kolom `is_active`, tidak dihapus.
- Pesanan dibatalkan lewat kolom `status`, tidak dihapus.
- **Jangan** memakai SoftDeletes di mana pun.
- Seluruh foreign key memakai `restrictOnDelete()`.

### 3. Snapshot pada baris transaksi

Tabel `order_items` dan `order_item_modifiers` **menyalin** nama, harga jual, dan HPP saat transaksi terjadi.

Alasannya: ketika harga atau HPP master diperbarui bulan depan, laporan periode yang sudah lewat tidak boleh ikut berubah. Baris pesanan lama harus permanen.

Foreign key ke master tetap ada untuk keperluan analitik, tetapi **tampilan dan perhitungan selalu memakai kolom snapshot**, bukan relasi.

### 4. Pemisahan `occurred_at` dan `created_at`

- `occurred_at` — kapan transaksi benar-benar terjadi
- `created_at` — kapan baris dicatat ke sistem

Keduanya bisa berbeda jauh karena pencatatan dilakukan di akhir shift. **Seluruh laporan dan filter tanggal wajib memakai `occurred_at`.**

### 5. Scoping organisasi

Setiap tabel bisnis punya `organization_id`. Sistem saat ini single-tenant dengan satu organisasi, tetapi struktur ini disiapkan agar multi-store dan multi-organisasi tidak memerlukan migrasi di kemudian hari.

### 6. Penamaan

- Tabel: `snake_case` jamak
- Kolom: `snake_case`
- Model: `PascalCase` tunggal
- Enum disimpan sebagai `string` di database, dengan PHP enum sebagai cast

---

## Skema Database

### `organizations`

| Kolom      | Tipe   | Catatan |
| ---------- | ------ | ------- |
| id         | id     |         |
| name       | string |         |
| timestamps |        |         |

### `stores`

| Kolom           | Tipe               | Catatan      |
| --------------- | ------------------ | ------------ |
| id              | id                 |              |
| organization_id | FK → organizations | restrict     |
| name            | string             |              |
| is_active       | boolean            | default true |
| timestamps      |                    |              |

### `users`

Perluasan tabel users bawaan Laravel.

| Kolom             | Tipe               | Catatan         |
| ----------------- | ------------------ | --------------- |
| id                | id                 |                 |
| organization_id   | FK → organizations | restrict        |
| name              | string             |                 |
| email             | string             | unique          |
| email_verified_at | timestamp nullable |                 |
| password          | string             |                 |
| role              | string             | default `owner` |
| is_active         | boolean            | default true    |
| remember_token    |                    |                 |
| timestamps        |                    |                 |

PHP enum `UserRole` dengan satu case: `Owner = 'owner'`.

Nilai lain akan ditambahkan di masa depan. Jangan menambahkannya sekarang.

### `channels`

| Kolom           | Tipe                  | Catatan                                       |
| --------------- | --------------------- | --------------------------------------------- |
| id              | id                    |                                               |
| organization_id | FK → organizations    | restrict                                      |
| name            | string                | mis. "GoFood"                                 |
| code            | string                | mis. `gofood`; unique bersama organization_id |
| commission_rate | decimal(5,2) nullable | persen; belum dipakai di M1                   |
| sort_order      | integer               | default 0                                     |
| is_active       | boolean               | default true                                  |
| timestamps      |                       |                                               |

### `categories`

| Kolom           | Tipe               | Catatan      |
| --------------- | ------------------ | ------------ |
| id              | id                 |              |
| organization_id | FK → organizations | restrict     |
| name            | string             |              |
| sort_order      | integer            | default 0    |
| is_active       | boolean            | default true |
| timestamps      |                    |              |

### `products`

| Kolom           | Tipe                     | Catatan                         |
| --------------- | ------------------------ | ------------------------------- |
| id              | id                       |                                 |
| organization_id | FK → organizations       | restrict                        |
| category_id     | FK → categories nullable | restrict                        |
| name            | string                   | nama tampilan                   |
| receipt_name    | string(20)               | nama singkat untuk nota thermal |
| sort_order      | integer                  | default 0                       |
| is_active       | boolean                  | default true                    |
| timestamps      |                          |                                 |

`receipt_name` dibatasi 20 karakter karena nota thermal 58mm hanya memuat sekitar 32 karakter per baris.

### `product_variants`

| Kolom      | Tipe          | Catatan            |
| ---------- | ------------- | ------------------ |
| id         | id            |                    |
| product_id | FK → products | restrict           |
| name       | string        | mis. "Medium"      |
| price      | bigInteger    | harga jual, rupiah |
| cost_price | bigInteger    | HPP, rupiah        |
| sort_order | integer       | default 0          |
| is_active  | boolean       | default true       |
| timestamps |               |                    |

Setiap produk wajib punya minimal satu varian. Produk satu ukuran tetap memakai satu varian.

### `modifier_groups`

| Kolom           | Tipe               | Catatan                  |
| --------------- | ------------------ | ------------------------ |
| id              | id                 |                          |
| organization_id | FK → organizations | restrict                 |
| name            | string             | mis. "Level Gula"        |
| is_required     | boolean            | default false            |
| selection_type  | string             | `single` atau `multiple` |
| sort_order      | integer            | default 0                |
| is_active       | boolean            | default true             |
| timestamps      |                    |                          |

Grup modifier bersifat **global per organisasi**, bukan milik satu produk, sehingga "Level Gula" cukup didefinisikan sekali dan dipakai ulang oleh banyak produk.

### `modifier_options`

| Kolom             | Tipe                 | Catatan           |
| ----------------- | -------------------- | ----------------- |
| id                | id                   |                   |
| modifier_group_id | FK → modifier_groups | restrict          |
| name              | string               | mis. "Less Sugar" |
| price_delta       | bigInteger           | default 0, rupiah |
| sort_order        | integer              | default 0         |
| is_active         | boolean              | default true      |
| timestamps        |                      |                   |

`price_delta` sudah ada di database tetapi **belum akan muncul di antarmuka**. Jangan membangun UI untuknya.

### `product_modifier_group` (pivot)

| Kolom             | Tipe                 | Catatan   |
| ----------------- | -------------------- | --------- |
| id                | id                   |           |
| product_id        | FK → products        | cascade   |
| modifier_group_id | FK → modifier_groups | cascade   |
| sort_order        | integer              | default 0 |

Unique bersama: `product_id` + `modifier_group_id`.

### `orders`

| Kolom             | Tipe                | Catatan                                           |
| ----------------- | ------------------- | ------------------------------------------------- |
| id                | id                  |                                                   |
| organization_id   | FK → organizations  | restrict                                          |
| store_id          | FK → stores         | restrict                                          |
| channel_id        | FK → channels       | restrict                                          |
| order_number      | string              | unique bersama organization_id                    |
| external_order_id | string nullable     | ID pesanan dari platform                          |
| customer_name     | string nullable     | teks bebas, bukan relasi                          |
| occurred_at       | timestamp           | kapan transaksi terjadi                           |
| entry_mode        | string              | `manual` atau `import`; default `manual`          |
| status            | string              | `completed` atau `cancelled`; default `completed` |
| cancelled_reason  | text nullable       |                                                   |
| cancelled_at      | timestamp nullable  |                                                   |
| subtotal          | bigInteger          |                                                   |
| discount_amount   | bigInteger          | default 0                                         |
| total             | bigInteger          |                                                   |
| note              | text nullable       |                                                   |
| created_by        | FK → users          | restrict                                          |
| updated_by        | FK → users nullable | restrict                                          |
| timestamps        |                     |                                                   |

**Index yang wajib dibuat:**

- `(organization_id, occurred_at)`
- `(channel_id, occurred_at)`
- Partial unique index pada `(channel_id, external_order_id)` **hanya untuk baris dengan `external_order_id IS NOT NULL`**

Partial unique index tersebut belum terpakai di M1 karena seluruh input manual, tetapi menjadi pengaman anti-duplikat ketika importir laporan platform dibangun nanti.

`customer_name` sengaja berupa teks, bukan relasi ke tabel pelanggan. Tabel pelanggan baru dibuat ketika ada fitur yang benar-benar memakainya.

### `order_items`

| Kolom              | Tipe                  | Catatan             |
| ------------------ | --------------------- | ------------------- |
| id                 | id                    |                     |
| order_id           | FK → orders           | cascade             |
| product_variant_id | FK → product_variants | restrict            |
| product_name       | string                | snapshot            |
| variant_name       | string                | snapshot            |
| unit_price         | bigInteger            | snapshot harga jual |
| unit_cost          | bigInteger            | snapshot HPP        |
| quantity           | integer               |                     |
| modifiers_total    | bigInteger            | default 0           |
| line_total         | bigInteger            |                     |
| sort_order         | integer               | default 0           |

`unit_cost` adalah snapshot HPP dan **wajib ada**. Tanpa ini, memperbarui HPP master akan mengubah laporan margin periode yang sudah lewat.

Index pada `order_id`.

### `order_item_modifiers`

| Kolom              | Tipe                  | Catatan  |
| ------------------ | --------------------- | -------- |
| id                 | id                    |          |
| order_item_id      | FK → order_items      | cascade  |
| modifier_option_id | FK → modifier_options | restrict |
| group_name         | string                | snapshot |
| option_name        | string                | snapshot |
| price_delta        | bigInteger            | snapshot |

Index pada `order_item_id`.

### `settings`

| Kolom           | Tipe               | Catatan  |
| --------------- | ------------------ | -------- |
| id              | id                 |          |
| organization_id | FK → organizations | restrict |
| key             | string             |          |
| value           | text nullable      |          |
| timestamps      |                    |          |

Unique bersama: `organization_id` + `key`.

Menyimpan header dan footer nota, sakelar tampilan, serta pengaturan lain di masa depan. Isi nota **tidak boleh ditulis di dalam kode** — Project Owner harus bisa mengubahnya sendiri tanpa deploy ulang.

---

## Model Eloquent

Buat model untuk seluruh tabel di atas, lengkap dengan:

- Relasi dua arah (`hasMany`, `belongsTo`, `belongsToMany` untuk pivot)
- `$fillable` eksplisit — jangan memakai `$guarded = []`
- Casting untuk enum, boolean, dan datetime
- Kolom uang bertipe `integer` di casting

Buat PHP enum: `UserRole`, `EntryMode`, `OrderStatus`, `SelectionType`.

---

## Trait Scoping Organisasi

Buat trait `BelongsToOrganization` yang:

1. Menambahkan global scope memfilter query berdasarkan `organization_id` milik user yang sedang login
2. Mengisi otomatis `organization_id` saat pembuatan baris baru
3. Menyediakan relasi `organization()`

Terapkan pada seluruh model yang punya kolom `organization_id`.

Global scope ini terlihat tidak berguna sekarang karena hanya ada satu organisasi. Tujuannya adalah memastikan tidak ada satu pun query yang ditulis tanpa scoping, sehingga penambahan organisasi kedua di masa depan tidak memerlukan audit seluruh basis kode.

---

## Otorisasi Terpusat

Buat **satu** berkas yang menjadi satu-satunya tempat aturan izin didefinisikan (service provider khusus atau kelas support yang dipanggil dari `AuthServiceProvider`).

Saat ini isinya sederhana: peran `owner` boleh melakukan segalanya.

**Aturan keras:** tidak boleh ada pengecekan seperti `$user->role === 'owner'` di controller, model, view, Livewire component, atau Filament Resource mana pun. Seluruh pengecekan izin memanggil gate/policy.

Ketika peran kedua ditambahkan nanti, perubahan harus terjadi hanya di berkas ini.

---

## Seeder

Buat seeder yang menghasilkan:

1. Satu `organization`
2. Satu `store`
3. Satu `user` dengan role `owner` — email dan password diambil dari variabel `.env`, bukan ditulis di kode
4. Lima `channel`: WhatsApp (`whatsapp`), GoFood (`gofood`), GrabFood (`grabfood`), ShopeeFood (`shopeefood`), Lainnya (`lainnya`) — seluruhnya dengan `commission_rate` null
5. Beberapa baris `settings` kosong untuk header dan footer nota

**Jangan** membuat data produk contoh. Menu asli akan dimasukkan Project Owner sendiri.

---

## Feature Test

Buat satu feature test yang membuktikan integritas transaksi penyimpanan pesanan:

1. Buat produk, dua varian, satu grup modifier dengan dua opsi
2. Simpan satu pesanan berisi dua item berbeda, salah satunya dengan modifier — seluruhnya dalam satu `DB::transaction()`
3. Pastikan `subtotal`, `discount_amount`, dan `total` terhitung benar
4. Pastikan kolom snapshot terisi, bukan null
5. Buat satu kasus di mana penyimpanan item kedua gagal, lalu pastikan **tidak ada** baris `orders` maupun `order_items` yang tertinggal

Poin nomor 5 adalah inti dari test ini. Pesanan tanpa item adalah data rusak yang tidak akan pernah cocok saat direkonsiliasi dengan laporan platform.

---

## Kriteria Selesai

- [ ] `php artisan migrate:fresh --seed` berjalan bersih pada PostgreSQL
- [ ] Bisa login ke panel `/admin` memakai user hasil seeder
- [ ] Panel tampil tanpa error, dengan dashboard kosong
- [ ] Seluruh model punya relasi lengkap dan bisa diuji lewat tinker
- [ ] Feature test lulus, termasuk kasus rollback
- [ ] Tidak ada SoftDeletes di mana pun
- [ ] Tidak ada kolom uang bertipe float, double, atau decimal
- [ ] Tidak ada pengecekan role di luar berkas otorisasi
- [ ] `.env.example` terisi lengkap

---

## Laporan yang Harus Kamu Kirim

Setelah selesai, laporkan:

1. Versi persis Laravel, Filament, dan PHP yang terpasang
2. Struktur folder hasil akhir
3. Daftar berkas yang dibuat, dikelompokkan per kategori
4. Daftar paket yang diinstal beserta alasannya
5. Keputusan apa pun yang terpaksa kamu ambil karena dokumen ini kurang jelas — **beserta alasannya**
6. Bagian yang menurutmu keliru atau berisiko, jika ada
7. Hasil menjalankan test

Laporan ini akan ditinjau sebelum Task 02 dimulai.
