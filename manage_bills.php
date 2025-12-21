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
<meta charset="utf-8" />
<title>Manage Bills</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
 <link href="output.css" rel="stylesheet">
  <link rel="stylesheet" href="fontawesome/css/all.min.css">
<style>
:root {
  --primary: #023783;
  --primary-hover: #7d0e30;
  --radius: 10px;
  --shadow-sm: 0 2px 4px rgba(0,0,0,.06);
  --shadow-md: 0 4px 10px rgba(0,0,0,.08);
  --shadow-lg: 0 8px 24px -4px rgba(0,0,0,.15);
  --font-stack: "Inter", "Arial", sans-serif;
}

html,body {
  font-family: var(--font-stack);
  background: #f1f5f9;
  color:#111827;
  -webkit-font-smoothing: antialiased;
}

a { text-decoration:none; }

.page-shell {
  max-width: 1340px;
  margin: 0 auto;
  padding: 20px 20px 60px;
}

/* Top bar (consistent with earlier "control bar" feel) */
.top-bar {
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius: var(--radius);
  padding:18px 20px;
  display:flex;
  flex-wrap:wrap;
  gap:14px;
  align-items:center;
  box-shadow: var(--shadow-sm);
  margin-bottom: 18px;
}

.top-bar h1 {
  font-size:22px;
  font-weight:700;
  letter-spacing:.5px;
  margin:0;
  display:flex;
  align-items:center;
  gap:10px;
}

.badge-chip {
  background:#f1f5f9;
  border:1px solid #e2e8f0;
  font-size:11px;
  padding:4px 10px;
  border-radius:999px;
  font-weight:600;
  letter-spacing:.4px;
  color:#334155;
}

.btn {
  --btn-bg:#fff;
  --btn-border:#d0d7e2;
  --btn-color:#111827;
  font-size:14px;
  font-weight:600;
  padding:10px 18px;
  border-radius:8px;
  border:1px solid var(--btn-border);
  background:var(--btn-bg);
  color:var(--btn-color);
  display:inline-flex;
  align-items:center;
  gap:8px;
  line-height:1.1;
  box-shadow:var(--shadow-sm);
  transition:background .18s, box-shadow .18s, transform .12s;
  cursor:pointer;
}
.btn:hover { filter:brightness(1.04); }
.btn:active { transform:translateY(1px); box-shadow:0 1px 2px rgba(0,0,0,.07); }
.btn[disabled] { opacity:.5; cursor:not-allowed; }
.btn-primary {
  --btn-bg:#2563eb;
  --btn-border:#1d4ed8;
  --btn-color:#fff;
}
.btn-secondary {
  --btn-bg:#64748b;
  --btn-border:#475569;
  --btn-color:#fff;
}
.btn-outline {
  --btn-bg:#fff;
  --btn-border:#cbd5e1;
  --btn-color:#1e293b;
}
.btn-danger {
  --btn-bg:#dc2626;
  --btn-border:#b91c1c;
  --btn-color:#fff;
}
.btn-success {
  --btn-bg:#059669;
  --btn-border:#047857;
  --btn-color:#fff;
}

/* Filter bar */
.filter-panel {
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius: var(--radius);
  padding:16px 18px 6px;
  box-shadow:var(--shadow-sm);
  margin-bottom:18px;
  display:flex;
  flex-wrap:wrap;
  gap:20px;
  position:relative;
}

.filter-panel .filter-field {
  display:flex;
  flex-direction:column;
  gap:4px;
  min-width:170px;
}
.filter-panel label {
  font-size:11px;
  text-transform:uppercase;
  letter-spacing:.55px;
  font-weight:600;
  color:#475569;
}
.filter-panel select,
.filter-panel input[type="text"] {
  font-size:13px;
  padding:8px 11px;
  border:1px solid #cbd5e1;
  border-radius:6px;
  background:#fff;
  outline:none;
  transition:border .15s, box-shadow .15s;
}
.filter-panel select:focus,
.filter-panel input[type="text"]:focus {
  border-color:#2563eb;
  box-shadow:0 0 0 2px rgba(37,99,235,.25);
}

