<?php
require_once 'config.php';
header('Content-Type: application/json');

/*
  save_pdf.php
  Saves a client-generated PDF to /bills_pdfs and (optionally) records path in bills.pdf_path.

  POST:
    bill_no   (string, required)
    pdf_data  (base64 data URL or raw base64, required)
    overwrite (optional '1')

  Response JSON:
    ok: bool
    file: relative path (if ok)
    pdf_path_updated: bool
    error: (if not ok)
*/

try {
    // Example auth placeholder
    // session_start();
    // if (empty($_SESSION['user_id'])) { throw new Exception("Unauthorized"); }

    $billNo    = $_POST['bill_no']  ?? '';
    $pdfData   = $_POST['pdf_data'] ?? '';
    $overwrite = !empty($_POST['overwrite']);

    if ($billNo === '' || $pdfData === '') {
        throw new Exception("Missing bill_no or pdf_data.");
    }

    // allow letters, numbers, dash, underscore
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $billNo)) {
        throw new Exception("Invalid bill number format.");
    }

    // Strip data URI prefix if present
    if (strpos($pdfData, 'base64,') !== false) {
        $pdfData = substr($pdfData, strpos($pdfData, 'base64,') + 7);
    }

    $binary = base64_decode($pdfData, true);
    if ($binary === false) {
        throw new Exception("Invalid base64 PDF data.");
    }

    // Ensure bill exists
    $stmt = $conn->prepare("SELECT id FROM bills WHERE bill_no=? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('s', $billNo);
    $stmt->execute();
    $res  = $stmt->get_result();
    $bill = $res->fetch_assoc();
    $stmt->close();
    if (!$bill) throw new Exception("Bill not found for bill_no: $billNo");

    $billId = (int)$bill['id'];

    // Folder
    $dir = __DIR__ . '/bills_pdfs';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            throw new Exception("Failed to create bills_pdfs directory.");
        }
    }
    if (!is_writable($dir)) {
        throw new Exception("Directory not writable: bills_pdfs");
    }

    $fileName = 'Bill-' . $billNo . '.pdf';
    $filePath = $dir . '/' . $fileName;

    if (file_exists($filePath) && !$overwrite) {
        throw new Exception("File already exists. Pass overwrite=1 to replace.");
    }

    if (file_put_contents($filePath, $binary) === false) {
        throw new Exception("Failed to write PDF file.");
    }

    $relativePath = 'bills_pdfs/' . $fileName;

    // Detect if pdf_path column exists (cache result statically to avoid repeated SHOW calls)
    static $hasPdfPath = null;
    if ($hasPdfPath === null) {
        $check = $conn->query("SHOW COLUMNS FROM bills LIKE 'pdf_path'");
        $hasPdfPath = $check && $check->num_rows > 0;
    }

    $updated = false;
    if ($hasPdfPath) {
        $upd = $conn->prepare("UPDATE bills SET pdf_path=? WHERE id=?");
        if ($upd) {
            $upd->bind_param('si', $relativePath, $billId);
            $upd->execute();
            $updated = $upd->affected_rows >= 0;
            $upd->close();
        }
    }

    echo json_encode([
        'ok'                => true,
        'bill_no'           => $billNo,
        'file'              => $relativePath,
        'pdf_path_updated'  => $updated,
        'message'           => 'PDF stored successfully.'
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}