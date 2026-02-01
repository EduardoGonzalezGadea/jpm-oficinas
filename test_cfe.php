<?php
require 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('docs/CFEs/CFE_214988770019_101_A_57.pdf');
$text = $pdf->getText();

echo "--- TEXT START ---\n";
echo $text;
echo "--- TEXT END ---\n";

// Serie y Número
if (preg_match('/SERIE\s+N.MERO[^\n]+\n\s*([A-Z]+)\s+(\d+)/iu', $text, $matches)) {
    echo "Serie: " . $matches[1] . "\n";
    echo "Numero: " . $matches[2] . "\n";
} else {
    echo "No match for Serie/Numero\n";
}

// Nombre Receptor
if (preg_match('/NOMBRE\s+O\s+DENOMINACI.N\s+DOMICILIO\s+FISCAL\s*\n\s*(.*?)(?=\s*\n\s*(?:INFORMACION\s+ADICIONAL|DETALLE\s+DESCRIPCI.N|PERIODO|FECHA|$))/isu', $text, $matches)) {
    echo "Nombre: " . trim($matches[1]) . "\n";
} else {
    echo "No match for Nombre\n";
}

// Extracción de Items
if (preg_match('/DETALLE\s+DESCRIPCI.N.*?IMPORTE\s*\n(.*?)(?=\s*MONTO\s+NO\s+FACTURABLE|MONTO\s+TOTAL|TOTAL\s+A\s+PAGAR|$)/isu', $text, $matches)) {
    echo "Items block detected\n";
    $bloqueItems = $matches[1];
    $lineas = explode("\n", $bloqueItems);
    foreach ($lineas as $linea) {
        if (preg_match('/[\d\.,]+\s*\(Unid\)\s*([\d\.,]+)\s+([\d\.,]+)/i', $linea, $m)) {
            echo "Item importe: " . $m[2] . "\n";
        }
    }
} else {
    echo "No match for Items\n";
}
