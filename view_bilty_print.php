<?php
require_once 'config.php';

/*
  A5 Single Bilty Printable (With Control Bar Editing)
  ----------------------------------------------------
  Changes:
    - When rate == 0, mark Rate as "Fixed" and treat Amount as manually set.
    - Client-side initializes manual override when rate is fixed.
    - Editing Rate to a numeric value will re-enable auto-compute.
*/

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { echo "Invalid bilty id."; exit; }

$sql = "SELECT c.*,
               cp.name    AS company_name,
               cp.address AS company_address
        FROM consignments c
        JOIN companies cp ON cp.id = c.company_id
        WHERE c.id = ?
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res  = $stmt->get_result();
$bilty = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$bilty) { echo "Bilty not found."; exit; }

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function n2($v){ return number_format((float)$v,2,'.',','); }
function n0($v){ return is_numeric($v)?(int)$v:0; }

$dateDisplay = !empty($bilty['date']) ? date('d-M-Y', strtotime($bilty['date'])) : '';
$company_name = $bilty['company_name'] ?? '';
$company_address = $bilty['company_address'] ?? '';
$route_str = trim(($bilty['from_city'] ?? '') . (($bilty['to_city'] ?? '') ? ' → '.$bilty['to_city'] : ''));
$vehicle_type = $bilty['vehicle_type'] ?: '';
$vehicle_no = $bilty['vehicle_no'] ?: '';
$driver_name = $bilty['driver_name'] ?: '';
$driver_number = $bilty['driver_number'] ?: '';

// Extract driver number if embedded inside details meta
if ($driver_number === '' && !empty($bilty['details']) &&
    preg_match('/Driver\s*number:\s*([0-9+\-\s()]+)/i', $bilty['details'], $m2)) {
  $driver_number = trim($m2[1]);
}

// Clean notes (remove meta style lines)
function clean_details_text(string $text): string {
  if ($text==='') return '';
  $lines = preg_split('/\R/', $text);
  $keep = [];
  foreach ($lines as $ln){
    $t = trim($ln);
    if ($t==='') continue;
    if (preg_match('/^(Vehicle|Driver\s*number|DriverNumber|Additional\s*info)\s*[:\-]/i',$t)) continue;
    if (preg_match('/^(Own|Rental)$/i',$t)) continue;
    $keep[] = $t;
  }
  return trim(implode("\n",$keep));
}
$notes = clean_details_text($bilty['details'] ?? '');

$qty     = n0($bilty['qty'] ?? 0);
$km      = n0($bilty['km'] ?? 0);
$rate    = (float)($bilty['rate'] ?? 0);
$amount  = (float)($bilty['amount'] ?? ($km * $rate)); // fallback compute

// If rate is exactly zero, treat this bilty as Fixed-rate (amount stored manually)
$isFixed = ($rate == 0.0);

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Print Bilty #<?php echo esc($bilty['bilty_no']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="pdf.js"></script>
<style>
@page { size: A5 portrait; margin: 0mm; }

:root {
  --font: "Arial","Helvetica",sans-serif;
  --fs: 11px;
  --hair: 0.6px;
  /* Updated color palette */
  --primary-color: #06324e;
  --primary-light: #dbeafe;
  --primary-dark: #06324e;
  --accent-bg: #e0e7ff;
  --header-bg: #06324e;
  --header-text: #ffffff;
  --th-bg: #e0f2fe;
  --th-border: #93c5fd;
  --row-alt: #f8fafc;
  --border-color: #cbd5e1;
  --gradient-header: linear-gradient(to right, #06324e, #06324e);
}

html,body {
  margin:0; padding:0;
  font-family:var(--font);
  font-size:var(--fs);
  background:#f1f5f9;
  color:#000;
  -webkit-print-color-adjust: exact !important;
  print-color-adjust: exact !important;
}

.no-print { }
@media print {
  body { background:#fff; }
  .no-print { display:none !important; }
  .a5-page { box-shadow:none; margin:0; width:auto; }
  #controlBar { display:none !important; }
}

#controlBar {
  background:#fff;
  border-bottom:1px solid #d1d5db;
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  align-items:center;
  justify-content:flex-end;
  padding:14px 18px;
  box-shadow:0 2px 4px rgba(0,0,0,.08);
}

#controlBar .status-badge {
  font-size:13px;
  padding:8px 16px;
  border-radius:999px;
  font-weight:600;
  background:var(--primary-light);
  color:var(--primary-dark);
  box-shadow:0 2px 4px rgba(0,0,0,.08);
}

