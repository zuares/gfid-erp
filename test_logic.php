<?php
$ready = 17;
$wh_prd = 100;
$targetRtsQty = 50;

$rts_deficit = max(0, $targetRtsQty - $ready);
$minta_prd = min($rts_deficit, $wh_prd);

echo "Ready: $ready\n";
echo "Max Display (Target): $targetRtsQty\n";
echo "PRD Stock: $wh_prd\n";
echo "Deficit: $rts_deficit\n";
echo "Minta PRD: $minta_prd\n";
echo "Total in RTS after PRD transfer: " . ($ready + $minta_prd) . "\n";
