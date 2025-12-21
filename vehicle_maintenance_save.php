<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Optional: session_start(); and auth check
    $entryDate   = trim($_POST['entry_date'] ?? '');
    $vehicleNo   = trim($_POST['vehicle_no'] ?? '');
    $expenseType = trim($_POST['expense_type'] ?? '');
    $amountRaw   = trim($_POST['amount'] ?? '0');
    $narration   = trim($_POST['narration'] ?? '');

    if (!$entryDate || !DateTime::createFromFormat('Y-m-d', $entryDate)) {
        throw new Exception("Invalid or missing date.");
    }
    if ($vehicleNo === '') throw new Exception("Vehicle number is required.");
    if ($expenseType === '') throw new Exception("Expense detail is required.");

    $amount = (float)$amountRaw;
    if ($amount < 0) throw new Exception("Amount cannot be negative.");

    $stmt = $conn->prepare("INSERT INTO vehicle_maintenance (entry_date, vehicle_no, expense_type, amount, narration) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('sssds', $entryDate, $vehicleNo, $expenseType, $amount, $narration);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Insert failed: " . $err);
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'id' => $newId,
        'entry' => [
            'id' => $newId,
            'entry_date' => $entryDate,
            'vehicle_no' => $vehicleNo,
            'expense_type' => $expenseType,
            'amount' => number_format($amount, 2, '.', ''),
            'narration' => $narration
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}