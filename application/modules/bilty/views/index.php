<style>
    .table-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .filter-section {
        background: #f9fafb;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .badge {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 6px;
    }
</style>

<div class="container-fluid" style="max-width: 1400px;">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin: 0 0 0.5rem 0;">
                <i class="fa-solid fa-list" style="color: #97113a;"></i>
                All Bilties
            </h1>
            <p style="color: #6b7280; font-size: 0.95rem; margin: 0;">Browse and manage all bilty records</p>
        </div>
        <div>
            <a href="<?php echo site_url('bilty/add'); ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add New Bilty
            </a>
        </div>
    </div>

    <div class="table-container">
        <!-- Filter Section -->
        <div class="filter-section">
            <?php echo form_open('', array('method' => 'get', 'class' => 'd-flex gap-3 flex-wrap')); ?>
                <div style="flex: 1; min-width: 200px;">
                    <label class="form-label">Search</label>
                    <input 
                        type="text" 
                        name="q" 
                        class="form-control" 
                        placeholder="Search bilty number, company, driver..." 
                        value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                    />
                </div>
                
                <div style="flex: 1; min-width: 200px;">
                    <label class="form-label">Company</label>
                    <select name="company" class="form-select">
                        <option value="">All Companies</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company->id; ?>" <?php echo ($filters['company_id'] == $company->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($company->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-search"></i> Filter
                    </button>
                    <a href="<?php echo site_url('bilty'); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-refresh"></i> Reset
                    </a>
                </div>
            <?php echo form_close(); ?>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Bilty No</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Route</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bilties)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fa-solid fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No bilties found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bilties as $bilty): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($bilty->bilty_no); ?></strong></td>
                                <td><?php echo format_date($bilty->date); ?></td>
                                <td><?php echo htmlspecialchars($bilty->company_name); ?></td>
                                <td><?php echo htmlspecialchars($bilty->vehicle_no); ?></td>
                                <td><?php echo htmlspecialchars($bilty->driver_name); ?></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($bilty->from_city); ?>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <?php echo htmlspecialchars($bilty->to_city); ?>
                                    </small>
                                </td>
                                <td><strong><?php echo format_currency($bilty->amount); ?></strong></td>
                                <td><?php echo format_currency($bilty->balance); ?></td>
                                <td><?php echo bilty_status_badge($bilty->balance); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo site_url('bilty/view/' . $bilty->id); ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?php echo site_url('bilty/print/' . $bilty->id); ?>" class="btn btn-outline-secondary" title="Print" target="_blank">
                                            <i class="fa-solid fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($bilties)): ?>
            <div class="mt-3">
                <p class="text-muted">Total: <?php echo count($bilties); ?> bilties</p>
            </div>
        <?php endif; ?>
    </div>
</div>
