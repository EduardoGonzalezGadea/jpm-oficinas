<?php
$text = file_get_contents('pdf_debug.txt');

if (preg_match('/FECHA[\s:]+(?:MONEDA[\s:]+)?(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
    echo "CAPTURED: " . $matches[1] . "\n";
} else {
    echo "NOT FOUND\n";
}
