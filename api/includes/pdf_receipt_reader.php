<?php
// Mini-program to read and verify PDF receipt contents
// Requires composer package: smalot/pdfparser
// require_once __DIR__ . '/../vendor/autoload.php';
use Smalot\PdfParser\Parser;

function read_pdf_receipt($pdf_path) {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdf_path);
    $text = $pdf->getText();
    // Extract fields using regex
    $fields = [
        'name' => null,
        'date' => null,
        'amount' => null,
        'receipt_pin' => null
    ];
    // Name: look for 'Name: ...' or similar
    if (preg_match('/Name\s*[:\-]\s*(.+)/i', $text, $m)) {
        $fields['name'] = trim($m[1]);
    }
    // Date: look for date patterns
    if (preg_match('/Date\s*[:\-]\s*([\d\-\/\.]+[\s\d:]*)/i', $text, $m)) {
        $fields['date'] = trim($m[1]);
    }
    // Amount: look for 'Amount: ...' or currency
    if (preg_match('/Amount\s*[:\-]\s*([₦N]?[\d,\.]+)/i', $text, $m)) {
        $fields['amount'] = trim($m[1]);
    }
    // Receipt PIN: look for 'PIN: ...' or 'Receipt No:'
    if (preg_match('/(PIN|Receipt No)\s*[:\-]\s*(\w+)/i', $text, $m)) {
        $fields['receipt_pin'] = trim($m[2]);
    }
    return $fields;
}
