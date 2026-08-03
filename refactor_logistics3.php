<?php
$file = 'app/Http/Controllers/MarketplaceLogisticsController.php';
$lines = file($file);

// Find the namespace and insert use statements
$namespaceIndex = -1;
for ($i=0; $i<count($lines); $i++) {
    if (strpos($lines[$i], 'namespace App\Http\Controllers;') !== false) {
        $namespaceIndex = $i;
        break;
    }
}
array_splice($lines, $namespaceIndex + 1, 0, [
    "\nuse App\Services\Marketplace\MarketplaceApiGateway;\n",
    "use App\Services\Marketplace\MarketplaceLogisticsService;\n"
]);

// Write it back to string
$c = implode("", $lines);

// Replace the constructor
$c = preg_replace(
    '/public function __construct\(\s*protected ChannelManager \$manager\s*\)\s*\{\}/',
    "public function __construct(\n        protected MarketplaceApiGateway \$gateway,\n        protected MarketplaceLogisticsService \$logistics\n    ) {}",
    $c
);

// Replace syncAwb
$c = preg_replace(
    '/public function syncAwb.*?return response\(\)->json\(\[\'success\' => true, \'awb\' => \$awb\]\);\s*\}\s*\}/s',
    "public function syncAwb(Store \$store, string \$orderSn): \Illuminate\Http\JsonResponse\n    {\n        \$awb = \$this->logistics->syncAwb(\$store, \$orderSn);\n        return response()->json(['success' => true, 'awb' => \$awb]);\n    }",
    $c
);

// Replace arrangeShipment
$c = preg_replace(
    '/public function arrangeShipment\(Request \$request, Store \$store, string \$orderSn\): JsonResponse.*?return response\(\)->json\(\$result\);\s*\}\s*finally\s*\{.*?\s*\}\s*\}/s',
    "public function arrangeShipment(Request \$request, Store \$store, string \$orderSn): JsonResponse\n    {\n        try {\n            \$isAutoSync = \$request->input('is_auto_sync', false);\n            \$params = \$request->input('params', []);\n            \$result = \$this->logistics->arrangeShipment(\$store, \$orderSn, \$params, \$isAutoSync);\n            return response()->json(\$result);\n        } catch (\\Exception \$e) {\n            return \$this->errorResponse(\$e);\n        }\n    }",
    $c
);

// Replace all $this->manager->driver($store) -> $this->gateway
$c = str_replace("\$this->manager->driver(\$store)", "\$this->gateway", $c);
$c = str_replace("\$driver = \$this->gateway;\n", "", $c); // remove leftover assignments

file_put_contents($file, $c);
