<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$request = Illuminate\Http\Request::create(
    '/marketplace/settings/preview-pdf', 'POST',
    ['marketplace_footer_template' => '2']
);
$controller = app(\App\Http\Controllers\MarketplaceController::class);

$config = $request->except(['marketplace_footer_image', 'social_platforms', 'social_usernames']);
if ($request->hasFile('marketplace_footer_image')) {
} else {
    if ($request->input('remove_footer_image') == '1') {
        $config['marketplace_footer_image'] = '';
    } else {
        $config['marketplace_footer_image'] = \App\Models\SystemSetting::get('marketplace_footer_image', '');
    }
}
$footerImagePath = $config['marketplace_footer_image'] ?? '';
$footerTemplate = $config['marketplace_footer_template'] ?? 'none';
echo "footerImagePath: " . $footerImagePath . "\n";
echo "footerTemplate: " . $footerTemplate . "\n";
