<?php
$file = 'app/Http/Controllers/MarketplaceLogisticsController.php';
$c = file_get_contents($file);

// 1. Add imports
$c = str_replace(
    "use App\Services\Channels\ChannelManager;",
    "use App\Services\Marketplace\MarketplaceApiGateway;\nuse App\Services\Marketplace\MarketplaceLogisticsService;\nuse App\Services\Channels\ChannelManager;",
    $c
);

// 2. Change constructor
$c = str_replace(
    "protected ChannelManager \$manager,",
    "protected MarketplaceApiGateway \$gateway,\n        protected MarketplaceLogisticsService \$logistics,",
    $c
);

// 3. Replace all $this->manager->driver($store) with $this->gateway
$c = str_replace("\$this->manager->driver(\$store)", "\$this->gateway", $c);

file_put_contents($file, $c);
