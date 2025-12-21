<?php
require_once 'config.php'; // database connection

// Helper: safe dynamic bind for mysqli_stmt using automatic type detection
function mysqli_stmt_bind_dynamic(mysqli_stmt $stmt, array $params) {
    if (empty($params)) return true;
    $types = '';
    foreach ($params as $index => &$v) {
        if ($v === null) { $v = ''; $types .= 's'; continue; }
        if (is_int($v)) { $types .= 'i'; continue; }
        if (is_float($v)) { $types .= 'd'; continue; }

        $sv = (string)$v;
        if (preg_match('/^-?\d+$/', $sv)) { $v = (int)$sv; $types .= 'i'; continue; }
        if (is_numeric($sv) && preg_match('/[.eE]/', $sv)) { $v = (float)$sv; $types .= 'd'; continue; }

        $v = $sv;
        $types .= 's';
    }
    $refs = [];
    $refs[] = & $types;
    foreach ($params as $k => &$val) { $refs[] = & $val; }
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}

// Get next bilty number (based on MAX(id) + 1)
function get_next_bilty_no($conn) {
    $next = 1;
    $res = $conn->query("SELECT MAX(id) AS maxid FROM consignments");
    if ($res) {
        $row = $res->fetch_assoc();
        $res->free();
        $next = (int)($row['maxid'] ?? 0) + 1;
    }
    return (string)$next;
}

// check if consignments has rate_type column
$hasRateType = false;
$colCheck = $conn->query("SHOW COLUMNS FROM consignments LIKE 'rate_type'");
if ($colCheck && $colCheck->num_rows > 0) $hasRateType = true;
if ($colCheck) $colCheck->close();

$errors = [];
$success = '';
$auto_bilty_no = get_next_bilty_no($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize input
    $bilty_no      = trim($_POST['bilty_no'] ?? $auto_bilty_no);
    $date          = $_POST['date'] ?? date('Y-m-d');
    $company_id    = intval($_POST['company'] ?? 0);
    $vehicle_no    = trim($_POST['vehicle_no'] ?? '');
    $vehicle_owner = (($_POST['vehicle_owner'] ?? 'own') === 'rental') ? 'rental' : 'own';
    $driver_name   = trim($_POST['driver_name'] ?? '');
    $driver_number = trim($_POST['driver_number'] ?? '');
    $vehicle_type  = trim($_POST['vehicle_type'] ?? '');
    $sender_name   = trim($_POST['sender_name'] ?? '');
    $from_city     = trim($_POST['from_city'] ?? '');
    $to_city       = trim($_POST['to_city'] ?? '');
    $qty           = intval($_POST['qty'] ?? 0);
    $details       = trim($_POST['details'] ?? '');
    $km            = intval($_POST['km'] ?? 0);
    $rate_input    = floatval($_POST['rate'] ?? 0);
    $fixed         = isset($_POST['fixed']) && $_POST['fixed'] === '1';

    // Rate saved to DB: if fixed, we force 0.0
    $rate_to_save = $fixed ? 0.0 : $rate_input;
    $rate_type = $fixed ? 'Fixed' : 'PerKM';

    // Amount calculation: if Fixed, prefer posted amount (user-editable),
    // otherwise compute km * rate_input.
    if ($fixed) {
        $amount = round(floatval(str_replace(',', '', $_POST['amount'] ?? 0)), 2);
    } else {
        $amount = round($km * $rate_input, 2);
    }

    $advance = floatval($_POST['advance'] ?? 0);
    $balance = round($amount - $advance, 2);

    // validations
    if ($bilty_no === '') { $errors[] = "Bilty number is required."; }
    elseif (strlen($bilty_no) > 50) { $errors[] = "Bilty number must be 50 characters or less."; }

    if ($company_id <= 0) { $errors[] = "Please select a company."; }
    if ($qty < 0) { $errors[] = "Quantity cannot be negative."; }
    if ($km < 0) { $errors[] = "Distance (KM) cannot be negative."; }
    if ($rate_input < 0) { $errors[] = "Rate cannot be negative."; }
    if ($advance < 0) { $errors[] = "Advance cannot be negative."; }
    if ($amount < 0) { $errors[] = "Amount cannot be negative."; }
    if ($amount == 0) { $errors[] = "Amount must be greater than zero."; }

    // extra info: vehicle ownership + driver number
    $extra = [];
    $extra[] = "Vehicle: " . ($vehicle_owner === 'rental' ? "Rental" : "Own");
    if ($driver_number !== '') {
        $extra[] = "Driver number: " . $driver_number;
    }
    if (!empty($extra)) {
        $details = trim($details);
        if ($details !== '') $details .= "\n\n";
        $details .= "Additional info:\n" . implode("\n", $extra);
    }

    // save if no errors
    if (empty($errors)) {
        // build INSERT with optional rate_type column
        $cols = [
            'company_id','bilty_no','date','vehicle_no','driver_name','vehicle_type',
            'sender_name','from_city','to_city','qty','details','km','rate','amount','advance','balance'
        ];
        $placeholders = array_fill(0, count($cols), '?');

        if ($hasRateType) {
            $cols[] = 'rate_type';
            $placeholders[] = '?';
        }

        $sql = "INSERT INTO consignments (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";

        $attempts = 0;
        $maxAttempts = 3;
        $inserted = false;

        while ($attempts < $maxAttempts && !$inserted) {
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[] = "Database prepare error: " . $conn->error;
                break;
            }

            // prepare params in same order as $cols
            $params = [
                $company_id,
                $bilty_no,
                $date,
                $vehicle_no,
                $driver_name,
                $vehicle_type,
                $sender_name,
                $from_city,
                $to_city,
                $qty,
                $details,
                $km,
                $rate_to_save,
                $amount,
                $advance,
                $balance
            ];
            if ($hasRateType) $params[] = $rate_type;

            if (!mysqli_stmt_bind_dynamic($stmt, $params)) {
                $errors[] = "Failed to bind parameters.";
                $stmt->close();
                break;
            }

            if ($stmt->execute()) {
                $inserted = true;
                $success = "Bilty has been saved successfully.";
                $_POST = [];
                $auto_bilty_no = get_next_bilty_no($conn);
            } else {
                if ($conn->errno === 1062) { // duplicate bilty_no
                    $attempts++;
                    $bilty_no = get_next_bilty_no($conn);
                    $stmt->close();
                    continue;
                } else {
                    $errors[] = "Database error: " . $conn->error;
                    $stmt->close();
                    break;
                }
            }
            $stmt->close();
        }

        if (!$inserted && empty($errors)) {
            $errors[] = "Failed to save bilty after multiple attempts. Please try again.";
        }
    }
}

