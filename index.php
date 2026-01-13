<?php
require_once 'config.php';

function getCompanyName($conn, $company_id) {
    $stmt = $conn->prepare("SELECT name FROM companies WHERE id = ?");
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();
    return $name ? $name : $company_id;
}

$bilty_no = '';
$found_bilty_id = null;
$bilty_row = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bilty_no = trim($_POST['bilty_no'] ?? '');
    if ($bilty_no !== '') {
        $sql = "SELECT * FROM consignments WHERE bilty_no = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $bilty_no);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $bilty_row = $result->fetch_assoc();
            $found_bilty_id = $bilty_row['id'];
        }
        $stmt->close();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>🚚 Bilty Management Dashboard</title>
  <style>
    :root {
      --main-color: #97113a;
      --main-color-hover: #b31547;
      --main-color-light: #fff0f5;
      --gradient-primary: linear-gradient(135deg, #97113a 0%, #c91f4f 100%);
      --gradient-card: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    
    body {
      background: #f0f2f5;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .dashboard-hero {
      background: var(--gradient-primary);
      border-radius: 20px;
      padding: 3rem 2rem;
      color: white;
      margin-bottom: 2rem;
      box-shadow: 0 10px 30px rgba(151, 17, 58, 0.3);
      position: relative;
      overflow: hidden;
    }
    
    .dashboard-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      border-radius: 50%;
    }
    
    .hero-content {
      position: relative;
      z-index: 1;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 1.75rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid rgba(0,0,0,0.05);
    }
    
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .stat-icon {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }
    
    .stat-icon.primary {
      background: linear-gradient(135deg, #97113a15, #97113a25);
      color: var(--main-color);
    }
    
    .stat-icon.success {
      background: linear-gradient(135deg, #10b98115, #10b98125);
      color: #10b981;
    }
    
    .stat-icon.info {
      background: linear-gradient(135deg, #3b82f615, #3b82f625);
      color: #3b82f6;
    }
    
    .stat-icon.warning {
      background: linear-gradient(135deg, #f59e0b15, #f59e0b25);
      color: #f59e0b;
    }
    
    .stat-label {
      color: #6b7280;
      font-size: 0.875rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      color: #111827;
      line-height: 1;
    }
    
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .action-card {
      background: white;
      border-radius: 16px;
      padding: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      overflow: hidden;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
      border: 1px solid rgba(0,0,0,0.05);
      display: flex;
      flex-direction: column;
    }
    
    .action-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .action-card-header {
      background: var(--gradient-primary);
      padding: 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .action-card-icon {
      width: 48px;
      height: 48px;
      background: rgba(255,255,255,0.2);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      color: white;
    }
    
    .action-card-title {
      color: white;
      font-size: 1.25rem;
      font-weight: 700;
      flex: 1;
    }
    
    .action-card-body {
      padding: 1.5rem;
      color: #6b7280;
      font-size: 0.95rem;
      flex: 1;
    }
    
    .search-section {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
      border: 1px solid rgba(0,0,0,0.05);
    }
    
    .search-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
    }
    
    .search-icon {
      width: 40px;
      height: 40px;
      background: var(--main-color-light);
      color: var(--main-color);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
    }
    
    .search-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #111827;
    }
    
    .search-form {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      align-items: flex-end;
    }
    
    .search-input-group {
      flex: 1;
      min-width: 250px;
    }
    
    .search-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .search-input {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 1rem;
      transition: all 0.2s;
    }
    
    .search-input:focus {
      outline: none;
      border-color: var(--main-color);
      box-shadow: 0 0 0 3px rgba(151, 17, 58, 0.1);
    }
    
    .search-btn {
      background: var(--gradient-primary);
      color: white;
      border: none;
      padding: 0.875rem 2rem;
      border-radius: 10px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 12px rgba(151, 17, 58, 0.3);
    }
    
    .search-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(151, 17, 58, 0.4);
    }
    
    .result-section {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-top: 1.5rem;
      border: 1px solid rgba(0,0,0,0.05);
    }
    
    .result-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      overflow: hidden;
    }
    
    .result-table th {
      background: #f9fafb;
      padding: 1rem;
      text-align: left;
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-bottom: 2px solid #e5e7eb;
    }
    
    .result-table td {
      padding: 1rem;
      border-bottom: 1px solid #f3f4f6;
      color: #111827;
    }
    
    .result-table tbody tr:hover {
      background: #f9fafb;
    }
    
    .btn-group {
      display: flex;
      gap: 0.75rem;
      margin-top: 1.5rem;
    }
    
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border: none;
    }
    
    .btn-primary {
      background: var(--gradient-primary);
      color: white;
      box-shadow: 0 2px 8px rgba(151, 17, 58, 0.2);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(151, 17, 58, 0.3);
    }
    
    .btn-secondary {
      background: #f3f4f6;
      color: #374151;
    }
    
    .btn-secondary:hover {
      background: #e5e7eb;
    }
    
    .no-result {
      text-align: center;
      padding: 3rem;
      color: #6b7280;
    }
    
    .no-result-icon {
      font-size: 3rem;
      color: #d1d5db;
      margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
      .dashboard-hero {
        padding: 2rem 1.5rem;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .quick-actions {
        grid-template-columns: 1fr;
      }
      
      .search-form {
        flex-direction: column;
      }
      
      .search-input-group {
        width: 100%;
      }
      
      .search-btn {
        width: 100%;
        justify-content: center;
      }
      
      .result-table {
        font-size: 0.875rem;
      }
      
      .result-table th,
      .result-table td {
        padding: 0.75rem 0.5rem;
      }
    }
  </style>
  <script>
    function printBiltyExternal(biltyId) {
      if (!biltyId) return;
      window.open('view_bilty_print.php?id=' + biltyId, '_blank');
    }
    function clearSearch() {
      window.location.href = window.location.pathname;
    }
  </script>
</head>
<body style="background: #f0f2f5;">
  <?php include 'header.php'; ?>

  <main class="container" style="max-width: 1400px; margin: 0 auto; padding: 2rem 1rem;">
    
    <!-- Hero Section -->
    <div class="dashboard-hero">
      <div class="hero-content">
        <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0;">
          Bilty Management System
        </h1>
        <p style="font-size: 1.125rem; opacity: 0.95; margin: 0;">
          Welcome back! Manage your bilties, track shipments, and generate reports efficiently.
        </p>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary">
          <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="stat-label">Total Bilties</div>
        <div class="stat-value">
          <?php
          $count_result = $conn->query("SELECT COUNT(*) as total FROM consignments");
          $total_count = $count_result ? $count_result->fetch_assoc()['total'] : 0;
          echo number_format($total_count);
          ?>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-label">Total Amount</div>
        <div class="stat-value">
          <?php
          $amount_result = $conn->query("SELECT SUM(amount) as total FROM consignments");
          $total_amount = $amount_result ? $amount_result->fetch_assoc()['total'] : 0;
          echo 'Rs. ' . number_format($total_amount);
          ?>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-label">Pending Balance</div>
        <div class="stat-value">
          <?php
          $balance_result = $conn->query("SELECT SUM(balance) as total FROM consignments WHERE balance > 0");
          $total_balance = $balance_result ? $balance_result->fetch_assoc()['total'] : 0;
          echo 'Rs. ' . number_format($total_balance);
          ?>
        </div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon info">
          <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div class="stat-label">This Month</div>
        <div class="stat-value">
          <?php
          $month_result = $conn->query("SELECT COUNT(*) as total FROM consignments WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())");
          $month_count = $month_result ? $month_result->fetch_assoc()['total'] : 0;
          echo number_format($month_count);
          ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <a href="add_bilty.php" class="action-card">
        <div class="action-card-header">
          <div class="action-card-icon">
            <i class="fa-solid fa-plus"></i>
          </div>
          <div class="action-card-title">Create New Bilty</div>
          <i class="fa-solid fa-arrow-right" style="color: white; font-size: 1.25rem;"></i>
        </div>
        <div class="action-card-body">
          Quickly add a new bilty record with all shipment details, vehicle information, and payment terms.
        </div>
      </a>
      
      <a href="view_bilty.php" class="action-card">
        <div class="action-card-header">
          <div class="action-card-icon">
            <i class="fa-solid fa-list"></i>
          </div>
          <div class="action-card-title">View All Bilties</div>
          <i class="fa-solid fa-arrow-right" style="color: white; font-size: 1.25rem;"></i>
        </div>
        <div class="action-card-body">
          Browse, search, and filter through all your bilty records with advanced filtering options.
        </div>
      </a>
      
      <a href="manage_bills.php" class="action-card">
        <div class="action-card-header">
          <div class="action-card-icon">
            <i class="fa-solid fa-file-invoice-dollar"></i>
          </div>
          <div class="action-card-title">Manage Bills</div>
          <i class="fa-solid fa-arrow-right" style="color: white; font-size: 1.25rem;"></i>
        </div>
        <div class="action-card-body">
          Generate, view, and manage bills for multiple bilties. Track payment status and outstanding amounts.
        </div>
      </a>
      
      <a href="reports.php" class="action-card">
        <div class="action-card-header">
          <div class="action-card-icon">
            <i class="fa-solid fa-chart-line"></i>
          </div>
          <div class="action-card-title">Reports & Analytics</div>
          <i class="fa-solid fa-arrow-right" style="color: white; font-size: 1.25rem;"></i>
        </div>
        <div class="action-card-body">
          View detailed reports, analytics, and insights about your business performance and trends.
        </div>
      </a>
    </div>

    <!-- Quick Search Section -->
    <div class="search-section">
      <div class="search-header">
        <div class="search-icon">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h2 class="search-title">Quick Bilty Search</h2>
      </div>
      
      <form class="search-form" action="" method="post" autocomplete="off">
        <div class="search-input-group">
          <label class="search-label">Bilty Number</label>
          <input 
            type="text" 
            name="bilty_no" 
            class="search-input" 
            placeholder="Enter bilty number..." 
            required 
            value="<?php echo htmlspecialchars($bilty_no); ?>"
          />
        </div>
        <button type="submit" class="search-btn">
          <i class="fa-solid fa-search"></i>
          Search Bilty
        </button>
      </form>
      
      <?php
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($bilty_no !== '' && $bilty_row) {
          echo '<div class="result-section">';
          echo '<h3 style="font-size: 1.25rem; font-weight: 700; margin: 0 0 1.5rem 0; color: #111827;">Bilty Details</h3>';
          echo '<div style="overflow-x: auto;">';
          echo '<table class="result-table">';
          echo '<thead><tr>
            <th>Bilty No</th>
            <th>Date</th>
            <th>Company</th>
            <th>Vehicle No</th>
            <th>Driver</th>
            <th>Route</th>
            <th>Amount</th>
            <th>Advance</th>
            <th>Balance</th>
          </tr></thead><tbody>';
          echo '<tr>
              <td><strong>'.htmlspecialchars($bilty_row['bilty_no']).'</strong></td>
              <td>'.htmlspecialchars($bilty_row['date']).'</td>
              <td>'.htmlspecialchars(getCompanyName($conn, $bilty_row['company_id'])).'</td>
              <td>'.htmlspecialchars($bilty_row['vehicle_no']).'</td>
              <td>'.htmlspecialchars($bilty_row['driver_name']).'</td>
              <td>'.htmlspecialchars($bilty_row['from_city']).' → '.htmlspecialchars($bilty_row['to_city']).'</td>
              <td><strong>Rs. '.number_format($bilty_row['amount']).'</strong></td>
              <td>Rs. '.number_format($bilty_row['advance']).'</td>
              <td>Rs. '.number_format($bilty_row['balance']).'</td>
          </tr>';
          echo '</tbody></table>';
          echo '</div>';
          
          echo '<div class="btn-group">';
          echo '<button type="button" class="btn btn-primary" onclick="printBiltyExternal('.(int)$found_bilty_id.')">
                  <i class="fa-solid fa-print"></i> Print Bilty
                </button>';
          echo '<button type="button" class="btn btn-secondary" onclick="clearSearch()">
                  <i class="fa-solid fa-times"></i> Clear Search
                </button>';
          echo '</div>';
          echo '</div>';
        } else {
          echo '<div class="result-section">';
          echo '<div class="no-result">';
          echo '<div class="no-result-icon"><i class="fa-solid fa-search"></i></div>';
          echo '<h3 style="font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem 0;">No bilty found</h3>';
          echo '<p style="margin: 0 0 1rem 0;">No bilty record found for number <strong>'.htmlspecialchars($bilty_no).'</strong></p>';
          echo '<button type="button" class="btn btn-secondary" onclick="clearSearch()">
                  <i class="fa-solid fa-times"></i> Try Another Search
                </button>';
          echo '</div>';
          echo '</div>';
        }
      }
      ?>
    </div>

  </main>

  <footer style="background: white; border-top: 1px solid #e5e7eb; margin-top: 4rem; padding: 2rem 1rem;">
    <div style="max-width: 1400px; margin: 0 auto; text-align: center; color: #6b7280; font-size: 0.875rem;">
      <div style="display: inline-flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: center;">
        <span style="display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-code" style="color: var(--main-color);"></i>
          <span>Developed by <strong>Ali Abbas</strong></span>
        </span>
        <span style="color: #d1d5db;">|</span>
        <a href="tel:+923483469617" style="display: flex; align-items: center; gap: 0.5rem; color: var(--main-color); text-decoration: none; font-weight: 500;">
          <i class="fa-solid fa-phone"></i>
          <span>+92 348 3469617</span>
        </a>
      </div>
    </div>
  </footer>
</body>
</html>