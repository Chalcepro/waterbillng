<?php
require '../includes/db_connect.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['receipt'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['receipt'];
$temp_path = $file['tmp_name'];

// Process OCR (simulated)
$extracted_data = process_receipt_ocr($temp_path);

echo json_encode([
    'success' => true,
    'data' => $extracted_data
]);
?>