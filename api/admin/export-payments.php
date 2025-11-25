<?php
// Export payments in multiple formats (CSV, PDF, DOCX, or ALL as ZIP)

// Set session cookie parameters for consistency
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 3600 * 24 * 30, // 30 days
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Set headers to prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Get request parameters
$format = strtolower($_GET['format'] ?? 'csv');
$status = strtolower($_GET['status'] ?? '');

// Validate session and permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Function to set common headers for file downloads
function setDownloadHeaders($filename, $contentType) {
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
}

// Function to fetch and normalize payment data
function getPaymentData($pdo, $status) {
    $where = '';
    $params = [];
    
    // Map status to database values
    $statusMap = [
        'pending' => 'pending',
        'completed' => 'approved',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'failed' => 'failed'
    ];
    
    if (isset($statusMap[$status])) {
        $dbStatus = $statusMap[$status];
        $where = 'WHERE p.status = ?';
        $params[] = $dbStatus;
    }
    
    $sql = "SELECT p.id, p.user_id, p.amount, p.method, p.status, p.created_at, p.reference,
                   u.first_name, u.last_name, u.email, u.flat_no
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            $where
            ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return array_map(function($r) {
        $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        $status = strtolower($r['status'] ?? 'pending');
        $status = $status === 'approved' ? 'completed' : $status;
        
        return [
            'User' => $name ?: 'User#' . $r['user_id'],
            'Email' => $r['email'] ?? '',
            'Flat' => $r['flat_no'] ?? '',
            'Amount' => (float)$r['amount'],
            'Method' => ucfirst($r['method'] ?? 'Unknown'),
            'Date' => date('Y-m-d', strtotime($r['created_at'] ?? 'now')),
            'Time' => date('H:i:s', strtotime($r['created_at'] ?? 'now')),
            'Status' => ucfirst($status),
            'Reference' => $r['reference'] ?? ''
        ];
    }, $rows);
}

// Get the payment data
$normalized = getPaymentData($pdo, $status);

function output_csv($data) {
    $filename = 'payments_export_' . date('Y-m-d_His') . '.csv';
    setDownloadHeaders($filename, 'text/csv; charset=UTF-8');
    
    // Output BOM for proper UTF-8 encoding in Excel
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    if (!$out) {
        throw new Exception('Failed to open output stream');
    }
    
    // Add headers if data is empty
    if (empty($data)) {
        $headers = ['User', 'Email', 'Flat', 'Amount', 'Method', 'Date', 'Time', 'Status', 'Reference'];
        fputcsv($out, $headers);
        fclose($out);
        return;
    }
    
    // Output headers
    fputcsv($out, array_keys($data[0]));
    
    // Output data rows
    foreach ($data as $row) {
        fputcsv($out, $row);
    }
    
    fclose($out);
}

