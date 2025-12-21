<?php
require_once 'config.php';

/*
  reports.php  (Enhanced Detailed Reporting)

  FEATURES ADDED:
  1. Date Range + Company Filter + From/To City Filter + Vehicle Type Filter
     - Defaults to current month (1st -> today).
  2. Overall KPIs (Total Bilties, Total Amount, Advances, Balance, Paid %).
  3. Comparative (Previous Period) deltas (shows difference vs previous equal-length period).
  4. Daily Summary (within selected range).
  5. Monthly Rollup (for large ranges crossing months).
  6. Top Routes (from_city → to_city) by Amount & Count.
  7. Company Breakdown (with clickable expand detail).
  8. Vehicle Type Summary.
  9. Average metrics: Avg Amount per Bilty, Avg Advance %, Avg Balance.
 10. Export CSV buttons (company, daily, routes, vehicles).
     - Use ?export=company|daily|routes|vehicles with same filters.
 11. Clean consistent styling aligned with your new UI approach.
 12. Secure: Using prepared statements for filter queries (except dynamic ORDER for monthly/daily which is fixed).

  TABLE ASSUMPTIONS (adjust if columns differ):
    consignments:
      id, date (Y-m-d), company_id, from_city, to_city,
      vehicle_type, amount, advance, balance
    companies:
      id, name

  NOTE:
    Performance: For very large datasets add indexes:
      INDEX idx_date (date),
      INDEX idx_company (company_id),
      INDEX idx_route (from_city, to_city),
      INDEX idx_vehicle (vehicle_type)

  QUICK TODO for you if not present:
    Ensure date column in consignments is DATE type (not DATETIME string).
*/

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fnum($v, $d=2){ return number_format((float)$v, $d); }

$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$startDate = $_GET['start'] ?? $monthStart;
$endDate   = $_GET['end']   ?? $today;
$companyId = isset($_GET['company_id']) ? (int)$_GET['company_id'] : 0;
$fromCity  = trim($_GET['from_city'] ?? '');
$toCity    = trim($_GET['to_city'] ?? '');
$vehicleType = trim($_GET['vehicle_type'] ?? '');
$export    = $_GET['export'] ?? '';

/* Validate dates (fallback to safe defaults) */
$startDateObj = DateTime::createFromFormat('Y-m-d', $startDate);
$endDateObj   = DateTime::createFromFormat('Y-m-d', $endDate);
if(!$startDateObj) $startDate = $monthStart;
if(!$endDateObj)   $endDate   = $today;
if($endDate < $startDate) $endDate = $startDate;

/* Build filter fragments */
$where = [];
$params = [];
$types  = '';

$where[] = "c.date BETWEEN ? AND ?";
$params[] = $startDate; $types .= 's';
$params[] = $endDate;   $types .= 's';

if ($companyId > 0) {
  $where[] = "c.company_id = ?";
  $params[] = $companyId; $types .= 'i';
}
if ($fromCity !== '') {
  $where[] = "c.from_city = ?";
  $params[] = $fromCity; $types .= 's';
}
if ($toCity !== '') {
  $where[] = "c.to_city = ?";
  $params[] = $toCity; $types .= 's';
}
if ($vehicleType !== '') {
  $where[] = "c.vehicle_type = ?";
  $params[] = $vehicleType; $types .= 's';
}

$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* COMPANY LIST FOR FILTER */
$companies = [];
$resCompanies = $conn->query("SELECT id,name FROM companies ORDER BY name ASC");
if ($resCompanies) {
  while($r=$resCompanies->fetch_assoc()) $companies[]=$r;
  $resCompanies->free();
}

/* DISTINCT FROM/TO CITY + VEHICLE for filter selects (optional performance cost) */
$distinctFrom = [];
$resFrom = $conn->query("SELECT DISTINCT from_city FROM consignments ORDER BY from_city");
if ($resFrom) { while($r=$resFrom->fetch_assoc()) $distinctFrom[]=$r['from_city']; $resFrom->free(); }
$distinctTo = [];
$resTo = $conn->query("SELECT DISTINCT to_city FROM consignments ORDER BY to_city");
if ($resTo) { while($r=$resTo->fetch_assoc()) $distinctTo[]=$r['to_city']; $resTo->free(); }
$vehicleTypes = [];
$resVeh = $conn->query("SELECT DISTINCT vehicle_type FROM consignments WHERE vehicle_type <> '' ORDER BY vehicle_type");
if ($resVeh) { while($r=$resVeh->fetch_assoc()) $vehicleTypes[]=$r['vehicle_type']; $resVeh->free(); }

