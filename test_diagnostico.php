<?php
require 'vendor/autoload.php';
$text = file_get_contents('raw_pdf_text.txt');
$out = "Testing Regexes:\n";

$re_sn_actual = '/SERIE\s+N.MERO.*?([A-Z])[\s\t]+(\d+)/is';
if (preg_match($re_sn_actual, $text, $m)) {
    $out .= "S/N Actual: OK (Serie=" . $m[1] . ", Num=" . $m[2] . ")\n";
} else {
    $out .= "S/N Actual: FAIL\n";
}

$re_sn_suelto = '/([A-Z])[\s\t]+(\d+)[\s\t]+(?:Contado|Cr.dito)/i';
if (preg_match($re_sn_suelto, $text, $m)) {
    $out .= "S/N Suelto: OK (Serie=" . $m[1] . ", Num=" . $m[2] . ")\n";
} else {
    $out .= "S/N Suelto: FAIL\n";
}

echo $out;
