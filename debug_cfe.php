<?php
require 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('docs/CFEs/CFE_214988770019_101_A_57.pdf');
$text = $pdf->getText();

echo "Raw Text:\n";
var_dump($text);

echo "\n--- Match Tests ---\n";

// Test Serie/Numero
$re = '/SERIE\s+N.MERO.*?([A-Z]+)\s+(\d+)/is';
if (preg_match($re, $text, $m)) {
    echo "Serie/Numero: Success\n";
    print_r($m);
} else {
    echo "Serie/Numero: Failed\n";
}

// Test Nombre
$re = '/NOMBRE.*?FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION|DETALLE|FECHA|$))/isu';
if (preg_match($re, $text, $m)) {
    echo "Nombre: Success\n";
    print_r($m);
} else {
    echo "Nombre: Failed\n";
}
