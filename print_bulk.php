<?php
require_once 'config.php';

/*
  Bulk Bill Printing (Finalizable) — PDF-Optimized
  Updates:
    - Added company address support: now selecting cp.address AS company_address
    - "Billing Add" field now shows company_address (falls back to to_city if empty)
    - Improved driver phone extraction regex (matches "Driver number:" or variations with spacing)
    - All other logic retained
*/

$ids_raw = trim($_GET['ids'] ?? '');
if ($ids_raw === '') { echo "No bilties selected."; exit; }

$ids_clean = preg_replace('/[^0-9,]/', '', $ids_raw);
$ids_arr = array_values(array_filter(array_map('intval', explode(',', $ids_clean)), fn($v) => $v > 0));
if (empty($ids_arr)) { echo "No valid bilty ids provided."; exit; }

$in_list = implode(',', $ids_arr);
$sql = "SELECT c.*,
               cp.name    AS company_name,
               cp.address AS company_address
        FROM consignments c
        JOIN companies cp ON cp.id = c.company_id
        WHERE c.id IN ($in_list)
        ORDER BY c.date ASC, c.id ASC";
$res = $conn->query($sql);
$bilties = [];
if ($res) { while ($row = $res->fetch_assoc()) $bilties[] = $row; $res->free(); }
if (empty($bilties)) { echo "No bilties found for selected ids."; exit; }

$bill_number = "DRAFT";

$bill_from_name     = "Bahar Ali";
$bill_from_business = "Mini Goods Transport Hyderabad";
$bill_from_phone    = "0306-3591311, 0302-3928417";
$bill_from_address  = "Hala Naka Road Near Isra University Hyderabad";
$account_name       = "Bahar Ali";
$bank_account_no    = "8511008167530";
$iban               = "PK32ALFH0851001008167530";

function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtAmount($v){ return number_format((float)$v, 2); }
function fmtNumber($v){ return is_numeric($v) ? (int)$v : $v; }

$gross = 0.0;
foreach ($bilties as $b) $gross += (float)($b['amount'] ?? 0);
$tax_default_percent = 4.0;
$tax = $gross * ($tax_default_percent / 100.0);
$net = $gross - $tax;

// Show human-friendly date on screen; server-side normalized version is handled on finalize
$date_now = date('d-M-Y');

$rows_per_page = 20;
$total_pages   = ceil(count($bilties) / $rows_per_page);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Bill <?php echo esc($bill_number); ?> — Selected Bilties</title>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<script src="pdf.js"></script>
<style>
@page { size: A4; margin: 12mm; }

:root{
  --page-width: calc(210mm - 24mm);
  --page-padding: 8mm;
  --font-base: "Arial","Helvetica",sans-serif;
  --font-size: 11px;
  --hairline: 0.6px;
}

html,body { margin:0; padding:0; background:#f1f5f9; font-family:var(--font-base); font-size:var(--font-size); color:#000; }
* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }

.page {
  width: var(--page-width);
  margin: 0 auto;
  padding: var(--page-padding);
  background: #fff;
  position: relative;
  box-sizing: border-box;
  page-break-after: always;
  break-after: page;
}
.page:last-child { page-break-after:auto; break-after:auto; margin-bottom:0; }

