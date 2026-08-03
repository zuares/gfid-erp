<?php
$file = 'app/Http/Controllers/MarketplaceBookingController.php';
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

// Replace ship method
$c = preg_replace(
    '/public function ship\(Request \$request, Store \$store, string \$bookingSn\): JsonResponse.*?return response\(\)->json\(\[.*?\s*\}\s*finally\s*\{.*?\s*\}\s*\}/s',
    "public function ship(Request \$request, Store \$store, string \$bookingSn): JsonResponse\n    {\n        try {\n            \$params = \$request->input('params', []);\n            \$result = \$this->logistics->shipBooking(\$store, \$bookingSn, \$params);\n            return response()->json(\$result);\n        } catch (\\Exception \$e) {\n            return \$this->errorResponse(\$e);\n        }\n    }",
    $c
);

// Replace all $this->manager->driver($store) -> $this->gateway
$c = str_replace("\$this->manager->driver(\$store)", "\$this->gateway", $c);
$c = str_replace("\$driver = \$this->gateway;\n", "", $c); // remove leftover assignments

file_put_contents($file, $c);
