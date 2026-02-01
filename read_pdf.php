<?php
require 'vendor/autoload.php';
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('docs/CFEs/CFE_214988770019_101_A_57.pdf');
echo $pdf->getText();
