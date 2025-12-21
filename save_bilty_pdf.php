<?php
require_once 'config.php';
header('Content-Type: application/json');

/*
  save_bilty_pdf.php
  Saves a client-generated PDF to /bilty_pdfs and (optionally) records path in consignments.pdf_path.

  POST:
    bilty_no   (string, required)
    pdf_data   (base64 data URL or raw base64, required)
    overwrite  (optional '1')

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

    $biltyNo   = $_POST['bilty_no']  ?? '';
    $pdfData   = $_POST['pdf_data'] ?? '';
    $overwrite = !empty($_POST['overwrite']);

    if ($biltyNo === '' || $pdfData === '') {
        throw new Exception("Missing bilty_no or pdf_data.");
    }

    // allow letters, numbers, dash, underscore
    if (!preg_match('/^[A-Za-z0-9_\-]+$/', $biltyNo)) {
        throw new Exception("Invalid bilty number format.");
    }

    // Strip data URI prefix if present
    if (strpos($pdfData, 'base64,') !== false) {
        $pdfData = substr($pdfData, strpos($pdfData, 'base64,') + 7);
    }

    $binary = base64_decode($pdfData, true);
    if ($binary === false) {
        throw new Exception("Invalid base64 PDF data.");
    }

    // Ensure bilty exists
    $stmt = $conn->prepare("SELECT id FROM consignments WHERE bilty_no=? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('s', $biltyNo);
    $stmt->execute();
    $res = $stmt->get_result();
    $bilty = $res->fetch_assoc();
    $stmt->close();
    if (!$bilty) throw new Exception("Bilty not found for bilty_no: $biltyNo");

    $biltyId = (int)$bilty['id'];

    // Folder
    $dir = __DIR__ . '/bilty_pdfs';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true)) {
            throw new Exception("Failed to create bilty_pdfs directory.");
        }
    }
    if (!is_writable($dir)) {
        throw new Exception("Directory not writable: bilty_pdfs");
    }

    $fileName = 'Bilty-' . $biltyNo . '.pdf';
    $filePath = $dir . '/' . $fileName;

    if (file_exists($filePath) && !$overwrite) {
        throw new Exception("File already exists. Pass overwrite=1 to replace.");
    }

    if (file_put_contents($filePath, $binary) === false) {
        throw new Exception("Failed to write PDF file.");
    }

    $relativePath = 'bilty_pdfs/' . $fileName;

    // Detect if pdf_path column exists (cache result statically to avoid repeated SHOW calls)
    static $hasPdfPath = null;
    if ($hasPdfPath === null) {
        $check = $conn->query("SHOW COLUMNS FROM consignments LIKE 'pdf_path'");
        $hasPdfPath = $check && $check->num_rows > 0;
    }

    $updated = false;
    if ($hasPdfPath) {
        $upd = $conn->prepare("UPDATE consignments SET pdf_path=? WHERE id=?");
        if ($upd) {
            $upd->bind_param('si', $relativePath, $biltyId);
            $upd->execute();
            $updated = $upd->affected_rows >= 0;
            $upd->close();
        }
    }

    echo json_encode([
        'ok'                => true,
        'bilty_no'          => $biltyNo,
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