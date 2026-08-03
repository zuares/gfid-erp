<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceChatMessage;
use App\Models\Store;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class RepairChatRawPayloadsCommand extends Command
{
    protected $signature = 'marketplace:repair-chat-raw-payloads
        {--store= : Optional store id}
        {--limit=500 : Max rows to inspect}
        {--apply : Actually write changes (dry-run otherwise)}';

    protected $description = 'Repair raw_payload, raw_context, and webhook_log_id for legacy marketplace chat messages.';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $apply = (bool) $this->option('apply');
        $storeId = $this->option('store');

        $messagesQuery = MarketplaceChatMessage::query()
            ->with(['store:id,name,external_shop_id', 'webhookLog'])
            ->where(function ($query) {
                $query->whereNull('raw_payload')
                    ->orWhereNull('raw_context')
                    ->orWhere(function ($sub) {
                        $sub->where('source', 'webhook')
                            ->whereNull('webhook_log_id');
                    });
            })
            ->orderBy('id');

        if ($storeId !== null && $storeId !== '') {
            $store = Store::find((int) $storeId);
            if (! $store) {
                $this->error("Store #{$storeId} tidak ditemukan.");
                return self::FAILURE;
            }

            $messagesQuery->where('store_id', $store->id);
        }

        $messages = $messagesQuery->limit($limit)->get();

        if ($messages->isEmpty()) {
            $this->info('Tidak ada message yang perlu direpair.');
            return self::SUCCESS;
        }

        $storeIds = $messages->pluck('store_id')->filter()->unique()->values();
        $stores = Store::whereIn('id', $storeIds)->get()->keyBy('id');

        $logIndex = $this->buildWebhookLogIndex();
        $stats = [
            'scanned' => $messages->count(),
            'updated' => 0,
            'linked_webhook_log' => 0,
            'synthesized' => 0,
        ];

        $this->info(sprintf(
            'Inspecting %d message(s)%s',
            $messages->count(),
            $apply ? ' with APPLY' : ' in dry-run'
        ));

        foreach ($messages as $message) {
            [$payload, $context, $webhookLogId, $mode] = $this->buildRepairArtifacts($message, $stores, $logIndex);

            if ($payload === null && $context === null && $webhookLogId === null) {
                continue;
            }

            $changed = false;

            if ($payload !== null && $message->raw_payload !== $payload) {
                $message->raw_payload = $payload;
                $changed = true;
            }

            if ($context !== null && $message->raw_context !== $context) {
                $message->raw_context = $context;
                $changed = true;
            }

            if ($webhookLogId !== null && (int) $message->webhook_log_id !== (int) $webhookLogId) {
                $message->webhook_log_id = $webhookLogId;
                $changed = true;
                $stats['linked_webhook_log']++;
            }

            if ($mode === 'synthetic') {
                $stats['synthesized']++;
            }

            if (! $changed) {
                continue;
            }

            $stats['updated']++;

            $this->line(sprintf(
                '[%s] message #%d store #%d ext_msg=%s source=%s %s',
                $apply ? 'APPLY' : 'DRY',
                $message->id,
                $message->store_id,
                $message->external_message_id ?? '-',
                $message->source ?? '-',
                $webhookLogId ? "webhook_log_id={$webhookLogId}" : $mode
            ));

            if ($apply) {
                $message->save();
            }
        }

        $this->newLine();
        $this->info('Repair summary');
        $this->line('Scanned           : ' . $stats['scanned']);
        $this->line('Updated           : ' . $stats['updated']);
        $this->line('Linked webhook log : ' . $stats['linked_webhook_log']);
        $this->line('Synthesized       : ' . $stats['synthesized']);
        $this->line('Mode              : ' . ($apply ? 'APPLY' : 'DRY-RUN'));

        if (! $apply) {
            $this->comment('Jalankan lagi dengan --apply untuk menulis perubahan.');
        }

        return self::SUCCESS;
    }

    /**
     * Build a lookup of recent webhook logs by shop id + message id.
     *
     * @return array<string, array<string, WebhookLog>>
     */
    protected function buildWebhookLogIndex(): array
    {
        $index = [];

        WebhookLog::query()
            ->where('provider', 'shopee')
            ->where('event_type', 'webchat_push')
            ->orderByDesc('id')
            ->limit(2000)
            ->get()
            ->each(function (WebhookLog $log) use (&$index) {
                $shopId = (string) (
                    data_get($log->payload, 'shop_id')
                    ?? data_get($log->payload, 'data.shop_id')
                    ?? ''
                );

                $messageId = (string) (
                    data_get($log->payload, 'data.content.message_id')
                    ?? data_get($log->payload, 'data.content.0.message_id')
                    ?? data_get($log->payload, 'data.message_id')
                    ?? ''
                );

                if ($shopId === '' || $messageId === '') {
                    return;
                }

                $index[$shopId][$messageId] = $log;
            });

        return $index;
    }

    /**
     * @param  array<string, array<string, WebhookLog>>  $logIndex
     * @return array{0:?array,1:?array,2:?int,3:string}
     */
    protected function buildRepairArtifacts(MarketplaceChatMessage $message, $stores, array $logIndex): array
    {
        $store = $stores->get($message->store_id);
        $existingContext = $message->raw_context;
        if (is_string($existingContext)) {
            $decoded = json_decode($existingContext, true);
            $existingContext = is_array($decoded) ? $decoded : [];
        }
        $existingContext = is_array($existingContext) ? $existingContext : [];

        $payload = $message->raw_payload;
        $webhookLogId = $message->webhook_log_id ? (int) $message->webhook_log_id : null;
        $mode = 'noop';

        if ($webhookLogId && $message->webhookLog) {
            $payload = $message->webhookLog->payload;
            $existingContext['repair'] = array_merge($existingContext['repair'] ?? [], [
                'mode' => 'linked_webhook_log',
                'webhook_log_id' => $webhookLogId,
            ]);
            return [$payload, $existingContext, $webhookLogId, 'linked'];
        }

        if ($message->source === 'webhook' && $store && filled($store->external_shop_id)) {
            $messageId = (string) ($message->external_message_id ?? '');
            $candidate = $logIndex[(string) $store->external_shop_id][$messageId] ?? null;
            if ($candidate) {
                $webhookLogId = (int) $candidate->id;
                $payload = $candidate->payload;
                $existingContext['repair'] = array_merge($existingContext['repair'] ?? [], [
                    'mode' => 'matched_webhook_log',
                    'webhook_log_id' => $webhookLogId,
                ]);
                return [$payload, $existingContext, $webhookLogId, 'linked'];
            }
        }

        if ($payload === null || $existingContext === [] || $message->raw_payload === null || $message->raw_context === null) {
            $mode = 'synthetic';
            if ($payload === null) {
                $payload = $this->synthesizePayload($message, $store);
            }

            $existingContext['repair'] = array_merge($existingContext['repair'] ?? [], [
                'mode' => 'synthetic_from_message_row',
                'generated_at' => now()->toIso8601String(),
                'message_id' => $message->external_message_id,
                'conversation_id' => $message->external_conversation_id,
            ]);
        }

        return [$payload, $existingContext, $webhookLogId, $mode];
    }

    protected function synthesizePayload(MarketplaceChatMessage $message, ?Store $store): array
    {
        return array_filter([
            'message_id' => $message->external_message_id,
            'conversation_id' => $message->external_conversation_id,
            'message_type' => $message->message_type,
            'from_id' => $message->from_id,
            'from_role' => $message->from_role,
            'store_id' => $store?->external_shop_id,
            'source' => $message->source,
            'created_timestamp' => $message->sent_at?->timestamp,
            'content' => $message->content ?: ['text' => $message->text],
            'text' => $message->text,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