#controlBar button, #controlBar a {
  font-size:14px;
  font-weight:600;
  padding:10px 18px;
  border-radius:8px;
  border:1px solid #d1d5db;
  background:#fff;
  color:#111827;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:6px;
  text-decoration:none;
  box-shadow:0 2px 4px rgba(0,0,0,.08);
  transition:background .18s, box-shadow .18s;
}
#controlBar button:hover, #controlBar a:hover { background:#f1f5f9; }
#controlBar .primary-btn { background:var(--primary-color); color:#fff; border-color:var(--primary-dark); }
#controlBar .primary-btn:hover { background:var(--primary-dark); }
#controlBar .print-btn { background:#4f46e5; color:#fff; border-color:#4338ca; }
#controlBar .print-btn:hover{ background:#4338ca; }
#controlBar .pdf-btn { background:#374151; color:#fff; border-color:#1f2937; }
#controlBar .pdf-btn:hover { background:#1f2937; }
#controlBar button[disabled] { opacity:.5; cursor:not-allowed; }

.a5-wrapper {
  width:100%;
  display:flex;
  justify-content:center;
  padding:16px 0 40px;
  box-sizing:border-box;
}

.a5-page {
  width:100%;
  max-width: 470px; /* approximate inner width for A5 with margins */
  background:#fff;
  border:1px solid var(--border-color);
  padding:14px 16px 18px;
  box-sizing:border-box;
  position:relative;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  margin-top: -10px;
}

.header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    border-bottom: var(--hair) solid var(--border-color);
    padding-bottom: 10px;
    margin-bottom: 10px;
    background: var(--gradient-header);
    margin: -20px -16px 13px;
    padding: 30px 9px;
    /* border-radius: 6px 6px 0 0; */
    color: var(--header-text);
}

.brand-left .brand-name {
  font-size:32px;           /* increased from 20px */
  font-weight:700;
  font-style:italic;
  letter-spacing:.4px;
  margin-bottom:4px;        /* slightly more space under */
  color: white;
  text-shadow: 0 2px 4px rgba(0,0,0,0.18);
}

.brand-left .brand-sub { 
  font-size:16px;           /* increased from 10px */
  font-weight:600; 
  color: rgba(255,255,255,0.92);
}
.inline-rows { margin-top:4px; font-size:10px; line-height:1.2; color: rgba(255,255,255,0.95); }
.inline-rows .lbl { font-weight:700; margin-right:4px; }

.section-pair {
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
  margin-top:12px;
}

