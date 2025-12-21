<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header('Location: view_bilty.php'); exit; }

// Process form submission if posted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bilty'])) {
    // Extract and sanitize form data
    $company_id = intval($_POST['company_id']);
    $bilty_no = trim($_POST['bilty_no']);
    $date = trim($_POST['date']);
    $from_city = trim($_POST['from_city']);
    $to_city = trim($_POST['to_city']);
    $vehicle_no = trim($_POST['vehicle_no']);
    $vehicle_type = trim($_POST['vehicle_type']);
    $driver_name = trim($_POST['driver_name']);
    $sender_name = trim($_POST['sender_name']);
    $qty = intval($_POST['qty']);
    $km = floatval($_POST['km']);
    $rate = floatval($_POST['rate']);
    $amount = floatval($_POST['amount']);
    $advance = floatval($_POST['advance']);
    $balance = floatval($_POST['balance']);
    $notes = trim($_POST['notes']);
    
    // Prepare meta details
    $vehicle_owner = isset($_POST['vehicle_owner']) ? trim($_POST['vehicle_owner']) : '';
    $driver_number = isset($_POST['driver_number']) ? trim($_POST['driver_number']) : '';
    
    $details = "---META---\nVehicle: $vehicle_owner\nDriverNumber: $driver_number\n---ENDMETA---\n\n$notes";
    
    // Update bilty in database
    $stmt = $conn->prepare("UPDATE consignments SET company_id = ?, bilty_no = ?, date = ?, from_city = ?, to_city = ?, 
                           vehicle_no = ?, vehicle_type = ?, driver_name = ?, sender_name = ?, qty = ?, 
                           km = ?, rate = ?, amount = ?, advance = ?, balance = ?, details = ? WHERE id = ?");
    
    $stmt->bind_param('issssssssidddddsi', $company_id, $bilty_no, $date, $from_city, $to_city, 
                      $vehicle_no, $vehicle_type, $driver_name, $sender_name, $qty, 
                      $km, $rate, $amount, $advance, $balance, $details, $id);
    
    if ($stmt->execute()) {
        $success_message = "Bilty updated successfully!";
    } else {
        $error_message = "Error updating bilty: " . $conn->error;
    }
    $stmt->close();
}

// Fetch companies for dropdown
$companies = [];
$result = $conn->query("SELECT id, name FROM companies ORDER BY name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    $result->free();
}

