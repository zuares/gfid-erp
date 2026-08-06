<?php

namespace App\Http\Controllers;

use App\Http\Requests\SyncOrdersRequest;
use App\Models\Channel;
use App\Models\ItemCostSnapshot;
use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdGroup;
use App\Models\MarketplaceAdItemMap;
use App\Models\MarketplaceOrder;
use App\Models\Item;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePromotion;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceOrderSettlement;
use App\Models\MarketplaceSyncLog;
use App\Models\OrderFulfillment;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\Warehouse;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\Ads\ItemHppResolver;
use App\Services\Channels\ChannelManager;
use App\Support\GmvMaxAnalytics;
use App\Services\MarketplaceIssueService;
use App\Services\MarketplaceSyncService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MarketplaceSettingsController extends Controller
{
    public function settings(): \Illuminate\View\View
    {
        $settings = [
            'marketplace_print_default_format' => \App\Models\SystemSetting::get('marketplace_print_default_format', 'THERMAL_AIR_WAYBILL'),
            'marketplace_print_branding'       => \App\Models\SystemSetting::get('marketplace_print_branding', '1'),
            'marketplace_footer_image'         => \App\Models\SystemSetting::get('marketplace_footer_image', ''),
            'marketplace_footer_greeting'      => \App\Models\SystemSetting::get('marketplace_footer_greeting', 'Terima kasih telah berbelanja!'),
            'marketplace_footer_alignment'     => \App\Models\SystemSetting::get('marketplace_footer_alignment', 'C'),
            'marketplace_footer_divider'       => \App\Models\SystemSetting::get('marketplace_footer_divider', '0'),
            'marketplace_sender_name'          => \App\Models\SystemSetting::get('marketplace_sender_name', ''),
            'marketplace_sender_phone'         => \App\Models\SystemSetting::get('marketplace_sender_phone', ''),
            'marketplace_social_accounts'      => \App\Models\SystemSetting::get('marketplace_social_accounts', '[]'),
            'marketplace_auto_sync'            => \App\Models\SystemSetting::get('marketplace_auto_sync', '0'),
            'marketplace_auto_push_stock'      => \App\Models\SystemSetting::get('marketplace_auto_push_stock', '0'),
            'marketplace_auto_process_orders'  => \App\Models\SystemSetting::get('marketplace_auto_process_orders', '0'),
            'marketplace_default_warehouse'    => \App\Models\SystemSetting::get('marketplace_default_warehouse', ''),
        ];

        $warehouses = \App\Models\Warehouse::orderBy('name')->get();

        // Ambil daftar file template greeting
        $greetingTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/greetings')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/greetings');
            foreach ($files as $file) {
                // Return just the basename, e.g., 'template_1.png'
                $greetingTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($greetingTemplates);

        // Ambil daftar file template footer
        $footerTemplates = [];
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists('templates/footers')) {
            $files = \Illuminate\Support\Facades\Storage::disk('public')->files('templates/footers');
            foreach ($files as $file) {
                $footerTemplates[] = pathinfo($file, PATHINFO_BASENAME);
            }
        }
        sort($footerTemplates);

        return view('marketplace.settings', compact('settings', 'warehouses', 'greetingTemplates', 'footerTemplates'));
    }

    public function updateSettings(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'marketplace_print_default_format' => ['required', 'string'],
            'marketplace_print_branding'       => ['required', 'in:0,1'],
            'marketplace_footer_image'         => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_footer_image'              => ['nullable', 'in:1'],
            'marketplace_footer_greeting'      => ['nullable', 'string', 'max:500'],
            'marketplace_footer_alignment'     => ['required', 'in:L,C,R'],
            'marketplace_footer_template'      => ['nullable', 'string', 'max:50'],
            'marketplace_footer_divider'       => ['nullable', 'in:0,1'],
            'marketplace_sender_name'          => ['nullable', 'string', 'max:255'],
            'marketplace_sender_phone'         => ['nullable', 'string', 'max:50'],
            'marketplace_auto_sync'            => ['required', 'in:0,1'],
            'marketplace_auto_push_stock'      => ['required', 'in:0,1'],
            'marketplace_print_greeting_card'  => ['nullable', 'in:0,1'],
            'marketplace_greeting_card_image'  => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'remove_greeting_card_image'       => ['nullable', 'in:1'],
            'marketplace_greeting_card_template' => ['nullable', 'string', 'max:50'],
            'marketplace_auto_process_orders'  => ['required', 'in:0,1'],
            'marketplace_default_warehouse'    => ['nullable', 'exists:warehouses,id'],
            'upload_template_1'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_2'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'upload_template_3'                => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_greeting_template'            => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
            'add_footer_template'              => ['nullable', 'file', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
        ]);

        if ($request->has('remove_footer_image') && $request->input('remove_footer_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_footer_image', '');
            unset($data['marketplace_footer_image']);
        } elseif ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer', 'public');
            \App\Models\SystemSetting::set('marketplace_footer_image', $path);
            unset($data['marketplace_footer_image']);
        }
        unset($data['remove_footer_image']);

        if ($request->has('remove_greeting_card_image') && $request->input('remove_greeting_card_image') == '1') {
            $oldPath = \App\Models\SystemSetting::get('marketplace_greeting_card_image', '');
            if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', '');
            unset($data['marketplace_greeting_card_image']);
        } elseif ($request->hasFile('marketplace_greeting_card_image')) {
            $path = $request->file('marketplace_greeting_card_image')->store('marketplace/greetings', 'public');
            \App\Models\SystemSetting::set('marketplace_greeting_card_image', $path);
            unset($data['marketplace_greeting_card_image']);
        }
        unset($data['remove_greeting_card_image']);

        for ($i = 1; $i <= 3; $i++) {
            $field = 'upload_template_' . $i;
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $ext = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'template_' . $i . '.' . $ext;
                // Delete existing ones with different extensions just in case
                foreach (['png', 'jpg', 'jpeg', 'pdf'] as $e) {
                    $old = 'templates/greetings/template_' . $i . '.' . $e;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($old)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                    }
                }
                $file->storeAs('templates/greetings', $filename, 'public');
            }
            unset($data[$field]);
        }

        if ($request->hasFile('add_greeting_template')) {
            $file = $request->file('add_greeting_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'custom_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/greetings', $filename, 'public');
            unset($data['add_greeting_template']);
        }

        if ($request->hasFile('add_footer_template')) {
            $file = $request->file('add_footer_template');
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $baseName = 'footer_' . time();
            $filename = $baseName . '.' . $ext;
            $file->storeAs('templates/footers', $filename, 'public');
            unset($data['add_footer_template']);
        }

        foreach ($data as $key => $value) {
            \App\Models\SystemSetting::set($key, $value);
        }

        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);

        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }

        \App\Models\SystemSetting::set('marketplace_social_accounts', json_encode($accounts));

        return redirect()->route('marketplace.settings')->with('success', 'Pengaturan berhasil disimpan');
    }

    public function deleteTemplate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'type' => 'required|in:greeting,footer',
            'filename' => 'required|string',
        ]);

        $type = $request->input('type');
        $filename = $request->input('filename');
        $folder = $type === 'greeting' ? 'templates/greetings' : 'templates/footers';
        $path = $folder . '/' . $filename;

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        return redirect()->route('marketplace.settings')->with('success', 'Template berhasil dihapus!');
    }

    public function previewSettingsPdf(\Illuminate\Http\Request $request)
    {
        $config = $request->except(['marketplace_footer_image', 'social_platforms', 'social_usernames']);

        $platforms = $request->input('social_platforms', []);
        $usernames = $request->input('social_usernames', []);

        $accounts = [];
        for ($i = 0; $i < count($platforms); $i++) {
            if (!empty(trim($usernames[$i] ?? ''))) {
                $accounts[] = [
                    'platform' => $platforms[$i],
                    'username' => trim($usernames[$i])
                ];
            }
        }
        $config['marketplace_social_accounts'] = json_encode($accounts);

        $tmpImgPath = null;
        if ($request->hasFile('marketplace_footer_image')) {
            $path = $request->file('marketplace_footer_image')->store('marketplace/footer/tmp', 'public');
            $config['marketplace_footer_image'] = $path;
            $tmpImgPath = storage_path('app/public/' . $path);
        } else {
            // Keep existing if any
            if ($request->input('remove_footer_image') == '1') {
                $config['marketplace_footer_image'] = '';
            } else {
                $config['marketplace_footer_image'] = \App\Models\SystemSetting::get('marketplace_footer_image', '');
            }
        }

        // Generate a fake Shopee AWB using FPDF
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'MARKETPLACE AWB MOCKUP', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, 'No. Pesanan: 260709908SV5CN', 0, 1, 'C');

        $pdf->SetFillColor(0, 0, 0);
        for($i = 0; $i < 60; $i++) {
            $w = rand(1, 3);
            $pdf->Rect(15 + ($i * 1.2), 30, $w * 0.5, 15, 'F');
        }

        $pdf->SetY(50);
        $pdf->Cell(0, 10, '==================================================', 0, 1, 'C');
        $rawPdf = $pdf->Output('S');

        $overlayService = new \App\Services\ShippingLabelOverlayService();
        $finalPdf = $overlayService->overlayPdfContent($rawPdf, $config);

        if ($tmpImgPath && file_exists($tmpImgPath)) {
            @unlink($tmpImgPath);
        }

        return response($finalPdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"'
        ]);
    }

    public function printSampleGreetingCard(\Illuminate\Http\Request $request)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);

        $settings = \App\Models\SystemSetting::all()->pluck('setting_value', 'setting_key')->toArray();

        $greetingImageFull = null;
        $customGreetingImg = $settings['marketplace_greeting_card_image'] ?? '';

        if (!empty($customGreetingImg) && file_exists(storage_path('app/public/' . $customGreetingImg))) {
            $greetingImageFull = storage_path('app/public/' . $customGreetingImg);
        } else {
            $gTpl = $request->query('template', $settings['marketplace_greeting_card_template'] ?? 'template_1.png');
            if ($gTpl !== 'none') {
                if (in_array($gTpl, ['1', '2', '3'])) {
                    $gTpl = 'template_' . $gTpl . '.png';
                }
                $tplPath = storage_path('app/public/templates/greetings/' . $gTpl);
                if (file_exists($tplPath)) {
                    $greetingImageFull = $tplPath;
                }
            }
        }

        if ($greetingImageFull) {
            $ext = strtolower(pathinfo($greetingImageFull, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                try {
                    $pdf->setSourceFile($greetingImageFull);
                    $gTplId = $pdf->importPage(1);
                    $pdf->useTemplate($gTplId, 0, 0, 100, 150);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to load PDF greeting card sample: " . $e->getMessage());
                }
            } else {
                $m = 4;
                $pdf->Image($greetingImageFull, $m, $m, 100 - ($m * 2), 150 - ($m * 2));
            }
        } else {
            $pdf->SetFont('Helvetica', 'B', 14);
            $pdf->SetXY(0, 70);
            $pdf->Cell(100, 10, 'Thank you for your order!', 0, 1, 'C');
        }

        return response($pdf->Output('S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Sample_Greeting_Card.pdf"'
        ]);
    }
}