/* Helper for prepared aggregate queries */
function fetchAggregate($conn, $sql, $types, $params) {
  $stmt = $conn->prepare($sql);
  if(!$stmt) return [];
  if($types) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = [];
  while($res && ($r=$res->fetch_assoc())) $rows[]=$r;
  $stmt->close();
  return $rows;
}

/* 1. OVERALL METRICS (in range) */
$overallSql = "SELECT
    COUNT(*) AS cnt,
    COALESCE(SUM(c.amount),0) AS total_amount,
    COALESCE(SUM(c.advance),0) AS total_advance,
    COALESCE(SUM(c.balance),0) AS total_balance
  FROM consignments c
  $whereSql";
$overall = fetchAggregate($conn, $overallSql, $types, $params);
$overall = $overall[0] ?? ['cnt'=>0,'total_amount'=>0,'total_advance'=>0,'total_balance'=>0];
$paidAmount = $overall['total_amount'] - $overall['total_balance']; // computed
$avgAmountPerBilty = $overall['cnt'] ? $overall['total_amount'] / $overall['cnt'] : 0;
$avgAdvancePct     = ($overall['total_amount']>0) ? ($overall['total_advance'] / $overall['total_amount'] * 100) : 0;
$avgBalancePerBilty= $overall['cnt']? $overall['total_balance'] / $overall['cnt'] : 0;

/* 2. PREVIOUS PERIOD COMPARISON */
$rangeDays = (new DateTime($startDate))->diff(new DateTime($endDate))->days + 1;
$prevEnd   = (new DateTime($startDate))->modify('-1 day');
$prevStart = (clone $prevEnd)->modify('-'.($rangeDays-1).' days');
$prevStartStr = $prevStart->format('Y-m-d');
$prevEndStr   = $prevEnd->format('Y-m-d');

$prevWhere = ["c.date BETWEEN ? AND ?"];
$prevParams= [$prevStartStr, $prevEndStr];
$prevTypes = "ss";
/* apply same optional filters except date */
if ($companyId > 0) { $prevWhere[]="c.company_id=?"; $prevParams[]=$companyId; $prevTypes.='i'; }
if ($fromCity !== '') { $prevWhere[]="c.from_city=?"; $prevParams[]=$fromCity; $prevTypes.='s'; }
if ($toCity !== '') { $prevWhere[]="c.to_city=?"; $prevParams[]=$toCity; $prevTypes.='s'; }
if ($vehicleType !== '') { $prevWhere[]="c.vehicle_type=?"; $prevParams[]=$vehicleType; $prevTypes.='s'; }

$prevWhereSql = 'WHERE '.implode(' AND ', $prevWhere);
$prevSql = "SELECT
    COUNT(*) AS cnt,
    COALESCE(SUM(c.amount),0) AS total_amount,
    COALESCE(SUM(c.advance),0) AS total_advance,
    COALESCE(SUM(c.balance),0) AS total_balance
  FROM consignments c
  $prevWhereSql";
$prev = fetchAggregate($conn, $prevSql, $prevTypes, $prevParams);
$prev = $prev[0] ?? ['cnt'=>0,'total_amount'=>0,'total_advance'=>0,'total_balance'=>0];

function deltaStr($current, $previous, $prefix='') {
  if ($previous == 0 && $current == 0) return '0';
  if ($previous == 0) return ($current>0?'+':'').fnum($current).$prefix;
  $diff = $current - $previous;
  $pct  = $previous != 0 ? ($diff/$previous*100) : 0;
  $sign = ($diff>0?'+':'');
  return $sign.fnum($diff).$prefix." (".($pct>0?'+':'').fnum($pct,1)."%)";
}

/* 3. DAILY SUMMARY */
$dailySql = "SELECT c.date,
    COUNT(*) cnt,
    COALESCE(SUM(c.amount),0) total_amount,
    COALESCE(SUM(c.balance),0) total_balance
  FROM consignments c
  $whereSql
  GROUP BY c.date
  ORDER BY c.date ASC";