// Fetch single bilty
$stmt = $conn->prepare("SELECT c.*, cp.name AS company_name FROM consignments c JOIN companies cp ON cp.id = c.company_id WHERE c.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$bilty = $res ? $res->fetch_assoc() : null;
$stmt->close();
if (!$bilty) { header('Location: view_bilty.php'); exit; }

// Extract meta
$owner = '';
$driver_number = '';
if (!empty($bilty['details'])) {
    if (preg_match('/Vehicle:\s*(Own|Rental)/i', $bilty['details'], $m)) $owner = ucfirst(strtolower($m[1]));
    if (preg_match('/DriverNumber:\s*([0-9+\-\s()]+)/i', $bilty['details'], $m2)) $driver_number = trim($m2[1]);
    $notes = preg_replace('/^---META---.*?---ENDMETA---\s*/s', '', $bilty['details']);
} else $notes = '';

// Default to view mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?> — Bilty Management</title>
  <style>
    :root {
      --primary: #97113a;
      --primary-hover: #b31547;
    }
    
    body {
      background: #f0f2f5;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .page-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .page-header {
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
    
    .page-title {
      font-size: 2rem;
      font-weight: 800;
      color: #111827;
      margin: 0 0 0.5rem 0;
    }
    
    .page-subtitle {
      color: #6b7280;
      font-size: 0.95rem;
      margin: 0;
    }
    
    .header-actions {
      display: flex;
      gap: 0.75rem;
    }
    
    .detail-card {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .card-section {
      margin-bottom: 2rem;
    }
    
    .card-section:last-child {
      margin-bottom: 0;
    }
    
    .section-title {
      font-size: 1.125rem;
      font-weight: 700;
      color: #111827;
      margin: 0 0 1.5rem 0;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #f3f4f6;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .section-icon {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #97113a15, #97113a25);
      color: var(--primary);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }
    
    .detail-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
    }
    
    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }
    
    .detail-label {
      font-size: 0.875rem;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .detail-value {
      font-size: 1rem;
      font-weight: 600;
      color: #111827;
    }
    
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0.375rem 0.75rem;
      border-radius: 999px;
      font-size: 0.8125rem;
      font-weight: 600;
      letter-spacing: 0.025em;
    }
    
    .badge-own {
      background: #d1fae5;
      color: #065f46;
    }
    
    .badge-rental {
      background: #dbeafe;
      color: #1e40af;
    }
    
    .btn {
      padding: 0.75rem 1.25rem;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border: none;
      text-decoration: none;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-hover));
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
    
    .financial-summary {
      background: linear-gradient(135deg, #f9fafb, #ffffff);
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      padding: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .financial-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f3f4f6;
    }
    
    .financial-row:last-child {
      border-bottom: none;
      padding-top: 1rem;
      margin-top: 0.5rem;
      border-top: 2px solid #e5e7eb;
    }
    
    .financial-label {
      font-size: 0.9375rem;
      font-weight: 600;
      color: #6b7280;
    }
    
    .financial-value {
      font-size: 1.125rem;
      font-weight: 700;
      color: #111827;
    }
    
    .financial-row:last-child .financial-label,
    .financial-row:last-child .financial-value {
      color: var(--primary);
      font-size: 1.25rem;
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
    
    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .detail-card { box-shadow: none; }
    }
    
    @media (max-width: 768px) {
      .page-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .header-actions {
        width: 100%;
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
      }
      
      .detail-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="page-container">
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="alert alert-success">
        <i class="fa-solid fa-check-circle" style="font-size: 1.25rem;"></i>
        <div><?php echo htmlspecialchars($success_message); ?></div>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
      <div class="alert alert-error">
        <i class="fa-solid fa-exclamation-circle" style="font-size: 1.25rem;"></i>
        <div><?php echo htmlspecialchars($error_message); ?></div>
      </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header no-print">
      <div>
        <h1 class="page-title">
          <i class="fa-solid fa-file-lines" style="color: var(--primary);"></i>
          Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?>
        </h1>
        <p class="page-subtitle">
          <?php echo htmlspecialchars($bilty['company_name'] ?? ''); ?> — 
          <?php echo htmlspecialchars(date('d M Y', strtotime($bilty['date']))); ?>
        </p>
      </div>
      <div class="header-actions">
        <a href="view_bilty.php" class="btn btn-secondary">
          <i class="fa-solid fa-arrow-left"></i>
          Back to List
        </a>
        <a href="view_bilty_print.php?id=<?php echo $id; ?>" target="_blank" class="btn btn-primary">
          <i class="fa-solid fa-print"></i>
          Print Bilty
        </a>
      </div>
    </div>

    <!-- Bilty Details Card -->
    <div class="detail-card">
      <!-- Company Information -->
      <div class="card-section">
        <h2 class="section-title">
          <div class="section-icon">
            <i class="fa-solid fa-building"></i>
          </div>
          Company Information
        </h2>
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label">Company Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['company_name'] ?? ''); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Sender Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['sender_name'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Bilty Number</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['bilty_no']); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Date</div>
            <div class="detail-value"><?php echo htmlspecialchars(date('d M Y', strtotime($bilty['date']))); ?></div>
          </div>
        </div>
      </div>

      <!-- Shipment Details -->
      <div class="card-section">
        <h2 class="section-title">
          <div class="section-icon">
            <i class="fa-solid fa-route"></i>
          </div>
          Shipment Details
        </h2>
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label">From City</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['from_city'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">To City</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['to_city'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Quantity</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['qty'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Distance (KM)</div>
            <div class="detail-value"><?php echo number_format($bilty['km'], 2); ?></div>
          </div>
        </div>
      </div>

      <!-- Vehicle & Driver Information -->
      <div class="card-section">
        <h2 class="section-title">
          <div class="section-icon">
            <i class="fa-solid fa-truck"></i>
          </div>
          Vehicle & Driver Information
        </h2>
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label">Vehicle Number</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['vehicle_no'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Vehicle Type</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['vehicle_type'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Vehicle Ownership</div>
            <div class="detail-value">
              <?php if ($owner === 'Rental'): ?>
                <span class="badge badge-rental">Rental</span>
              <?php elseif ($owner === 'Own'): ?>
                <span class="badge badge-own">Own</span>
              <?php else: ?>
                —
              <?php endif; ?>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Driver Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($bilty['driver_name'] ?: '—'); ?></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Driver Phone</div>
            <div class="detail-value"><?php echo htmlspecialchars($driver_number ?: '—'); ?></div>
          </div>
        </div>
      </div>

      <!-- Financial Details -->
      <div class="card-section">
        <h2 class="section-title">
          <div class="section-icon">
            <i class="fa-solid fa-dollar-sign"></i>
          </div>
          Financial Details
        </h2>
        <div class="financial-summary">
          <div class="financial-row">
            <div class="financial-label">Rate (per KM)</div>
            <div class="financial-value">Rs. <?php echo number_format($bilty['rate'], 2); ?></div>
          </div>
          <div class="financial-row">
            <div class="financial-label">Total Amount</div>
            <div class="financial-value">Rs. <?php echo number_format($bilty['amount'], 2); ?></div>
          </div>
          <div class="financial-row">
            <div class="financial-label">Advance Paid</div>
            <div class="financial-value">Rs. <?php echo number_format($bilty['advance'], 2); ?></div>
          </div>
          <div class="financial-row">
            <div class="financial-label">Balance Due</div>
            <div class="financial-value">Rs. <?php echo number_format($bilty['balance'], 2); ?></div>
          </div>
        </div>
      </div>

      <!-- Additional Notes -->
      <?php if ($notes): ?>
      <div class="card-section">
        <h2 class="section-title">
          <div class="section-icon">
            <i class="fa-solid fa-note-sticky"></i>
          </div>
          Additional Notes
        </h2>
        <div style="background: #f9fafb; border-radius: 10px; padding: 1rem; color: #374151; white-space: pre-wrap;">
          <?php echo htmlspecialchars($notes); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
                <input type="number" step="0.01" name="balance" id="balance" value="<?php echo htmlspecialchars($bilty['balance']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
            </div>
          </div>
          
          <!-- Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" id="notes" rows="4" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm"><?php echo htmlspecialchars($notes); ?></textarea>
          </div>
          
          <div class="flex justify-end gap-2">
            <a href="?id=<?php echo $id; ?>" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
              Cancel
            </a>
            <button type="submit" name="update_bilty" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              Save Changes
            </button>
          </div>
        </form>
      <?php else: ?>
        <!-- View Mode -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-3">
            <div>
              <div class="text-xs text-gray-500">Route</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['from_city'] ?? ''); ?> → <?php echo htmlspecialchars($bilty['to_city'] ?? ''); ?></div>
            </div>

            <div>
              <div class="text-xs text-gray-500">Vehicle</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['vehicle_no'] ?? ''); ?></div>
              <?php if (!empty($bilty['vehicle_type'])): ?><div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($bilty['vehicle_type']); ?></div><?php endif; ?>
              <div class="mt-2">
                <span class="text-xs text-gray-500">Ownership</span>
                <?php if ($owner === 'Rental'): ?>
                  <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-800">Rental</div>
                <?php elseif ($owner === 'Own'): ?>
                  <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Own</div>
                <?php else: ?>
                  <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-700">—</div>
                <?php endif; ?>
              </div>
            </div>

            <div>
              <div class="text-xs text-gray-500">Driver</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['driver_name'] ?? ''); ?></div>
              <?php if ($driver_number): ?><div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($driver_number); ?></div><?php endif; ?>
            </div>

            <div>
              <div class="text-xs text-gray-500">Sender</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['sender_name'] ?? ''); ?></div>
            </div>
          </div>

          <div class="space-y-3">
            <div>
              <div class="text-xs text-gray-500">Quantity</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['qty'] ?? 0); ?></div>
            </div>

            <div>
              <div class="text-xs text-gray-500">Distance (KM)</div>
              <div class="font-medium text-gray-800"><?php echo htmlspecialchars($bilty['km'] ?? 0); ?></div>
            </div>

            <div>
              <div class="text-xs text-gray-500">Rate (per KM)</div>
              <div class="font-medium text-gray-800"><?php echo number_format((float)($bilty['rate'] ?? 0), 2); ?></div>
            </div>

            <div>
              <div class="text-xs text-gray-500">Amount</div>
              <div class="text-lg font-bold text-gray-900"><?php echo number_format((float)($bilty['amount'] ?? 0), 2); ?></div>
              <div class="text-sm text-gray-600 mt-1">Advance: <?php echo number_format((float)($bilty['advance'] ?? 0), 2); ?> • Balance: <span class="text-red-600"><?php echo number_format((float)($bilty['balance'] ?? 0), 2); ?></span></div>
            </div>
          </div>
        </div>

        <?php if (!empty($notes)): ?>
          <div class="mt-6">
            <div class="text-xs text-gray-500">Notes</div>
            <div class="mt-2 whitespace-pre-line text-gray-700"><?php echo htmlspecialchars($notes); ?></div>
          </div>
        <?php endif; ?>

        <div class="mt-6 flex justify-end gap-2 no-print">
          <a href="view_bilty.php" class="px-4 py-2 rounded-md border bg-white">Close</a>
          <a href="?id=<?php echo $id; ?>&edit=true" class="px-4 py-2 rounded-md bg-primary text-white">Edit Bilty</a>
          <button id="printBtn2" class="px-4 py-2 rounded-md border bg-white">Print (Template)</button>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // Calculate amount based on KM and rate
    function calculateAmount() {
      const km = parseFloat(document.getElementById('km').value) || 0;
      const rate = parseFloat(document.getElementById('rate').value) || 0;
      const amount = km * rate;
      document.getElementById('amount').value = amount.toFixed(2);
      calculateBalance();
    }
    
    // Calculate balance based on amount and advance
    function calculateBalance() {
      const amount = parseFloat(document.getElementById('amount').value) || 0;
      const advance = parseFloat(document.getElementById('advance').value) || 0;
      const balance = amount - advance;
      document.getElementById('balance').value = balance.toFixed(2);
    }
    
    // Open printable template in a new window
    document.getElementById('printBtn')?.addEventListener('click', function(){
      const url = 'view_bilty_print.php?id=<?php echo urlencode($bilty['id']); ?>';
      const w = window.open(url, '_blank', 'noopener');
      if (w) w.focus();
    });
    
    document.getElementById('printBtn2')?.addEventListener('click', function(){
      const url = 'view_bilty_print.php?id=<?php echo urlencode($bilty['id']); ?>&template=1';
      const w = window.open(url, '_blank', 'noopener');
      if (w) w.focus();
    });
  </script>
</body>
</html>