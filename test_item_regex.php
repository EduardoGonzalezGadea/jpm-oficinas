<?php
$lineas = [
    "1,000 (Unid) 7.392,00	7.392,00",
    "1,000 924,00	924,00"
];

$regex_old = '/^(.*?)([\d\.,]+\s*\(Unid\)[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))/i';
$regex_new = '/^(.*?)([\d\.,]+\s*(?:\(Unid\))?[\s\t]*[\d\.,]+[\s\t]+([\d\.,]+))/i';

foreach ($lineas as $linea) {
    echo "Testing: $linea\n";
    if (preg_match($regex_old, $linea, $m)) {
        echo "  OLD: MATCH - Importe: " . $m[3] . "\n";
    } else {
        echo "  OLD: NO MATCH\n";
    }

    if (preg_match($regex_new, $linea, $m)) {
        echo "  NEW: MATCH - Importe: " . $m[3] . "\n";
    } else {
        echo "  NEW: NO MATCH\n";
    }
}