// fetch companies (with address if column exists)
$hasAddress = false;
$colCheck = $conn->query("SHOW COLUMNS FROM companies LIKE 'address'");
if ($colCheck && $colCheck->num_rows > 0) $hasAddress = true;
if ($colCheck) $colCheck->close();

$companies = [];
if ($hasAddress) {
    $res = $conn->query("SELECT id, name, address FROM companies ORDER BY name ASC");
} else {
    $res = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
}
if ($res) {
    while ($row = $res->fetch_assoc()) { $companies[] = $row; }
    $res->free();
}

// ensure displayed bilty_no is fresh when not posting
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $auto_bilty_no = get_next_bilty_no($conn); }
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Add New Bilty — Bilty Management</title>
  <style>
    :root {
      --primary: #97113a;
      --primary-hover: #b31547;
      --primary-light: #fff0f5;
    }
    
    body {
      background: #f0f2f5;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .form-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .form-header {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }
    
    .form-title {
      font-size: 2rem;
      font-weight: 800;
      color: #111827;
      margin: 0 0 0.5rem 0;
    }
    
    .form-subtitle {
      color: #6b7280;
      font-size: 0.95rem;
      margin: 0;
    }
    
    .header-actions {
      display: flex;
      gap: 0.75rem;
    }
    
    .form-card {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .card-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
      margin: 0 0 1.5rem 0;
      padding-bottom: 1rem;
      border-bottom: 2px solid #f3f4f6;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .card-icon {
      width: 40px;
      height: 40px;
      background: var(--primary-light);
      color: var(--primary);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
    
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }
    
    .form-group {
      margin-bottom: 0;
    }
    
    .form-group.full-width {
      grid-column: 1 / -1;
    }
    
    .form-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .form-label.required::after {
      content: '*';
      color: #ef4444;
      margin-left: 0.25rem;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s;
      background: white;
    }
    
    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(151, 17, 58, 0.1);
    }
    
    .form-input:read-only {
      background: #f9fafb;
      cursor: not-allowed;
    }
    
    .form-hint {
      font-size: 0.8125rem;
      color: #6b7280;
      margin-top: 0.375rem;
    }
    
    .radio-group {
      display: flex;
      gap: 1.5rem;
      margin-top: 0.5rem;
    }
    
    .radio-label {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      cursor: pointer;
      font-size: 0.95rem;
      color: #374151;
    }
    
    .radio-label input[type="radio"] {
      width: 1.125rem;
      height: 1.125rem;
      cursor: pointer;
    }
    
    .input-with-button {
      display: flex;
      gap: 0.75rem;
    }
    
    .input-with-button .form-input,
    .input-with-button .form-select {
      flex: 1;
    }
    
    .btn {
      padding: 0.875rem 1.5rem;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border: none;
      text-decoration: none;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(151, 17, 58, 0.3);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(151, 17, 58, 0.4);
    }
    
    .btn-secondary {
      background: #f3f4f6;
      color: #374151;
      border: 2px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
      background: #e5e7eb;
    }
    
    .btn-small {
      padding: 0.625rem 1rem;
      font-size: 0.875rem;
    }
    
    .alert {
      padding: 1rem 1.25rem;
      border-radius: 10px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
    }
    
    .alert-success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #10b981;
    }
    
    .alert-error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #ef4444;
    }
    
    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 2px solid #f3f4f6;
    }
    
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 5000;
      backdrop-filter: blur(4px);
    }
    
    .modal-backdrop.show {
      display: flex;
    }
    
    .modal-panel {
      background: white;
      width: 500px;
      max-width: 92%;
      border-radius: 16px;
      padding: 2rem;
      position: relative;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      animation: modalSlideIn 0.3s ease-out;
    }
    
    @keyframes modalSlideIn {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    
    .modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #111827;
      margin: 0;
    }
    
    .modal-close {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      background: #f3f4f6;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: background 0.2s;
    }
    
    .modal-close:hover {
      background: #e5e7eb;
    }
    
    .company-address-preview {
      margin-top: 0.5rem;
      padding: 0.75rem;
      background: #f9fafb;
      border-radius: 8px;
      font-size: 0.875rem;
      color: #6b7280;
      display: none;
    }
    
    .company-address-preview.show {
      display: block;
    }
    
    @media (max-width: 768px) {
      .form-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .header-actions {
        width: 100%;
        flex-direction: column;
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .form-actions {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?> 

  <div class="form-container">
    <!-- Form Header -->
    <div class="form-header">
      <div>
        <h1 class="form-title">
          <i class="fa-solid fa-truck-fast" style="color: var(--primary);"></i>
          Create New Bilty
        </h1>
        <p class="form-subtitle">Fill in the details below to create a new bilty record</p>
      </div>
      <div class="header-actions">
        <a href="view_bilty.php" class="btn btn-secondary btn-small">
          <i class="fa-solid fa-list"></i>
          View All
        </a>
        <a href="index.php" class="btn btn-secondary btn-small">
          <i class="fa-solid fa-home"></i>
          Home
        </a>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-check-circle" style="font-size: 1.25rem;"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-exclamation-circle" style="font-size: 1.25rem;"></i>
        <div>
          <strong>Please fix the following errors:</strong>
          <ul style="margin: 0.5rem 0 0 1.25rem; list-style: disc;">
            <?php foreach ($errors as $e): ?>
              <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <form method="post" id="biltyForm" novalidate>
      <!-- Basic Information Card -->
      <div class="form-card">
        <h2 class="card-title">
          <div class="card-icon">
            <i class="fa-solid fa-file-lines"></i>
          </div>
          Basic Information
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label required">Bilty Number</label>
            <input 
              type="text" 
              name="bilty_no" 
              class="form-input" 
              value="<?php echo htmlspecialchars($_POST['bilty_no'] ?? $auto_bilty_no); ?>"
              readonly
            />
            <p class="form-hint">Auto-generated bilty number</p>
          </div>
          
          <div class="form-group">
            <label class="form-label required">Date</label>
            <input 
              type="date" 
              name="date" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>"
              required
            />
          </div>
          
          <div class="form-group full-width">
            <label class="form-label required">Company</label>
            <div class="input-with-button">
              <select name="company" class="form-select" required id="company">
                <option value="">— Select company —</option>
                <?php foreach ($companies as $c): ?>
                  <option
                    value="<?php echo $c['id']; ?>"
                    data-address="<?php echo htmlspecialchars($c['address'] ?? ''); ?>"
                    <?php if (isset($_POST['company']) && (int)$_POST['company'] === (int)$c['id']) echo 'selected'; ?>
                  >
                    <?php echo htmlspecialchars($c['name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" id="btnAddCompany" class="btn btn-primary btn-small">
                <i class="fa-solid fa-plus"></i>
                Add Company
              </button>
            </div>
            <div id="companyAddressPreview" class="company-address-preview"></div>
          </div>
        </div>
      </div>

      <!-- Vehicle Information Card -->
      <div class="form-card">
        <h2 class="card-title">
          <div class="card-icon">
            <i class="fa-solid fa-truck"></i>
          </div>
          Vehicle Information
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Vehicle Ownership</label>
            <div class="radio-group">
              <label class="radio-label">
                <input 
                  type="radio" 
                  name="vehicle_owner" 
                  value="own" 
                  <?php if (($_POST['vehicle_owner'] ?? 'own') !== 'rental') echo 'checked'; ?>
                />
                <span>Own</span>
              </label>
              <label class="radio-label">
                <input 
                  type="radio" 
                  name="vehicle_owner" 
                  value="rental" 
                  <?php if (($_POST['vehicle_owner'] ?? '') === 'rental') echo 'checked'; ?>
                />
                <span>Rental</span>
              </label>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">Vehicle Number</label>
            <input 
              type="text" 
              name="vehicle_no" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['vehicle_no'] ?? ''); ?>"
              placeholder="e.g., ABC-1234"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Vehicle Type</label>
            <input 
              type="text" 
              name="vehicle_type" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['vehicle_type'] ?? ''); ?>"
              placeholder="e.g., Truck, Van"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Driver Name</label>
            <input 
              type="text" 
              name="driver_name" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['driver_name'] ?? ''); ?>"
              placeholder="Enter driver name"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Driver Phone</label>
            <input 
              type="tel" 
              name="driver_number" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['driver_number'] ?? ''); ?>"
              placeholder="+92 300 1234567"
            />
          </div>
        </div>
      </div>

      <!-- Shipment Details Card -->
      <div class="form-card">
        <h2 class="card-title">
          <div class="card-icon">
            <i class="fa-solid fa-box"></i>
          </div>
          Shipment Details
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Sender Name</label>
            <input 
              type="text" 
              name="sender_name" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['sender_name'] ?? ''); ?>"
              placeholder="Enter sender name"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">From City</label>
            <input 
              type="text" 
              name="from_city" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['from_city'] ?? ''); ?>"
              placeholder="Origin city"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">To City</label>
            <input 
              type="text" 
              name="to_city" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['to_city'] ?? ''); ?>"
              placeholder="Destination city"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input 
              type="number" 
              name="qty" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['qty'] ?? ''); ?>"
              placeholder="0"
              min="0"
            />
          </div>
          
          <div class="form-group full-width">
            <label class="form-label">Details / Notes</label>
            <textarea 
              name="details" 
              class="form-textarea" 
              rows="3"
              placeholder="Additional shipment details..."
            ><?php echo htmlspecialchars($_POST['details'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Financial Details Card -->
      <div class="form-card">
        <h2 class="card-title">
          <div class="card-icon">
            <i class="fa-solid fa-dollar-sign"></i>
          </div>
          Financial Details
        </h2>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Distance (KM)</label>
            <input 
              type="number" 
              name="km" 
              id="km" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['km'] ?? ''); ?>"
              placeholder="0"
              min="0"
              step="0.01"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Rate (per KM)</label>
            <input 
              type="number" 
              name="rate" 
              id="rate" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['rate'] ?? ''); ?>"
              placeholder="0.00"
              min="0"
              step="0.01"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">
              <input 
                type="checkbox" 
                name="fixed" 
                id="fixed" 
                value="1"
                <?php if (isset($_POST['fixed']) && $_POST['fixed'] === '1') echo 'checked'; ?>
                style="margin-right: 0.5rem;"
              />
              Fixed Amount
            </label>
          </div>
          
          <div class="form-group">
            <label class="form-label required">Amount</label>
            <input 
              type="number" 
              name="amount" 
              id="amount" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>"
              placeholder="0.00"
              min="0"
              step="0.01"
              required
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Advance</label>
            <input 
              type="number" 
              name="advance" 
              id="advance" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['advance'] ?? '0'); ?>"
              placeholder="0.00"
              min="0"
              step="0.01"
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Balance</label>
            <input 
              type="number" 
              name="balance" 
              id="balance" 
              class="form-input"
              value="<?php echo htmlspecialchars($_POST['balance'] ?? ''); ?>"
              placeholder="0.00"
              readonly
            />
            <p class="form-hint">Auto-calculated (Amount - Advance)</p>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <a href="index.php" class="btn btn-secondary">
          <i class="fa-solid fa-times"></i>
          Cancel
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fa-solid fa-check"></i>
          Create Bilty
        </button>
      </div>
    </form>
  </div>

  <!-- Add Company Modal -->
  <div class="modal-backdrop" id="companyModal">
    <div class="modal-panel">
      <div class="modal-header">
        <h3 class="modal-title">Add New Company</h3>
        <button type="button" class="modal-close" id="closeModal">
          <i class="fa-solid fa-times"></i>
        </button>
      </div>
      <form id="companyForm" method="post" action="company_save.php">
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="filter-label">Company Name</label>
          <input type="text" name="company_name" class="form-input" required />
        </div>
        <div class="form-group" style="margin-bottom: 1rem;">
          <label class="filter-label">Address</label>
          <textarea name="company_address" class="form-textarea" rows="3"></textarea>
        </div>
        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
          <button type="button" class="btn btn-secondary" onclick="closeCompanyModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Company</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal functions
    function openCompanyModal() {
      document.getElementById('companyModal').classList.add('show');
      document.getElementById('companyModal').style.display = 'flex';
    }
    
    function closeCompanyModal() {
      document.getElementById('companyModal').classList.remove('show');
      document.getElementById('companyModal').style.display = 'none';
    }
    
    document.getElementById('btnAddCompany')?.addEventListener('click', openCompanyModal);
    document.getElementById('closeModal')?.addEventListener('click', closeCompanyModal);
    
    // Company address preview
    document.getElementById('company')?.addEventListener('change', function() {
      const selected = this.options[this.selectedIndex];
      const address = selected?.getAttribute('data-address');
      const preview = document.getElementById('companyAddressPreview');
      if (address && address.trim()) {
        preview.textContent = 'Address: ' + address;
        preview.classList.add('show');
      } else {
        preview.textContent = '';
        preview.classList.remove('show');
      }
    });
    
    // Financial calculations
    const kmInput = document.getElementById('km');
    const rateInput = document.getElementById('rate');
    const fixedCheckbox = document.getElementById('fixed');
    const amountInput = document.getElementById('amount');
    const advanceInput = document.getElementById('advance');
    const balanceInput = document.getElementById('balance');
    
    function calculateFinancials() {
      const km = parseFloat(kmInput?.value || 0);
      const rate = parseFloat(rateInput?.value || 0);
      const isFixed = fixedCheckbox?.checked || false;
      const advance = parseFloat(advanceInput?.value || 0);
      
      let amount = parseFloat(amountInput?.value || 0);
      
      if (!isFixed) {
        amount = km * rate;
        if (amountInput) amountInput.value = amount.toFixed(2);
      }
      
      const balance = amount - advance;
      if (balanceInput) balanceInput.value = balance.toFixed(2);
    }
    
    // Attach calculation listeners
    [kmInput, rateInput, advanceInput, amountInput].forEach(input => {
      input?.addEventListener('input', calculateFinancials);
    });
    fixedCheckbox?.addEventListener('change', function() {
      if (this.checked) {
        amountInput?.removeAttribute('readonly');
        amountInput?.focus();
      } else {
        calculateFinancials();
      }
    });
    
    // Initial calculation
    calculateFinancials();
  </script>
</body>
</html>