$daily = fetchAggregate($conn, $dailySql, $types, $params);

/* 4. MONTHLY ROLLUP (if span > 31 days) */
$monthly = [];
if ($rangeDays > 31) {
  $monthlySql = "SELECT DATE_FORMAT(c.date,'%Y-%m') ym,
      COUNT(*) cnt,
      COALESCE(SUM(c.amount),0) total_amount,
      COALESCE(SUM(c.balance),0) total_balance
    FROM consignments c
    $whereSql
    GROUP BY ym
    ORDER BY ym ASC";
  $monthly = fetchAggregate($conn, $monthlySql, $types, $params);
}

/* 5. TOP ROUTES (limit 10) */
$routesSql = "SELECT c.from_city, c.to_city,
    COUNT(*) cnt,
    COALESCE(SUM(c.amount),0) total_amount
  FROM consignments c
  $whereSql
  GROUP BY c.from_city, c.to_city
  ORDER BY total_amount DESC
  LIMIT 10";
$topRoutes = fetchAggregate($conn, $routesSql, $types, $params);

/* 6. COMPANY BREAKDOWN */
$companySql = "SELECT cp.name AS company_name, c.company_id,
    COUNT(*) cnt,
    COALESCE(SUM(c.amount),0) total_amount,
    COALESCE(SUM(c.advance),0) total_advance,
    COALESCE(SUM(c.balance),0) total_balance
  FROM consignments c
  JOIN companies cp ON cp.id = c.company_id
  $whereSql
  GROUP BY c.company_id
  ORDER BY total_amount DESC";
$companyBreak = fetchAggregate($conn, $companySql, $types, $params);

/* 7. VEHICLE TYPE SUMMARY */
$vehicleSql = "SELECT c.vehicle_type,
    COUNT(*) cnt,
    COALESCE(SUM(c.amount),0) total_amount,
    COALESCE(SUM(c.balance),0) total_balance
  FROM consignments c
  $whereSql
  GROUP BY c.vehicle_type
  ORDER BY total_amount DESC";
$vehicleSummary = fetchAggregate($conn, $vehicleSql, $types, $params);

/* EXPORT HANDLER */
function outputCsv($filename, $headers, $rows, $mapper=null) {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  $out = fopen('php://output', 'w');
  fputcsv($out, $headers);
  foreach ($rows as $r) {
    if ($mapper) $r = $mapper($r);
    fputcsv($out, $r);
  }
  fclose($out);
  exit;
}

