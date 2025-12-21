<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Optionally add authentication / role check here.
    $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['note'] ?? '');
    $date   = trim($_POST['payment_date'] ?? '');

    if ($billId <= 0) throw new Exception("Invalid bill_id.");
    if (!in_array($action, ['PAID','UNPAID'], true)) {
        throw new Exception("Invalid action.");
    }

    // Validate date if provided
    $paymentDate = null;
    if ($date !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d) throw new Exception("Invalid payment_date format; use YYYY-MM-DD.");
        $paymentDate = $d->format('Y-m-d');
    }

    // Ensure bill exists
    $stmt = $conn->prepare("SELECT id, payment_status FROM bills WHERE id=? LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('i', $billId);
    $stmt->execute();
    $res = $stmt->get_result();
    $bill = $res->fetch_assoc();
    $stmt->close();
    if (!$bill) throw new Exception("Bill not found.");

    if ($action === 'PAID') {
        // If no date provided, use today
        if (!$paymentDate) $paymentDate = date('Y-m-d');
        $stmt2 = $conn->prepare("UPDATE bills SET payment_status='PAID', payment_date=?, payment_note=? WHERE id=?");
        if (!$stmt2) throw new Exception("Prepare failed: " . $conn->error);
        $stmt2->bind_param('ssi', $paymentDate, $note, $billId);
        $stmt2->execute();
        $stmt2->close();
    } else {
        // UNPAID reset
        $stmt2 = $conn->prepare("UPDATE bills SET payment_status='UNPAID', payment_date=NULL, payment_note=? WHERE id=?");
        if (!$stmt2) throw new Exception("Prepare failed: " . $conn->error);
        $stmt2->bind_param('si', $note, $billId);
        $stmt2->execute();
        $stmt2->close();
    }

    echo json_encode([
        'ok' => true,
        'bill_id' => $billId,
        'payment_status' => $action,
        'payment_date' => $paymentDate,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}