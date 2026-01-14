<style>
    .form-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .form-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        margin: 0 0 1.5rem 0;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .card-icon {
        width: 40px;
        height: 40px;
        background: #fff0f5;
        color: #97113a;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }
</style>

<div class="form-container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin: 0 0 0.5rem 0;">
                <i class="fa-solid fa-truck-fast" style="color: #97113a;"></i>
                Create New Bilty
            </h1>
            <p style="color: #6b7280; font-size: 0.95rem; margin: 0;">Fill in the details below to create a new bilty record</p>
        </div>
        <div>
            <a href="<?php echo site_url('bilty'); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-list"></i> View All
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle"></i>
            <?php echo $this->session->flashdata('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fa-solid fa-exclamation-circle"></i>
            <?php echo $this->session->flashdata('error'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (validation_errors()): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-exclamation-circle"></i>
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <?php echo form_open('bilty/save', array('id' => 'biltyForm')); ?>
        
        <!-- Basic Information -->
        <div class="form-card">
            <h2 class="card-title">
                <div class="card-icon"><i class="fa-solid fa-file-lines"></i></div>
                Basic Information
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Bilty Number <span class="text-danger">*</span></label>
                    <input type="text" name="bilty_no" class="form-control" value="<?php echo set_value('bilty_no', $auto_bilty_no); ?>" readonly required />
                    <small class="text-muted">Auto-generated bilty number</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" value="<?php echo set_value('date', date('Y-m-d')); ?>" required />
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Company <span class="text-danger">*</span></label>
                    <select name="company" class="form-select" required>
                        <option value="">— Select company —</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company->id; ?>" <?php echo set_select('company', $company->id); ?>>
                                <?php echo htmlspecialchars($company->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Vehicle Information -->
        <div class="form-card">
            <h2 class="card-title">
                <div class="card-icon"><i class="fa-solid fa-truck"></i></div>
                Vehicle Information
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Vehicle Ownership</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vehicle_owner" id="own" value="own" <?php echo set_radio('vehicle_owner', 'own', TRUE); ?>>
                            <label class="form-check-label" for="own">Own</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vehicle_owner" id="rental" value="rental" <?php echo set_radio('vehicle_owner', 'rental'); ?>>
                            <label class="form-check-label" for="rental">Rental</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Vehicle Number</label>
                    <input type="text" name="vehicle_no" class="form-control" value="<?php echo set_value('vehicle_no'); ?>" placeholder="e.g., ABC-1234" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Vehicle Type</label>
                    <input type="text" name="vehicle_type" class="form-control" value="<?php echo set_value('vehicle_type'); ?>" placeholder="e.g., Truck, Van" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Driver Name</label>
                    <input type="text" name="driver_name" class="form-control" value="<?php echo set_value('driver_name'); ?>" placeholder="Enter driver name" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Driver Phone</label>
                    <input type="tel" name="driver_number" class="form-control" value="<?php echo set_value('driver_number'); ?>" placeholder="+92 300 1234567" />
                </div>
            </div>
        </div>

        <!-- Shipment Details -->
        <div class="form-card">
            <h2 class="card-title">
                <div class="card-icon"><i class="fa-solid fa-box"></i></div>
                Shipment Details
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Sender Name</label>
                    <input type="text" name="sender_name" class="form-control" value="<?php echo set_value('sender_name'); ?>" placeholder="Enter sender name" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">From City</label>
                    <input type="text" name="from_city" class="form-control" value="<?php echo set_value('from_city'); ?>" placeholder="Origin city" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">To City</label>
                    <input type="text" name="to_city" class="form-control" value="<?php echo set_value('to_city'); ?>" placeholder="Destination city" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="qty" class="form-control" value="<?php echo set_value('qty', 0); ?>" placeholder="0" min="0" />
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Details / Notes</label>
                    <textarea name="details" class="form-control" rows="3" placeholder="Additional shipment details..."><?php echo set_value('details'); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Financial Details -->
        <div class="form-card">
            <h2 class="card-title">
                <div class="card-icon"><i class="fa-solid fa-dollar-sign"></i></div>
                Financial Details
            </h2>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Distance (KM)</label>
                    <input type="number" name="km" id="km" class="form-control" value="<?php echo set_value('km', 0); ?>" placeholder="0" min="0" step="0.01" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rate (per KM)</label>
                    <input type="number" name="rate" id="rate" class="form-control" value="<?php echo set_value('rate', 0); ?>" placeholder="0.00" min="0" step="0.01" />
                </div>
                
                <div class="form-group">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="fixed" id="fixed" value="1" <?php echo set_checkbox('fixed', '1'); ?>>
                        <label class="form-check-label" for="fixed">Fixed Amount</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" name="amount" id="amount" class="form-control" value="<?php echo set_value('amount', 0); ?>" placeholder="0.00" min="0" step="0.01" required />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Advance</label>
                    <input type="number" name="advance" id="advance" class="form-control" value="<?php echo set_value('advance', 0); ?>" placeholder="0.00" min="0" step="0.01" />
                </div>
                
                <div class="form-group">
                    <label class="form-label">Balance</label>
                    <input type="number" name="balance" id="balance" class="form-control" value="<?php echo set_value('balance', 0); ?>" placeholder="0.00" readonly />
                    <small class="text-muted">Auto-calculated (Amount - Advance)</small>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex gap-2 justify-content-end">
            <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-secondary">
                <i class="fa-solid fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i> Create Bilty
            </button>
        </div>
        
    <?php echo form_close(); ?>
</div>

<script>
// Financial calculations
document.addEventListener('DOMContentLoaded', function() {
    const kmInput = document.getElementById('km');
    const rateInput = document.getElementById('rate');
    const fixedCheckbox = document.getElementById('fixed');
    const amountInput = document.getElementById('amount');
    const advanceInput = document.getElementById('advance');
    const balanceInput = document.getElementById('balance');
    
    function calculateFinancials() {
        const km = parseFloat(kmInput.value || 0);
        const rate = parseFloat(rateInput.value || 0);
        const isFixed = fixedCheckbox.checked;
        const advance = parseFloat(advanceInput.value || 0);
        
        let amount = parseFloat(amountInput.value || 0);
        
        if (!isFixed) {
            amount = km * rate;
            amountInput.value = amount.toFixed(2);
        }
        
        const balance = amount - advance;
        balanceInput.value = balance.toFixed(2);
    }
    
    kmInput.addEventListener('input', calculateFinancials);
    rateInput.addEventListener('input', calculateFinancials);
    advanceInput.addEventListener('input', calculateFinancials);
    amountInput.addEventListener('input', calculateFinancials);
    fixedCheckbox.addEventListener('change', function() {
        if (this.checked) {
            amountInput.removeAttribute('readonly');
            amountInput.focus();
        } else {
            calculateFinancials();
        }
    });
    
    calculateFinancials();
});
</script>
