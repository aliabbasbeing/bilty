<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Optional: session_start(); auth check
    $name    = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        throw new Exception("Company name is required.");
    }
    if (mb_strlen($name) > 150) {
        throw new Exception("Company name too long (max 150).");
    }
    if ($address !== '' && mb_strlen($address) > 255) {
        throw new Exception("Address too long (max 255).");
    }

    // Prevent duplicates (case-insensitive)
    $stmt = $conn->prepare("SELECT id FROM companies WHERE LOWER(name)=LOWER(?) LIMIT 1");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->fetch_assoc()) {
        $stmt->close();
        throw new Exception("A company with this name already exists.");
    }
    $stmt->close();

    $stmt2 = $conn->prepare("INSERT INTO companies (name, address) VALUES (?, ?)");
    if (!$stmt2) throw new Exception("Prepare failed: " . $conn->error);
    $stmt2->bind_param('ss', $name, $address);
    if (!$stmt2->execute()) {
        $err = $stmt2->error;
        $stmt2->close();
        throw new Exception("Insert failed: " . $err);
    }
    $id = $stmt2->insert_id;
    $stmt2->close();

    echo json_encode([
        'ok' => true,
        'company' => [
            'id'      => $id,
            'name'    => $name,
            'address' => $address
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}