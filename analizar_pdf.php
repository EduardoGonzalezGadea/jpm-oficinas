<?php
require 'vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('docs/CFEs/CFE_214988770019_101_A_57.pdf');
$text = $pdf->getText();

file_put_contents('pdf_analisis.txt', $text);
echo "Texto guardado en pdf_analisis.txt\n";
echo "Longitud: " . strlen($text) . " caracteres\n";

// Extraer secciones específicas
$output = "=== SECCIONES DEL PDF ===\n\n";

// Items
if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE)/isu', $text, $m)) {
    $output .= "ITEMS:\n" . $m[1] . "\n\n";
}

// Información adicional / Adenda
if (preg_match('/INFORMACION\s+ADICIONAL(.*?)(?=DETALLE\s+DESCRIPCI.N)/isu', $text, $m)) {
    $output .= "INFO ADICIONAL:\n" . trim($m[1]) . "\n\n";
}

file_put_contents('pdf_secciones.txt', $output);
echo "Secciones guardadas en pdf_secciones.txt\n";
