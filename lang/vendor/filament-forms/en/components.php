<?php

/**
 * Scoped override of filament-forms::components.
 *
 * Only the sections actually reachable in this app are translated:
 * file_upload (image editor is enabled on the product photo field),
 * repeater (product variants, modifier options), and select (category,
 * modifier groups). Other top-level keys from the vendor file (builder,
 * rich_editor, markdown_editor, tags_input, key_value, color_picker,
 * date_time_picker, modal_table_select, radio.boolean, toggle_buttons,
 * text_input) are intentionally omitted because none of those
 * components are used anywhere in the app today. If a future resource
 * introduces one of them, its strings will fall back to the untranslated
 * key path until that section is added here.
 */
return [

    'file_upload' => [

        'actions' => [

            'download' => [
                'label' => 'Unduh',
            ],

            'open' => [
                'label' => 'Buka di tab baru',
            ],

        ],

        'editor' => [

            'label' => 'Editor gambar',

            'actions' => [

                'cancel' => [
                    'label' => 'Batal',
                ],

                'drag_crop' => [
                    'label' => 'Mode geser "potong"',
                ],

                'drag_move' => [
                    'label' => 'Mode geser "pindah"',
                ],

                'flip_horizontal' => [
                    'label' => 'Balik gambar horizontal',
                ],

                'flip_vertical' => [
                    'label' => 'Balik gambar vertikal',
                ],

                'move_down' => [
                    'label' => 'Pindahkan gambar ke bawah',
                ],

                'move_left' => [
                    'label' => 'Pindahkan gambar ke kiri',
                ],

                'move_right' => [
                    'label' => 'Pindahkan gambar ke kanan',
                ],

                'move_up' => [
                    'label' => 'Pindahkan gambar ke atas',
                ],

                'reset' => [
                    'label' => 'Atur ulang',
                ],

                'rotate_left' => [
                    'label' => 'Putar gambar ke kiri',
                ],

                'rotate_right' => [
                    'label' => 'Putar gambar ke kanan',
                ],

                'set_aspect_ratio' => [
                    'label' => 'Atur rasio aspek ke :ratio',
                ],

                'save' => [
                    'label' => 'Simpan',
                ],

                'zoom_100' => [
                    'label' => 'Perbesar gambar ke 100%',
                ],

                'zoom_in' => [
                    'label' => 'Perbesar',
                ],

                'zoom_out' => [
                    'label' => 'Perkecil',
                ],

            ],

            'fields' => [

                'height' => [
                    'label' => 'Tinggi',
                    'unit' => 'px',
                ],

                'rotation' => [
                    'label' => 'Rotasi',
                    'unit' => 'deg',
                ],

                'width' => [
                    'label' => 'Lebar',
                    'unit' => 'px',
                ],

                'x_position' => [
                    'label' => 'X',
                    'unit' => 'px',
                ],

                'y_position' => [
                    'label' => 'Y',
                    'unit' => 'px',
                ],

            ],

            'aspect_ratios' => [

                'label' => 'Rasio aspek',

                'no_fixed' => [
                    'label' => 'Bebas',
                ],

            ],

            'svg' => [

                'messages' => [
                    'confirmation' => 'Mengedit berkas SVG tidak disarankan karena dapat menurunkan kualitas saat diperbesar.\n Lanjutkan?',
                    'disabled' => 'Mengedit berkas SVG dinonaktifkan karena dapat menurunkan kualitas saat diperbesar.',
                ],

            ],

        ],

    ],

    'repeater' => [

        'columns' => [

            'actions' => [
                'label' => 'Aksi',
            ],

            'reorder' => [
                'label' => 'Urutkan',
            ],

        ],

        'actions' => [

            'add' => [
                'label' => 'Tambah ke :label',
            ],

            'add_between' => [
                'label' => 'Sisipkan di antara',
            ],

            'delete' => [
                'label' => 'Hapus',
            ],

            'clone' => [
                'label' => 'Duplikat',
            ],

            'reorder' => [
                'label' => 'Pindahkan',
            ],

            'move_down' => [
                'label' => 'Pindah ke bawah',
            ],

            'move_up' => [
                'label' => 'Pindah ke atas',
            ],

            'collapse' => [
                'label' => 'Tutup',
            ],

            'expand' => [
                'label' => 'Buka',
            ],

            'collapse_all' => [
                'label' => 'Tutup semua',
            ],

            'expand_all' => [
                'label' => 'Buka semua',
            ],

        ],

    ],

    'select' => [

        'actions' => [

            'create_option' => [

                'label' => 'Buat',

                'modal' => [

                    'heading' => 'Buat',

                    'actions' => [

                        'create' => [
                            'label' => 'Buat',
                        ],

                        'create_another' => [
                            'label' => 'Buat & buat lagi',
                        ],

                    ],

                ],

            ],

            'edit_option' => [

                'label' => 'Ubah',

                'modal' => [

                    'heading' => 'Ubah',

                    'actions' => [

                        'save' => [
                            'label' => 'Simpan',
                        ],

                    ],

                ],

            ],

        ],

        'boolean' => [
            'true' => 'Aktif',
            'false' => 'Nonaktif',
        ],

        'loading_message' => 'Memuat...',

        'max_items_message' => 'Hanya :count yang bisa dipilih.',

        'no_options_message' => 'Tidak ada opsi tersedia.',

        'no_search_results_message' => 'Tidak ada opsi yang cocok dengan pencarian.',

        'placeholder' => 'Pilih opsi',

        'searching_message' => 'Mencari...',

        'search_prompt' => 'Ketik untuk mencari...',

    ],

];
