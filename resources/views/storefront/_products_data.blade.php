@php
/**
 * Shared product data untuk semua halaman storefront.
 * Nanti ganti dengan query database ketika model Produk sudah ada.
 */
$products = [
    [
        'slug'    => 'track-jacket-black',
        'name'    => 'Track Jacket Black',
        'price'   => 149000,
        'label'   => 'Best',
        'dark'    => true,
        'sold'    => '1.200+',
        'sizes'   => ['S','M','L','XL','XXL'],
        'desc'    => 'Jaket track harian dengan bahan ringan dan nyaman. Cocok untuk aktivitas outdoor maupun santai.',
    ],
    [
        'slug'    => 'track-jacket-grey',
        'name'    => 'Track Jacket Grey',
        'price'   => 149000,
        'label'   => 'New',
        'dark'    => false,
        'sold'    => '842+',
        'sizes'   => ['S','M','L','XL','XXL'],
        'desc'    => 'Varian abu dari Track Jacket favorit. Warna netral yang mudah dipadukan.',
    ],
    [
        'slug'    => 'sport-jacket-navy',
        'name'    => 'Sport Jacket Navy',
        'price'   => 159000,
        'label'   => 'Ready',
        'dark'    => true,
        'sold'    => '623+',
        'sizes'   => ['S','M','L','XL'],
        'desc'    => 'Jaket sport dengan potongan slim. Bahan breathable untuk aktivitas fisik.',
    ],
    [
        'slug'    => 'active-pants-grey',
        'name'    => 'Active Pants Grey',
        'price'   => 129000,
        'label'   => 'Promo',
        'dark'    => false,
        'sold'    => '514+',
        'sizes'   => ['S','M','L','XL','XXL'],
        'desc'    => 'Celana aktif dengan material stretch nyaman sepanjang hari.',
    ],
];

$channels = [
    ['label' => 'Website',   'url' => '#', 'dark' => true],
    ['label' => 'Shopee',    'url' => '#', 'dark' => false],
    ['label' => 'TikTok',    'url' => '#', 'dark' => false],
    ['label' => 'Tokopedia', 'url' => '#', 'dark' => false],
];
@endphp
