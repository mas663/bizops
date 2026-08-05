# TASK 02 — Follow-up 3: Konsistensi List/Form + Redesain Kartu Product

## Peran dan Batasan

Sama seperti task sebelumnya: implementasikan, jangan mengambil keputusan arsitektur. Kalau ada bagian ambigu, tulis di laporan, jangan menebak.

---

## Berlaku untuk KETIGA Resource (Category, Product, ModifierGroup)

### 1. Klik baris membuka Edit, hapus tombol Edit terpisah

Pasang `->recordUrl()` pada masing-masing table, arahkan ke halaman edit resource itu sendiri. Hapus `Tables\Actions\EditAction` dari kolom aksi.

### 2. Hapus kolom urutan manual dari form, di mana pun masih ada

Cek form Category dan ModifierGroup — kemungkinan masih punya input `sort_order` manual seperti yang sudah dihapus dari Product form di Follow-up 2. Terapkan pola yang sama: hapus dari form, kolom database tetap ada, default di-set lewat model event `creating` (samakan dengan pendekatan `Product::booted()` yang sudah ada).

Default sort semua table: alfabetis berdasarkan `name` ascending.

### 3. Sembunyikan tombol nonaktifkan/aktifkan dari list

Cek Category dan ModifierGroup — terapkan pola yang sama seperti sudah dilakukan pada Product di Follow-up 2 (aksi toggle status hanya ada di form Edit, tidak di list).

### 4. Filter status aktif/nonaktif

Ganti perilaku default: query dasar table hanya menampilkan `is_active = true`. Tambahkan filter (boleh `Tables\Filters\TernaryFilter` atau toggle sederhana) berlabel "Tampilkan yang nonaktif" untuk melihat seluruhnya termasuk yang nonaktif. Terapkan ke ketiga Resource.

**Perhatikan label pilihan filter ini sendiri** — dari screenshot saat ini, dropdown "Status Aktif" menampilkan pilihan "Yes" dalam bahasa Inggris. Kalau memakai `TernaryFilter`, override label `true`/`false`-nya ke Bahasa Indonesia (mis. "Aktif" / "Nonaktif"), jangan biarkan default bawaan Filament.

### 5. Audit dan perbaiki sisa label berbahasa Inggris

Cek breadcrumb, tombol, placeholder, judul kolom di ketiga Resource. Contoh yang sudah diketahui dari task sebelumnya (New → Tambah, List → Daftar, Search → Cari) — cari pola serupa yang belum tersentuh, termasuk pesan validasi dan notifikasi sukses/gagal.

**Temuan konkret yang wajib diperbaiki:** komponen `FileUpload` pada field Foto Produk masih menampilkan teks bawaan "Drag & Drop your files or Browse". Ganti ke Bahasa Indonesia yang setara (mis. "Seret & lepas file di sini, atau Jelajahi") lewat opsi konfigurasi FileUpload yang tersedia (`->uploadingMessage()`, publish/override file translation, atau override lang bawaan Filament — pilih cara yang paling sesuai pola FileUpload di Filament v4, jelaskan pilihanmu di laporan).

### 6. Varian: hapus input angka "Urutan", pertahankan drag handle

Repeater Varian pada form Product saat ini punya **dua mekanisme urutan sekaligus**: drag handle (↑↓) DAN input angka "Urutan" manual per baris — redundan, sama seperti masalah yang sudah diperbaiki di level Product/Category.

**Penting: solusinya BUKAN alfabetis di sini.** Urutan varian (S, M, L) punya makna bisnis (kecil ke besar) yang akan salah kalau diurutkan alfabetis (L lebih dulu dari S secara alfabet). Drag-and-drop yang sudah ada sudah cukup untuk mengatur ini secara manual sesekali saat produk dibuat — yang dihapus hanya **input angka eksplisitnya**, bukan mekanisme drag maupun kolom `sort_order` di database.

---

## Khusus Product

### 6. Filter kategori sebagai Tabs, bukan dropdown

Override `getTabs()` pada halaman List Product. Tab pertama "Semua", diikuti satu tab per Category yang aktif (urut alfabetis), dibuat dinamis dari data — jangan hardcode nama kategori.

### 7. Redesain kartu grid Product

Tata letak baru, gantikan tata letak `aspect-square` dominan dari Follow-up 2:

```
┌─────────────────┐
│                  │
│   [Foto 3:4]     │   ← rasio portrait (mis. aspect-[3/4]),
│                  │      rata tengah, lebar penuh kartu
├─────────────────┤
│ Nama Produk      │   ← rata kiri, bold
│ Kategori   Harga │   ← satu baris: kategori rata kiri,
└─────────────────┘      harga rata kanan, harga bold
```

Kalau foto belum diupload (nullable, sesuai skema), tampilkan placeholder rapi — jangan biarkan kartu terlihat rusak/kosong.

---

## Routing

### 8. Redirect root URL ke /admin

Di `routes/web.php`, ubah agar mengakses `/` langsung mengarahkan (redirect) ke `/admin`. Hapus atau nonaktifkan halaman welcome bawaan Laravel yang sekarang tampil di root.

---

## Kriteria Selesai

- [ ] Klik baris pada ketiga table membuka halaman edit, tanpa tombol Edit terpisah
- [ ] Tidak ada input urutan manual di form Category/ModifierGroup; kolom database tetap utuh
- [ ] Input angka "Urutan" pada baris Varian di form Product dihapus; drag handle tetap berfungsi
- [ ] Label filter "Status Aktif" berbahasa Indonesia (bukan "Yes"/"No")
- [ ] Teks widget upload foto ("Drag & Drop your files or Browse") sudah dilokalisasi
- [ ] Tidak ada tombol nonaktifkan/aktifkan di ketiga list
- [ ] Default table hanya menampilkan data aktif, dengan opsi menampilkan semua
- [ ] Tidak ada string berbahasa Inggris yang tersisa di ketiga Resource (breadcrumb, tombol, placeholder, notifikasi)
- [ ] Tab kategori di halaman Product List dinamis mengikuti data, termasuk tab "Semua"
- [ ] Kartu Product sesuai tata letak baru: foto portrait atas, nama-kategori-harga di bawah
- [ ] Kartu tanpa foto tetap tampil rapi
- [ ] Mengakses `http://127.0.0.1:8000/` langsung menampilkan panel admin (redirect ke `/admin`), bukan halaman welcome Laravel
- [ ] Seluruh test lama disesuaikan dengan perubahan default query (aktif-saja) dan hilangnya tombol Edit/Nonaktifkan terpisah
- [ ] `php artisan test` lulus seluruhnya

## Laporan yang Harus Dikirim

1. Konfirmasi ketiga Resource sudah konsisten (bukan cuma Product)
2. Screenshot atau deskripsi tata letak kartu Product baru
3. Keputusan yang terpaksa diambil karena ambigu, beserta alasan
4. Hasil `php artisan test`
