<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\PurchaseOrderWhatsAppMessageBuilder;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
            'contextTitle' => 'PO '.$purchase_order->code,
            'contextUrl' => route('purchasing.purchase_orders.show', $purchase_order),
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function broadcast(): View
    {
        $templates = WhatsAppTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('whatsapp.broadcast', [
            'templates' => $templates,
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function sendBroadcast(Request $request, WhatsAppMessageService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:10000'],
            'message' => ['required', 'string', 'max:4000'],
            'template_key' => ['nullable', 'string', 'exists:whatsapp_templates,key'],
        ]);

        $template = ! empty($data['template_key'] ?? null)
            ? WhatsAppTemplate::query()
                ->where('key', $data['template_key'])
                ->where('is_active', true)
                ->first()
            : null;

        if (($data['template_key'] ?? null) && ! $template) {
            return back()->withInput()->with('error', 'Template WhatsApp tidak aktif atau tidak ditemukan.');
        }

        $recipients = $this->parseBroadcastRecipients($data['recipients']);

        if (count($recipients) > 100) {
            throw ValidationException::withMessages([
                'recipients' => 'Maksimal 100 nomor per broadcast agar pengiriman tetap aman.',
            ]);
        }

        if (! filled(config('services.fonnte.token'))) {
            return back()
                ->withInput()
                ->with('error', 'FONNTE_TOKEN belum dikonfigurasi di server.');
        }

        $messageLogs = $whatsapp->sendBroadcast(
            $recipients,
            $data['message'],
            [
                'module' => 'broadcast',
                'reference_type' => self::class,
                'reference_label' => 'Broadcast WhatsApp',
            ],
            $template?->key,
        );

        $sentCount = collect($messageLogs)->where('status', 'sent')->count();
        $failedCount = count($messageLogs) - $sentCount;
        $flashKey = $failedCount === 0 ? 'success' : 'error';
        $flashMessage = "Broadcast selesai: {$sentCount} terkirim, {$failedCount} gagal dari ".count($messageLogs).' nomor.';

        return redirect()->route('whatsapp.index')->with($flashKey, $flashMessage);
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

    /**
     * @return list<string>
     */
    private function parseBroadcastRecipients(string $recipients): array
    {
        $tokens = preg_split('/[\s,;]+/', trim($recipients), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $normalized = [];
        $invalid = [];

        foreach ($tokens as $token) {
            $phone = preg_replace('/\D+/', '', $token) ?? '';

            if (str_starts_with($phone, '0')) {
                $phone = '62'.substr($phone, 1);
            }

            if (! preg_match('/^62\d{8,13}$/', $phone)) {
                $invalid[] = $token;

                continue;
            }

            $normalized[$phone] = true;
        }

        if ($invalid !== []) {
            $invalidPreview = implode(', ', array_slice($invalid, 0, 5));
            $suffix = count($invalid) > 5 ? ' dan nomor lainnya' : '';

            throw ValidationException::withMessages([
                'recipients' => "Format nomor tidak valid: {$invalidPreview}{$suffix}.",
            ]);
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'recipients' => 'Masukkan minimal satu nomor WhatsApp yang valid.',
            ]);
        }

        return array_keys($normalized);
    }
}
