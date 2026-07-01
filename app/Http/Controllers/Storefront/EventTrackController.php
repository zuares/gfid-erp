<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\StorefrontEvent;
use App\Models\StorefrontVisitor;
use Illuminate\Http\Request;

class EventTrackController extends Controller
{
    /**
     * Terima client-side tracking events dari browser (beacon/XHR).
     * Route: POST /storefront/track  (name: storefront.track)
     *
     * Payload yang diterima:
     *   visitor_token  — token dari cookie gf_vid (dikirim JS)
     *   event_type     — page_view_duration | click
     *   payload        — JSON string berisi data event
     */
    public function store(Request $request): \Illuminate\Http\Response
    {
        $token     = $request->input('visitor_token');
        $eventType = $request->input('event_type');

        // Validasi minimal — event_type harus salah satu yang kita kenal
        $allowed = ['page_view_duration', 'click', 'click_product', 'click_cta', 'click_wa'];
        if (! $token || ! in_array($eventType, $allowed, true)) {
            return response('', 204);
        }

        // Verify token ada di DB (hindari spam dari luar)
        if (! StorefrontVisitor::where('visitor_token', $token)->exists()) {
            return response('', 204);
        }

        // Parse payload JSON
        $rawPayload = $request->input('payload', '{}');
        $payload    = is_string($rawPayload)
            ? (json_decode($rawPayload, true) ?? [])
            : (is_array($rawPayload) ? $rawPayload : []);

        // Sanitasi: batasi panjang string dalam payload
        array_walk_recursive($payload, function (&$v) {
            if (is_string($v)) $v = substr($v, 0, 500);
        });

        // Untuk page_view_duration, pastikan seconds adalah integer wajar (1–3600)
        if ($eventType === 'page_view_duration') {
            $seconds = (int) ($payload['seconds'] ?? 0);
            if ($seconds < 1 || $seconds > 3600) {
                return response('', 204);
            }
            $payload['seconds'] = $seconds;
        }

        StorefrontEvent::create([
            'visitor_token' => $token,
            'event_type'    => $eventType,
            'payload'       => $payload,
            'created_at'    => now(),
        ]);

        // sendBeacon tidak peduli response body, tapi 204 adalah konvensi
        return response('', 204);
    }
}
