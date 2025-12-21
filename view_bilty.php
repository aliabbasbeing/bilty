<?php
require_once 'config.php';

// Read filter/search params (GET)
$q = trim($_GET['q'] ?? '');
$company_filter = isset($_GET['company']) ? intval($_GET['company']) : 0;

// Build WHERE clause
$where = [];
if ($company_filter > 0) {
    $where[] = "c.company_id = " . intval($company_filter);
}
if ($q !== '') {
    $esc = $conn->real_escape_string($q);
    $where[] = "(
        c.bilty_no LIKE '%{$esc}%'
        OR cp.name LIKE '%{$esc}%'
        OR c.driver_name LIKE '%{$esc}%'
        OR c.from_city LIKE '%{$esc}%'
        OR c.to_city LIKE '%{$esc}%'
        OR c.vehicle_no LIKE '%{$esc}%'
    )";
}
$where_sql = '';
if (!empty($where)) $where_sql = 'WHERE ' . implode(' AND ', $where);

// Fetch companies for company filter dropdown
$companies = [];
$cres = $conn->query("SELECT id, name FROM companies ORDER BY name ASC");
if ($cres) {
    while ($crow = $cres->fetch_assoc()) $companies[] = $crow;
    $cres->free();
}

// Fetch rows with applied filters
$sql = "SELECT c.*, cp.name AS company_name
        FROM consignments c
        JOIN companies cp ON cp.id = c.company_id
        {$where_sql}
        ORDER BY c.date DESC, c.id DESC";
