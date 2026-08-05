<?php

return [

    'column_manager' => [

        'heading' => 'Kolom',

        'actions' => [

            'apply' => [
                'label' => 'Terapkan kolom',
            ],

            'reorder' => [
                'label' => 'Urutkan kolom',
            ],

            'reset' => [
                'label' => 'Atur ulang',
            ],

        ],

    ],

    'columns' => [

        'actions' => [
            'label' => 'Aksi|Aksi',
        ],

        'icon' => [

            'boolean' => [
                'true' => 'Aktif',
                'false' => 'Nonaktif',
            ],

        ],

        'select' => [

            'loading_message' => 'Memuat...',

            'no_options_message' => 'Tidak ada opsi tersedia.',

            'no_search_results_message' => 'Tidak ada opsi yang cocok dengan pencarian.',

            'placeholder' => 'Pilih opsi',

            'searching_message' => 'Mencari...',

            'search_prompt' => 'Ketik untuk mencari...',

        ],

        'text' => [

            'actions' => [
                'collapse_list' => 'Sembunyikan :count',
                'expand_list' => 'Tampilkan :count lagi',
            ],

            'more_list_items' => 'dan :count lainnya',

        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Pilih/batalkan semua item untuk aksi massal.',
        ],

        'bulk_select_record' => [
            'label' => 'Pilih/batalkan item :key untuk aksi massal.',
        ],

        'bulk_select_group' => [
            'label' => 'Pilih/batalkan grup :title untuk aksi massal.',
        ],

        'search' => [
            'label' => 'Cari',
            'placeholder' => 'Cari',
            'indicator' => 'Cari',
        ],

    ],

    'summary' => [

        'heading' => 'Ringkasan',

        'subheadings' => [
            'all' => 'Semua :label',
            'group' => 'Ringkasan :group',
            'page' => 'Halaman ini',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Rata-rata',
            ],

            'count' => [
                'label' => 'Jumlah',
            ],

            'sum' => [
                'label' => 'Total',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Selesai mengurutkan',
        ],

        'enable_reordering' => [
            'label' => 'Urutkan data',
        ],

        'reorder_record' => [
            'label' => 'Urutkan item :key',
        ],

        'filter' => [
            'label' => 'Filter',
        ],

        'group' => [
            'label' => 'Kelompokkan',
        ],

        'open_bulk_actions' => [
            'label' => 'Aksi massal',
        ],

        'column_manager' => [
            'label' => 'Pengatur kolom',
        ],

        'toggle_record_content' => [
            'label' => 'Buka/tutup item :key',
        ],

    ],

    'empty' => [

        'heading' => 'Tidak ada :model',

        'description' => 'Buat :model untuk memulai.',

    ],

    'filters' => [

        'actions' => [

            'apply' => [
                'label' => 'Terapkan filter',
            ],

            'remove' => [
                'label' => 'Hapus filter',
            ],

            'remove_all' => [
                'label' => 'Hapus semua filter',
                'tooltip' => 'Hapus semua filter',
            ],

            'reset' => [
                'label' => 'Atur ulang',
            ],

        ],

        'heading' => 'Filter',

        'indicator' => 'Filter aktif',

        'multi_select' => [
            'placeholder' => 'Semua',
        ],

        'select' => [

            'placeholder' => 'Semua',

            'relationship' => [
                'empty_option_label' => 'Tidak ada',
            ],

        ],

        'trashed' => [

            'label' => 'Data terhapus',

            'only_trashed' => 'Hanya data terhapus',

            'with_trashed' => 'Termasuk data terhapus',

            'without_trashed' => 'Tanpa data terhapus',

        ],

    ],

    'grouping' => [

        'fields' => [

            'group' => [
                'label' => 'Kelompokkan berdasarkan',
            ],

            'direction' => [

                'label' => 'Arah pengelompokan',

                'options' => [
                    'asc' => 'Naik',
                    'desc' => 'Turun',
                ],

            ],

        ],

    ],

    'loading' => 'Memuat...',

    'reorder_indicator' => 'Seret dan lepas data untuk mengurutkan.',

    'result_count' => '{0} Tidak ada hasil|{1} :count hasil|[2,*] :count hasil',

    'selection_indicator' => [

        'selected_count' => '1 data terpilih|:count data terpilih',

        'actions' => [

            'select_all' => [
                'label' => 'Pilih semua :count',
            ],

            'deselect_all' => [
                'label' => 'Batalkan semua',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Urutkan berdasarkan',
            ],

            'direction' => [

                'label' => 'Arah urutan',

                'options' => [
                    'asc' => 'Naik',
                    'desc' => 'Turun',
                ],

            ],

        ],

    ],

    'default_model_label' => 'data',

];
