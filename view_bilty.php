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
  <title>Bilties — Bilty Management</title>
  <style>
    :root { --tw-shadow-color: #2c1810; }

    /* Page bg to match theme */
    body.bg-page {
      background:
        radial-gradient(1200px 420px at 50% -10%, rgba(153, 27, 65, .10), transparent 60%),
        #efe6f3;
    }

    /* Keep same layout, just style fields */
    .filter-field {
      appearance: none;
      width: 100%;
      border: 1px solid #d7dde7;
      border-radius: 10px;
      padding: 10px 12px;
      background: #fff;
      box-shadow: 2px 2px 0 0 var(--tw-shadow-color);
      transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .filter-field:focus {
      outline: none;
      border-color: var(--primary, #97113a);
      box-shadow:
        0 0 0 2px rgba(151,17,58,.22),
        2px 2px 0 0 var(--tw-shadow-color);
    }

    .filter-chip {
      display:inline-flex; align-items:center; gap:8px;
      padding: 8px 12px; border-radius: 10px; font-size:14px; font-weight:600;
      border: 1px solid #d7dde7; background:#fff; color:#0f172a;
      box-shadow: 2px 2px 0 0 var(--tw-shadow-color);
      user-select: none;
      cursor: pointer;
    }
    .filter-chip i { opacity:.9; }
    .filter-chip:hover {
      background: #f8fafc;
    }

    /* Table card with scrollable container */
    .table-card {
      background: #fff;
      border: 1px solid rgba(2, 6, 23, 0.06);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 2px 2px 0 0 var(--tw-shadow-color);
    }

    .table-scroll-container {
      max-height: 600px;
      overflow-y: auto;
      overflow-x: auto;
    }

    /* Custom scrollbar styling */
    .table-scroll-container::-webkit-scrollbar {
      width: 10px;
      height: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 10px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
      border: 2px solid #f1f5f9;
    }

    .table-scroll-container::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* Firefox scrollbar */
    .table-scroll-container {
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 #f1f5f9;
    }

    table { width:100%; border-collapse: separate; border-spacing: 0; }
    thead th {
      position: sticky; 
      top: 0;
      background: linear-gradient(180deg, #f9fafb, #f3f4f6);
      color:#475569; 
      font-size:12px; 
      text-transform:uppercase; 
      letter-spacing:.02em;
      border-bottom:1px solid #e5e7eb; 
      padding:10px 12px; 
      z-index:10;
      text-align:left;
    }
    tbody td { padding:12px; border-bottom:1px solid #f1f5f9; color:#0f172a; font-size:14px; }
    tbody tr:nth-child(odd) { background:#ffffff; }
    tbody tr:nth-child(even) { background:#fcfcfd; }
    tbody tr:hover { background:#f8fafc; }

    .select-col { width:44px; text-align:center; }
    .text-right { text-align:right; }

    /* Badges */
    .badge {
      display:inline-flex; align-items:center; gap:6px;
      font-size: 11px; font-weight: 700;
      padding: 2px 8px; border-radius: 999px; border:1px solid transparent;
    }
    .badge-own { background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
    .badge-rental { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .badge-dash { background:#f1f5f9; color:#475569; border-color:#e2e8f0; }

    /* Buttons */
    .btn {
      display:inline-flex; align-items:center; gap:8px;
      padding: 8px 12px; border-radius: 10px; font-size:14px; font-weight:600;
      border: 1px solid #d7dde7; background:#fff; color:#0f172a;
      box-shadow: 2px 2px 0 0 var(--tw-shadow-color);
      transition: filter .15s ease;
      cursor: pointer;
    }
    .btn:hover { filter: brightness(1.03); }
    .btn-primary { border-color: var(--primary, #97113a); background: var(--primary, #97113a); color:#fff; }

    .count-muted { color:#64748b; font-size:13px; }

    /* Mobile card */
    .card {
      background:#fff; border:1px solid rgba(2,6,23,.06); border-radius:16px; padding:16px;
      box-shadow: 2px 2px 0 0 var(--tw-shadow-color);
    }

    @media (max-width:768px) {
      .select-col { width:34px; }
      .table-scroll-container {
        max-height: 500px;
      }
    }
  </style>
</head>
<body class="bg-page min-h-screen text-gray-800">
  <?php include 'header.php'; ?>

  <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6">

      <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-primary">Bilty Records</h1>
          <!-- <p class="text-sm text-gray-600">Filters auto-apply; positions kept same.</p> -->
        </div>
        <!-- Kept right side clear (no New/Export per request) -->
      </header>

      <!-- Keep SAME PLACES: left has company + search; right has area where buttons used to be -->
      <form id="filterForm" method="get" class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <!-- Left: dropdown + search (same container and widths as before) -->
        <div class="flex items-center gap-3 w-full md:max-w-md">
          <select id="company" name="company" class="block w-1/2 rounded-md border border-gray-200 px-0">
            <option value="0">— All companies —</option>
            <?php foreach ($companies as $c): ?>
              <option value="<?php echo intval($c['id']); ?>" <?php if ($company_filter == intval($c['id'])) echo 'selected'; ?>>
                <?php echo htmlspecialchars($c['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>

          <input id="q" name="q" type="search" placeholder="Search bilty, company, driver, route..." value="<?php echo htmlspecialchars($q); ?>" class="block w-1/2 rounded-md border border-gray-200 px-3 py-2">
        </div>

        <!-- Right: keep the place where Apply/Clear buttons were, but show a non-action chip -->
        <div class="">
          <span class="" title="Filtering applies automatically">
            
          </span>
          <!-- second chip to keep spacing similar to two buttons previously -->
          <span class="filter-chip" id="resetFilters" role="button" tabindex="0" title="Reset filters to defaults">
            <i class="fa-solid fa-rotate-left"></i> Clear Search
          </span>
        </div>
      </form>

      <div class="flex items-center justify-between">
        <div class="count-muted">Showing <strong><?php echo count($rows); ?></strong> record(s)</div>

        <div class="flex items-center gap-2">
          <button id="printSelectedBtn" class="btn btn-primary" type="button">
            <i class="fa-solid fa-file-invoice"></i>
            Generate Bill
          </button>
          <!-- Download PDF / Export CSV / New Bilty removed as requested -->
        </div>
      </div>

      <!-- Desktop Table with Scrollable Container -->
      <div class="hidden md:block table-card">
        <div class="table-scroll-container">
          <table class="min-w-full">
            <thead>
              <tr>
                <th class="select-col"><input id="selectAll" type="checkbox" aria-label="Select all"></th>
                <th>#</th>
                <th>Bilty No</th>
                <th>Date</th>
                <th>Company</th>
                <th>Route</th>
                <th>Vehicle No</th>
                <th>Owner</th>
                <th>Driver</th>
                <th class="text-right">KM</th>
                <th class="text-right">Rate</th>
                <th class="text-right">Amount</th>
                <th class="text-right">Advance</th>
                <th class="text-right">Balance</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>

            <tbody id="biltiesTableBody">
              <?php if (empty($rows)): ?>
                <tr>
                  <td colspan="15" style="padding:20px; text-align:center; color:#64748b;">
                    No bilties found. Adjust filters or clear the search.
                  </td>
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