.stat-cards {
  display:flex;
  flex-wrap:wrap;
  gap:14px;
  margin-bottom:20px;
}
.stat-card {
  flex:1 1 180px;
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:12px;
  padding:14px 16px 12px;
  position:relative;
  overflow:hidden;
  box-shadow:var(--shadow-sm);
  min-width:180px;
}
.stat-card h4 {
  margin:0 0 6px;
  font-size:12px;
  letter-spacing:.5px;
  text-transform:uppercase;
  font-weight:700;
  color:#64748b;
}
.stat-card .value {
  font-size:22px;
  font-weight:700;
  letter-spacing:.5px;
  color:#111827;
  line-height:1.1;
}
.stat-card .mini {
  font-size:10px;
  letter-spacing:.4px;
  color:#64748b;
  margin-top:4px;
}

/* Table */
.data-table-wrap {
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow-x:auto;
}

table.data-table {
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:13px;
}
.data-table thead th {
  background:#f8fafc;
  font-size:11px;
  font-weight:700;
  letter-spacing:.6px;
  text-transform:uppercase;
  color:#475569;
  padding:10px 10px;
  border-bottom:1px solid #e2e8f0;
  text-align:left;
  white-space:nowrap;
}
.data-table thead th.text-right { text-align:right; }
.data-table thead th.text-center { text-align:center; }
.data-table tbody tr {
  transition: background .12s;
}
.data-table tbody tr:hover {
  background:#f1f5f9;
}
.data-table tbody td {
  padding:9px 10px;
  border-bottom:1px solid #edf2f7;
  vertical-align:middle;
  color:#1e293b;
}
.data-table tbody td.text-right { text-align:right; }
.data-table tbody td.text-center { text-align:center; }
.data-table tbody td:first-child {
  font-weight:600;
}

.status-pill {
  padding:4px 10px;
  font-size:11px;
  border-radius:999px;
  font-weight:600;
  letter-spacing:.3px;
  display:inline-block;
  line-height:1.1;
}
.pill-paid {
  background:#dcfce7;
  color:#166534;
  border:1px solid #86efac;
}
.pill-unpaid {
  background:#fee2e2;
  color:#991b1b;
  border:1px solid #fecaca;
}
.pill-final {
  background:#e0f2fe;
  color:#075985;
  border:1px solid #bae6fd;
}

/* Row action buttons (compact) */
.row-actions .btn {
  padding:6px 12px;
  font-size:12px;
  font-weight:600;
  border-radius:6px;
  box-shadow:none;
}

/* Pagination */
.pagination {
  display:flex;
  gap:6px;
  margin-top:20px;
  flex-wrap:wrap;
}
.pagination a,
.pagination span {
  display:inline-flex;
  align-items:center;
  justify-content:center;
  font-size:12px;
  min-width:34px;
  height:34px;
  padding:0 10px;
  border:1px solid #e2e8f0;
  border-radius:8px;
  font-weight:600;
  background:#fff;
  color:#334155;
  box-shadow:var(--shadow-sm);
  transition: all .15s;
}
.pagination a:hover {
  background:#f1f5f9;
}
.pagination .active {
  background:#2563eb;
  border-color:#2563eb;
  color:#fff;
  box-shadow:0 4px 10px -2px rgba(37,99,235,.4);
}

