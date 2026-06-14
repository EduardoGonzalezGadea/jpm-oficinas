<?php
require __DIR__.'/vendor/autoload.php';

function testGeometricReceptor($file) {
    $parser = new \Smalot\PdfParser\Parser();
    $content = file_get_contents($file);
    if (str_starts_with($content, "\xEF\xBB\xBF")) $content = substr($content, 3);
    $content = rtrim($content);
    if (str_ends_with($content, '%%E')) $content .= 'OF';
    elseif (str_ends_with($content, '%%EO')) $content .= 'F';
    
    $pdf = $parser->parseContent($content);
    $data = $pdf->getPages()[0]->getDataTm();
    
    $yTop = null;
    $yBottom = null;
    
    foreach ($data as $tm) {
        $text = trim($tm[1]);
        $y = round($tm[0][5], 2);
        
        if (str_contains(str_replace('Ó', 'O', strtoupper($text)), 'NOMBRE O DENOMINACION')) {
            $yTop = $y;
        }
    }
    
    foreach ($data as $tm) {
        $text = trim($tm[1]);
        $y = round($tm[0][5], 2);
        
        if (str_contains(strtoupper($text), 'INFORMACION ADICIONAL') || strtoupper($text) === 'PERIODO') {
            if ($yTop !== null && $y < $yTop) {
                if ($yBottom === null || $y > $yBottom) { // we want the highest one below yTop
                    $yBottom = $y;
                }
            }
        }
    }
    
    if ($yTop === null) return;
    if ($yBottom === null) $yBottom = $yTop - 100;
    
    $nombre = [];
    $domicilio = [];
    
    foreach ($data as $tm) {
        $text = trim($tm[1]);
        if ($text === '') continue;
        $x = round($tm[0][4], 2);
        $y = round($tm[0][5], 2);
        
        if ($y < $yTop && $y > $yBottom) {
            if ($x >= 230 && $x < 390) { // Limit X to avoid left-side Emisor header overlap
                $nombre[] = $text;
            } elseif ($x >= 390) {
                $domicilio[] = $text;
            }
        }
    }
    
    echo "--- " . basename($file) . " ---" . PHP_EOL;
    echo "NOMBRE: " . implode(" ", $nombre) . PHP_EOL;
    echo "DOMICILIO: " . implode(" ", $domicilio) . PHP_EOL;
}

testGeometricReceptor('C:\DESARROLLO\CFEs\CFE_214988770019_101_A_1002.pdf');
testGeometricReceptor('C:\DESARROLLO\CFEs\CFE_214988770019_111_A_1052.pdf');
testGeometricReceptor('C:\DESARROLLO\CFEs\CFE_214988770019_111_A_1081.pdf');
testGeometricReceptor('C:\DESARROLLO\CFEs\CFE_214988770019_111_A_1222.pdf');
