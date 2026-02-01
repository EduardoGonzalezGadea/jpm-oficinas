<?php
require 'c:/xampp/htdocs/oficinas/vendor/autoload.php';
try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile('c:/xampp/htdocs/oficinas/docs/CFEs/CFE_214988770019_101_A_57.pdf');
    $text = $pdf->getText();
    file_put_contents('c:/xampp/htdocs/oficinas/raw_pdf_text.txt', $text);
    echo "SUCCESS\n";
    echo "Length: " . strlen($text) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
