# TASK 03 — Follow-up 1: Perbaiki Halaman Tidak Bertema + Penamaan Field

## Peran dan Batasan

Sama seperti sebelumnya: implementasikan, jangan menebak jika ambigu.

---

## Konteks

Halaman Input Pesanan (Task 03) tampil sama sekali tanpa styling — teks polos, tanpa kartu, tanpa tombol, tanpa warna. Sementara Resource lain (Kategori, Produk, Grup Modifier) tampil normal dengan tema Filament yang sudah benar.

## 1. Diagnosis dan Perbaikan Utama (WAJIB DIPERIKSA LEBIH DULU)

Periksa apakah Blade view halaman Input Pesanan dibungkus dengan benar oleh layout panel Filament — pastikan root elemen memakai komponen layout resmi Filament v4 untuk custom page (mis. `<x-filament-panels::page>` atau setara sesuai versi yang terpasang), sama seperti yang dipakai halaman-halaman Resource yang sudah tampil benar.

Kemungkinan penyebab lain yang perlu dicek satu per satu kalau pembungkus layout ternyata sudah benar:

- Apakah class Page menetapkan `$view` yang tepat, mengarah ke file Blade yang benar
- Apakah ada aset (CSS/JS) yang butuh dikompilasi ulang (`npm run build`) setelah komponen Alpine ditambahkan
- Apakah ada elemen HTML mentah di luar komponen Blade Filament yang sengaja tidak memakai class Tailwind

**Jangan menambal dengan menulis ulang seluruh tampilan dari nol.** Cari akar masalahnya, karena kemungkinan besar ini satu baris atau satu file yang salah, bukan kesalahan struktural besar. Begitu diperbaiki, halaman ini seharusnya otomatis mewarisi warna, tipografi, dan komponen yang sama dengan Resource lain — karena keduanya berbagi tema panel yang sama.

## 2. Ganti label "Waktu Terjadi" → "Waktu Pesanan"

Field ini merujuk ke kolom `occurred_at`, tapi label yang tampil ke pengguna harus "Waktu Pesanan", bukan nama teknis kolom.

## 3. Audit seluruh label di halaman ini (termasuk modal pemilihan varian/modifier)

Screenshot yang tersedia belum menunjukkan isi modal (tertutup saat screenshot diambil). Periksa seluruh label di dalam modal pemilihan varian, modifier, dan jumlah — pastikan berbahasa Indonesia yang wajar dan konsisten dengan pola yang sudah dipakai di Task 02 (mis. "Jumlah" bukan "Quantity", "Harga Satuan" bukan "Unit Price", dst., sesuaikan dengan istilah yang natural untuk pemilik warung, bukan istilah teknis).

## 4. Verifikasi visual setelah perbaikan

Setelah perbaikan poin 1, halaman ini seharusnya terlihat konsisten dengan kartu produk di Kelola Produk (foto portrait, styling kartu yang sama), tombol bergaya sama seperti tombol "Tambah Produk" di Resource lain, dan keranjang tampil sebagai panel dengan border/shadow yang jelas — bukan daftar teks polos.

---

## Kriteria Selesai

- [ ] Halaman Input Pesanan tampil dengan tema yang identik dengan Resource lain (warna, tipografi, spacing, komponen kartu/tombol)
- [ ] Label "Waktu Terjadi" diganti "Waktu Pesanan"
- [ ] Seluruh label di dalam modal pemilihan varian/modifier diaudit dan dalam Bahasa Indonesia yang natural
- [ ] Tabs kategori tampil sebagai pill/tab bergaya, bukan teks polos
- [ ] Kartu produk di grid pemilihan tampil dengan styling yang sama seperti di Kelola Produk
- [ ] `php artisan test` tetap lulus seluruhnya

## Laporan yang Harus Dikirim

1. Akar penyebab halaman tidak bertema (jelaskan spesifik, bukan cuma "sudah diperbaiki")
2. Screenshot halaman setelah perbaikan: grid produk, modal terbuka, keranjang terisi
3. Daftar label yang diubah dari audit poin 3
4. Hasil `php artisan test`
