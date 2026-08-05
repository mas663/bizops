# TASK 02 — Modul Kelola Produk

## Peran dan Batasan

Sama seperti Task 01: kamu mengimplementasikan, bukan mengambil keputusan arsitektur. Jika ada bagian dokumen ini yang ambigu atau kurang lengkap, hentikan bagian itu, tulis pertanyaan di laporan akhir, lanjutkan bagian lain yang jelas.

Jangan menambah Resource, halaman, kolom, atau paket yang tidak diminta dokumen ini.

---

## Konteks

Task 01 (fondasi, skema, autentikasi) sudah selesai dan diterima, termasuk perbaikan bug rekursi pada `BelongsToOrganization`. Seluruh migrasi, model, dan trait dari Task 01 sudah ada — **jangan ubah strukturnya**, kecuali disebutkan eksplisit di bawah.

Task ini membangun antarmuka Filament untuk mengelola master data: kategori, produk, varian, dan modifier. Ini alat kerja untuk Project Owner mengisi menu sendiri — **bukan** tempat menu final ditulis oleh Claude Code.

---

## Ruang Lingkup

### Yang DIKERJAKAN

1. Filament Resource: Category
2. Filament Resource: Product (dengan varian sebagai relasi bersarang)
3. Filament Resource: ModifierGroup (dengan opsi sebagai relasi bersarang)
4. Penghubung produk ke modifier group pada form Product
5. Seeder data dummy (lihat bagian khusus di bawah)
6. Navigasi/menu sidebar yang rapi untuk ketiga Resource

### Yang TIDAK DIKERJAKAN

- Resource untuk Channel, Order, Settings, User (menyusul di task lain)
- Layar input pesanan
- Laporan apa pun
- Field harga pada modifier option di antarmuka (lihat aturan di bawah)
- Upload gambar produk — belum diminta, jangan ditambahkan sendiri

---

## Aturan Wajib

### 1. `price_delta` pada modifier option TETAP TERSEMBUNYI dari form

Kolom ini sudah ada di database sejak Task 01. **Jangan tampilkan input untuk kolom ini di form ModifierOption.** Simpan otomatis dengan nilai `0` saat membuat opsi baru lewat Filament.

Alasan: keputusan bisnis apakah personalisasi (base, extra) memengaruhi harga belum final di sisi Project Owner. Field ini akan diaktifkan di task terpisah setelah keputusan itu dibuat, tanpa migrasi karena kolomnya sudah ada.

### 2. Grup modifier bersifat global, dihubungkan lewat pemilihan multi-select pada form Product

Saat mengedit sebuah Product, Owner memilih grup modifier mana saja yang berlaku untuk produk itu (misalnya "Sweetness" dicentang untuk hampir semua produk, tapi tidak untuk produk seperti Kunafa Pistachio yang bukan minuman).

Ini **wajib opsional** — sebuah produk sah-sah saja tidak punya modifier group sama sekali.

### 3. Varian dikelola sebagai relasi bersarang di dalam form Product (Filament Repeater atau Relation Manager — pilih yang menurutmu paling sesuai dengan pola Filament v4, jelaskan pilihanmu di laporan)

Field per varian: nama, harga jual, HPP, urutan, aktif — sesuai skema Task 01.

**Validasi wajib:** setiap produk harus disimpan dengan minimal satu varian aktif. Kalau form disubmit tanpa varian sama sekali, tolak dengan pesan error yang jelas.

### 4. Kolom uang di form memakai input rupiah yang mudah dibaca

Format tampilan boleh memakai pemisah ribuan (misalnya `25.000`), tapi nilai yang tersimpan ke database tetap bilangan bulat murni (`25000`), sesuai aturan Task 01. Pastikan parsing dari tampilan ke database tidak meleset.

### 5. `receipt_name` wajib diisi, maksimal 20 karakter, validasi di level form

Ingatkan lewat placeholder atau helper text bahwa field ini dipakai untuk nota thermal 58mm.

### 6. Tabel index (list view) masing-masing Resource

- **Category**: nama, urutan, jumlah produk, status aktif — bisa diurutkan lewat drag
- **Product**: nama, kategori, jumlah varian, status aktif
- **ModifierGroup**: nama, wajib/opsional, tipe pilihan (tunggal/ganda), jumlah opsi, status aktif

