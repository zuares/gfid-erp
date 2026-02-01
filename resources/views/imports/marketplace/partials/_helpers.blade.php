{{-- resources/views/imports/marketplace/partials/_helpers.blade.php --}}
@php
  // Sentinel supaya aman di-include berkali-kali
  if (!isset($GLOBALS['__mp_helpers_loaded'])) {
    $GLOBALS['__mp_helpers_loaded'] = true;

    $tz = 'Asia/Jakarta';

    $filters = $filters ?? [];
    $period  = $period ?? null;

    $orders  = $orders ?? [];
    $ship    = $ship ?? [];
    $delta   = $delta ?? [];

    $stores    = $stores ?? [];
    $shipments = $shipments ?? null;
    $summary   = $summary ?? [];

    $draft = $draft ?? session('mp_import_preview');
    $advActive = (bool)($advActive ?? false);

    $money = fn($n) => 'Rp ' . number_format((float)($n ?? 0), 0, ',', '.');

    $fmtDate = function ($v, $fmt='d M Y') use ($tz) {
      if (!$v) return '-';
      try { return \Carbon\Carbon::parse($v)->timezone($tz)->format($fmt); }
      catch (\Throwable $e) { return (string)$v; }
    };

    $dPct = function($v){
      $v = (float)($v ?? 0);
      $sign = $v > 0 ? '+' : '';
      return $sign . number_format($v, 1) . '%';
    };

    $deltaClass = function($v){
      $v = (float)($v ?? 0);
      if ($v > 0) return 'text-success';
      if ($v < 0) return 'text-danger';
      return 'text-muted';
    };

    $statusLabel = function($s){
      $s = (string)$s;
      return match($s){
        'delivered' => ['Delivered','success'],
        'in_transit' => ['In Transit','primary'],
        'canceled' => ['Canceled','danger'],
        default => ['Unknown','secondary'],
      };
    };
  }
@endphp