@media print {
  body { background:#fff; }
  .no-print { display:none !important; }
  .page { box-shadow:none; margin:0; width:auto; padding:12mm; }
}

.header-row { display:flex; justify-content:space-between; align-items:flex-start; border-bottom: var(--hairline) solid #000; padding-bottom:3px; margin-bottom:4px; }
.brand-name { font-size:26px; font-weight:700; font-style:italic; letter-spacing:.5px; }
.brand-business { font-size:18px; font-weight:600; text-align:right; max-width:60%; line-height:1.15; }
.info-bar { display:flex; font-size:10.5px; border-bottom:var(--hairline) solid #000; padding:3px 0 4px 0; margin-bottom:6px; }
.info-bar .block-strong { font-weight:700; margin-right:10px; text-transform:uppercase; }
.info-bar .label-strong { font-weight:700; margin-right:4px; }
.bill-title { background:#d1d5db; text-align:center; font-weight:700; padding:4px 0; margin-bottom:6px; font-size:13px; letter-spacing:.5px; }
.flex-row { display:flex; gap:6mm; margin-bottom:8px; }
.panel { flex:1; border:var(--hairline) solid #000; display:flex; flex-direction:column; }
.panel-title { border-bottom:var(--hairline) solid #000; text-align:center; font-weight:700; font-size:11px; padding:3px 2px; background:#f3f4f6; }
.panel-body { padding:6px 6px 4px 6px; font-size:10.5px; }
.row-line { display:flex; margin-bottom:3px; }
.row-line-label { width:58px; font-weight:700; flex-shrink:0; }

.table-wrap { width:100%; overflow:hidden; }
.bill-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:10px; }
.bill-table th, .bill-table td { border:var(--hairline) solid #000; background:#fff; padding:2px 3px; vertical-align:middle; word-wrap:break-word; overflow-wrap:anywhere; }
.bill-table th { background:#e5e7eb; font-weight:700; text-align:center; }
.bill-table colgroup col.col-sno   { width:7%; }
.bill-table colgroup col.col-bilty { width:11%; }
.bill-table colgroup col.col-date  { width:12%; }
.bill-table colgroup col.col-route { width:25%; }
.bill-table colgroup col.col-veh   { width:11%; }
.bill-table colgroup col.col-vno   { width:11%; }
.bill-table colgroup col.col-km    { width:6%; }
.bill-table colgroup col.col-rate  { width:7%; }
.bill-table colgroup col.col-amt   { width:10%; }
.bill-table .center { text-align:center; }
.bill-table .num { text-align:right; font-variant-numeric:tabular-nums; }
.bill-table tr.empty td { border-bottom:0.6px dotted #999; height:18px; font-size:0; line-height:0; }

.totals { margin-top:10px; width:100%; font-size:11px; }
.totals-table { width:100%; border-collapse:collapse; }
.totals-table td { padding:2px 3px; }
.totals-label { font-weight:700; }
.currency-row { display:flex; justify-content:flex-end; gap:4px; font-variant-numeric:tabular-nums; }
.currency-code { font-weight:700; min-width:30px; text-align:left; }

.account-section { margin-top:14px; font-size:11px; }
.acc-row { display:flex; margin-bottom:3px; }
.acc-label { width:110px; font-weight:700; flex-shrink:0; }

.signature-block { margin-top:28mm; text-align:center; }
.signature-line { width:55mm; margin:0 auto; border-bottom:var(--hairline) solid #000; height:14mm; }
.signature-text { margin-top:4px; font-weight:700; font-size:10.5px; }

.inline-input { font-size:10px; padding:2px 4px; border:0.6px solid #555; border-radius:3px; width:100%; background:#fff; box-sizing:border-box; }
.amount-cell.editing input { text-align:right; width:100%; }
.tax-edit-input { width:48px; text-align:right; }

.badge-draft { background:#fef3c7; color:#92400e; }
.badge-final { background:#dcfce7; color:#166534; }
button[disabled] { opacity:.5; cursor:not-allowed; }

.progress-overlay {
  position:fixed; inset:0; background:rgba(17,24,39,.55);
  display:none; align-items:center; justify-content:center;
  z-index:2000; color:#fff; flex-direction:column; gap:12px; font-size:14px;
}
.progress-wrap { width:240px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.35); border-radius:6px; overflow:hidden; height:10px; }
.progress-bar { height:10px; width:0%; background:#10b981; transition:width .3s; }

.control-bar {
  --cb-font-size: 15px;
  --cb-badge-font: 13px;
  --cb-btn-font: 14px;
  --cb-btn-pad-y: 10px;
  --cb-btn-pad-x: 18px;
  --cb-gap: 10px;
  --cb-shadow: 0 2px 4px rgba(0,0,0,.08);
  font-size: var(--cb-font-size);
  display: flex;
  flex-wrap: wrap;
  gap: var(--cb-gap);
  align-items: center;
  justify-content: flex-end;
  padding: 14px 18px;
}
.control-bar .status-badge { font-size: var(--cb-badge-font); padding: 8px 16px; border-radius: 999px; box-shadow: var(--cb-shadow); }
.control-bar button, .control-bar a { font-size: var(--cb-btn-font); padding: var(--cb-btn-pad-y) var(--cb-btn-pad-x); line-height:1.1; font-weight:600; border-radius:8px; display:inline-flex; align-items:center; gap:6px; transition: background .18s, box-shadow .18s, transform .12s; box-shadow: var(--cb-shadow); }
.control-bar .plain-btn { background:#fff; border:1px solid #d1d5db; color:#111827; }
.control-bar .primary-btn { background:#2563eb; color:#fff; border:1px solid #1d4ed8; }
.control-bar .success-btn { background:#059669; color:#fff; border:1px solid #047857; }
.control-bar .print-btn { background:#4f46e5; color:#fff; border:1px solid #4338ca; }
.control-bar .pdf-btn { background:#374151; color:#fff; border:1px solid #1f2937; }
.control-bar button[disabled] { filter: grayscale(.5); cursor:not-allowed; opacity:0.55; box-shadow:none; }
@media (max-width:640px) {
  .control-bar { justify-content:flex-start; font-size:14px; }
  .control-bar button, .control-bar a { flex:1 1 auto; }
}
</style>
</head>
<body>

<!-- Control Bar -->
<div class="no-print control-bar bg-white border-b shadow">
  <span id="billStatusBadge" class="status-badge badge-draft">DRAFT</span>
  <button id="toggleEdit" class="plain-btn" type="button">Edit</button>
  <button id="applyBtn" class="primary-btn" type="button">Apply (No Save)</button>
  <button id="finalizeBtn" class="success-btn" type="button">Generate Bill No</button>
  <button id="printBtn" class="print-btn" type="button" disabled>Print</button>
  <button id="savePdfBtn" class="pdf-btn" type="button" disabled>Save PDF</button>
  <button id="resetBtn" class="plain-btn" type="button">Reset</button>
  <a href="view_bilty.php" class="plain-btn">Back</a>
</div>

<div id="billPages">
<?php
for ($page = 0; $page < $total_pages; $page++):
  $start_idx = $page * $rows_per_page;
  $end_idx = min(($page + 1) * $rows_per_page, count($bilties));
  $page_bilties = array_slice($bilties, $start_idx, $end_idx - $start_idx);

  $page_gross = 0;
  foreach ($page_bilties as $b) $page_gross += (float)($b['amount'] ?? 0);
  $page_tax = $page_gross * ($tax_default_percent / 100.0);
  $page_net = $page_gross - $page_tax;
?>
  <div class="page">
    <div class="header-row">
      <div id="display_bill_from_name" class="brand-name"><?php echo esc($bill_from_name); ?></div>
      <div id="display_bill_from_business" class="brand-business"><?php echo esc($bill_from_business); ?></div>
    </div>

    <div class="info-bar">
      <div class="block-strong">PRO:</div>
      <div id="display_pro" class="block-strong" style="text-transform:none;"><?php echo esc($bill_from_name); ?></div>
      <div style="flex:1;">
        <span class="label-strong">Cell:</span>
        <span id="display_phone" style="font-weight:600;"><?php echo esc($bill_from_phone); ?></span>
        <span id="display_address" style="margin-left:6px;"><?php echo esc($bill_from_address); ?></span>
      </div>
    </div>

    <div class="bill-title">Bill</div>

    <div class="flex-row">
      <div class="panel">
        <div class="panel-title">Bill From</div>
        <div class="panel-body">
          <div class="row-line">
            <div class="row-line-label">Name</div>
            <div id="bf_name"><?php echo esc($bill_from_name . ' Mini Goods Transport'); ?></div>
          </div>
          <div class="row-line">
            <div class="row-line-label">Bill No.</div>
            <div id="bf_bilty_no" data-status="draft"><?php echo esc($bill_number); ?></div>
          </div>
          <div class="row-line">
            <div class="row-line-label">Bill Date</div>
            <div id="bf_bill_date"><?php echo esc($date_now); ?></div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-title">Bill To</div>
        <div class="panel-body">
          <?php $first = $bilties[0]; ?>
          <div class="row-line">
            <div class="row-line-label">Name</div>
            <div id="bt_name"><?php echo esc($first['company_name'] ?? ''); ?></div>
          </div>
          <div class="row-line">
            <div class="row-line-label">Address</div>
            <div id="bt_addr">
             <?php
  $billingAddress = trim($first['company_address'] ?? '');
  echo esc($billingAddress);
?>

            </div>
          </div>
          <div class="row-line">
            <div class="row-line-label">Phone #</div>
            <div id="bt_phone">
              <?php
                $phone = '';
                // Adjusted regex to match "Driver number:" pattern used in added details
                if (!empty($first['details']) && preg_match('/Driver\s*number:\s*([0-9+\-\s()]+)/i', $first['details'], $m2)) {
                  $phone = trim($m2[1]);
                }
                echo esc($phone);
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-wrap">
      <table class="bill-table">
        <colgroup>
          <col class="col-sno">
          <col class="col-bilty">
          <col class="col-date">
          <col class="col-route">
          <col class="col-veh">
          <col class="col-vno">
          <col class="col-km">
          <col class="col-rate">
          <col class="col-amt">
        </colgroup>
        <thead>
          <tr>
            <th>S.No</th>
            <th>Bilty No</th>
            <th>Date</th>
            <th>Route/Cities</th>
            <th>Vehicle</th>
            <th>V-No</th>
            <th>KM</th>
            <th>Rate</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody id="items_tbody_<?php echo $page; ?>">
        <?php foreach ($page_bilties as $i => $b):
            $row_num      = $start_idx + $i + 1;
            $dateDisplay  = !empty($b['date']) ? date('d-M-y', strtotime($b['date'])) : '';
            $amount_val   = (float)($b['amount'] ?? 0);
            $vehicle_type = $b['vehicle_type'] ?? 'Suzuki';
        ?>
          <tr data-id="<?php echo intval($b['id']); ?>">
            <td class="center"><?php echo $row_num; ?></td>
            <td class="center"><?php echo esc($b['bilty_no']); ?></td>
            <td class="center"><?php echo esc($dateDisplay); ?></td>
            <td><?php echo esc(trim(($b['from_city'] ?? '') . (($b['to_city'] ?? '') ? ' → ' . $b['to_city'] : ''))); ?></td>
            <td class="center"><?php echo esc($vehicle_type); ?></td>
            <td class="center"><?php echo esc($b['vehicle_no'] ?? ''); ?></td>
            <td class="num"><?php echo fmtNumber($b['km'] ?? 0); ?></td>
            <td class="num"><?php echo fmtAmount($b['rate'] ?? 0); ?></td>
            <td class="num amount-cell" data-id="<?php echo intval($b['id']); ?>" data-raw="<?php echo $amount_val; ?>" data-page="<?php echo $page; ?>" data-idx="<?php echo $i; ?>">
              <?php echo fmtAmount($amount_val); ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php
          $current = count($page_bilties);
          for ($r = $current; $r < $rows_per_page; $r++):
        ?>
          <tr class="empty">
            <td class="center"><?php echo $start_idx + $r + 1; ?></td>
            <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
          </tr>
        <?php endfor; ?>
        </tbody>
      </table>
    </div>

    <div class="totals">
      <table class="totals-table">
        <tr>
          <td class="totals-label">Gross Amount</td>
          <td>
            <div class="currency-row">
              <span class="currency-code">PKR</span>
              <span id="display_gross_<?php echo $page; ?>"><?php echo fmtAmount($page_gross); ?></span>
            </div>
          </td>
        </tr>
        <tr>
          <td class="totals-label">Tax <span id="tax_percent_label_<?php echo $page; ?>" class="tax-percent-label">(<?php echo fmtAmount($tax_default_percent); ?>%)</span></td>
          <td>
            <div class="currency-row">
              <span class="currency-code">PKR</span>
              <span id="display_tax_<?php echo $page; ?>"><?php echo fmtAmount($page_tax); ?></span>
            </div>
          </td>
        </tr>
        <tr>
          <td class="totals-label">Net Amount</td>
          <td>
            <div class="currency-row">
              <span class="currency-code">PKR</span>
              <span id="display_net_<?php echo $page; ?>"><?php echo fmtAmount($page_net); ?></span>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <div class="account-section">
      <div class="acc-row">
        <div class="acc-label">Account Name</div>
        <div id="acc_name" style="font-weight:600;"><?php echo esc($account_name); ?></div>
      </div>
      <div class="acc-row">
        <div class="acc-label">Bank Account No</div>
        <div id="acc_bank" style="font-weight:600;"><?php echo esc($bank_account_no); ?></div>
      </div>
      <div class="acc-row">
        <div class="acc-label">IBAN</div>
        <div id="acc_iban" style="font-weight:600;"><?php echo esc($iban); ?></div>
      </div>
    </div>

    <div class="signature-block">
      <div class="signature-line"></div>
      <div class="signature-text">Sign And Stamp</div>
    </div>

    <?php if ($total_pages > 1): ?>
      <div style="position:absolute; bottom:6mm; left:0; right:0; text-align:center; font-size:9.5px; font-weight:600;">
        Page <?php echo ($page + 1); ?> of <?php echo $total_pages; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endfor; ?>
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
  function parseNumber(v){ if(v==null)return 0; v=String(v).replace(/,/g,'').replace(/[^\d.\-]/g,'').trim(); return v===''?0:parseFloat(v); }
  function formatNumber(v){ return Number(v).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); }
  function parsePercentFromLabel(text){ const m=String(text).match(/([0-9]+(?:\.[0-9]+)?)/); return m?parseFloat(m[1]):0; }
  function textContent(id){ const el=document.getElementById(id); return el?el.textContent.trim():''; }

  let editMode=false, finalized=false;
  let currentTaxPercent=parsePercentFromLabel(document.querySelector('.tax-percent-label')?.textContent||'4.00');
  const totalPages=<?php echo $total_pages; ?>;
  const idsCsv="<?php echo implode(',', $ids_arr); ?>";

  const toggleEditBtn=document.getElementById('toggleEdit');
  const applyBtn=document.getElementById('applyBtn');
  const finalizeBtn=document.getElementById('finalizeBtn');
  const resetBtn=document.getElementById('resetBtn');
  const printBtn=document.getElementById('printBtn');
  const savePdfBtn=document.getElementById('savePdfBtn');
  const statusBadge=document.getElementById('billStatusBadge');
  const billNoEl=document.getElementById('bf_bilty_no');

  const progressOverlay=document.getElementById('progressOverlay');
  const progressBar=document.getElementById('progressBar');
  const progressText=document.getElementById('progressText');

  const editableFields=[
    'display_bill_from_name','display_bill_from_business','display_pro','display_phone',
    'display_address','bf_name','bf_bill_date','bt_name','bt_addr','bt_phone',
    'acc_name','acc_bank','acc_iban'
  ];

  function enterEdit(){
    if(finalized){ alert('Bill already finalized.'); return; }
    if(editMode) return;
    editMode=true;
    toggleEditBtn.textContent='Exit Edit';
    editableFields.forEach(id=>{
      const el=document.getElementById(id);
      if(!el || el.querySelector('input')) return;
      const val=el.textContent.trim();
      const inp=document.createElement('input');
      inp.type='text';
      inp.value=val;
      inp.className='inline-input';
      el.textContent='';
      el.appendChild(inp);
    });
    document.querySelectorAll('.amount-cell').forEach(td=>{
      td.classList.add('editing');
      const raw=parseNumber(td.dataset.raw||td.textContent);
      td.innerHTML='<input class="inline-input" style="text-align:right;" value="'+formatNumber(raw)+'">';
    });
    document.querySelectorAll('.tax-percent-label').forEach((el,idx)=>{
      el.innerHTML='(<input id="tax_edit_'+idx+'" type="number" step="0.01" min="0" max="100" value="'+currentTaxPercent+'" class="inline-input" style="width:48px;text-align:right;">%)';
    });
  }

  function exitEdit(){
    if(!editMode) return;
    const taxInput=document.querySelector('[id^="tax_edit_"]');
    if(taxInput){ let t=parseNumber(taxInput.value); currentTaxPercent=Math.min(100,Math.max(0,t)); }
    editableFields.forEach(id=>{
      const el=document.getElementById(id);
      if(!el) return;
      const inp=el.querySelector('input');
      if(inp) el.textContent=inp.value.trim();
    });
    document.querySelectorAll('.amount-cell').forEach(td=>{
      const inp=td.querySelector('input');
      if(inp){
        const val=parseNumber(inp.value);
        td.dataset.raw=val;
        td.textContent=formatNumber(val);
      }
      td.classList.remove('editing');
    });
    document.querySelectorAll('.tax-percent-label').forEach(el=>{
      el.textContent='('+formatNumber(currentTaxPercent)+'%)';
    });
    editMode=false;
    toggleEditBtn.textContent='Edit';
    recomputeTotals();
  }

  function toggleEdit(){ editMode?exitEdit():enterEdit(); }

  function recomputeTotals(){
    for(let p=0;p<totalPages;p++){
      const cells=document.querySelectorAll('.amount-cell[data-page="'+p+'"]');
      let gross=0;
      cells.forEach(td=>{
        const v=parseNumber(td.dataset.raw||td.textContent);
        if(!isNaN(v)) gross+=v;
      });
      const tax=gross*(currentTaxPercent/100);
      const net=gross-tax;
      const gEl=document.getElementById('display_gross_'+p);
      const tEl=document.getElementById('display_tax_'+p);
      const nEl=document.getElementById('display_net_'+p);
      if(gEl) gEl.textContent=formatNumber(gross);
      if(tEl) tEl.textContent=formatNumber(tax);
      if(nEl) nEl.textContent=formatNumber(net);
    }
  }

  function applyChanges(){ editMode?exitEdit():recomputeTotals(); }

  function gatherPayload(){
    const overrides={};
    document.querySelectorAll('.amount-cell').forEach(td=>{
      const id=td.getAttribute('data-id');
      const val=parseNumber(td.dataset.raw||td.textContent);
      if(id) overrides[id]=val;
    });
    const fd=new FormData();
    fd.append('ids',idsCsv);
    fd.append('amount_overrides',JSON.stringify(overrides));
    fd.append('tax_percent',currentTaxPercent);
    fd.append('bill_from_name',textContent('display_bill_from_name'));
    fd.append('bill_from_business',textContent('display_bill_from_business'));
    fd.append('pro_name',textContent('display_pro'));
    fd.append('bill_from_phone',textContent('display_phone'));
    fd.append('bill_from_address',textContent('display_address'));
    fd.append('bill_to_name',textContent('bt_name'));
    fd.append('bill_to_address',textContent('bt_addr'));
    fd.append('bill_to_phone',textContent('bt_phone'));
    fd.append('account_name',textContent('acc_name'));
    fd.append('account_bank',textContent('acc_bank'));
    fd.append('account_iban',textContent('acc_iban'));
    fd.append('bill_date',textContent('bf_bill_date'));
    return fd;
  }

  async function finalizeBill(){
    if(finalized) return;
    if(editMode) exitEdit();
    finalizeBtn.disabled=true;
    finalizeBtn.textContent='Generating...';
    try{
      const resp=await fetch('finalize_bill.php',{method:'POST',body:gatherPayload()});
      const data=await resp.json();
      if(!data.ok) throw new Error(data.error||'Failed to generate');
      billNoEl.textContent=data.bill_no;
      billNoEl.dataset.status='final';
      finalized=true;
      statusBadge.textContent='FINAL';
      statusBadge.classList.remove('badge-draft');
      statusBadge.classList.add('badge-final');
      toggleEditBtn.disabled=true;
      applyBtn.disabled=true;
      finalizeBtn.textContent='Bill Generated';
      finalizeBtn.classList.add('opacity-70','cursor-not-allowed');
      printBtn.disabled=false;
      savePdfBtn.disabled=false;
    }catch(e){
      alert(e.message);
      finalizeBtn.disabled=false;
      finalizeBtn.textContent='Generate Bill No';
    }
  }

  function printBill(){
    if(!finalized){ alert('Generate bill number first.'); return; }
    window.print();
  }

  async function waitForResources(timeoutMs = 7000) {
    if (document.fonts && document.fonts.ready) {
      try { await Promise.race([document.fonts.ready, new Promise(r=>setTimeout(r, timeoutMs))]); } catch(e){}
    }
    const imgs = Array.from(document.images || []);
    await Promise.all(imgs.map(img => img.complete ? Promise.resolve() : new Promise((res) => {
      const t = setTimeout(res, timeoutMs);
      img.addEventListener('load', ()=>{ clearTimeout(t); res(); });
      img.addEventListener('error', ()=>{ clearTimeout(t); res(); });
    })));
  }

  async function savePdf(){
    if(!finalized){ alert('Generate bill number first.'); return; }
    if (typeof html2pdf === 'undefined') { alert('PDF export library did not load. Reload the page and try again.'); return; }

    const billNo = (billNoEl.textContent.trim() || 'Bill').replace(/[^A-Za-z0-9_\-]/g,'_');
    savePdfBtn.disabled = true;
    const originalBtnText = savePdfBtn.textContent;
    savePdfBtn.textContent = 'Generating...';
    progressOverlay.style.display = 'flex';
    progressBar.style.width = '10%';
    progressText.textContent = 'Preparing resources...';

    try {
      await waitForResources(7000);
      progressBar.style.width = '25%';
      progressText.textContent = 'Rendering PDF...';

      const element = document.getElementById('billPages');
      const opt = {
        margin: [0,0,0,0],
        filename: 'Bill-'+billNo+'.pdf',
        image: { type:'jpeg', quality:0.95 },
        html2canvas: { scale: 2.0, useCORS:true, allowTaint:false, backgroundColor:'#FFFFFF', letterRendering:true },
        jsPDF: { unit:'mm', format:'a4', orientation:'portrait' },
        pagebreak: { mode:['css','legacy'] }
      };

      const worker = html2pdf().set(opt).from(element).toPdf();
      progressBar.style.width = '50%';
      progressText.textContent = 'Composing PDF...';

      const pdf = await worker.get('pdf');
      progressBar.style.width = '75%';
      progressText.textContent = 'Encoding PDF...';

      const blob = pdf.output('blob');
      const base64Data = await new Promise((resolve, reject)=>{
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Failed to read PDF blob'));
        reader.readAsDataURL(blob);
      });

      progressBar.style.width = '85%';
      progressText.textContent = 'Uploading PDF...';

      const formData = new FormData();
      formData.append('bill_no', billNo);
      formData.append('pdf_data', base64Data);

      const resp = await fetch('save_pdf.php', { method:'POST', body: formData });
      const data = await resp.json();
      if(!data.ok) throw new Error(data.error || 'Upload failed');

      progressBar.style.width = '100%';
      progressText.textContent = 'Stored on server.';
      alert('PDF saved on server: ' + data.file);

      let linkBox = document.getElementById('serverPdfLinkBox');
      if(!linkBox){
        linkBox = document.createElement('div');
        linkBox.id = 'serverPdfLinkBox';
        linkBox.style.marginLeft = '12px';
        linkBox.style.fontSize = '13px';
        document.querySelector('.control-bar')?.appendChild(linkBox);
      }
      linkBox.innerHTML = '<a href="'+data.file+'" target="_blank" style="color:#2563eb;text-decoration:underline;font-weight:600;">Open Stored PDF</a>';

    } catch(e){
      console.error(e);
      alert('PDF save failed: ' + (e && e.message ? e.message : e));
    } finally {
      setTimeout(()=>{ progressOverlay.style.display='none'; }, 600);
      savePdfBtn.disabled = false;
      savePdfBtn.textContent = originalBtnText;
    }
  }

  function resetAll(){
    if(finalized){ alert('Bill is finalized; cannot reset this page.'); return; }
    location.reload();
  }

  toggleEditBtn.addEventListener('click',toggleEdit);
  applyBtn.addEventListener('click',applyChanges);
  finalizeBtn.addEventListener('click',finalizeBill);
  printBtn.addEventListener('click',printBill);
  savePdfBtn.addEventListener('click',savePdf);
  resetBtn.addEventListener('click',resetAll);

  recomputeTotals();
})();
</script>
</body>
</html>