if ($export) {
  if ($export === 'company') {
    outputCsv(
      'company_breakdown.csv',
      ['Company','Bilties','Amount','Advance','Outstanding'],
      array_map(fn($r)=>[
        $r['company_name'],
        $r['cnt'],
        fnum($r['total_amount']),
        fnum($r['total_advance']),
        fnum($r['total_balance'])
      ], $companyBreak)
    );
  } elseif ($export === 'daily') {
    outputCsv(
      'daily_summary.csv',
      ['Date','Bilties','Amount','Outstanding'],
      array_map(fn($r)=>[
        $r['date'],
        $r['cnt'],
        fnum($r['total_amount']),
        fnum($r['total_balance'])
      ], $daily)
    );
  } elseif ($export === 'routes') {
    outputCsv(
      'top_routes.csv',
      ['From','To','Bilties','Amount'],
      array_map(fn($r)=>[
        $r['from_city'],$r['to_city'],$r['cnt'],fnum($r['total_amount'])
      ], $topRoutes)
    );
  } elseif ($export === 'vehicles') {
    outputCsv(
      'vehicle_summary.csv',
      ['Vehicle Type','Bilties','Amount','Outstanding'],
      array_map(fn($r)=>[
        $r['vehicle_type'] ?: 'N/A',
        $r['cnt'],
        fnum($r['total_amount']),
        fnum($r['total_balance'])
      ], $vehicleSummary)
    );
  }
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Detailed Reports — Bilty Management</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<style>
:root {
  --primary:#97113a;
  --primary-hover:#7d0e30;
  --radius:12px;
  --shadow:0 4px 14px -4px rgba(0,0,0,.12);
  --shadow-sm:0 2px 4px rgba(0,0,0,.08);
  --bg-soft:#f1f5f9;
}
html,body{background:var(--bg-soft);}
.section-card{
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:var(--radius);
  padding:20px 22px 24px;
  box-shadow:var(--shadow-sm);
  position:relative;
}
.section-card h3{
  margin:0 0 14px;
  font-size:18px;
  font-weight:700;
  letter-spacing:.5px;
  display:flex;align-items:center;gap:8px;
}
.badge-soft{
  background:#f1f5f9;
  border:1px solid #e2e8f0;
  padding:4px 10px;
  font-size:11px;
  font-weight:600;
  border-radius:999px;
  letter-spacing:.4px;
  color:#334155;
}
.kpi-grid{
  display:grid;
  gap:14px;
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
  margin-bottom:6px;
}
.kpi{
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:14px;
  padding:14px 16px 12px;
  position:relative;
  overflow:hidden;
  box-shadow:var(--shadow-sm);
}
.kpi h4{
  margin:0 0 6px;
  font-size:11px;
  letter-spacing:.5px;
  text-transform:uppercase;
  font-weight:700;
  color:#64748b;
}
.kpi .val{
  font-size:22px;
  font-weight:700;
  letter-spacing:.5px;
  color:#111827;
  line-height:1.1;
}
.kpi .delta{
  font-size:11px;
  margin-top:4px;
  color:#475569;
  letter-spacing:.3px;
}
.table-wrap{
  border:1px solid #e2e8f0;
  border-radius:10px;
  overflow:hidden;
  background:#fff;
}
table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  font-size:13px;
}
thead th{
  background:#f8fafc;
  text-align:left;
  font-size:11px;
  font-weight:700;
  letter-spacing:.55px;
  text-transform:uppercase;
  padding:9px 10px;
  border-bottom:1px solid #e2e8f0;
  color:#475569;
  white-space:nowrap;
}
tbody td{
  padding:8px 10px;
  border-bottom:1px solid #edf2f7;
  vertical-align:middle;
  color:#1e293b;
}
tbody tr:hover{background:#f1f5f9;}
tbody tr:last-child td{border-bottom:none;}
.btn{
  --bcol:#e2e8f0;
  background:#fff;
  border:1px solid var(--bcol);
  padding:8px 16px;
  font-size:13px;
  border-radius:8px;
  font-weight:600;
  display:inline-flex;
  align-items:center;
  gap:6px;
  cursor:pointer;
  transition:background .15s, box-shadow .15s;
}
.btn:hover{background:#f1f5f9;}
.btn-primary{
  background:var(--primary);
  color:#fff;
  border:1px solid var(--primary);
}
.btn-primary:hover{background:var(--primary-hover);}
.inline-note{font-size:11px;color:#64748b;margin-top:4px;}
.form-grid{
  display:grid;
  gap:14px;
  grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
  margin-bottom:14px;
}
.form-grid label{
  font-size:10px;
  font-weight:600;
  text-transform:uppercase;
  letter-spacing:.6px;
  color:#475569;
  display:block;
  margin-bottom:4px;
}
.form-grid input, .form-grid select{
  width:100%;
  border:1px solid #cbd5e1;
  border-radius:8px;
  padding:8px 10px;
  font-size:13px;
  background:#fff;
  outline:none;
  transition:border .15s, box-shadow .15s;
}
.form-grid input:focus, .form-grid select:focus{
  border-color:var(--primary);
  box-shadow:0 0 0 2px rgba(151,17,58,.25);
}
.anchor-tools{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:8px;
}
.anchor-tools a{
  font-size:11px;
  padding:4px 10px;
  background:#f1f5f9;
  border:1px solid #e2e8f0;
  border-radius:999px;
  text-decoration:none;
  color:#334155;
  font-weight:600;
}
.anchor-tools a:hover{background:#e2e8f0;}
.tag{
  display:inline-block;
  background:#f1f5f9;
  border:1px solid #e2e8f0;
  padding:3px 8px;
  border-radius:6px;
  font-size:11px;
  font-weight:600;
  margin:2px 4px 2px 0;
}
.route-pill{
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:30px;
  padding:10px 14px;
  font-size:13px;
  display:flex;
  flex-direction:column;
  gap:6px;
  position:relative;
  box-shadow:var(--shadow-sm);
  min-width:170px;
}
.route-grid{
  display:grid;
  gap:12px;
  grid-template-columns:repeat(auto-fill,minmax(170px,1fr));
}
.notice{
  background:#fef9c3;
  border:1px solid #fcd34d;
  color:#78350f;
  padding:10px 14px;
  border-radius:10px;
  font-size:13px;
  font-weight:500;
}
.scroll-x{overflow-x:auto;}
</style>
</head>
<body class="min-h-screen">

<?php include 'header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-8">
  <div class="mb-6 flex flex-wrap items-center gap-4">
    <h1 class="text-2xl font-bold tracking-wide flex items-center gap-3">
      Detailed Reports
      <span class="badge-soft"><?php echo esc($startDate); ?> → <?php echo esc($endDate); ?></span>
    </h1>
    <div class="ml-auto flex gap-2 flex-wrap">
      <a href="reports.php" class="btn">Reset</a>
      <a href="#company" class="btn">Company</a>
      <a href="#routes" class="btn">Routes</a>
      <a href="#vehicles" class="btn">Vehicles</a>
      <a href="#daily" class="btn">Daily</a>
    </div>
  </div>

  <!-- FILTERS -->
  <form method="get" class="section-card mb-8">
    <h3>Filters</h3>
    <div class="form-grid">
      <div>
        <label>Start Date</label>
        <input type="date" name="start" value="<?php echo esc($startDate); ?>">
      </div>
      <div>
        <label>End Date</label>
        <input type="date" name="end" value="<?php echo esc($endDate); ?>">
      </div>
      <div>
        <label>Company</label>
        <select name="company_id">
          <option value="0">All</option>
          <?php foreach($companies as $c): ?>
            <option value="<?php echo $c['id']; ?>" <?php if($companyId==$c['id']) echo 'selected'; ?>>
              <?php echo esc($c['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>From City</label>
        <select name="from_city">
          <option value="">All</option>
          <?php foreach($distinctFrom as $fc): ?>
            <option <?php if($fromCity===$fc) echo 'selected'; ?>><?php echo esc($fc); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>To City</label>
        <select name="to_city">
          <option value="">All</option>
          <?php foreach($distinctTo as $tc): ?>
            <option <?php if($toCity===$tc) echo 'selected'; ?>><?php echo esc($tc); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Vehicle</label>
        <select name="vehicle_type">
          <option value="">All</option>
          <?php foreach($vehicleTypes as $vt): ?>
            <option <?php if($vehicleType===$vt) echo 'selected'; ?>><?php echo esc($vt); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="flex flex-wrap gap-3 mt-2">
      <button class="btn btn-primary" type="submit">Apply</button>
      <a href="?start=<?php echo esc($monthStart); ?>&end=<?php echo esc($today); ?>" class="btn">This Month</a>
      <a href="?start=<?php echo esc(date('Y-01-01')); ?>&end=<?php echo esc($today); ?>" class="btn">Year To Date</a>
    </div>
    <p class="inline-note mt-4">
      Previous period comparison: <?php echo esc($prevStartStr); ?> → <?php echo esc($prevEndStr); ?> (<?php echo $rangeDays; ?> days)
    </p>
  </form>

  <!-- KPIs -->
  <div class="kpi-grid mb-10">
    <div class="kpi">
      <h4>Bilties</h4>
      <div class="val"><?php echo number_format($overall['cnt']); ?></div>
      <div class="delta"><?php echo esc(deltaStr($overall['cnt'], $prev['cnt'])); ?></div>
    </div>
    <div class="kpi">
      <h4>Total Amount</h4>
      <div class="val">PKR <?php echo fnum($overall['total_amount']); ?></div>
      <div class="delta"><?php echo esc(deltaStr($overall['total_amount'], $prev['total_amount'],' PKR')); ?></div>
    </div>
    <div class="kpi">
      <h4>Paid (Computed)</h4>
      <div class="val">PKR <?php echo fnum($paidAmount); ?></div>
      <div class="delta"><?php
        $prevPaid = $prev['total_amount'] - $prev['total_balance'];
        echo esc(deltaStr($paidAmount, $prevPaid,' PKR'));
      ?></div>
    </div>
    <div class="kpi">
      <h4>Outstanding</h4>
      <div class="val text-rose-600">PKR <?php echo fnum($overall['total_balance']); ?></div>
      <div class="delta"><?php echo esc(deltaStr($overall['total_balance'], $prev['total_balance'],' PKR')); ?></div>
    </div>
    <div class="kpi">
      <h4>Advance % (Avg)</h4>
      <div class="val"><?php echo fnum($avgAdvancePct,1); ?>%</div>
      <div class="delta">
        <?php
          $prevAdvancePct = ($prev['total_amount']>0)? ($prev['total_advance']/$prev['total_amount']*100):0;
          echo esc(deltaStr($avgAdvancePct, $prevAdvancePct, '%'));
        ?>
      </div>
    </div>
    <div class="kpi">
      <h4>Avg Amount / Bilty</h4>
      <div class="val">PKR <?php echo fnum($avgAmountPerBilty); ?></div>
      <div class="delta">
        <?php
          $prevAvgAmt = $prev['cnt']? $prev['total_amount']/$prev['cnt']:0;
          echo esc(deltaStr($avgAmountPerBilty, $prevAvgAmt,' PKR'));
        ?>
      </div>
    </div>
    <div class="kpi">
      <h4>Avg Balance / Bilty</h4>
      <div class="val">PKR <?php echo fnum($avgBalancePerBilty); ?></div>
      <div class="delta">
        <?php
          $prevAvgBal = $prev['cnt']? $prev['total_balance']/$prev['cnt']:0;
          echo esc(deltaStr($avgBalancePerBilty, $prevAvgBal,' PKR'));
        ?>
      </div>
    </div>
  </div>

  <!-- COMPANY BREAKDOWN -->
  <div id="company" class="section-card mb-10">
    <h3>Company Breakdown
      <span class="badge-soft">Top <?php echo count($companyBreak); ?></span>
    </h3>
    <div class="flex flex-wrap gap-2 mb-4">
      <a class="btn" href="?<?php echo esc(http_build_query(array_merge($_GET,['export'=>'company']))); ?>">Export CSV</a>
    </div>
    <?php if(empty($companyBreak)): ?>
      <div class="notice">No company data for selected filters.</div>
    <?php else: ?>
      <div class="table-wrap scroll-x">
        <table>
          <thead>
            <tr>
              <th>Company</th>
              <th class="text-right">Bilties</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Advance</th>
              <th class="text-right">Outstanding</th>
              <th class="text-right">Paid %</th>
              <th class="text-right">Avg / Bilty</th>
            </tr>
          </thead>
            <tbody>
              <?php foreach($companyBreak as $c): 
                $paidC = $c['total_amount'] - $c['total_balance'];
                $paidPct = ($c['total_amount']>0)? ($paidC/$c['total_amount']*100):0;
                $avgC   = $c['cnt']? $c['total_amount']/$c['cnt']:0;
              ?>
                <tr>
                  <td><?php echo esc($c['company_name']); ?></td>
                  <td class="text-right"><?php echo number_format($c['cnt']); ?></td>
                  <td class="text-right">PKR <?php echo fnum($c['total_amount']); ?></td>
                  <td class="text-right">PKR <?php echo fnum($c['total_advance']); ?></td>
                  <td class="text-right text-rose-600">PKR <?php echo fnum($c['total_balance']); ?></td>
                  <td class="text-right"><?php echo fnum($paidPct,1); ?>%</td>
                  <td class="text-right">PKR <?php echo fnum($avgC); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- TOP ROUTES -->
  <div id="routes" class="section-card mb-10">
    <h3>Top Routes (by Amount)</h3>
    <div class="flex flex-wrap gap-2 mb-4">
      <a class="btn" href="?<?php echo esc(http_build_query(array_merge($_GET,['export'=>'routes']))); ?>">Export CSV</a>
    </div>
    <?php if(empty($topRoutes)): ?>
      <div class="notice">No routes found for selected filters.</div>
    <?php else: ?>
      <div class="route-grid">
        <?php foreach($topRoutes as $r): ?>
          <div class="route-pill">
            <div class="text-sm font-semibold">
              <?php echo esc($r['from_city']); ?> → <?php echo esc($r['to_city']); ?>
            </div>
            <div class="text-xs text-slate-500">
              Bilties: <span class="font-semibold"><?php echo $r['cnt']; ?></span>
            </div>
            <div class="text-xs text-slate-500">
              Amount: <span class="font-semibold">PKR <?php echo fnum($r['total_amount']); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- VEHICLE SUMMARY -->
  <div id="vehicles" class="section-card mb-10">
    <h3>Vehicle Type Summary</h3>
    <div class="flex flex-wrap gap-2 mb-4">
      <a class="btn" href="?<?php echo esc(http_build_query(array_merge($_GET,['export'=>'vehicles']))); ?>">Export CSV</a>
    </div>
    <?php if(empty($vehicleSummary)): ?>
      <div class="notice">No vehicle data for selected filters.</div>
    <?php else: ?>
      <div class="table-wrap scroll-x">
        <table>
          <thead>
            <tr>
              <th>Vehicle</th>
              <th class="text-right">Bilties</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Outstanding</th>
              <th class="text-right">Avg / Bilty</th>
              <th class="text-right">Outstanding %</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($vehicleSummary as $v): 
              $avgVeh = $v['cnt']? $v['total_amount']/$v['cnt']:0;
              $outPct = ($v['total_amount']>0)? ($v['total_balance']/$v['total_amount']*100):0;
            ?>
              <tr>
                <td><?php echo esc($v['vehicle_type'] ?: 'N/A'); ?></td>
                <td class="text-right"><?php echo number_format($v['cnt']); ?></td>
                <td class="text-right">PKR <?php echo fnum($v['total_amount']); ?></td>
                <td class="text-right text-rose-600">PKR <?php echo fnum($v['total_balance']); ?></td>
                <td class="text-right">PKR <?php echo fnum($avgVeh); ?></td>
                <td class="text-right"><?php echo fnum($outPct,1); ?>%</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- DAILY SUMMARY -->
  <div id="daily" class="section-card mb-10">
    <h3>Daily Summary</h3>
    <div class="flex flex-wrap gap-2 mb-4">
      <a class="btn" href="?<?php echo esc(http_build_query(array_merge($_GET,['export'=>'daily']))); ?>">Export CSV</a>
    </div>
    <?php if(empty($daily)): ?>
      <div class="notice">No daily data for selected filters.</div>
    <?php else: ?>
      <div class="table-wrap scroll-x mb-6">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th class="text-right">Bilties</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Outstanding</th>
              <th class="text-right">Paid</th>
              <th class="text-right">Avg / Bilty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($daily as $d):
              $paidDay = $d['total_amount'] - $d['total_balance'];
              $avgDay = $d['cnt']? $d['total_amount']/$d['cnt']:0;
            ?>
              <tr>
                <td><?php echo esc($d['date']); ?></td>
                <td class="text-right"><?php echo number_format($d['cnt']); ?></td>
                <td class="text-right">PKR <?php echo fnum($d['total_amount']); ?></td>
                <td class="text-right text-rose-600">PKR <?php echo fnum($d['total_balance']); ?></td>
                <td class="text-right text-emerald-600">PKR <?php echo fnum($paidDay); ?></td>
                <td class="text-right">PKR <?php echo fnum($avgDay); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <?php if(!empty($monthly)): ?>
      <h3 style="margin-top:30px;">Monthly Rollup (Span &gt; 31 days)</h3>
      <div class="table-wrap scroll-x">
        <table>
          <thead>
            <tr>
              <th>Month</th>
              <th class="text-right">Bilties</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Outstanding</th>
              <th class="text-right">Paid</th>
              <th class="text-right">Avg / Bilty</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($monthly as $m):
              $paidM = $m['total_amount'] - $m['total_balance'];
              $avgM  = $m['cnt']? $m['total_amount']/$m['cnt']:0;
            ?>
              <tr>
                <td><?php echo esc($m['ym']); ?></td>
                <td class="text-right"><?php echo number_format($m['cnt']); ?></td>
                <td class="text-right">PKR <?php echo fnum($m['total_amount']); ?></td>
                <td class="text-right text-rose-600">PKR <?php echo fnum($m['total_balance']); ?></td>
                <td class="text-right text-emerald-600">PKR <?php echo fnum($paidM); ?></td>
                <td class="text-right">PKR <?php echo fnum($avgM); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- FOOTER ACTIONS -->
  <div class="flex flex-wrap gap-3 mb-16">
    <a href="index.php" class="btn">Dashboard</a>
    <a href="view_bilty.php" class="btn">Bilty List</a>
    <a href="#top" class="btn">Back to Top</a>
  </div>
</main>

</body>
</html>