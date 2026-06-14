<?php
require __DIR__.'/vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$file = 'C:\DESARROLLO\CFEs\CFE_214988770019_101_A_1002.pdf';

$content = file_get_contents($file);
if (str_starts_with($content, "\xEF\xBB\xBF")) $content = substr($content, 3);
$content = rtrim($content);
if (str_ends_with($content, '%%E')) $content .= 'OF';
elseif (str_ends_with($content, '%%EO')) $content .= 'F';

$pdf = $parser->parseContent($content);
$pages = $pdf->getPages();
$data = $pages[0]->getDataTm();

$dump = [];
foreach ($data as $tm) {
    $text = trim($tm[1]);
    if ($text === '') continue;
    $dump[] = [
        'text' => $text,
        'x' => round($tm[0][4], 2),
        'y' => round($tm[0][5], 2),
    ];
}
file_put_contents('test_dump3.json', json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Done";
