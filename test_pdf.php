<?php
require __DIR__.'/vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
$content = file_get_contents('C:\DESARROLLO\CFEs\CFE_214988770019_101_A_1002.pdf');
if (str_starts_with($content, "\xEF\xBB\xBF")) { $content = substr($content, 3); }
$content = rtrim($content);
if (str_ends_with($content, '%%E')) $content .= 'OF';
elseif (str_ends_with($content, '%%EO')) $content .= 'F';
$pdf = $parser->parseContent($content);
echo "--- RAW TEXT ---" . PHP_EOL;
echo $pdf->getText() . PHP_EOL;
echo "----------------" . PHP_EOL;