Semua wajib punya filter status aktif, dan pencarian berdasarkan nama.

### 7. Tidak ada tombol hapus permanen di mana pun

Sesuai prinsip Task 01: nonaktifkan (`is_active = false`), jangan hapus. Filament punya aksi delete bawaan pada tabel — **nonaktifkan/sembunyikan aksi itu** di ketiga Resource, ganti dengan toggle status aktif sebagai aksi utama.

---

## Data Dummy (Seeder)

Ini **bukan** menu final — data karangan untuk menguji struktur, terutama untuk memastikan skema menangani ketidakpastian "Base" yang sedang dieksplorasi Project Owner (water-based vs milk-based belum diputuskan).

Buat seeder terpisah `DummyMenuSeeder` (jangan digabung ke `DatabaseSeeder` utama — panggil manual saat dibutuhkan), berisi:

**Kategori:**

- Fresh Juice
- Smoothies
- Seasonal

**Grup Modifier:**

1. **Sweetness** — wajib, pilihan tunggal. Opsi: No Sugar, Less Sugar, Normal
2. **Base** — tidak wajib, pilihan tunggal. Opsi: Water Based, Fresh Milk, Oat Milk, Coconut Water
3. **Extra** — tidak wajib, pilihan ganda. Opsi: Chia Seed, Oat, Protein, Extra Fruit

**Produk (tiga, sengaja berbeda pola untuk menguji fleksibilitas struktur):**

1. **Jus Mangga** (kategori Fresh Juice)
    - Varian: S (18.000 / HPP 8.000), M (22.000 / HPP 10.000), L (26.000 / HPP 12.000)
    - Modifier: Sweetness saja

2. **Smoothie Mangga Yogurt** (kategori Smoothies)
    - Varian: S (25.000 / HPP 12.000), M (29.000 / HPP 14.000), L (33.000 / HPP 16.000)
    - Modifier: Sweetness, Base, Extra — ketiganya

3. **Kunafa Pistachio Fruit** (kategori Seasonal)
    - Varian: hanya satu ukuran, namai "Reguler" (35.000 / HPP 18.000)
    - Modifier: tidak ada satu pun

Produk ketiga ini sengaja dipakai untuk membuktikan bahwa satu ukuran tetap valid meski aturan umum menyebut S/M/L, dan bahwa produk tanpa modifier apa pun tetap tersimpan dan tertampil dengan benar.

---

## Kriteria Selesai

- [ ] Ketiga Resource bisa diakses dari sidebar `/admin`
- [ ] Bisa membuat Category baru dari nol lewat UI
- [ ] Bisa membuat Product baru lengkap dengan minimal satu varian dari satu form yang sama
- [ ] Mencoba menyimpan Product tanpa varian menghasilkan error yang jelas, bukan tersimpan kosong
- [ ] Bisa membuat ModifierGroup beserta opsinya, dan `price_delta` tidak muncul di form sama sekali
- [ ] Bisa mencentang beberapa ModifierGroup pada satu Product
- [ ] `DummyMenuSeeder` berjalan tanpa error dan menghasilkan persis: 3 kategori, 3 grup modifier, 3 produk dengan total 7 varian
- [ ] Produk "Kunafa Pistachio Fruit" tampil normal di list meski hanya satu varian dan tanpa modifier
- [ ] Tidak ada aksi hapus permanen aktif di ketiga Resource
- [ ] `php artisan test` tetap lulus seluruhnya (test Task 01 tidak boleh rusak)

---

## Laporan yang Harus Kamu Kirim

1. Pendekatan yang dipilih untuk mengelola varian bersarang (Repeater vs Relation Manager) dan alasannya
2. Daftar Resource dan file pendukung yang dibuat
3. Screenshot atau deskripsi hasil akhir form Product (karena ini form paling kompleks di task ini)
4. Keputusan yang terpaksa diambil karena dokumen kurang eksplisit, beserta alasan
5. Hasil `php artisan test`
6. Konfirmasi jumlah data dummy sesuai kriteria selesai di atas