.panel {
  border:var(--hair) solid var(--border-color);
  display:flex;
  flex-direction:column;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.panel-title {
  background:var(--primary-color);
  color: white;
  font-weight:7 00;
  font-size:16px;
  padding: 8px 12px;
  text-shadow: 0 1px 1px rgba(0,0,0,0.1);
}
.panel-body { 
  padding:8px 8px 6px; 
  font-size: 15px;
  min-height: 22px; 
  background: #fff;
}

.field-line {
  display:flex;
  margin-bottom:4px;
  gap:4px;
  align-items:flex-start;
}
.field-line .flabel { width:60px; font-weight:700; flex-shrink:0; color: var(--primary-dark); }
.field-line .fval {
  flex:1;
  min-height:22px;
  line-height:1.2;
  word-break:break-word;
}

.route-box {
  margin-top:8px;
  border:var(--hair) solid var(--border-color);
  padding:6px 6px 4px;
  font-size:11px;
  background:#fff;
  min-height:32px;
  white-space:pre-wrap;
  word-break:break-word;
  border-radius: 4px;
}

.items-table-wrap {
  margin-top:12px;
  border:var(--hair) solid var(--border-color);
  overflow:hidden;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.items-table {
  width:100%;
  border-collapse:collapse;
  table-layout:fixed;
  font-size:15px;
}
.items-table th, .items-table td {
  border:var(--hair) solid var(--border-color);
  padding:6px 6px;
  vertical-align:middle;
}
.items-table th {
  background: var(--gradient-header);
  color: white;
  font-weight:700;
  font-size:15px;
  text-align:center;
  text-shadow: 0 1px 1px rgba(0,0,0,0.1);
  padding: 8px 6px;
}
.items-table tr:nth-child(even) {
  background-color: var(--row-alt);
}
.items-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
.items-table td.center { text-align:center; }

.amount-summary {
  margin-top:12px;
  width:100%;
  border:var(--hair) solid var(--border-color);
  border-collapse:collapse;
  font-size:15px;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.amount-summary td {
  border:var(--hair) solid var(--border-color);
  padding:6px 8px;
}
.amount-summary .label { 
  font-weight:700; 
  width:70%; 
  background: var(--th-bg);
  color: var(--primary-dark);
}
.amount-summary .val { 
  text-align:right; 
  font-variant-numeric:tabular-nums;
  font-weight: 600;
  color: var(--primary-dark);
}

.notes-box {
  margin-top:12px;
  border:var(--hair) solid var(--border-color);
  padding:8px 8px 6px;
  font-size:10.5px;
  min-height:40px;
  white-space:pre-wrap;
  word-break:break-word;
  border-radius: 6px;
  background: #fcfcfc;
}

.signature-block {
  margin-top:30mm; /* or 120px */
  display:flex;
  justify-content:space-between;
  align-items:flex-end;
  font-size:10px;
  gap:10px;
}
.signature-area {
  flex: 1;
  border-top:var(--hair) solid var(--border-color);
  padding-top:4px;
  text-align:center;
  font-weight:600;
  min-height:30px;
  color: var(--primary-dark);
}

.print-footer-tag {
  font-size:9px;
  opacity:.75;
  text-align:right;
}

.inline-input {
  font-size:10px;
  padding:4px 6px;
  border:0.7px solid var(--border-color);
  border-radius:3px;
  width:100%;
  background:#fff;
  box-sizing:border-box;
  font-family:inherit;
}

.editable-block.editing {
  background: #fff;
  min-height:16px;
}

/* Overlay for progress (PDF save) */
.progress-overlay {
  position:fixed; inset:0;
  background:rgba(17,24,39,.55);
  display:none;
  align-items:center; justify-content:center;
  z-index:2000;
  color:#fff;
  flex-direction:column;
  gap:12px;
  font-size:14px;
}
.progress-wrap {
  width:240px;
  background:rgba(255,255,255,.15);
  border:1px solid rgba(255,255,255,.35);
  border-radius:6px;
  overflow:hidden;
  height:10px;
}
.progress-bar {
  height:10px;
  width:0%;
  background:#10b981;
  transition:width .3s;
}
@media (max-width:560px){
  .section-pair { grid-template-columns:1fr; }
  .a5-page { max-width:100%; }
}

/* Add printable color support */
@media print {
  .header-row {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    color-adjust: exact;
  }
  .panel-title, .items-table th {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    color-adjust: exact;
  }
  .amount-summary .label {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    color-adjust: exact;
  }
}

</style>
</head>
<body>

<!-- Control Bar -->
<div id="controlBar" class="no-print">
  <span id="statusBadge" class="status-badge">BILTY</span>
  <button id="toggleEdit" type="button">Edit</button>
  <button id="applyBtn" class="primary-btn" type="button">Apply (No Save)</button>
  <button id="printBtn" class="print-btn" type="button">Print</button>
  <button id="savePdfBtn" class="pdf-btn" type="button">Save PDF</button>
  <button id="resetBtn" type="button">Reset</button>
  <a href="view_bilty.php">Back</a>
</div>

<div class="a5-wrapper" id="biltyRoot">
  <div class="a5-page" id="biltyPage">
    <div class="header-row">
      <div class="brand-left">
        <div id="from_name" class="brand-name editable-block"><?php echo esc('Bahar Ali'); ?></div>
        <div id="from_business" class="brand-sub editable-block"><?php echo esc('Mini Goods Transport Hyderabad'); ?></div>
        <div class="inline-rows">
          <div><span class="lbl">Cell:</span><span id="from_phone" class="editable-block" style="font-weight:600;"><?php echo esc('0306-3591311, 0302-3928417'); ?></span></div>
          <div><span class="lbl">Addr:</span><span id="from_address" class="editable-block"><?php echo esc('Hala Naka Road Near Isra University Hyderabad'); ?></span></div>
        </div>
      </div>
      <div style="text-align:right; min-width:140px;">
        <div style="font-size:16px; font-weight:700; letter-spacing:.5px; margin-bottom:4px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">BILTY</div>
        <div style="margin-top:6px; font-size:10.5px;">
          <div style="margin-bottom:4px; font-size:15.5px;"><strong>No:</strong> <span><?php echo esc($bilty['bilty_no']); ?></span></div>
          <div style="margin-bottom:4px; font-size:15.5px;"><strong>Date:</strong> <span id="bilty_date" class="editable-block"><?php echo esc($dateDisplay); ?></span></div>
        </div>
      </div>
    </div>

    <div class="section-pair">
      <div class="panel">
        <div class="panel-title">Company / To</div>
        <div class="panel-body">
          <div class="field-line">
            <div class="flabel">Name</div>
            <div id="to_name" class="fval editable-block"><?php echo esc($company_name); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Address</div>
            <div id="to_address" class="fval editable-block"><?php echo esc($company_address); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Phone</div>
            <div id="to_phone" class="fval editable-block"><?php echo esc($driver_number); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">Sender</div>
            <div id="sender_name" class="fval editable-block"><?php echo esc($bilty['sender_name'] ?: ''); ?></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-title">Vehicle / Driver</div>
        <div class="panel-body">
          <div class="field-line">
            <div class="flabel">Vehicle</div>
            <div id="vehicle_type" class="fval editable-block"><?php echo esc($vehicle_type); ?></div>
          </div>
          <div class="field-line">
            <div class="flabel">V No</div>
            <div id="vehicle_no" class="fval editable-block"><?php echo esc($vehicle_no); ?></div>
          </div>
            <div class="field-line">
              <div class="flabel">Driver</div>
              <div id="driver_name" class="fval editable-block"><?php echo esc($driver_name); ?></div>
            </div>
          <div class="field-line">
            <div class="flabel">Route</div>
            <div id="route_text" class="fval editable-block"><?php echo esc($route_str); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="items-table-wrap">
      <table class="items-table">
        <thead>
          <tr>
            <th style="width:18%;">Qty</th>
            <th style="width:22%;">KM</th>
            <th style="width:25%;">Rate</th>
            <th style="width:35%;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="center amount-cell" data-field="qty" data-type="int"><?php echo $qty; ?></td>
            <td class="center amount-cell" data-field="km" data-type="int"><?php echo $km; ?></td>

            <?php if ($isFixed): ?>
              <!-- mark fixed: show label and add data-attr so JS knows -->
              <td class="center amount-cell" data-field="rate" data-type="float" data-rate-fixed="1">Fixed</td>
            <?php else: ?>
              <td class="center amount-cell" data-field="rate" data-type="float"><?php echo n2($rate); ?></td>
            <?php endif; ?>

            <td class="center amount-cell" data-field="amount" data-type="float" data-computed="1"><?php echo n2($amount); ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <table class="amount-summary">
      <tr>
        <td class="label">Total Amount</td>
        <td class="val"><span id="total_amount"><?php echo n2($amount); ?></span></td>
      </tr>
    </table>

    <div class="signature-block">
      <div class="signature-area">
        Transporter Signature
      </div>
    </div>
  </div>
</div>

<div id="progressOverlay" class="progress-overlay">
  <div style="text-align:center;">
    <div style="font-weight:600;">Generating PDF…</div>
    <div class="progress-wrap"><div id="progressBar" class="progress-bar"></div></div>
    <div id="progressText" style="font-size:11px; opacity:.9; margin-top:6px;">Please wait…</div>
  </div>
</div>

<script>
(function(){
  const editableBlocks = [
    'from_name','from_business','from_phone','from_address',
    'bilty_date','to_name','to_address','to_phone',
    'sender_name','vehicle_type','vehicle_no','driver_name','route_text','notes_box'
  ];

  // Initialize manual override based on server-side fixed state
  let manualAmountOverride = <?php echo $isFixed ? 'true' : 'false'; ?>;
  let editMode = false;
  const toggleBtn   = document.getElementById('toggleEdit');
  const applyBtn    = document.getElementById('applyBtn');
  const printBtn    = document.getElementById('printBtn');
  const savePdfBtn  = document.getElementById('savePdfBtn');
  const resetBtn    = document.getElementById('resetBtn');
  const progressOverlay = document.getElementById('progressOverlay');
  const progressBar     = document.getElementById('progressBar');
  const progressText    = document.getElementById('progressText');

  function asNumber(val, type){
    let v = (''+val).replace(/,/g,'').trim();
    if(v==='') return 0;
    let n = type==='int'? parseInt(v,10): parseFloat(v);
    return isNaN(n)?0:n;
  }

  function format2(n){
    return (Math.round(n*100)/100).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }

  function enterEdit(){
    if(editMode) return;
    editMode = true;
    toggleBtn.textContent = 'Exit Edit';
    // Turn text nodes into inputs
    editableBlocks.forEach(id=>{
      const el = document.getElementById(id);
      if(!el) return;
      if(el.querySelector('input,textarea')) return;
      let text = el.innerText.replace(/\u00A0/g,' ').trim();
      // For multiline notes: use textarea
      if(id==='notes_box'){
        const ta = document.createElement('textarea');
        ta.className='inline-input';
        ta.style.minHeight='70px';
        ta.value=text;
        el.innerHTML='';
        el.appendChild(ta);
      } else {
        const inp = document.createElement('input');
        inp.type='text';
        inp.className='inline-input';
        inp.value=text;
        el.innerHTML='';
        el.appendChild(inp);
      }
    });
    // Amount table cells -> inputs
    document.querySelectorAll('.amount-cell').forEach(cell=>{
      if(cell.querySelector('input')) return;
      const txt = cell.textContent.trim();
      const inp = document.createElement('input');
      inp.type = 'text';
      inp.value = txt;
      inp.className='inline-input';
      inp.style.textAlign='center';
      cell.innerHTML = '';
      cell.appendChild(inp);

      if(cell.dataset.field==='amount'){
        // manual edit of amount -> enforce manual override
        inp.addEventListener('input',()=> { manualAmountOverride = true; recompute(); });
      } else if(cell.dataset.field==='rate' || cell.dataset.field==='km'){
        // for rate/km: if rate becomes numeric, re-enable auto compute
        inp.addEventListener('input',()=> {
          if(cell.dataset.field==='rate'){
            const v = inp.value.replace(/,/g,'').trim();
            const num = parseFloat(v);
            if (!isNaN(num)) {
              // user supplied numeric rate -> allow auto compute
              manualAmountOverride = false;
            } else {
              // non-numeric (e.g. still "Fixed") -> keep manual override
              manualAmountOverride = true;
            }
          } else {
            // km changed -> if rate is numeric, allow auto compute
            const rateCell = document.querySelector('.amount-cell[data-field="rate"]');
            const rateInp = rateCell?.querySelector('input');
            const rateVal = rateInp ? rateInp.value : (rateCell ? rateCell.textContent : '');
            const rnum = parseFloat((''+rateVal).replace(/,/g,'').trim());
            if(!isNaN(rnum)) manualAmountOverride = false;
          }
          recompute();
        });
      }
    });
  }

  function exitEdit(){
    if(!editMode) return;
    // Commit values
    editableBlocks.forEach(id=>{
      const el = document.getElementById(id);
      if(!el) return;
      const input = el.querySelector('input,textarea');
      if(input){
        el.textContent = input.value.trim();
      }
    });
    document.querySelectorAll('.amount-cell').forEach(cell=>{
      const inp = cell.querySelector('input');
      if(!inp) return;
      cell.textContent = inp.value.trim();
    });
    editMode = false;
    toggleBtn.textContent = 'Edit';
    recompute();
  }

  function toggleEdit(){
    editMode?exitEdit():enterEdit();
  }

  function applyChanges(){
    if(editMode) exitEdit();
    recompute();
  }

  function recompute(){
    // Get numeric fields
    const getFieldVal = (f)=>{
      const cell = document.querySelector(`.amount-cell[data-field="${f}"]`);
      if(!cell) return 0;
      let v;
      if(editMode){
        const inp = cell.querySelector('input');
        v = inp ? inp.value : cell.textContent;
      } else {
        v = cell.textContent;
      }
      return asNumber(v, cell.dataset.type);
    };

    let km = getFieldVal('km');
    let rate = getFieldVal('rate');
    let amountCell = document.querySelector('.amount-cell[data-field="amount"]');

    if(!manualAmountOverride){
      const computedAmount = km * rate; // business rule: KM * Rate
      if(editMode && amountCell){
        let inp = amountCell.querySelector('input');
        if(inp) inp.value = format2(computedAmount);
      } else if(amountCell){
        amountCell.textContent = format2(computedAmount);
      }
    } else {
      // keep user-entered amount
    }

    // Update total
    const amountFinal = asNumber(
      editMode
        ? (amountCell?.querySelector('input')?.value || amountCell?.textContent || '0')
        : (amountCell?.textContent || '0'),
      'float'
    );

    const totalEl = document.getElementById('total_amount');
    if(totalEl){
      totalEl.textContent = format2(amountFinal);
    }
  }

  async function waitForResources(timeoutMs=5000){
    if(document.fonts && document.fonts.ready){
      try { await Promise.race([document.fonts.ready,new Promise(r=>setTimeout(r,timeoutMs))]); } catch(e){}
    }
    const imgs = Array.from(document.images);
    await Promise.all(imgs.map(img=>img.complete?Promise.resolve():new Promise(res=>{
      const t=setTimeout(res,timeoutMs);
      img.addEventListener('load',()=>{clearTimeout(t);res();});
      img.addEventListener('error',()=>{clearTimeout(t);res();});
    })));
  }

  async function savePdf(){
    if(editMode) exitEdit();
    if(typeof html2pdf === 'undefined'){
      alert('PDF library not loaded.'); return;
    }
    const biltyNo = "<?php echo esc($bilty['bilty_no']); ?>".replace(/[^A-Za-z0-9_\-]/g,'_');
    savePdfBtn.disabled=true;
    const prevTxt = savePdfBtn.textContent;
    savePdfBtn.textContent='Generating...';

    progressOverlay.style.display='flex';
    progressBar.style.width='10%';
    progressText.textContent='Preparing...';

    try{
      await waitForResources();
      progressBar.style.width='30%';
      progressText.textContent='Rendering...';

      const element = document.getElementById('biltyRoot');
      const opt = {
        margin:[0,0,0,0],
        filename:'Bilty-'+biltyNo+'.pdf',
        image:{ type:'jpeg', quality:0.95 },
        html2canvas:{ scale:2, useCORS:true, allowTaint:false, backgroundColor:'#FFFFFF' },
        jsPDF:{ unit:'mm', format:'a5', orientation:'portrait' },
        pagebreak:{ mode:['css','legacy'] }
      };
      const worker = html2pdf().set(opt).from(element).toPdf();
      progressBar.style.width='60%';
      progressText.textContent='Composing...';
      const pdf = await worker.get('pdf');
      progressBar.style.width='80%';
      progressText.textContent='Encoding...';
      const blob = pdf.output('blob');

      const base64Data = await new Promise((resolve,reject)=>{
        const fr=new FileReader();
        fr.onload=()=>resolve(fr.result);
        fr.onerror=()=>reject(new Error('Blob read failed'));
        fr.readAsDataURL(blob);
      });

      progressBar.style.width='90%';
      progressText.textContent='Uploading...';

      const fd = new FormData();
      fd.append('bilty_no', biltyNo);
      fd.append('pdf_data', base64Data);

      const resp = await fetch('save_bilty_pdf.php',{method:'POST',body:fd});
      const data = await resp.json();
      if(!data.ok) throw new Error(data.error||'Upload failed');

      progressBar.style.width='100%';
      progressText.textContent='Saved!';
      alert('PDF saved on server: '+data.file);

      let linkBox = document.getElementById('serverPdfLinkBox');
      if(!linkBox){
        linkBox = document.createElement('div');
        linkBox.id='serverPdfLinkBox';
        linkBox.style.marginLeft='12px';
        linkBox.style.fontSize='13px';
        document.getElementById('controlBar')?.appendChild(linkBox);
      }
      linkBox.innerHTML='<a href="'+data.file+'" target="_blank" style="color:#2563eb;font-weight:600;text-decoration:underline;">Open Stored PDF</a>';

    }catch(e){
      console.error(e);
      alert('PDF save failed: '+(e?.message||e));
    }finally{
      setTimeout(()=>{ progressOverlay.style.display='none'; },600);
      savePdfBtn.disabled=false;
      savePdfBtn.textContent=prevTxt;
    }
  }

  function resetAll(){
    if(editMode) exitEdit();
    location.reload();
  }

  toggleBtn.addEventListener('click', toggleEdit);
  applyBtn.addEventListener('click', applyChanges);
  printBtn.addEventListener('click', () => {
    if(editMode) exitEdit();
    window.print();
  });
  savePdfBtn.addEventListener('click', savePdf);
  resetBtn.addEventListener('click', resetAll);

  // Auto print if ?auto=1
  (function(){
    const url = new URL(window.location.href);
    if(url.searchParams.get('auto') === '1'){
      setTimeout(()=>window.print(), 400);
    }
  })();

  // Initial compute
  recompute();
})();
</script>
</body>
</html>