function output_pdf($data) {
    require_once __DIR__ . '/../../lib/fpdf186/fpdf.php';
    
    $filename = 'payments_export_' . date('Y-m-d_His') . '.pdf';
    setDownloadHeaders($filename, 'application/pdf');
    
    // Create PDF in landscape mode
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    
    // Title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Payments Export - ' . date('Y-m-d'), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Table header
    $pdf->SetFont('Arial', 'B', 9);
    $headers = [
        'User' => 40,
        'Email' => 50,
        'Flat' => 20,
        'Amount' => 25,
        'Method' => 25,
        'Date' => 25,
        'Time' => 20,
        'Status' => 25,
        'Reference' => 30
    ];
    
    // Draw header
    foreach ($headers as $header => $width) {
        $pdf->Cell($width, 8, $header, 1, 0, 'C');
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFont('Arial', '', 8);
    
    if (empty($data)) {
        $pdf->Cell(array_sum($headers), 8, 'No payment records found', 1, 1, 'C');
    } else {
        foreach ($data as $row) {
            $pdf->Cell($headers['User'], 7, substr($row['User'], 0, 30), 1);
            $pdf->Cell($headers['Email'], 7, substr($row['Email'], 0, 40), 1);
            $pdf->Cell($headers['Flat'], 7, substr($row['Flat'], 0, 15), 1, 0, 'C');
            $pdf->Cell($headers['Amount'], 7, number_format((float)$row['Amount'], 2), 1, 0, 'R');
            $pdf->Cell($headers['Method'], 7, $row['Method'], 1);
            $pdf->Cell($headers['Date'], 7, $row['Date'], 1);
            $pdf->Cell($headers['Time'], 7, $row['Time'], 1);
            
            // Color code status
            $status = strtolower($row['Status']);
            if ($status === 'completed' || $status === 'approved') {
                $pdf->SetFillColor(200, 255, 200); // Light green
            } elseif ($status === 'pending') {
                $pdf->SetFillColor(255, 255, 200); // Light yellow
            } else {
                $pdf->SetFillColor(255, 200, 200); // Light red
            }
            
            $pdf->Cell($headers['Status'], 7, $row['Status'], 1, 0, 'C', true);
            $pdf->SetFillColor(255, 255, 255); // Reset fill
            
            $pdf->Cell($headers['Reference'], 7, substr($row['Reference'], 0, 15), 1);
            $pdf->Ln();
            
            // Add new page if we're near the bottom
            if ($pdf->GetY() > 180) {
                $pdf->AddPage();
                // Redraw header on new page
                $pdf->SetFont('Arial', 'B', 9);
                foreach ($headers as $header => $width) {
                    $pdf->Cell($width, 8, $header, 1, 0, 'C');
                }
                $pdf->Ln();
                $pdf->SetFont('Arial', '', 8);
            }
        }
    }
    
    // Output the PDF
    $pdf->Output('I', $filename);
}

function output_docx($data) {
    // Create a temporary file for the DOCX
    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
    $tempDir = dirname($tempFile);
    $docxDir = $tempDir . '/word';
    $relsDir = $tempDir . '/_rels';
    
    // Create necessary directories
    if (!file_exists($docxDir)) mkdir($docxDir, 0777, true);
    if (!file_exists($docxDir . '/_rels')) mkdir($docxDir . '/_rels', 0777, true);
    if (!file_exists($relsDir)) mkdir($relsDir, 0777, true);

    // Helper function to escape XML
    $xml = function($s) {
        return htmlspecialchars($s ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    };

    // Define column headers
    $headers = ['User', 'Email', 'Flat', 'Amount', 'Method', 'Date', 'Time', 'Status', 'Reference'];
    
    // Build table rows XML
    $rowsXml = '';
    
    // Helper function to create a table cell
    $mkCell = function($text) use ($xml) {
        return '<w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . $xml($text) . '</w:t></w:r></w:p></w:tc>';
    };
    
    // Header row
    $rowsXml .= '<w:tr>' . implode('', array_map($mkCell, $headers)) . '</w:tr>';
    
    // Data rows
    foreach ($data as $row) {
        $cells = [];
        foreach ($headers as $h) { 
            $value = $row[$h] ?? '';
            if ($h === 'Amount' && is_numeric($value)) {
                $value = number_format((float)$value, 2);
            }
            $cells[] = $mkCell($value);
        }
        $rowsXml .= '<w:tr>' . implode('', $cells) . '</w:tr>';
    }

    // Document XML
    $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <w:document 
        xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" 
        xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" 
        xmlns:o="urn:schemas-microsoft-com:office:office" 
        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
        xmlns:v="urn:schemas-microsoft-com:vml" 
        xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" 
        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
        xmlns:w10="urn:schemas-microsoft-com:office:word" 
        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
        xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" 
        xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" 
        xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" 
        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" 
        xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" 
        mc:Ignorable="w14 wp14">
        <w:body>
            <w:p><w:r><w:t>Payments Export - ' . date('Y-m-d H:i:s') . '</w:t></w:r></w:p>
            <w:tbl>
                <w:tblPr>
                    <w:tblStyle w:val="TableGrid"/>
                    <w:tblW w:w="100%" w:type="pct"/>
                    <w:tblBorders>
                        <w:top w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                        <w:left w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                        <w:bottom w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                        <w:right w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                        <w:insideH w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                        <w:insideV w:val="single" w:sz="4" w:space="0" w:color="auto"/>
                    </w:tblBorders>
                    <w:tblLook w:val="04A0"/>
                </w:tblPr>
                <w:tblGrid>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                <w:gridCol w:w="2000"/>
                </w:tblGrid>
                ' . $rowsXml . '
            </w:tbl>
            <w:sectPr>
                <w:pgSz w:w="11906" w:h="16838" w:orient="landscape"/>
                <w:pgMar w:top="1417" w:right="1417" w:bottom="1417" w:left="1417" w:header="708" w:footer="708" w:gutter="0"/>
                <w:cols w:space="708"/>
                <w:docGrid w:linePitch="360"/>
            </w:sectPr>
        </w:body>
    </w:document>';

    // Write document.xml
    file_put_contents($docxDir . '/document.xml', $documentXml);

    // Document relationships
    $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
    </Relationships>';
    file_put_contents($docxDir . '/_rels/document.xml.rels', $docRels);

    // Package relationships
    $pkgRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    </Relationships>';
    file_put_contents($relsDir . '/.rels', $pkgRels);

    // Content types
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
        <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
        <Default Extension="xml" ContentType="application/xml"/>
        <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
        <Override PartName="/word/_rels/document.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
        <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
    </Types>';
    file_put_contents($tempDir . '/[Content_Types].xml', $contentTypes);

    // Create a simple settings file
    $settings = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
    <w:settings xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                xmlns:o="urn:schemas-microsoft-com:office:office" 
                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                xmlns:v="urn:schemas-microsoft-com:vml" 
                xmlns:w10="urn:schemas-microsoft-com:office:word" 
                xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" 
                xmlns:sl="http://schemas.openxmlformats.org/schemaLibrary/2006/main" 
                mc:Ignorable="w14">
        <w:zoom w:percent="100"/>
        <w:doNotTrackMoves/>
        <w:defaultTabStop w:val="708"/>
        <w:characterSpacingControl w:val="doNotCompress"/>
        <w:compat/>
        <w:rsids>
            <rsidRoot w:val="00A52A7B"/>
            <rsid w:val="00A52A7B"/>
        </w:rsids>
        <m:mathPr>
            <m:mathFont m:val="Cambria Math"/>
            <m:brkBin m:val="before"/>
            <m:brkBinSub m:val="--"/>
            <m:smallFrac m:val="0"/>
            <m:dispDef/>
            <m:lMargin m:val="0"/>
            <m:rMargin m:val="0"/>
            <m:defJc m:val="centerGroup"/>
            <m:wrapIndent m:val="1440"/>
            <m:intLim m:val="subSup"/>
            <m:naryLim m:val="undOvr"/>
        </m:mathPr>
    </w:settings>';
    file_put_contents($docxDir . '/settings.xml', $settings);

    // Create ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('Cannot create ZIP file');
    }

    // Add files to ZIP
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();

    // Clean up temporary files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($tempDir);

    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="payments_export_' . date('Y-m-d_His') . '.docx"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    
    // Output the file
    readfile($tempFile);
    
    // Clean up
    unlink($tempFile);
    exit;
}

// Handle the export based on format
try {
    switch ($format) {
        case 'csv':
            output_csv($normalized);
            break;
            
        case 'pdf':
            output_pdf($normalized);
            break;
            
        case 'docx':
            output_docx($normalized);
            break;
            
        case 'all':
            // Create a temporary directory for export files
            $tempDir = sys_get_temp_dir() . '/waterbill_export_' . uniqid();
            if (!mkdir($tempDir, 0755, true)) {
                throw new Exception('Failed to create temporary directory');
            }
            
            // Generate all formats
            $files = [];
            
            // CSV
            $csvFile = $tempDir . '/payments_export_' . date('Y-m-d_His') . '.csv';
            ob_start();
            output_csv($normalized);
            file_put_contents($csvFile, ob_get_clean());
            $files[] = $csvFile;
            
            // PDF
            $pdfFile = $tempDir . '/payments_export_' . date('Y-m-d_His') . '.pdf';
            ob_start();
            output_pdf($normalized);
            file_put_contents($pdfFile, ob_get_clean());
            $files[] = $pdfFile;
            
            // DOCX
            $docxFile = $tempDir . '/payments_export_' . date('Y-m-d_His') . '.docx';
            ob_start();
            output_docx($normalized);
            file_put_contents($docxFile, ob_get_clean());
            $files[] = $docxFile;
            
            // Create ZIP
            $zipFile = $tempDir . '/payments_export_' . date('Y-m-d_His') . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Failed to create ZIP file');
            }
            
            foreach ($files as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
            
            // Clean up individual files
            foreach ($files as $file) {
                @unlink($file);
            }
            
            // Stream the ZIP file
            setDownloadHeaders('payments_export_' . date('Y-m-d') . '.zip', 'application/zip');
            readfile($zipFile);
            
            // Clean up
            @unlink($zipFile);
            @rmdir($tempDir);
            break;
            
        default:
            throw new Exception('Invalid export format');
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