/* Modal */
.modal-backdrop {
  position:fixed; inset:0;
  background:rgba(15,23,42,.55);
  backdrop-filter:blur(3px);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:2000;
}
.modal-panel {
  background:#fff;
  width:420px;
  max-width:92%;
  border-radius:16px;
  padding:26px 26px 24px;
  position:relative;
  box-shadow:var(--shadow-lg);
  animation: popIn .28s cubic-bezier(.18,.89,.32,1.28);
  border:1px solid #e2e8f0;
}
@keyframes popIn {
  0% { transform:scale(.9) translateY(8px); opacity:0; }
  100% { transform:scale(1) translateY(0); opacity:1; }
}
.modal-panel h3 {
  margin:0 0 6px;
  font-size:20px;
  font-weight:700;
  letter-spacing:.6px;
}
.modal-sub {
  font-size:13px;
  color:#475569;
  margin-bottom:18px;
  line-height:1.35;
}
.modal-form label {
  font-size:11px;
  text-transform:uppercase;
  font-weight:600;
  letter-spacing:.55px;
  color:#475569;
  margin-bottom:5px;
  display:block;
}
.modal-form input[type="date"],
.modal-form textarea {
  width:100%;
  border:1px solid #cbd5e1;
  border-radius:8px;
  padding:10px 12px;
  font-size:13px;
  outline:none;
  resize:vertical;
  min-height:46px;
  background:#fff;
  transition:border .15s, box-shadow .15s;
}
.modal-form input[type="date"]:focus,
.modal-form textarea:focus {
  border-color:#2563eb;
  box-shadow:0 0 0 2px rgba(37,99,235,.25);
}

.helper-text {
  font-size:11px;
  letter-spacing:.4px;
  color:#64748b;
  margin-top:4px;
}

.fade-in { animation: fadeIn .4s ease; }
@keyframes fadeIn {
  from { opacity:0; transform:translateY(4px); }
  to { opacity:1; transform:translateY(0); }
}

/* Responsive adjustments */
@media (max-width: 900px) {
  .data-table thead { display:none; }
  .data-table tbody td {
     display:flex;
     justify-content:space-between;
     gap:14px;
     font-size:12.5px;
     padding:10px 14px;
  }
  .data-table tbody tr {
     display:block;
     border:1px solid #e2e8f0;
     margin-bottom:10px;
     border-radius:12px;
     background:#fff;
     box-shadow: var(--shadow-sm);
  }
  .data-table tbody td::before {
     content: attr(data-label);
     font-weight:600;
     color:#475569;
     letter-spacing:.4px;
  }
  .row-actions { justify-content:flex-end; }
}

