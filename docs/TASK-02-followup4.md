# TASK 02 — Follow-up 4: Bersihkan Duplikasi Filter & Kolom Urutan

## Peran dan Batasan

Sama seperti sebelumnya: implementasikan, jangan menebak jika ambigu.

---

## 1. Hapus filter dropdown "Kategori" yang duplikat di Product

Tabs kategori (Semua, Fresh Juice, Seasonal, Smoothies, dst.) sudah berfungsi di halaman List Product. Filter "Kategori" yang masih ada di dalam panel Filter (ikon corong) sekarang duplikat — hapus `SelectFilter` untuk `category_id` dari `->filters([...])`. Sisakan hanya filter Status Aktif di panel tersebut.

## 2. Hapus kolom "Urutan" dan ikon drag-reorder dari tampilan tabel Category

Kolom `sort_order` di database **tetap dipertahankan**, tidak di-drop. Yang dihapus:

- Kolom "Urutan" dari `->columns([...])` pada table Category
- Ikon/tombol drag-reorder (`->reorderable()`) pada table Category

Alasan: default sort sekarang alfabetis berdasarkan nama, sehingga kolom dan mekanisme reorder ini tidak lagi punya fungsi yang terlihat dan hanya membingungkan.

Cek juga apakah ModifierGroup punya kolom serupa yang perlu dihapus dengan alasan yang sama.

## 3. Opsi pada ModifierGroup: hapus input angka "Urutan", pertahankan drag handle

Sama seperti perbaikan pada Varian di Follow-up 3. Repeater opsi modifier (mis. No Sugar, Less Sugar, Normal di dalam grup Sweetness) saat ini masih punya input angka manual "Urutan".

**Bukan alfabetis** — urutan opsi punya makna bisnis (mis. urutan tingkat gula dari rendah ke tinggi) yang bisa salah kalau diurutkan berdasarkan abjad. Hapus input angka, pertahankan drag handle untuk pengurutan manual sesekali.

---

## Kriteria Selesai

- [ ] Filter panel Product hanya berisi Status Aktif; tidak ada filter Kategori duplikat (Tabs tetap ada dan berfungsi sebagai satu-satunya cara filter kategori)
- [ ] Table Category tidak menampilkan kolom "Urutan" maupun ikon drag-reorder
- [ ] Kolom `sort_order` di database Category tidak dihapus/diubah strukturnya
- [ ] Repeater opsi ModifierGroup tidak lagi punya input angka "Urutan"; drag handle tetap berfungsi
- [ ] `php artisan test` tetap lulus seluruhnya; sesuaikan test yang mengasumsikan keberadaan filter/kolom yang dihapus

## Laporan yang Harus Dikirim

1. Konfirmasi ketiga poin selesai
2. Hasil `php artisan test`
3. Screenshot halaman List Product (memastikan tidak ada filter kategori duplikat) dan List Category (memastikan kolom Urutan sudah hilang)
