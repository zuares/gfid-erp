<?php
function toNumber($value): float
{
    if ($value === null || $value === '') {
        return 0.0;
    }

    if (is_int($value) || is_float($value)) {
        return (float) $value;
    }

    $value = trim((string) $value);
    $value = str_replace(' ', '', $value);

    // format indo: 1.234,56
    if (strpos($value, ',') !== false) {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    // ribuan: 1.234.567
    if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
        $value = str_replace('.', '', $value);
        return (float) $value;
    }

    return (float) $value;
}

echo "25000 -> " . toNumber("25000") . "\n";
echo "25.000 -> " . toNumber("25.000") . "\n";
echo "1.234,56 -> " . toNumber("1.234,56") . "\n";
echo "1.234.567 -> " . toNumber("1.234.567") . "\n";
echo "1.234.567,89 -> " . toNumber("1.234.567,89") . "\n";
echo "250.000 -> " . toNumber("250.000") . "\n";
echo "1,5 -> " . toNumber("1,5") . "\n";
echo "1.5 -> " . toNumber("1.5") . "\n";

