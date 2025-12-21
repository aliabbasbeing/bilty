<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // TODO: Add authentication / permission check
    // if (!isset($_SESSION['user_id'])) { throw new Exception("Unauthorized"); }

    // Helper: normalize various date inputs to YYYY-MM-DD (MySQL DATE)
    function normalize_date(?string $input): string {
        $input = trim((string)$input);
        if ($input === '') return date('Y-m-d');

        // If already in YYYY-MM-DD format, accept it (basic check)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            // additional validation via DateTime
            $d = DateTime::createFromFormat('Y-m-d', $input);
            if ($d && $d->format('Y-m-d') === $input) return $input;
        }

        // Try d-M-Y (e.g. 10-Sep-2025 or 1-Jan-2025)
        $d = DateTime::createFromFormat('d-M-Y', $input);
        if ($d && $d->format('Y-m-d') !== '1970-01-01') {
            return $d->format('Y-m-d');
        }

        // Try d/m/Y or d-m-Y (common formats)
        $d = DateTime::createFromFormat('d/m/Y', $input);
        if ($d && $d->format('Y-m-d') !== '1970-01-01') return $d->format('Y-m-d');

        $d = DateTime::createFromFormat('d-m-Y', $input);
        if ($d && $d->format('Y-m-d') !== '1970-01-01') return $d->format('Y-m-d');

        // Fallback: try strtotime
        $ts = strtotime($input);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        // Last resort: current date
        return date('Y-m-d');
    }

    // 1. Read & sanitize consignment IDs
    $rawIds = $_POST['ids'] ?? '';
    $rawIds = preg_replace('/[^0-9,]/', '', $rawIds);
    $idArr  = array_values(array_filter(array_map('intval', explode(',', $rawIds)), fn($v)=>$v>0));
    if (empty($idArr)) {
        throw new Exception("No valid consignment IDs provided.");
    }

    // 2. Fetch consignments from DB
    $inList = implode(',', $idArr);
    $sql = "SELECT c.*, cp.name AS company_name
            FROM consignments c
            JOIN companies cp ON cp.id = c.company_id
            WHERE c.id IN ($inList)
            ORDER BY c.date ASC, c.id ASC";
    $res = $conn->query($sql);
    $bilties = [];
    while ($res && ($row = $res->fetch_assoc())) {
        $bilties[] = $row;
    }
    if (empty($bilties)) {
        throw new Exception("Consignments not found.");
    }

    // 3. Overrides (optional)
    $overridesJson = $_POST['amount_overrides'] ?? '{}';
    $overrides = json_decode($overridesJson, true);
    if (!is_array($overrides)) $overrides = [];

    $gross = 0.0;
    foreach ($bilties as &$b) {
        $amt = (float)($b['amount'] ?? 0);
        if (isset($overrides[$b['id']])) {
            $ov = $overrides[$b['id']];
            if (is_numeric($ov) && $ov >= 0) {
                $amt = (float)$ov;
            }
        }
        $b['_final_amount'] = $amt;
        $gross += $amt;
    }
    unset($b);

    // 4. Tax & totals
    $taxPercent = isset($_POST['tax_percent']) ? (float)$_POST['tax_percent'] : 4.0;
    if ($taxPercent < 0) $taxPercent = 0;
    if ($taxPercent > 100) $taxPercent = 100;

    $taxAmount = round($gross * ($taxPercent / 100), 2);
    $netAmount = round($gross - $taxAmount, 2);

    // 5. Meta fields
    $metaFields = [
        'bill_from_name','bill_from_business','pro_name','bill_from_phone','bill_from_address',
        'bill_to_name','bill_to_address','bill_to_phone',
        'account_name','account_bank','account_iban','bill_date'
    ];
    $meta = [];
    foreach ($metaFields as $f) {
        if (isset($_POST[$f])) {
            $meta[$f] = substr(trim((string)$_POST[$f]), 0, 255);
        }
    }

    // Normalize incoming bill_date to YYYY-MM-DD. If not present, use current date.
    $incomingBillDate = $meta['bill_date'] ?? '';
    $normalizedBillDate = normalize_date($incomingBillDate);
    $meta['bill_date'] = $normalizedBillDate;
    $issueDate = $normalizedBillDate; // this will be safe for MySQL DATE

    // 6. Company ID (nullable)
    $companyId = $bilties[0]['company_id'] ?? null;
    $companyId = is_numeric($companyId) ? (int)$companyId : null;
    if ($companyId !== null && $companyId <= 0) $companyId = null;

    // 7. Prepare final insert data
    $financialYear      = date('Y'); // current year
    $consignmentIdsStr  = implode(',', $idArr);
    $metaJson           = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // 8. Begin transaction
    if (!$conn->begin_transaction()) {
        throw new Exception("Cannot start transaction: " . $conn->error);
    }

    /*
      INSERT INTO bills
        (bill_no, financial_year, issue_date, company_id, consignment_ids,
         gross_amount, tax_percent, tax_amount, net_amount, meta, status)
        VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    */
    $stmt = $conn->prepare("INSERT INTO bills
        (bill_no, financial_year, issue_date, company_id, consignment_ids,
         gross_amount, tax_percent, tax_amount, net_amount, meta, status)
        VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // types: s s s i s s s s s s  => 'ssisssssss'
    $types = 'ssisssssss';

    // Convert numeric to string for safe binding (except company_id is i)
    $grossStr      = number_format($gross, 2, '.', '');
    $taxPercentStr = number_format($taxPercent, 3, '.', '');
    $taxAmountStr  = number_format($taxAmount, 2, '.', '');
    $netAmountStr  = number_format($netAmount, 2, '.', '');
    $status        = 'FINAL';

    // Bind parameters (note: company_id may be null)
    // mysqli will insert NULL for a bound PHP null when binding an 'i' type if the variable is null.
    if (!$stmt->bind_param(
        $types,
        $financialYear,
        $issueDate,
        $companyId,
        $consignmentIdsStr,
        $grossStr,
        $taxPercentStr,
        $taxAmountStr,
        $netAmountStr,
        $metaJson,
        $status
    )) {
        $err = $stmt->error;
        $stmt->close();
        $conn->rollback();
        throw new Exception("Bind failed: " . $err);
    }

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        $conn->rollback();
        throw new Exception("Insert failed: " . $err);
    }

    $billId = $conn->insert_id;
    $stmt->close();

    // Use numeric id as bill_no (adjust format if you prefer)
    $billNo = (string)$billId;

    // Update bill_no
    $stmt2 = $conn->prepare("UPDATE bills SET bill_no=? WHERE id=?");
    if (!$stmt2) {
        $conn->rollback();
        throw new Exception("Prepare update failed: " . $conn->error);
    }
    if (!$stmt2->bind_param('si', $billNo, $billId)) {
        $err = $stmt2->error;
        $stmt2->close();
        $conn->rollback();
        throw new Exception("Bind update failed: " . $err);
    }
    if (!$stmt2->execute()) {
        $err = $stmt2->error;
        $stmt2->close();
        $conn->rollback();
        throw new Exception("Update bill_no failed: " . $err);
    }
    $stmt2->close();

    $conn->commit();

    echo json_encode([
        'ok'         => true,
        'bill_id'    => $billId,
        'bill_no'    => $billNo,
        'gross'      => $grossStr,
        'tax_amount' => $taxAmountStr,
        'net'        => $netAmountStr,
        'issue_date' => $issueDate
    ]);
} catch (Throwable $e) {
    // Attempt rollback (if transaction active)
    if (isset($conn) && $conn instanceof mysqli) {
        @ $conn->rollback();
    }
    http_response_code(400);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage()
    ]);
}