/* Utility */
.inline-badge {
  background:#f1f5f9;
  border:1px solid #e2e8f0;
  padding:3px 8px;
  font-size:11px;
  border-radius:999px;
  display:inline-block;
  font-weight:600;
  letter-spacing:.4px;
  color:#334155;
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-shell">

  <!-- Top Bar -->
  <div class="top-bar">
    <h1>
      <span>Bill Management</span>
      <span class="badge-chip">Total <?php echo $stats['total']; ?></span>
    </h1>
    <div style="margin-left:auto; display:flex; gap:10px; flex-wrap:wrap;">
      <a href="print_bulk.php" class="btn btn-secondary" title="Create New Bill from Bilties">New Bill +</a>
      <a href="view_bilty.php" class="btn btn-outline">Bilty List</a>
      <a href="reports.php" class="btn btn-outline">Reports</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stat-cards">
    <div class="stat-card">
      <h4>Unpaid Bills</h4>
      <div class="value"><?php echo number_format($stats['unpaid']); ?></div>
      <div class="mini">Awaiting payment</div>
    </div>
    <div class="stat-card">
      <h4>Paid Bills</h4>
      <div class="value text-emerald-600"><?php echo number_format($stats['paid']); ?></div>
      <div class="mini">Completed</div>
    </div>
    <div class="stat-card">
      <h4>Outstanding Amount</h4>
      <div class="value text-rose-600" style="font-size:20px;">PKR <?php echo fmtMoney($stats['out_amt']); ?></div>
      <div class="mini">Net total unpaid</div>
    </div>
    <div class="stat-card">
      <h4>Filter Result</h4>
      <div class="value" style="font-size:20px;"><?php echo $totalRows; ?></div>
      <div class="mini">Records in current view</div>
    </div>
  </div>

  <!-- Filters -->
  <form method="get" class="filter-panel">
    <div class="filter-field">
      <label>Status</label>
      <select name="status">
        <option value="all"    <?php if($statusFilter==='all') echo 'selected'; ?>>All</option>
        <option value="UNPAID" <?php if($statusFilter==='UNPAID') echo 'selected'; ?>>UNPAID</option>
        <option value="PAID"   <?php if($statusFilter==='PAID') echo 'selected'; ?>>PAID</option>
      </select>
    </div>
    <div class="filter-field">
      <label>Search</label>
      <input type="text" name="q" value="<?php echo esc($search); ?>" placeholder="Bill No / Company">
    </div>
    <div class="filter-field">
      <label>Sort</label>
      <select name="sort">
        <option value="date_desc"  <?php if($sort==='date_desc') echo 'selected'; ?>>Date (Newest)</option>
        <option value="date_asc"   <?php if($sort==='date_asc') echo 'selected'; ?>>Date (Oldest)</option>
        <option value="billno_asc" <?php if($sort==='billno_asc') echo 'selected'; ?>>Bill No (Low → High)</option>
        <option value="billno_desc"<?php if($sort==='billno_desc') echo 'selected'; ?>>Bill No (High → Low)</option>
      </select>
    </div>
    <div class="filter-field" style="min-width:120px;">
      <label>&nbsp;</label>
      <button class="btn btn-primary" type="submit">Apply Filters</button>
    </div>
    <div class="filter-field" style="min-width:120px;">
      <label>&nbsp;</label>
      <a href="manage_bills.php" class="btn btn-outline">Reset</a>
    </div>
  </form>

  <!-- Data Table -->
  <div class="data-table-wrap fade-in">
    <table class="data-table">
      <thead>
        <tr>
          <th>Bill No</th>
          <th>Issue Date</th>
          <th>Company</th>
          <th class="text-right">Gross</th>
          <th class="text-right">Tax</th>
          <th class="text-right">Net</th>
          <th class="text-center">Final</th>
          <th class="text-center">Payment</th>
          <th class="text-center">PDF</th>
          <th class="text-center" style="min-width:130px;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if(empty($bills)): ?>
        <tr>
          <td colspan="10" class="py-12 text-center text-slate-500 font-medium">
            No bills match your filters.
          </td>
        </tr>
      <?php else: foreach ($bills as $b): 
          $payStatus = $b['payment_status'] ?: 'UNPAID';
          $pillClass = $payStatus === 'PAID' ? 'pill-paid' : 'pill-unpaid';
      ?>
        <tr>
          <td data-label="Bill No">
            <?php echo esc($b['bill_no']); ?>
          </td>
          <td data-label="Issue Date">
            <?php echo esc(date('d-M-Y', strtotime($b['issue_date']))); ?>
          </td>
          <td data-label="Company">
            <?php echo esc($b['company_name'] ?: '-'); ?>
          </td>
          <td data-label="Gross" class="text-right">
            <?php echo fmtMoney($b['gross_amount']); ?>
          </td>
          <td data-label="Tax" class="text-right">
            <?php echo fmtMoney($b['tax_amount']); ?>
          </td>
          <td data-label="Net" class="text-right" style="font-weight:600;">
            <?php echo fmtMoney($b['net_amount']); ?>
          </td>
          <td data-label="Final" class="text-center">
            <span class="status-pill pill-final" title="Bill status"><?php echo esc($b['status']); ?></span>
          </td>
          <td data-label="Payment" class="text-center">
            <span class="status-pill <?php echo $pillClass; ?>" id="paypill-<?php echo $b['id']; ?>">
              <?php echo esc($payStatus); ?>
            </span>
            <?php if($b['payment_date']): ?>
              <div class="text-[11px] text-slate-500 mt-1" id="paydate-<?php echo $b['id']; ?>">
                <?php echo esc(date('d-M-y', strtotime($b['payment_date']))); ?>
              </div>
            <?php else: ?>
              <div class="text-[11px] text-slate-400 mt-1" id="paydate-<?php echo $b['id']; ?>">--</div>
            <?php endif; ?>
          </td>
          <td data-label="PDF" class="text-center">
            <?php if(!empty($b['pdf_path'])): ?>
              <a href="<?php echo esc($b['pdf_path']); ?>" target="_blank" class="text-blue-600 hover:underline font-medium">View</a>
            <?php else: ?>
              <span class="text-slate-400">N/A</span>
            <?php endif; ?>
          </td>
          <td data-label="Actions">
            <div class="row-actions" style="display:flex; gap:6px; flex-wrap:wrap; justify-content:center;">
              <?php if($payStatus !== 'PAID'): ?>
                <button class="btn btn-success" data-bill="<?php echo $b['id']; ?>" data-action="mark-paid">
                  Paid
                </button>
              <?php else: ?>
                <button class="btn btn-secondary" data-bill="<?php echo $b['id']; ?>" data-action="mark-unpaid">
                  Unpaid
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if($totalPages > 1): ?>
    <div class="pagination">
      <?php
      $qs = $_GET;
      $qs['page'] = 1;
      $firstLink  = '?'.http_build_query($qs);
      $qs['page'] = max(1, $page-1);
      $prevLink   = '?'.http_build_query($qs);
      ?>
      <a href="<?php echo esc($firstLink); ?>" title="First">&laquo;</a>
      <a href="<?php echo esc($prevLink); ?>" title="Prev">&lsaquo;</a>
      <?php
      // window of pages
      $start = max(1, $page-2);
      $end   = min($totalPages, $page+2);
      if ($start > 1) {
        $qs['page']=1; echo '<a href="'.esc('?'.http_build_query($qs)).'">1</a>';
        if ($start > 2) echo '<span style="background:#fff; border:none; box-shadow:none;">…</span>';
      }
      for($p=$start;$p<=$end;$p++){
        $qs['page']=$p;
        $link='?'.http_build_query($qs);
        if($p==$page){
          echo '<span class="active">'.esc($p).'</span>';
        } else {
          echo '<a href="'.esc($link).'">'.esc($p).'</a>';
        }
      }
      if ($end < $totalPages) {
        if ($end < $totalPages-1) echo '<span style="background:#fff; border:none; box-shadow:none;">…</span>';
        $qs['page']=$totalPages;
        echo '<a href="'.esc('?'.http_build_query($qs)).'">'.esc($totalPages).'</a>';
      }
      $qs['page'] = min($totalPages, $page+1);
      $nextLink = '?'.http_build_query($qs);
      $qs['page'] = $totalPages;
      $lastLink = '?'.http_build_query($qs);
      ?>
      <a href="<?php echo esc($nextLink); ?>" title="Next">&rsaquo;</a>
      <a href="<?php echo esc($lastLink); ?>" title="Last">&raquo;</a>
    </div>
  <?php endif; ?>

</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal-backdrop" style="display:none;">
  <div class="modal-panel">
    <h3>Mark Bill as Paid</h3>
    <div class="modal-sub" id="modalBillInfo"></div>
    <form id="paymentForm" class="modal-form">
      <input type="hidden" name="bill_id" id="pm_bill_id" />
      <div class="mb-5">
        <label>Payment Date</label>
        <input type="date" name="payment_date" id="pm_payment_date" value="<?php echo date('Y-m-d'); ?>" />
        <div class="helper-text">Default is today. Adjust if received earlier.</div>
      </div>
      <div class="mb-5">
        <label>Note (optional)</label>
        <textarea name="note" id="pm_note" placeholder="Reference / remarks / receipt ID ..."></textarea>
      </div>
      <div class="flex gap-3 justify-end pt-1">
        <button type="button" id="pm_cancel" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-success">Confirm Paid</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const modal      = document.getElementById('paymentModal');
  const form       = document.getElementById('paymentForm');
  const cancelBtn  = document.getElementById('pm_cancel');
  const infoEl     = document.getElementById('modalBillInfo');
  const dateInput  = document.getElementById('pm_payment_date');
  let currentBillId = null;

  function openModal(billId, billNo){
    currentBillId = billId;
    document.getElementById('pm_bill_id').value = billId;
    infoEl.textContent = 'Bill ID: ' + billId + ' • Bill No: ' + billNo;
    dateInput.value = (new Date()).toISOString().slice(0,10);
    modal.style.display = 'flex';
  }
  function closeModal(){
    modal.style.display = 'none';
    currentBillId = null;
    form.reset();
    dateInput.value = (new Date()).toISOString().slice(0,10);
  }

  cancelBtn.addEventListener('click', e=>{
    e.preventDefault();
    closeModal();
  });

  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('button[data-action]');
    if(!btn) return;
    const action = btn.getAttribute('data-action');
    const billId = btn.getAttribute('data-bill');
    const row    = btn.closest('tr');
    const billNo = row ? row.querySelector('td').textContent.trim() : billId;

    if(action === 'mark-paid'){
      openModal(billId, billNo);
    } else if(action === 'mark-unpaid'){
      if(!confirm('Mark this bill as UNPAID again?')) return;
      updatePaymentStatus(billId, 'UNPAID', '');
    }
  });

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const billId      = document.getElementById('pm_bill_id').value;
    const paymentDate = dateInput.value;
    const note        = document.getElementById('pm_note').value.trim();
    await updatePaymentStatus(billId, 'PAID', note, paymentDate);
    closeModal();
  });

  async function updatePaymentStatus(billId, action, note, paymentDate=''){
    const formData = new FormData();
    formData.append('bill_id', billId);
    formData.append('action', action);
    formData.append('note', note);
    if(paymentDate) formData.append('payment_date', paymentDate);

    try{
      const resp = await fetch('update_bill_payment.php', { method:'POST', body:formData });
      const data = await resp.json();
      if(!data.ok) throw new Error(data.error || 'Update failed');

      // Update UI
      const pill    = document.getElementById('paypill-'+billId);
      if(!pill) return;
      const dateEl  = document.getElementById('paydate-'+billId);
      const row     = pill.closest('tr');
      const actions = row.querySelector('.row-actions');

      if(data.payment_status === 'PAID'){
        pill.textContent = 'PAID';
        pill.classList.remove('pill-unpaid');
        pill.classList.add('pill-paid');
        dateEl.textContent = data.payment_date ? formatLocalDate(data.payment_date) : '';
        actions.innerHTML = '<button class="btn btn-secondary" data-bill="'+billId+'" data-action="mark-unpaid">Unpaid</button>';
      } else {
        pill.textContent = 'UNPAID';
        pill.classList.remove('pill-paid');
        pill.classList.add('pill-unpaid');
        dateEl.textContent = '--';
        actions.innerHTML = '<button class="btn btn-success" data-bill="'+billId+'" data-action="mark-paid">Paid</button>';
      }
    }catch(err){
      alert(err.message);
    }
  }

  function formatLocalDate(iso){
    try {
      const d = new Date(iso);
      return d.toLocaleDateString(undefined,{year:'2-digit',month:'short',day:'2-digit'});
    } catch { return iso; }
  }

  // Close modal by clicking outside panel
  modal.addEventListener('click', e=>{
    if(e.target === modal) closeModal();
  });

  // ESC key to close
  document.addEventListener('keydown', e=>{
    if(e.key === 'Escape' && modal.style.display === 'flex') closeModal();
  });
})();
</script>
</body>
</html>