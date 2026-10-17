<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\MarketplaceCrmController;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\PurchaseOrderWhatsAppMessageBuilder;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppCenterController extends Controller
{
    public function index(Request $request): View
    {
        $messages = WhatsAppMessage::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->string('module')->toString()))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $templates = WhatsAppTemplate::query()->orderBy('name')->get();
        $activeTemplates = $templates->where('is_active', true)->values();
        $modules = WhatsAppMessage::query()
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('whatsapp.index', compact('messages', 'templates', 'activeTemplates', 'modules'));
    }

    public function broadcast(Request $request, MarketplaceCrmController $marketplaceCrm): View
    {
        $audience = $request->string('audience')->toString();
        $audience = in_array($audience, ['all', 'recent_90', 'repeat'], true) ? $audience : 'all';
        $search = trim($request->string('q')->toString());

        $recipientQuery = $this->broadcastRecipientsQuery($marketplaceCrm, $audience, $search);
        $recipients = (clone $recipientQuery)
            ->orderByRaw("CASE WHEN name IS NULL OR name = '' THEN 1 ELSE 0 END")
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $templates = WhatsAppTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $broadcastMessages = WhatsAppMessage::query()
            ->where('module', 'whatsapp_broadcast')
            ->latest()
            ->limit(8)
            ->get();

        return view('whatsapp.broadcast', [
            'recipients' => $recipients,
            'recipientCount' => (clone $recipientQuery)->count(),
            'templates' => $templates,
            'audience' => $audience,
            'search' => $search,
            'broadcastMessages' => $broadcastMessages,
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function sendBroadcast(Request $request, WhatsAppMessageService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'customer_ids' => ['nullable', 'array', 'max:100'],
            'customer_ids.*' => ['integer', 'distinct', 'exists:customers,id'],
            'recipient_phones' => ['nullable', 'array', 'max:100'],
            'recipient_phones.*' => ['string', 'max:30'],
            'recipient_names' => ['nullable', 'array', 'max:100'],
            'recipient_names.*' => ['nullable', 'string', 'max:160'],
            'manual_phones' => ['nullable', 'array', 'max:100'],
            'manual_phones.*' => ['string', 'max:30'],
            'manual_name' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'template_key' => ['nullable', 'string', 'exists:whatsapp_templates,key'],
        ]);

        $manualPhones = collect($data['manual_phones'] ?? [])
            ->map(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
            ->filter(fn ($phone) => strlen($phone) >= 8)
            ->unique()
            ->values();
        $customerIds = collect($data['customer_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $recipientNames = collect($data['recipient_names'] ?? [])->values();
        $selectedRecipients = collect($data['recipient_phones'] ?? [])
            ->values()
            ->map(function ($phone, $index) use ($recipientNames): array {
                return [
                    'phone' => preg_replace('/\D+/', '', (string) $phone) ?: '',
                    'name' => trim((string) ($recipientNames->get($index) ?? '')) ?: 'Customer',
                ];
            })
            ->filter(fn (array $recipient) => strlen($recipient['phone']) >= 8)
            ->unique('phone')
            ->values();
        $requestedRecipientInputs = $customerIds->count() + $selectedRecipients->count();

        if ($manualPhones->isEmpty() && $customerIds->isEmpty() && $selectedRecipients->isEmpty()) {
            return back()->withInput()->with('error', 'Tambahkan minimal satu nomor manual atau pilih satu customer.');
        }
        if ($manualPhones->count() + $customerIds->count() + $selectedRecipients->count() > 100) {
            return back()->withInput()->with('error', 'Maksimal 100 penerima per broadcast, termasuk nomor manual dan customer terpilih.');
        }

        if (! filled(config('services.fonnte.token'))) {
            return back()->withInput()->with('error', 'Token Fonnte belum dikonfigurasi. Atur koneksi WhatsApp sebelum mengirim broadcast.');
        }

        $template = ! empty($data['template_key'])
            ? WhatsAppTemplate::query()
                ->where('key', $data['template_key'])
                ->where('is_active', true)
                ->first()
            : null;

        if (! empty($data['template_key']) && ! $template) {
            return back()->withInput()->with('error', 'Template WhatsApp tidak aktif atau tidak ditemukan.');
        }

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('phone', 'not like', '%*%')
            ->whereRaw("LOWER(phone) NOT LIKE '%x%'")
            ->get(['id', 'name', 'phone']);

        $normalizePhone = static function (?string $phone): string {
            $phone = preg_replace('/\D+/', '', (string) $phone) ?? '';

            return str_starts_with($phone, '0')
                ? '62' . substr($phone, 1)
                : $phone;
        };
        $selectedRecipients = $selectedRecipients
            ->map(fn (array $recipient): array => [
                'phone' => $normalizePhone($recipient['phone']),
                'name' => $recipient['name'],
            ])
            ->filter(fn (array $recipient) => $recipient['phone'] !== '')
            ->merge($customers->map(fn ($customer): array => [
                'phone' => $normalizePhone($customer->phone),
                'name' => trim((string) $customer->name) ?: 'Customer',
            ]))
            ->filter(fn (array $recipient) => $recipient['phone'] !== '')
            ->unique('phone')
            ->values();
        $selectedPhones = $selectedRecipients->pluck('phone');
        $manualRecipients = $manualPhones
            ->map(fn ($phone) => $normalizePhone($phone))
            ->filter()
            ->unique()
            ->reject(fn ($phone) => $selectedPhones->contains($phone))
            ->values();

        if ($selectedRecipients->isEmpty() && $manualRecipients->isEmpty()) {
            return back()->withInput()->with('error', 'Tidak ada penerima valid dengan nomor WhatsApp.');
        }

        $sent = 0;
        $failed = 0;
        $send = function (string $phone, ?string $recipientName, string $referenceLabel) use ($data, $template, $whatsapp, &$sent, &$failed): void {
            $recipientName = trim((string) $recipientName) ?: 'Customer';
            $message = str_replace(
                '{customer_name}',
                $recipientName,
                $data['message'],
            );
            $message = mb_substr($message, 0, 4000);

            $log = $whatsapp->sendText(
                $phone,
                $message,
                [
                    'module' => 'whatsapp_broadcast',
                    'reference_type' => self::class,
                    'reference_label' => $referenceLabel,
                ],
                $recipientName,
                $template?->key,
            );

            $log->isSent() ? $sent++ : $failed++;
        };

        foreach ($selectedRecipients as $recipient) {
            $send($recipient['phone'], $recipient['name'], 'Broadcast WhatsApp');
        }
        $manualRecipientName = trim((string) ($data['manual_name'] ?? '')) ?: 'Test Recipient';
        foreach ($manualRecipients as $phone) {
            $send($phone, $manualRecipientName, 'Test broadcast manual');
        }

        $requestedRecipients = $requestedRecipientInputs + $manualPhones->count();
        $skipped = max(0, $requestedRecipients - $selectedRecipients->count() - $manualRecipients->count());
        $flashMessage = "Broadcast selesai: {$sent} terkirim";
        if ($failed > 0) {
            $flashMessage .= ", {$failed} gagal";
        }
        if ($skipped > 0) {
            $flashMessage .= ", {$skipped} dilewati karena nomor tidak tersedia";
        }
        $flashMessage .= '.';

        return redirect()
            ->route('whatsapp.broadcast')
            ->with($failed > 0 ? 'error' : 'success', $flashMessage);
    }

    private function broadcastRecipientsQuery(MarketplaceCrmController $marketplaceCrm, string $audience, string $search)
    {
        $query = $marketplaceCrm->customerProfileQuery();
        $query->havingRaw("TRIM(COALESCE(phone, '')) <> '' AND phone NOT LIKE '%*%' AND LOWER(phone) NOT LIKE '%x%'");

        if ($audience === 'recent_90') {
            $query->having('last_marketplace_order_at', '>=', now()->subDays(90)->startOfDay());
        } elseif ($audience === 'repeat') {
            $query->having('marketplace_order_count', '>=', 2);
        }

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->havingRaw(
                "(LOWER(COALESCE(name, '')) LIKE ? OR LOWER(COALESCE(phone, '')) LIKE ? OR LOWER(COALESCE(email, '')) LIKE ? OR LOWER(COALESCE(buyer_username, '')) LIKE ?)",
                [$needle, $needle, $needle, $needle],
            );
        }

        return $query;
    }

    public function composePurchaseOrder(
        PurchaseOrder $purchase_order,
        PurchaseOrderWhatsAppMessageBuilder $builder,
    ): View|RedirectResponse {
        $draft = $builder->build($purchase_order);

        if ($draft['phone'] === '') {
            return redirect()
                ->route('purchasing.purchase_orders.show', $purchase_order)
                ->with('error', 'Nomor WhatsApp supplier belum diisi.');
        }

        $templates = WhatsAppTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('whatsapp.compose', [
            'draft' => $draft,
            'order' => $purchase_order,
            'templates' => $templates,
            'contextTitle' => 'PO ' . $purchase_order->code,
            'contextUrl' => route('purchasing.purchase_orders.show', $purchase_order),
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function send(Request $request, WhatsAppMessageService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'recipient_phone' => ['required', 'string', 'max:30'],
            'recipient_name' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'template_key' => ['nullable', 'string', 'exists:whatsapp_templates,key'],
            'module' => ['nullable', 'string', 'max:60'],
            'reference_type' => ['nullable', 'string', 'max:160'],
            'reference_id' => ['nullable', 'integer'],
            'reference_label' => ['nullable', 'string', 'max:120'],
            'return_to' => ['nullable', 'in:whatsapp,purchase_order,marketplace_prospect'],
        ]);

        $template = ! empty($data['template_key'])
            ? WhatsAppTemplate::query()
                ->where('key', $data['template_key'])
                ->where('is_active', true)
                ->first()
            : null;

        if ($data['template_key'] && ! $template) {
            return back()->withInput()->with('error', 'Template WhatsApp tidak aktif atau tidak ditemukan.');
        }

        $messageLog = $whatsapp->sendText(
            $data['recipient_phone'],
            $data['message'],
            [
                'module' => $data['module'] ?? 'manual',
                'reference_type' => $data['reference_type'] ?? self::class,
                'reference_id' => $data['reference_id'] ?? null,
                'reference_label' => $data['reference_label'] ?? 'Pesan manual',
            ],
            $data['recipient_name'] ?? null,
            $template?->key,
        );

        $flashKey = $messageLog->isSent() ? 'success' : 'error';
        $flashMessage = $messageLog->isSent()
            ? 'Pesan WhatsApp berhasil dikirim.'
            : 'Pesan WhatsApp gagal dikirim. Periksa koneksi device Fonnte dan log aplikasi.';

        if (($data['return_to'] ?? 'whatsapp') === 'purchase_order'
            && ($data['reference_type'] ?? null) === PurchaseOrder::class
            && ! empty($data['reference_id'])) {
            return redirect()
                ->route('purchasing.purchase_orders.show', (int) $data['reference_id'])
                ->with($flashKey, $flashMessage);
        }

        if (($data['return_to'] ?? 'whatsapp') === 'marketplace_prospect'
            && ($data['reference_type'] ?? null) === \App\Models\Customer::class
            && ! empty($data['reference_id'])) {
            return redirect()
                ->route('admin.crm.marketplace.prospects')
                ->with($flashKey, $flashMessage);
        }

        return redirect()->route('whatsapp.index')->with($flashKey, $flashMessage);
    }

    public function resend(WhatsAppMessage $whatsapp_message, WhatsAppMessageService $whatsapp): RedirectResponse
    {
        $messageLog = $whatsapp->sendText(
            $whatsapp_message->recipient_phone,
            $whatsapp_message->message,
            [
                'module' => $whatsapp_message->module,
                'reference_type' => $whatsapp_message->reference_type,
                'reference_id' => $whatsapp_message->reference_id,
                'reference_label' => $whatsapp_message->reference_label,
            ],
            $whatsapp_message->recipient_name,
            $whatsapp_message->template_key,
        );

        return redirect()
            ->route('whatsapp.index')
            ->with(
                $messageLog->isSent() ? 'success' : 'error',
                $messageLog->isSent()
                    ? 'Pesan WhatsApp berhasil dikirim ulang.'
                    : 'Pesan WhatsApp gagal dikirim ulang.'
            );
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9._-]*$/', 'unique:whatsapp_templates,key'],
            'name' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $data['is_active'] = true;

        WhatsAppTemplate::create($data);

        return back()->with('success', 'Template WhatsApp berhasil ditambahkan.');
    }

    public function updateTemplate(Request $request, WhatsAppTemplate $whatsapp_template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = auth()->id();

        $whatsapp_template->update($data);

        return back()->with('success', 'Template WhatsApp berhasil diperbarui.');
    }
}
