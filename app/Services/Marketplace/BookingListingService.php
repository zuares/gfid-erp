<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BookingListingService
{
    /** Shared classification for counts, filters and rows, before pagination. */
    public function query($storeIds): Builder
    {
        $base = DB::table('marketplace_bookings as b')->select('b.*')
            ->whereIn('b.store_id', $storeIds);

        // Prefer the explicit order number; legacy bookings only have booking_sn.
        // Scope both links by store and choose one order to avoid duplicate rows.
        foreach (['order_sn' => 'channel_order_id', 'booking_sn' => 'booking_sn'] as $bookingKey => $orderKey) {
            $base->selectSub(
                DB::table('marketplace_orders as o')->select('o.id')
                    ->whereColumn('o.store_id', 'b.store_id')
                    ->whereColumn("o.{$orderKey}", "b.{$bookingKey}")
                    ->where("b.{$bookingKey}", '<>', '')
                    ->orderByDesc('o.updated_at')->orderByDesc('o.id')->limit(1),
                "linked_{$bookingKey}_id"
            );
        }

        $linked = DB::query()->fromSub($base, 'b')
            ->leftJoin('marketplace_orders as o', 'o.id', '=', DB::raw('COALESCE(b.linked_order_sn_id, b.linked_booking_sn_id)'))
            ->select('b.*', 'o.id as linked_order_id', 'o.channel_order_id as linked_channel_order_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(b.tracking_number), ''), NULLIF(TRIM(o.shipping_awb_no), '')) as effective_tracking_number")
            ->selectRaw("UPPER(TRIM(COALESCE(b.booking_status, ''))) as listing_status")
            ->selectRaw("CASE WHEN EXISTS (SELECT 1 FROM order_fulfillments f WHERE f.marketplace_order_id = o.id AND f.status = 'confirmed') THEN 1 ELSE 0 END as is_confirmed");

        $shipping = DB::query()->fromSub($linked, 'b')->select('b.*')
            ->selectRaw("CASE WHEN listing_status IN ('PENDING', 'READY_TO_SHIP', 'PROCESSED', '')
                AND effective_tracking_number IS NULL
                AND COALESCE(TRIM(package_number), '') = ''
                AND COALESCE(TRIM(shipping_document_status), '') = ''
                THEN 1 ELSE 0 END as listing_needs_shipping");

        $stages = DB::query()->fromSub($shipping, 'b')->select('b.*')
            ->selectRaw("CASE
                WHEN listing_status = 'SHIPPED' THEN 'warehouse'
                WHEN listing_status IN ('READY_TO_HANDOVER', 'COMPLETED') THEN 'shipped'
                WHEN listing_status IN ('CANCELLED', 'CANCELLED_BEFORE_SHIPPING', 'FAILED') THEN 'cancelled'
                WHEN listing_status IN ('READY_TO_SHIP', 'PROCESSED') OR listing_needs_shipping = 1 THEN 'ready'
                ELSE 'waiting' END as listing_stage")
            ->selectRaw("CASE WHEN listing_status IN ('SHIPPED', 'READY_TO_HANDOVER') AND effective_tracking_number IS NOT NULL THEN 1 ELSE 0 END as has_seller_awb");

        $classified = DB::query()->fromSub($stages, 'b')->select('b.*')
            ->selectRaw("CASE WHEN listing_stage = 'ready' OR has_seller_awb = 1 THEN 1 ELSE 0 END as listing_seller_visible")
            ->selectRaw("CASE WHEN is_confirmed = 1 OR has_seller_awb = 1 THEN 'ready_ship'
                WHEN listing_needs_shipping = 1 THEN 'to_arrange'
                ELSE 'packing' END as listing_ready_sub");

        return MarketplaceBooking::query()->fromSub($classified, 'marketplace_bookings');
    }

    public function counts(Builder $query): array
    {
        $counts = array_fill_keys(['all', 'ready', 'waiting', 'warehouse', 'shipped', 'cancelled'], 0);
        $ready = array_fill_keys(['all', 'to_arrange', 'packing', 'ready_ship'], 0);
        $groups = (clone $query)->toBase()
            ->select('listing_stage', 'listing_seller_visible', 'listing_ready_sub')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('listing_stage', 'listing_seller_visible', 'listing_ready_sub')->get();

        foreach ($groups as $group) {
            $total = (int) $group->total;
            $counts['all'] += $total;
            if ($group->listing_stage !== 'ready') {
                $counts[$group->listing_stage] += $total;
            }
            if ($group->listing_seller_visible) {
                $counts['ready'] += $total;
                $ready['all'] += $total;
                $ready[$group->listing_ready_sub] += $total;
            }
        }

        return ['counts' => $counts, 'ready_counts' => $ready];
    }
}