$res = $conn->query($sql);
$rows = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $res->free();
}
?>
<!doctype html>
<html lang="en">
<head>
  <?php include 'head.php'; ?>
  <title>All Bilties — Bilty Management</title>
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
      max-width: 1600px;
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
    
    .filter-card {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .filter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
      align-items: end;
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
    
    .filter-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 0.5rem;
    }
    
    .table-card {
      background: white;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      margin-bottom: 2rem;
    }
    
    .table-header {
      padding: 1.5rem;
      border-bottom: 2px solid #f3f4f6;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .table-info {
      color: #6b7280;
      font-size: 0.95rem;
    }
    
    .table-actions {
      display: flex;
      gap: 0.75rem;
    }
    
    .table-wrapper {
      overflow-x: auto;
      max-height: 70vh;
    }
    
    .modern-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }
    
    .modern-table thead th {
      position: sticky;
      top: 0;
      background: linear-gradient(to bottom, #f9fafb, #f3f4f6);
      color: #374151;
      font-size: 0.8125rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding: 1rem;
      text-align: left;
      border-bottom: 2px solid #e5e7eb;
      z-index: 10;
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
    
    .checkbox-cell {
      width: 50px;
      text-align: center;
    }
    
    .checkbox-cell input[type="checkbox"] {
      width: 1.125rem;
      height: 1.125rem;
      cursor: pointer;
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
    
    .btn-icon {
      padding: 0.625rem;
      width: 36px;
      height: 36px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
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
    
    .empty-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #374151;
      margin: 0 0 0.5rem 0;
    }
    
    .empty-text {
      font-size: 0.95rem;
      margin: 0;
    }
    
    @media (max-width: 768px) {
      .page-header {
        padding: 1.5rem;
      }
      
      .page-title {
        font-size: 1.5rem;
      }
      
      .filter-grid {
        grid-template-columns: 1fr;
      }
      
      .table-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .table-actions {
        width: 100%;
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
        justify-content: center;
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

  <div class="page-container">
  <div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
        All Bilties
      </h1>
      <p class="page-subtitle">View, search, and manage all your bilty records</p>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
      <form id="filterForm" method="get">
        <div class="filter-grid">
          <div>
            <label class="filter-label">Company</label>
            <select id="company" name="company" class="filter-select">
              <option value="0">All Companies</option>
              <?php foreach ($companies as $c): ?>
                <option value="<?php echo intval($c['id']); ?>" <?php if ($company_filter == intval($c['id'])) echo 'selected'; ?>>
                  <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="filter-label">Search</label>
            <input 
              id="q" 
              name="q" 
              type="search" 
              class="filter-input"
              placeholder="Bilty no, company, driver, route..." 
              value="<?php echo htmlspecialchars($q); ?>"
            />
          </div>
          
          <div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">
              <i class="fa-solid fa-search"></i>
              Search
            </button>
          </div>
          
          <div>
            <button type="button" id="resetFilters" class="btn btn-secondary" style="width: 100%;">
              <i class="fa-solid fa-rotate-left"></i>
              Clear
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
      <div class="table-header">
        <div class="table-info">
          Showing <strong><?php echo count($rows); ?></strong> bilty records
        </div>
        <div class="table-actions">
          <a href="add_bilty.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i>
            Add New Bilty
          </a>
          <button id="printSelectedBtn" class="btn btn-secondary" type="button">
            <i class="fa-solid fa-file-invoice"></i>
            Generate Bill
          </button>
        </div>
      </div>
      
      <div class="table-wrapper">
        <?php if (empty($rows)): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <i class="fa-solid fa-inbox"></i>
            </div>
            <h3 class="empty-title">No bilties found</h3>
            <p class="empty-text">Try adjusting your search filters or add a new bilty</p>
          </div>
        <?php else: ?>
          <table class="modern-table">
            <thead>
              <tr>
                <th class="checkbox-cell">
                  <input id="selectAll" type="checkbox" aria-label="Select all" />
                </th>
                <th>#</th>
                <th>Bilty No</th>
                <th>Date</th>
                <th>Company</th>
                <th>Route</th>
                <th>Vehicle</th>
                <th>Driver</th>
                <th style="text-align: right;">Amount</th>
                <th style="text-align: right;">Balance</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody id="biltiesTableBody">
              <?php
              $index = 1;
              foreach ($rows as $r):
                $bilty_id = (int)$r['id'];
                $bilty_no = htmlspecialchars($r['bilty_no'] ?? '');
                $date = htmlspecialchars($r['date'] ?? '');
                $company = htmlspecialchars($r['company_name'] ?? '');
                $from = htmlspecialchars($r['from_city'] ?? '');
                $to = htmlspecialchars($r['to_city'] ?? '');
                $route = $from . ($to ? ' → ' . $to : '');
                $vehicle_no = htmlspecialchars($r['vehicle_no'] ?? '');
                $driver = htmlspecialchars($r['driver_name'] ?? '');
                $amount = number_format((float)($r['amount'] ?? 0), 2);
                $balance = number_format((float)($r['balance'] ?? 0), 2);
                
                // Determine vehicle ownership
                $details = $r['details'] ?? '';
                $isRental = (stripos($details, 'rental') !== false);
                $ownerBadge = $isRental 
                  ? '<span class="badge badge-rental">Rental</span>'
                  : '<span class="badge badge-own">Own</span>';
              ?>
              <tr>
                <td class="checkbox-cell">
                  <input type="checkbox" class="bilty-checkbox" value="<?php echo $bilty_id; ?>" />
                </td>
                <td><?php echo $index++; ?></td>
                <td><strong><?php echo $bilty_no; ?></strong></td>
                <td><?php echo $date; ?></td>
                <td><?php echo $company; ?></td>
                <td><?php echo $route ?: '—'; ?></td>
                <td>
                  <?php echo $vehicle_no ?: '—'; ?>
                  <?php echo $ownerBadge; ?>
                </td>
                <td><?php echo $driver ?: '—'; ?></td>
                <td style="text-align: right;"><strong>Rs. <?php echo $amount; ?></strong></td>
                <td style="text-align: right;">Rs. <?php echo $balance; ?></td>
                <td style="text-align: center;">
                  <a href="view_bilty_details.php?id=<?php echo $bilty_id; ?>" class="btn btn-secondary btn-icon" title="View Details">
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
  </div>
                </tr>
              <?php endif; ?>

              <?php foreach ($rows as $r):
                  $owner = '';
                  $driver_number = '';
                  if (!empty($r['details'])) {
                      if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
                      if (preg_match('/Driver\s*number:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
                  }
                  $balance = (float)($r['balance'] ?? 0);
              ?>
                <tr>
                  <td class="select-col">
                    <input class="row-checkbox" type="checkbox" value="<?php echo intval($r['id']); ?>" aria-label="Select bilty <?php echo htmlspecialchars($r['bilty_no']); ?>">
                  </td>
                  <td><?php echo htmlspecialchars($r['id']); ?></td>
                  <td class="text-primary font-semibold"><?php echo htmlspecialchars($r['bilty_no']); ?></td>
                  <td class="text-gray-600"><?php echo htmlspecialchars($r['date']); ?></td>
                  <td><?php echo htmlspecialchars($r['company_name'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['from_city'] ?? '') . ' → ' . htmlspecialchars($r['to_city'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?></td>
                  <td>
                    <?php if ($owner === 'Rental'): ?>
                      <span class="badge badge-rental">Rental</span>
                    <?php elseif ($owner === 'Own'): ?>
                      <span class="badge badge-own">Own</span>
                    <?php else: ?>
                      <span class="badge badge-dash">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div><?php echo htmlspecialchars($r['driver_name'] ?? ''); ?></div>
                    <?php if ($driver_number): ?><div style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($driver_number); ?></div><?php endif; ?>
                  </td>

                  <td class="text-right text-gray-700"><?php echo number_format((float)($r['km'] ?? 0)); ?></td>
                  <td class="text-right text-gray-700"><?php echo number_format((float)($r['rate'] ?? 0), 2); ?></td>

                  <td class="text-right font-medium"><?php echo number_format((float)($r['amount'] ?? 0), 2); ?></td>
                  <td class="text-right"><?php echo number_format((float)($r['advance'] ?? 0), 2); ?></td>
                  <td class="text-right <?php echo $balance > 0 ? 'text-red-600 font-bold' : 'text-green-700'; ?>">
                    <?php echo number_format($balance, 2); ?>
                  </td>

                  <td class="text-right">
                    <a href="view_bilty_details.php?id=<?php echo urlencode($r['id']); ?>" class="btn btn-primary" style="padding:6px 10px;">
                      <i class="fa-solid fa-eye"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile cards -->
      <div class="md:hidden space-y-4">
        <?php if (empty($rows)): ?>
          <div class="card" style="text-align:center; color:#475569;">No bilties found. Adjust filters or clear the search.</div>
        <?php endif; ?>

        <?php foreach ($rows as $r):
           $owner = '';
           $driver_number = '';
           if (!empty($r['details'])) {
               if (preg_match('/Vehicle:\s*(Own|Rental)/i', $r['details'], $m)) $owner = ucfirst(strtolower($m[1]));
               if (preg_match('/Driver\s*number:\s*([0-9+\-\s()]+)/i', $r['details'], $m2)) $driver_number = trim($m2[1]);
           }
           $balance = (float)($r['balance'] ?? 0);
        ?>
          <article class="card">
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <input class="row-checkbox" type="checkbox" value="<?php echo intval($r['id']); ?>">
                  <div class="text-lg font-semibold text-primary"><?php echo htmlspecialchars($r['bilty_no']); ?></div>
                </div>
                <div class="text-xs text-gray-500">
                  <?php echo htmlspecialchars($r['date']); ?> • <?php echo htmlspecialchars($r['company_name'] ?? ''); ?>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-500">Amount</div>
                <div class="text-lg font-medium"><?php echo number_format((float)($r['amount'] ?? 0), 2); ?></div>
                <div class="mt-2">
                  <a href="view_bilty_details.php?id=<?php echo urlencode($r['id']); ?>" class="btn btn-primary" style="padding:6px 10px;">View</a>
                </div>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2 text-sm text-gray-700">
              <div><span class="font-medium">Route:</span> <?php echo htmlspecialchars($r['from_city'] ?? '') . ' → ' . htmlspecialchars($r['to_city'] ?? ''); ?></div>
              <div><span class="font-medium">Vehicle:</span> <?php echo htmlspecialchars($r['vehicle_no'] ?? ''); ?></div>
              <div><span class="font-medium">Driver:</span> <?php echo htmlspecialchars($r['driver_name'] ?? ''); ?></div>
              <div><span class="font-medium">Owner:</span> <?php echo $owner ?: '—'; ?></div>
              <div><span class="font-medium">KM:</span> <?php echo number_format((float)($r['km'] ?? 0)); ?></div>
              <div><span class="font-medium">Rate:</span> <?php echo number_format((float)($r['rate'] ?? 0), 2); ?></div>
              <div><span class="font-medium">Balance:</span>
                <span class="<?php echo $balance > 0 ? 'text-red-600 font-semibold' : 'text-green-700'; ?>">
                  <?php echo number_format($balance, 2); ?>
                </span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </main>

  <script>
    // Enhance the two left controls with the requested styled background/shadow
    (function styleFilterFields() {
      const company = document.getElementById('company');
      const q = document.getElementById('q');
      if (company) company.classList.add('filter-field');
      if (q) q.classList.add('filter-field');
    })();

    // Keep same places, but auto-apply: debounce search, immediate on company change
    (function(){
      const companySel = document.getElementById('company');
      const qInput = document.getElementById('q');
      const reset = document.getElementById('resetFilters');

      function applyFilters() {
        const params = new URLSearchParams(window.location.search);
        const company = companySel ? companySel.value : '0';
        const q = qInput ? qInput.value.trim() : '';

        if (company && company !== '0') params.set('company', company); else params.delete('company');
        if (q) params.set('q', q); else params.delete('q');

        const url = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
        window.location.replace(url);
      }

      function debounce(fn, wait) {
        let t; return function(...args){
          clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), wait);
        }
      }
      const debouncedApply = debounce(applyFilters, 350);

      if (companySel) companySel.addEventListener('change', applyFilters);
      if (qInput) qInput.addEventListener('input', debouncedApply);

      // Reset chip behaves like previous "Clear"
      if (reset) {
        const doReset = () => {
          if (companySel) companySel.value = '0';
          if (qInput) qInput.value = '';
          applyFilters();
        };
        reset.addEventListener('click', doReset);
        reset.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); doReset(); }});
      }
    })();

    // Selection UI and bulk print
    (function(){
      const selectAll = document.getElementById('selectAll');
      const checkboxes = () => Array.from(document.querySelectorAll('.row-checkbox'));
      const getSelectedIds = () => checkboxes().filter(cb => cb.checked).map(cb => cb.value);

      if (selectAll) {
        selectAll.addEventListener('change', function(){
          checkboxes().forEach(cb => cb.checked = this.checked);
        });
      }

      document.addEventListener('change', function(e){
        if (!e.target.matches('.row-checkbox')) return;
        const all = checkboxes();
        if (all.length === 0) return;
        const allChecked = all.every(cb => cb.checked);
        if (selectAll) selectAll.checked = allChecked;
      });

      const openInNewWindow = (url) => {
        const w = window.open(url, '_blank', 'noopener');
        if (w) w.focus();
      };

      const printBtn = document.getElementById('printSelectedBtn');
      if (printBtn) {
        printBtn.addEventListener('click', function(){
          const ids = getSelectedIds();
          if (ids.length === 0) { alert('Please select at least one bilty to print.'); return; }
          const url = 'print_bulk.php?ids=' + encodeURIComponent(ids.join(','));
          openInNewWindow(url + '&auto=1');
        });
      }
    })();
  </script>
</body>
</html>