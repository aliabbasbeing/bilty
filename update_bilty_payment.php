<?php
require_once 'config.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) exit(json_encode(['ok'=>false, 'error'=>'No data']));

$consignment_id = intval($data['consignment_id'] ?? 0);
$payment_date = $data['payment_date'] ?? '';
$amount = (float)($data['amount'] ?? 0);

if ($consignment_id <= 0 || !$payment_date || $amount <= 0)
    exit(json_encode(['ok'=>false, 'error'=>'Invalid input']));

// Insert payment record
$stmt = $conn->prepare("INSERT INTO payments (consignment_id, payment_date, amount) VALUES (?, ?, ?)");
$stmt->bind_param('isd', $consignment_id, $payment_date, $amount);
$stmt->execute();
$stmt->close();

// Update advance/balance in consignments
$conn->query("UPDATE consignments SET advance = advance + $amount, balance = GREATEST(0, amount - (advance + $amount)) WHERE id = $consignment_id");

// Updated values
$row = $conn->query("SELECT advance, balance FROM consignments WHERE id = $consignment_id")->fetch_assoc();

// Return updated payments list
$plist = [];
$res = $conn->query("SELECT payment_date, amount, method FROM payments WHERE consignment_id = $consignment_id ORDER BY payment_date, id");
while($res && ($p = $res->fetch_assoc())) $plist[] = $p;
if ($res) $res->free();

echo json_encode([
    'ok'=>true,
    'advance'=>round((float)$row['advance'],2),
    'balance'=>round((float)$row['balance'],2),
    'payments'=>$plist
]);