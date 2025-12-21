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
    /* print-friendly adjustments for the print preview page when opened */
    @media print {
      body { background: #fff; color: #000; }
      .no-print { display: none !important; }
      .print-box { box-shadow: none !important; border: none !important; }
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <?php if (isset($success_message)): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
      <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
      <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-6 print-box">
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-primary">
            <?php if ($edit_mode): ?>
              Edit Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?>
            <?php else: ?>
              Bilty #<?php echo htmlspecialchars($bilty['bilty_no']); ?>
            <?php endif; ?>
          </h1>
          <p class="text-sm text-gray-600"><?php echo htmlspecialchars($bilty['company_name'] ?? ''); ?> — <?php echo htmlspecialchars($bilty['date']); ?></p>
        </div>
        <div class="flex items-center gap-2 no-print">
          <a href="view_bilty.php" class="px-3 py-2 rounded-md border bg-white hover:bg-gray-50">Back</a>
          
          <?php if ($edit_mode): ?>
            <button type="button" onclick="window.location.href='?id=<?php echo $id; ?>'" class="px-3 py-2 rounded-md border bg-white">Cancel</button>
          <?php else: ?>
            <button type="button" onclick="window.location.href='?id=<?php echo $id; ?>&edit=true'" class="px-3 py-2 rounded-md bg-primary text-white">Edit</button>
            <button id="printBtn" class="px-3 py-2 rounded-md border bg-white">Print</button>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($edit_mode): ?>
        <!-- Edit Form -->
        <form method="post" action="" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <!-- Company -->
              <div>
                <label for="company_id" class="block text-sm font-medium text-gray-700">Company</label>
                <select id="company_id" name="company_id" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                  <?php foreach ($companies as $company): ?>
                    <option value="<?php echo $company['id']; ?>" <?php if ($company['id'] == $bilty['company_id']) echo 'selected'; ?>>
                      <?php echo htmlspecialchars($company['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              
              <!-- Bilty No -->
              <div>
                <label for="bilty_no" class="block text-sm font-medium text-gray-700">Bilty Number</label>
                <input type="text" name="bilty_no" id="bilty_no" value="<?php echo htmlspecialchars($bilty['bilty_no']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Date -->
              <div>
                <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" id="date" value="<?php echo htmlspecialchars($bilty['date']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- From City -->
              <div>
                <label for="from_city" class="block text-sm font-medium text-gray-700">From City</label>
                <input type="text" name="from_city" id="from_city" value="<?php echo htmlspecialchars($bilty['from_city']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- To City -->
              <div>
                <label for="to_city" class="block text-sm font-medium text-gray-700">To City</label>
                <input type="text" name="to_city" id="to_city" value="<?php echo htmlspecialchars($bilty['to_city']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Vehicle Number -->
              <div>
                <label for="vehicle_no" class="block text-sm font-medium text-gray-700">Vehicle Number</label>
                <input type="text" name="vehicle_no" id="vehicle_no" value="<?php echo htmlspecialchars($bilty['vehicle_no']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Vehicle Type -->
              <div>
                <label for="vehicle_type" class="block text-sm font-medium text-gray-700">Vehicle Type</label>
                <input type="text" name="vehicle_type" id="vehicle_type" value="<?php echo htmlspecialchars($bilty['vehicle_type']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Vehicle Ownership -->
              <div>
                <label for="vehicle_owner" class="block text-sm font-medium text-gray-700">Vehicle Ownership</label>
                <select id="vehicle_owner" name="vehicle_owner" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
                  <option value="">-- Select Ownership --</option>
                  <option value="Own" <?php if ($owner === 'Own') echo 'selected'; ?>>Own</option>
                  <option value="Rental" <?php if ($owner === 'Rental') echo 'selected'; ?>>Rental</option>
                </select>
              </div>
            </div>
            
            <div class="space-y-4">
              <!-- Driver Name -->
              <div>
                <label for="driver_name" class="block text-sm font-medium text-gray-700">Driver Name</label>
                <input type="text" name="driver_name" id="driver_name" value="<?php echo htmlspecialchars($bilty['driver_name']); ?>" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Driver Number -->
              <div>
                <label for="driver_number" class="block text-sm font-medium text-gray-700">Driver Number</label>
                <input type="text" name="driver_number" id="driver_number" value="<?php echo htmlspecialchars($driver_number); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Sender Name -->
              <div>
                <label for="sender_name" class="block text-sm font-medium text-gray-700">Sender Name</label>
                <input type="text" name="sender_name" id="sender_name" value="<?php echo htmlspecialchars($bilty['sender_name']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Quantity -->
              <div>
                <label for="qty" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" name="qty" id="qty" value="<?php echo htmlspecialchars($bilty['qty']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- KM -->
              <div>
                <label for="km" class="block text-sm font-medium text-gray-700">Distance (KM)</label>
                <input type="number" step="0.01" name="km" id="km" value="<?php echo htmlspecialchars($bilty['km']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" onchange="calculateAmount()">
              </div>
              
              <!-- Rate -->
              <div>
                <label for="rate" class="block text-sm font-medium text-gray-700">Rate (per KM)</label>
                <input type="number" step="0.01" name="rate" id="rate" value="<?php echo htmlspecialchars($bilty['rate']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" onchange="calculateAmount()">
              </div>
              
              <!-- Amount -->
              <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
                <input type="number" step="0.01" name="amount" id="amount" value="<?php echo htmlspecialchars($bilty['amount']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm">
              </div>
              
              <!-- Advance -->
              <div>
                <label for="advance" class="block text-sm font-medium text-gray-700">Advance</label>
                <input type="number" step="0.01" name="advance" id="advance" value="<?php echo htmlspecialchars($bilty['advance']); ?>" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" onchange="calculateBalance()">
              </div>
              
              <!-- Balance -->
              <div>
                <label for="balance" class="block text-sm font-medium text-gray-700">Balance</label>
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