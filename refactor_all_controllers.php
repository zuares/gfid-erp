<?php
$controllers = [
    'app/Http/Controllers/MarketplaceReturnController.php',
    'app/Http/Controllers/MarketplaceController.php',
    'app/Http/Controllers/MarketplaceChatController.php',
    'app/Http/Controllers/Marketplace/MarketplaceOrderController.php'
];

foreach ($controllers as $file) {
    if (!file_exists($file)) continue;
    $c = file_get_contents($file);

    // Replace namespace/imports
    if (strpos($c, 'use App\Services\Marketplace\MarketplaceApiGateway;') === false) {
        $c = str_replace(
            "use App\Services\Channels\ChannelManager;",
            "use App\Services\Marketplace\MarketplaceApiGateway;\nuse App\Services\Channels\ChannelManager;",
            $c
        );
    }

    // Replace constructor injection
    $c = preg_replace(
        '/public function __construct\(\s*protected ChannelManager \$manager\s*\)\s*\{\}/',
        "public function __construct(\n        protected MarketplaceApiGateway \$gateway\n    ) {}",
        $c
    );

    // Replace driver access
    $c = str_replace("\$this->manager->driver(\$store)", "\$this->gateway", $c);
    $c = str_replace("\$driver = \$this->gateway;\n", "", $c);
    
    // For MarketplaceOrderController which instantiates ShopeeChannel directly
    $c = preg_replace(
        '/app\(\\\\App\\\\Services\\\\Channels\\\\Shopee\\\\ShopeeChannel::class\)/',
        "app(\App\Services\Marketplace\MarketplaceApiGateway::class)",
        $c
    );

    // For MarketplaceController which instantiates ShopeeChannel directly
    $c = preg_replace(
        '/app\(\\\\App\\\\Services\\\\Channels\\\\Shopee\\\\ShopeeChannel::class\)/',
        "app(\App\Services\Marketplace\MarketplaceApiGateway::class)",
        $c
    );

    file_put_contents($file, $c);
}
