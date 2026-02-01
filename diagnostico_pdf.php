<?php
require 'vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('docs/CFEs/CFE_214988770019_101_A_57.pdf');
$text = $pdf->getText();

echo "=== TEXTO COMPLETO DEL PDF ===\n";
echo $text;
echo "\n\n=== ANÁLISIS DE SECCIONES ===\n\n";

// Buscar sección de DETALLE
if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*(?:MONTO\s+NO\s+FACTURABLE|MONTO\s+TOTAL|TOTAL\s+A\s+PAGAR|$))/isu', $text, $matches)) {
    echo "BLOQUE DE ITEMS:\n";
    echo "---START---\n";
    echo $matches[1];
    echo "\n---END---\n\n";
}

// Buscar sección de ADENDA
if (preg_match('/INFORMACION\s+ADICIONAL.*?\n(.*?)(?=\s*(?:DETALLE|MONTO|TOTAL|C.digo\s+de\s+seguridad|$))/isu', $text, $matches)) {
    echo "BLOQUE DE ADENDA/INFO ADICIONAL:\n";
    echo "---START---\n";
    echo $matches[1];
    echo "\n---END---\n\n";
}

// Buscar todo después de TOTAL A PAGAR
if (preg_match('/TOTAL\s+A\s+PAGAR:.*?\n(.*?)$/isu', $text, $matches)) {
    echo "TEXTO DESPUÉS DE TOTAL A PAGAR:\n";
    echo "---START---\n";
    echo $matches[1];
    echo "\n---END---\n";
}
