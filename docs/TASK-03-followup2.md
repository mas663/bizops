# TASK 03 — Follow-up 2: Perbaikan Bug Input Uang, Search, Konsistensi Kartu

## Peran dan Batasan

Sama seperti sebelumnya: implementasikan, jangan menebak jika ambigu.

---

## 1. PRIORITAS TERTINGGI — Bug input angka pada field uang

**Reproduksi bug yang dilaporkan:** pada field HPP di form Produk, mengetik "333" lalu menambah "3" (bermaksud jadi "3333") hasilnya malah menjadi "3000333".

Ini bug integritas data finansial, bukan sekadar tampilan — proyek ini bergantung pada presisi rupiah sebagai bilangan bulat (ditetapkan sejak Task 01). Kalau nilai yang tersimpan ke database sudah salah sejak input, seluruh laporan margin di masa depan ikut tercemar tanpa ada yang sadar.

**Langkah wajib:**

1. Temukan akar penyebabnya di komponen/logic formatting angka rupiah (kemungkinan besar di logika yang menyisipkan pemisah ribuan saat mengetik — cek bagaimana posisi kursor dan parsing ulang nilai ditangani setiap keystroke)
2. **Audit SEMUA field uang di aplikasi**, bukan cuma HPP di form Produk:
    - Harga Jual dan HPP per varian (form Produk)
    - Field override harga satuan di modal Input Pesanan (Task 03)
    - Field Diskon di Input Pesanan
3. Perbaiki akar masalahnya di satu tempat kalau logikanya memang dipakai bersama (jangan tambal berulang di tiap field kalau sumbernya sama)
4. **Tulis test yang mensimulasikan pengetikan bertahap** (bukan cuma mengisi value sekali jadi) untuk field ini — reproduksi persis skenario yang dilaporkan: ketik "3", "3", "3", lalu "3" lagi, pastikan hasil akhirnya "3333", bukan angka lain

## 2. Tambahkan input pencarian di halaman Input Pesanan

Sama seperti pencarian yang sudah ada di halaman Produk (Kelola Produk) — pengguna bisa mengetik nama produk untuk menyaring grid produk yang tampil, bekerja berdampingan dengan Tabs kategori yang sudah ada (kombinasi keduanya: filter kategori aktif + kata kunci pencarian).

## 3. Konsistensi tinggi kartu produk saat nama dua baris

Kartu produk dengan nama panjang (bungkus ke 2 baris) saat ini punya tata letak yang berbeda dari kartu dengan nama satu baris — foto bergeser posisi, tinggi kartu tidak seragam dalam satu baris grid.

**Perbaikan:** area nama produk diberi tinggi tetap yang cukup untuk 2 baris (gunakan `line-clamp-2` atau setara, dengan tinggi kontainer nama yang konsisten baik nama pendek maupun panjang). Foto tetap di posisi yang sama persis di semua kartu, apa pun panjang namanya.

**Terapkan ke SEMUA tempat kartu produk dipakai** — grid Produk di Kelola Produk, dan grid pemilihan produk di Input Pesanan. Kalau kartu ini belum memakai satu partial/komponen Blade yang sama di kedua tempat, pertimbangkan menyatukannya jadi satu komponen supaya perbaikan ini (dan perbaikan masa depan) otomatis konsisten di semua tempat — jelaskan pendekatan yang dipilih di laporan.

---

## Kriteria Selesai

- [ ] Mengetik angka bertahap pada field HPP/Harga Jual/Diskon/harga override menghasilkan nilai yang benar, sesuai urutan ketik
- [ ] Test simulasi pengetikan bertahap ditulis dan lulus
- [ ] Halaman Input Pesanan punya input pencarian produk yang bekerja bersama filter kategori
- [ ] Seluruh kartu produk (di Kelola Produk maupun Input Pesanan) punya tinggi dan posisi foto yang konsisten, apa pun panjang nama produknya
- [ ] `php artisan test` lulus seluruhnya

## Laporan yang Harus Dikirim

1. Akar penyebab bug input angka (jelaskan spesifik, sertakan contoh sebelum/sesudah)
2. Konfirmasi field mana saja yang ternyata terpengaruh bug yang sama
3. Hasil `php artisan test`
