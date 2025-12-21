<?php
require_once 'config.php';

/*
  Bill Management Page (Styled Consistently)

  Query Params (GET):
    status: all | UNPAID | PAID (default UNPAID)
    q: search string (bill_no or company name)
    sort: date_desc | date_asc | billno_asc | billno_desc
    page: 1+
*/

$statusFilter = $_GET['status'] ?? 'UNPAID';
$search       = trim($_GET['q'] ?? '');
$sort         = $_GET['sort'] ?? 'date_desc';
$page         = max(1, (int)($_GET['page'] ?? 1));
$pageSize     = 20;

$where  = [];
$params = [];
$types  = '';

if ($statusFilter !== 'all') {
    $where[]  = "b.payment_status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

if ($search !== '') {
    $where[]  = "(b.bill_no LIKE ? OR cp.name LIKE ?)";
    $like      = "%$search%";
    $params[]  = $like; $types .= 's';
    $params[]  = $like; $types .= 's';
}

$orderBy = "b.issue_date DESC";
switch ($sort) {
    case 'date_asc':    $orderBy = "b.issue_date ASC"; break;
    case 'billno_asc':  $orderBy = "CAST(b.bill_no AS UNSIGNED) ASC"; break;
    case 'billno_desc': $orderBy = "CAST(b.bill_no AS UNSIGNED) DESC"; break;
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) cnt
             FROM bills b
             LEFT JOIN companies cp ON cp.id = b.company_id
             $whereSql";
$stmtCount = $conn->prepare($countSql);
if ($stmtCount && $types) $stmtCount->bind_param($types, ...$params);
$stmtCount->execute();
$resCount  = $stmtCount->get_result();
$totalRows = ($rowC = $resCount->fetch_assoc()) ? (int)$rowC['cnt'] : 0;
$stmtCount->close();

$totalPages = max(1, (int)ceil($totalRows / $pageSize));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $pageSize;

$sql = "SELECT b.id, b.bill_no, b.issue_date, b.company_id,
               b.gross_amount, b.tax_amount, b.net_amount,
               b.payment_status, b.payment_date, b.payment_note,
               b.status, b.pdf_path,
               cp.name AS company_name
        FROM bills b
        LEFT JOIN companies cp ON cp.id = b.company_id
        $whereSql
        ORDER BY $orderBy
        LIMIT $offset, $pageSize";
$stmt = $conn->prepare($sql);
if ($stmt && $types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res   = $stmt->get_result();
$bills = [];
while ($row = $res->fetch_assoc()) $bills[] = $row;
$stmt->close();

/* Quick stats (ignore filters) */
$stats = [
  'total'   => 0,
  'paid'    => 0,
  'unpaid'  => 0,
  'out_amt' => 0.0
];
$statSql = "SELECT payment_status, COUNT(*) c, SUM(net_amount) s FROM bills GROUP BY payment_status";
$statRes = $conn->query($statSql);
if ($statRes) {
  while($r=$statRes->fetch_assoc()){
    $stats['total'] += (int)$r['c'];
    if ($r['payment_status']==='PAID') {
      $stats['paid'] = (int)$r['c'];
    } else {
      $stats['unpaid'] += (int)$r['c'];
      $stats['out_amt'] += (float)$r['s'];
    }
  }
  $statRes->close();
}

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtMoney($v){ return number_format((float)$v, 2); }
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>Manage Bills — Bilty Management</title>
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
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem 1rem;
    }
    
    .page-header {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .page-title {
      font-size: 2rem;
      font-weight: 800;
      color: #111827;
      margin: 0 0 0.5rem 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .page-subtitle {
      color: #6b7280;
      font-size: 0.95rem;
      margin: 0;
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
      color: var(--primary);
    }
    
    .stat-icon.success {
      background: linear-gradient(135deg, #10b98115, #10b98125);
      color: #10b981;
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
    
    .filter-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      align-items: end;
    }
    
    .filter-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .filter-input,
    .filter-select {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      font-size: 0.95rem;
      transition: all 0.2s;
    }
    
    .filter-input:focus,
    .filter-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(151, 17, 58, 0.1);
    }
    
    .table-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
    }
    
    .table-wrapper {
      overflow-x: auto;
    }
    
    .modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .modern-table thead th {
      background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
      color: #374151;
      font-size: 0.8125rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 1rem;
      text-align: left;
      border-bottom: 2px solid #e5e7eb;
    }
    
    .modern-table tbody tr {
      transition: background 0.15s;
    }
    
    .modern-table tbody tr:hover {
      background: #f9fafb;
    }
    
    .modern-table tbody td {
      padding: 1rem;
      border-bottom: 1px solid #f3f4f6;
      font-size: 0.9375rem;
      color: #111827;
    }
    
    .modern-table tbody tr:last-child td {
      border-bottom: none;
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
    
    .badge-paid {
      background: #d1fae5;
      color: #065f46;
    }
    
    .badge-unpaid {
      background: #fee2e2;
      color: #991b1b;
    }
    
    .btn {
      padding: 0.625rem 1.25rem;
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
    
    .btn-small {
      padding: 0.5rem 1rem;
      font-size: 0.8125rem;
    }
    
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      color: #6b7280;
    }
    
    .empty-icon {
      font-size: 3rem;
      color: #d1d5db;
      margin-bottom: 1rem;
    }
    
    .pagination {
      display: flex;
      gap: 0.5rem;
      justify-content: center;
      align-items: center;
      padding: 1.5rem;
      background: white;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .pagination a,
    .pagination span {
      padding: 0.5rem 0.75rem;
      border-radius: 8px;
      text-decoration: none;
      color: #374151;
      font-weight: 600;
      transition: all 0.2s;
    }
    
    .pagination a:hover {
      background: #f3f4f6;
    }
    
    .pagination .active {
      background: var(--primary);
      color: white;
    }
    
    @media (max-width: 768px) {
      .page-header {
        padding: 1.5rem;
      }
      
      .page-title {
        font-size: 1.5rem;
      }
      
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .filter-grid {
        grid-template-columns: 1fr;
      }
      
      .modern-table {
        font-size: 0.875rem;
      }
      
      .modern-table thead th,
      .modern-table tbody td {
        padding: 0.75rem 0.5rem;
      }
    }
  </style>
</head>
<body>
  <?php include 'header.php'; ?>


    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i>
        Manage Bills
      </h1>
      <p class="page-subtitle">View, manage, and track all billing records</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon primary">
          <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="stat-label">Total Bills</div>
        <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fa-solid fa-check-circle"></i>
        </div>
        <div class="stat-label">Paid Bills</div>
        <div class="stat-value"><?php echo number_format($stats['paid']); ?></div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-label">Unpaid Bills</div>
        <div class="stat-value"><?php echo number_format($stats['unpaid']); ?></div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon warning">
          <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-label">Outstanding</div>
        <div class="stat-value">Rs. <?php echo fmtMoney($stats['out_amt']); ?></div>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
      <form method="get" class="filter-grid">
        <div>
          <label class="filter-label">Status</label>
          <select name="status" class="filter-select">
            <option value="all" <?php if($statusFilter==='all') echo 'selected'; ?>>All Bills</option>
            <option value="UNPAID" <?php if($statusFilter==='UNPAID') echo 'selected'; ?>>Unpaid</option>
            <option value="PAID" <?php if($statusFilter==='PAID') echo 'selected'; ?>>Paid</option>
          </select>
        </div>
        
        <div>
          <label class="filter-label">Search</label>
          <input 
            type="text" 
            name="q" 
            class="filter-input" 
            placeholder="Bill no or company name..." 
            value="<?php echo esc($search); ?>"
          />
        </div>
        
        <div>
          <label class="filter-label">Sort By</label>
          <select name="sort" class="filter-select">
            <option value="date_desc" <?php if($sort==='date_desc') echo 'selected'; ?>>Date (Newest First)</option>
            <option value="date_asc" <?php if($sort==='date_asc') echo 'selected'; ?>>Date (Oldest First)</option>
            <option value="billno_asc" <?php if($sort==='billno_asc') echo 'selected'; ?>>Bill No (A-Z)</option>
            <option value="billno_desc" <?php if($sort==='billno_desc') echo 'selected'; ?>>Bill No (Z-A)</option>
          </select>
        </div>
        
        <div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fa-solid fa-filter"></i>
            Apply Filters
          </button>
        </div>
      </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-wrapper">
        <?php if (empty($bills)): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <i class="fa-solid fa-inbox"></i>
            </div>
            <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0 0 0.5rem 0;">No bills found</h3>
            <p style="margin: 0;">Try adjusting your filters or create a new bill</p>
          </div>
        <?php else: ?>
          <table class="modern-table">
            <thead>
              <tr>
                <th>Bill No</th>
                <th>Date</th>
                <th>Company</th>
                <th style="text-align: right;">Gross Amount</th>
                <th style="text-align: right;">Tax</th>
                <th style="text-align: right;">Net Amount</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bills as $b):
                  $isPaid = ($b['payment_status'] === 'PAID');
              ?>
              <tr>
                <td><strong><?php echo esc($b['bill_no']); ?></strong></td>
                <td><?php echo esc($b['issue_date']); ?></td>
                <td><?php echo esc($b['company_name'] ?? 'N/A'); ?></td>
                <td style="text-align: right;">Rs. <?php echo fmtMoney($b['gross_amount']); ?></td>
                <td style="text-align: right;">Rs. <?php echo fmtMoney($b['tax_amount']); ?></td>
                <td style="text-align: right;"><strong>Rs. <?php echo fmtMoney($b['net_amount']); ?></strong></td>
                <td style="text-align: center;">
                  <span class="badge <?php echo $isPaid ? 'badge-paid' : 'badge-unpaid'; ?>">
                    <?php echo $isPaid ? 'PAID' : 'UNPAID'; ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <a href="print_bulk.php?ids=<?php echo implode(',', explode(',', $b['consignment_ids'] ?? '')); ?>" 
                     target="_blank" 
                     class="btn btn-secondary btn-small"
                     title="View Bill">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">
            <i class="fa-solid fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        
        <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
          <?php if ($i === $page): ?>
            <span class="active"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
              <?php echo $i; ?>
            </a>
          <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
          <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">
            <i class="fa-solid fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>
