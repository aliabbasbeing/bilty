<?php
require_once 'config.php';

/*
  Vehicle Maintenance (Simplified)
  --------------------------------
  Features:
    - Add new entry via modal (AJAX)
    - Simple filters: Date range + Vehicle No + Expense keyword
    - Paginated table (default 25 per page)
    - Basic summary (total entries + total amount in current filter)
    - No extra reports, charts, or CSV exports
*/

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fnum($v, $d=2){ return number_format((float)$v, $d); }

$today      = date('Y-m-d');
$monthStart = date('Y-m-01');

$startDate = $_GET['start'] ?? $monthStart;
$endDate   = $_GET['end']   ?? $today;
$vehicle   = trim($_GET['vehicle'] ?? '');
$expense   = trim($_GET['expense'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 25;

// Validate dates
$sd = DateTime::createFromFormat('Y-m-d', $startDate);
$ed = DateTime::createFromFormat('Y-m-d', $endDate);
if(!$sd) $startDate = $monthStart;
if(!$ed) $endDate   = $today;
if($endDate < $startDate) $endDate = $startDate;

// WHERE clause
$where  = [];
$params = [];
$types  = '';

$where[] = "entry_date BETWEEN ? AND ?";
$params[] = $startDate; $types .= 's';
$params[] = $endDate;   $types .= 's';

if($vehicle !== ''){
  $where[]  = "vehicle_no LIKE ?";
  $params[] = '%'.$vehicle.'%';
  $types   .= 's';
}
if($expense !== ''){
  $where[]  = "(expense_type LIKE ? OR narration LIKE ?)";
  $params[] = '%'.$expense.'%';
  $params[] = '%'.$expense.'%';
  $types   .= 'ss';
}
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

// Count & totals (for pagination + brief summary)
$countSql = "SELECT COUNT(*) cnt, COALESCE(SUM(amount),0) total_amt
             FROM vehicle_maintenance
             $whereSql";
$stmt = $conn->prepare($countSql);
if($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res  = $stmt->get_result();
$meta = $res->fetch_assoc() ?: ['cnt'=>0,'total_amt'=>0];
$stmt->close();

$totalRows  = (int)$meta['cnt'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Fetch page rows
$listSql = "SELECT id, entry_date, vehicle_no, expense_type, amount, narration
            FROM vehicle_maintenance
            $whereSql
            ORDER BY entry_date DESC, id DESC
            LIMIT ?, ?";
$stmt = $conn->prepare($listSql);
if(!$stmt){
  die("Query prepare failed: ".$conn->error);
}
$bindTypes = $types . 'ii';
$pageParams = array_merge($params, [$offset, $perPage]);
$stmt->bind_param($bindTypes, ...$pageParams);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while($res && ($r = $res->fetch_assoc())) $rows[] = $r;
$stmt->close();

function paginationLinks($page, $totalPages, $query){
  if($totalPages <= 1) return '';
  $html = '<div class="pagination">';
  $makeLink = function($p) use($query){
    $q = $query; $q['page']=$p;
    return '?'.http_build_query($q);
  };
  // Prev
  if($page > 1){
    $html .= '<a href="'.$makeLink($page-1).'" class="pg-btn">&laquo; Prev</a>';
  } else {
    $html .= '<span class="pg-btn disabled">&laquo; Prev</span>';
  }
  // Window
  $start = max(1, $page-2);
  $end   = min($totalPages, $page+2);
  if($start > 1){
    $html .= '<a href="'.$makeLink(1).'" class="pg-btn">1</a>';
    if($start > 2) $html .= '<span class="dots">...</span>';
  }
  for($p=$start;$p<=$end;$p++){
    if($p == $page){
      $html .= '<span class="pg-btn active">'.$p.'</span>';
    } else {
      $html .= '<a href="'.$makeLink($p).'" class="pg-btn">'.$p.'</a>';
    }
  }
  if($end < $totalPages){
    if($end < $totalPages-1) $html .= '<span class="dots">...</span>';
    $html .= '<a href="'.$makeLink($totalPages).'" class="pg-btn">'.$totalPages.'</a>';
  }
  // Next
  if($page < $totalPages){
    $html .= '<a href="'.$makeLink($page+1).'" class="pg-btn">Next &raquo;</a>';
  } else {
    $html .= '<span class="pg-btn disabled">Next &raquo;</span>';
  }
  $html .= '</div>';
  return $html;
}

$queryBase = [
  'start'   => $startDate,
  'end'     => $endDate,
  'vehicle' => $vehicle,
  'expense' => $expense
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Vehicle Maintenance</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<style>
:root {
  --primary:#97113a;
  --primary-hover:#7d0e30;
  --bg:#f1f5f9;
  --radius:12px;
  --shadow-sm:0 2px 4px rgba(0,0,0,.08);
}
html,body { background:var(--bg); font-family: "Inter","Arial",sans-serif; }
h1 { font-size:1.4rem; font-weight:700; letter-spacing:.4px; }
.page-shell { max-width:1100px; margin:0 auto; padding:20px 18px 60px; }
.toolbar {
  background:#fff; border:1px solid #e2e8f0; border-radius:var(--radius);
  padding:14px 16px; display:flex; flex-wrap:wrap; gap:14px; align-items:center;
  box-shadow:var(--shadow-sm); margin-bottom:18px;
}
.toolbar h1 { margin:0; display:flex; align-items:center; gap:10px; }
.badge {
  background:#f1f5f9; border:1px solid #e2e8f0; border-radius:999px;
  padding:4px 10px; font-size:11px; font-weight:600; letter-spacing:.4px;
  color:#334155;
}
.btn {
  background:#fff; border:1px solid #d0d7e2; padding:8px 15px; font-size:13px;
  font-weight:600; border-radius:8px; display:inline-flex; align-items:center; gap:6px;
  cursor:pointer; transition:background .15s, box-shadow .15s;
}
.btn:hover { background:#f1f5f9; }
.btn-primary {
  background:var(--primary); border-color:var(--primary); color:#fff;
}
.btn-primary:hover { background:var(--primary-hover); }
.filter-box {
  background:#fff; border:1px solid #e2e8f0; border-radius:var(--radius);
  padding:16px 18px 10px; box-shadow:var(--shadow-sm); margin-bottom:18px;
}
.filter-grid {
  display:grid; gap:14px; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
}
.filter-grid label {
  font-size:10px; font-weight:600; letter-spacing:.55px; text-transform:uppercase;
  margin-bottom:4px; display:block; color:#475569;
}
.filter-grid input {
  width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:8px 10px;
  font-size:13px; background:#fff; outline:none; transition:border .15s, box-shadow .15s;
}
.filter-grid input:focus {
  border-color:var(--primary);
  box-shadow:0 0 0 2px rgba(151,17,58,.25);
}
.summary-bar {
  display:flex; flex-wrap:wrap; gap:14px; margin-bottom:14px;
}
.summary-pill {
  background:#fff; border:1px solid #e2e8f0; border-radius:10px;
  padding:10px 14px; font-size:12px; font-weight:600; letter-spacing:.3px;
  display:flex; gap:6px; align-items:center; box-shadow:var(--shadow-sm);
}
.table-wrap {
  background:#fff; border:1px solid #e2e8f0; border-radius:var(--radius);
  box-shadow:var(--shadow-sm); overflow-x:auto;
}
table {
  width:100%; border-collapse:separate; border-spacing:0; font-size:13px;
}
thead th {
  background:#f8fafc; font-size:11px; font-weight:700; letter-spacing:.55px;
  text-transform:uppercase; color:#475569; padding:8px 9px; text-align:left;
  border-bottom:1px solid #e2e8f0; white-space:nowrap;
}
tbody td {
  padding:8px 9px; border-bottom:1px solid #edf2f7; vertical-align:middle;
}
tbody tr:hover { background:#f1f5f9; }
tbody tr:last-child td { border-bottom:none; }
.empty {
  padding:30px 12px; text-align:center; color:#64748b; font-size:13px; font-weight:500;
}
.pagination {
  display:flex; flex-wrap:wrap; gap:6px; margin-top:18px;
}
.pg-btn {
  border:1px solid #e2e8f0; background:#fff; padding:6px 12px;
  font-size:12px; font-weight:600; border-radius:8px; color:#334155;
  text-decoration:none; display:inline-flex; align-items:center; justify-content:center;
  min-width:40px; transition:background .15s;
}
.pg-btn:hover { background:#f1f5f9; }
.pg-btn.active {
  background:var(--primary); border-color:var(--primary); color:#fff;
  box-shadow:0 4px 12px -3px rgba(151,17,58,.4);
}
.pg-btn.disabled { opacity:.45; cursor:default; }
.dots { padding:6px 4px; font-size:12px; color:#475569; }
.footer-links {
  margin-top:40px; display:flex; gap:10px; flex-wrap:wrap;
}
@media (max-width:800px) {
  thead { display:none; }
  tbody tr {
    display:block; margin-bottom:12px; border:1px solid #e2e8f0;
    border-radius:12px; overflow:hidden; background:#fff;
  }
  tbody td {
    display:flex; justify-content:space-between; gap:10px;
    font-size:12.5px; border-bottom:1px solid #f1f5f9;
  }
  tbody td:last-child { border-bottom:none; }
  tbody td::before {
    content:attr(data-label);
    font-weight:600; color:#475569;
  }
}

/* Modal */
.modal-backdrop {
  position:fixed; inset:0; background:rgba(15,23,42,.55);
  backdrop-filter:blur(2px); display:none; align-items:center; justify-content:center;
  z-index:2000;
}
.modal {
  background:#fff; width:440px; max-width:92%; border-radius:18px;
  padding:26px 24px 22px; position:relative; box-shadow:0 8px 26px -5px rgba(0,0,0,.22);
  animation:pop .28s cubic-bezier(.18,.89,.32,1.28);
}
@keyframes pop {
  0% { transform:scale(.9) translateY(8px); opacity:0; }
  100% { transform:scale(1) translateY(0); opacity:1; }
}
.modal h3 {
  margin:0 0 6px; font-size:20px; font-weight:700; letter-spacing:.5px;
}
.modal .sub {
  font-size:12px; color:#475569; margin-bottom:18px;
}
.modal form .field { margin-bottom:14px; }
.modal form label {
  font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.55px;
  display:block; margin-bottom:5px; color:#475569;
}
.modal form input[type="date"],
.modal form input[type="text"],
.modal form input[type="number"],
.modal form textarea {
  width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px;
  font-size:13px; background:#fff; outline:none; resize:vertical; min-height:44px;
  transition:border .15s, box-shadow .15s;
}
.modal form textarea { min-height:90px; }
.modal form input:focus,
.modal form textarea:focus {
  border-color:var(--primary);
  box-shadow:0 0 0 2px rgba(151,17,58,.27);
}
.close-btn {
  position:absolute; top:10px; right:10px;
  width:34px; height:34px; border-radius:10px; background:#f1f5f9;
  border:1px solid #e2e8f0; font-weight:700; cursor:pointer;
  display:inline-flex; align-items:center; justify-content:center;
  transition:background .15s;
}
.close-btn:hover { background:#e2e8f0; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-shell">

  <!-- Toolbar -->
  <div class="toolbar">
    <h1>Vehicle Maintenance <span class="badge"><?php echo esc($startDate); ?> → <?php echo esc($endDate); ?></span></h1>
    <div style="margin-left:auto; display:flex; gap:10px; flex-wrap:wrap;">
      <button id="openModalBtn" class="btn btn-primary" type="button">+ Add Entry</button>
      <a href="vehicle_maintenance.php" class="btn">Reset</a>
    </div>
  </div>

  <!-- Filters -->
  <form method="get" class="filter-box">
    <div class="filter-grid">
      <div>
        <label>Start Date</label>
        <input type="date" name="start" value="<?php echo esc($startDate); ?>">
      </div>
      <div>
        <label>End Date</label>
        <input type="date" name="end" value="<?php echo esc($endDate); ?>">
      </div>
      <div>
        <label>Vehicle No</label>
        <input type="text" name="vehicle" value="<?php echo esc($vehicle); ?>" placeholder="ABC-123">
      </div>
      <div>
        <label>Expense / Keyword</label>
        <input type="text" name="expense" value="<?php echo esc($expense); ?>" placeholder="battery, oil...">
      </div>
    </div>
    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:14px;">
      <button type="submit" class="btn btn-primary">Apply</button>
      <a href="?start=<?php echo esc($monthStart); ?>&end=<?php echo esc($today); ?>" class="btn">This Month</a>
      <a href="?start=<?php echo esc(date('Y-01-01')); ?>&end=<?php echo esc($today); ?>" class="btn">YTD</a>
    </div>
  </form>

  <!-- Summary -->
  <div class="summary-bar">
    <div class="summary-pill">Entries: <strong><?php echo number_format($totalRows); ?></strong></div>
    <div class="summary-pill">Total Amount: <strong>PKR <?php echo fnum($meta['total_amt']); ?></strong></div>
    <div class="summary-pill">Page: <strong><?php echo $page; ?>/<?php echo $totalPages; ?></strong></div>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <?php if(empty($rows)): ?>
      <div class="empty">No entries found for selected filters.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>S.No</th>
            <th>Date</th>
          <th>Vehicle No</th>
          <th>Expense Detail</th>
          <th class="text-right">Amount</th>
          <th>Narration</th>
        </tr>
      </thead>
      <tbody id="entriesTbody">
        <?php
          $sn = $offset + 1;
          foreach($rows as $r):
        ?>
          <tr data-id="<?php echo $r['id']; ?>">
            <td data-label="S.No"><?php echo $sn++; ?></td>
            <td data-label="Date"><?php echo esc($r['entry_date']); ?></td>
            <td data-label="Vehicle No"><?php echo esc($r['vehicle_no']); ?></td>
            <td data-label="Expense Detail"><?php echo esc($r['expense_type']); ?></td>
            <td data-label="Amount" class="text-right">PKR <?php echo fnum($r['amount']); ?></td>
            <td data-label="Narration"><?php echo esc($r['narration']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php echo paginationLinks($page, $totalPages, $queryBase); ?>

  <div class="footer-links">
    <a href="index.php" class="btn">Dashboard</a>
    <a href="reports.php" class="btn">Reports</a>
    <a href="#top" class="btn">Top</a>
  </div>
</div>

<!-- Modal -->
<div id="vmModal" class="modal-backdrop">
  <div class="modal">
    <button class="close-btn" id="closeModalBtn" type="button" aria-label="Close">✕</button>
    <h3>Add Maintenance Entry</h3>
    <div class="sub">Record a vehicle expense (battery change, oil, self motor, etc.).</div>
    <form id="vmForm" autocomplete="off">
      <div class="field">
        <label>Date</label>
        <input type="date" name="entry_date" id="vm_date" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
      <div class="field">
        <label>Vehicle No</label>
        <input type="text" name="vehicle_no" id="vm_vehicle" placeholder="ABC-123" required>
      </div>
      <div class="field">
        <label>Expense Detail</label>
        <input type="text" name="expense_type" id="vm_expense" placeholder="Battery" required>
      </div>
      <div class="field">
        <label>Amount</label>
        <input type="number" step="0.01" min="0" name="amount" id="vm_amount" placeholder="0.00" required>
      </div>
      <div class="field">
        <label>Narration (optional)</label>
        <textarea name="narration" id="vm_narration" placeholder="Changed battery (brand XYZ, 12V, warranty 1 year)"></textarea>
      </div>
      <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:6px;">
        <button type="button" class="btn" id="cancelModalBtn">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const openBtn   = document.getElementById('openModalBtn');
  const modal     = document.getElementById('vmModal');
  const closeBtn  = document.getElementById('closeModalBtn');
  const cancelBtn = document.getElementById('cancelModalBtn');
  const form      = document.getElementById('vmForm');
  const tbody     = document.getElementById('entriesTbody');

  function openModal(){ modal.style.display='flex'; }
  function closeModal(){
    modal.style.display='none';
    form.reset();
    document.getElementById('vm_date').value = (new Date()).toISOString().slice(0,10);
  }

  openBtn?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  cancelBtn?.addEventListener('click', closeModal);
  modal.addEventListener('click', e=>{ if(e.target === modal) closeModal(); });
  document.addEventListener('keydown', e=>{ if(e.key==='Escape' && modal.style.display==='flex') closeModal(); });

  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(form);
    try{
      const resp = await fetch('vehicle_maintenance_save.php', { method:'POST', body: fd });
      const data = await resp.json();
      if(!data.ok) throw new Error(data.error || 'Save failed');

      // If current page is not first page, just alert user (new record won't appear)
      const pageParam = new URLSearchParams(window.location.search).get('page');
      if(pageParam && parseInt(pageParam,10) > 1){
        alert('Entry saved. It will appear on first page or when filters include its date.');
        closeModal();
        return;
      }

      // Recalculate S.No: shift existing numbers
      if(tbody){
        [...tbody.querySelectorAll('tr')].forEach(tr=>{
          const cell = tr.querySelector('td:first-child');
          if(cell){
            const num = parseInt(cell.textContent.trim(),10);
            if(!isNaN(num)) cell.textContent = num + 1;
          }
        });

        const r = data.entry;
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', r.id);
        tr.innerHTML = `
          <td data-label="S.No">1</td>
          <td data-label="Date">${r.entry_date}</td>
          <td data-label="Vehicle No">${escapeHtml(r.vehicle_no)}</td>
          <td data-label="Expense Detail">${escapeHtml(r.expense_type)}</td>
          <td data-label="Amount" class="text-right">PKR ${parseFloat(r.amount).toFixed(2)}</td>
          <td data-label="Narration">${escapeHtml(r.narration || '')}</td>
        `;
        tbody.prepend(tr);
      }
      closeModal();
    }catch(err){
      alert(err.message);
    }
  });

  function escapeHtml(str){
    if(str==null) return '';
    return String(str)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }
})();
</script>
</body>
</html>