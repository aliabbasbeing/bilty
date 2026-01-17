<style>
    :root {
        --main-color: #97113a;
        --main-color-hover: #b31547;
        --main-color-light: #fff0f5;
    }
    
    .dashboard-hero {
        background: linear-gradient(135deg, #97113a 0%, #c91f4f 100%);
        border-radius: 20px;
        padding: 3rem 2rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(151, 17, 58, 0.3);
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
        transition: transform 0.2s;
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
    
    .stat-icon.primary { background: linear-gradient(135deg, #97113a15, #97113a25); color: var(--main-color); }
    .stat-icon.success { background: linear-gradient(135deg, #10b98115, #10b98125); color: #10b981; }
    .stat-icon.info { background: linear-gradient(135deg, #3b82f615, #3b82f625); color: #3b82f6; }
    .stat-icon.warning { background: linear-gradient(135deg, #f59e0b15, #f59e0b25); color: #f59e0b; }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #111827;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 0.875rem;
        font-weight: 500;
        text-transform: uppercase;
    }
    
    .search-section {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .action-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s;
        text-decoration: none;
    }
    
    .action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .action-card-header {
        background: linear-gradient(135deg, #97113a 0%, #c91f4f 100%);
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        color: white;
    }
    
    .action-card-body {
        padding: 1.5rem;
        color: #6b7280;
    }
</style>

<!-- Hero Section -->
<div class="dashboard-hero">
    <h1 style="font-size: 2.5rem; font-weight: 800; margin: 0 0 0.5rem 0;">
        Bilty Management System
    </h1>
    <p style="font-size: 1.125rem; opacity: 0.95; margin: 0;">
        Welcome back! Manage your bilties, track shipments, and generate reports efficiently.
    </p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fa-solid fa-file-invoice"></i>
        </div>
        <div class="stat-label">Total Bilties</div>
        <div class="stat-value"><?php echo format_number($stats['total_bilties']); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div class="stat-label">Total Amount</div>
        <div class="stat-value"><?php echo format_currency($stats['total_amount']); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stat-label">Pending Balance</div>
        <div class="stat-value"><?php echo format_currency($stats['pending_balance']); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon info">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div class="stat-label">This Month</div>
        <div class="stat-value"><?php echo format_number($stats['month_count']); ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="<?php echo site_url('bilty/add'); ?>" class="action-card">
        <div class="action-card-header">
            <i class="fa-solid fa-plus" style="font-size: 1.5rem;"></i>
            <div style="flex: 1; font-size: 1.25rem; font-weight: 700;">Create New Bilty</div>
        </div>
        <div class="action-card-body">
            Quickly add a new bilty record with all shipment details, vehicle information, and payment terms.
        </div>
    </a>
    
    <a href="<?php echo site_url('bilty'); ?>" class="action-card">
        <div class="action-card-header">
            <i class="fa-solid fa-list" style="font-size: 1.5rem;"></i>
            <div style="flex: 1; font-size: 1.25rem; font-weight: 700;">View All Bilties</div>
        </div>
        <div class="action-card-body">
            Browse, search, and filter through all your bilty records with advanced filtering options.
        </div>
    </a>
    
    <a href="<?php echo site_url('bilty/manage'); ?>" class="action-card">
        <div class="action-card-header">
            <i class="fa-solid fa-file-invoice-dollar" style="font-size: 1.5rem;"></i>
            <div style="flex: 1; font-size: 1.25rem; font-weight: 700;">Manage Bills</div>
        </div>
        <div class="action-card-body">
            Generate, view, and manage bills for multiple bilties. Track payment status and outstanding amounts.
        </div>
    </a>
    
    <a href="<?php echo site_url('finance/reports'); ?>" class="action-card">
        <div class="action-card-header">
            <i class="fa-solid fa-chart-line" style="font-size: 1.5rem;"></i>
            <div style="flex: 1; font-size: 1.25rem; font-weight: 700;">Reports & Analytics</div>
        </div>
        <div class="action-card-body">
            View detailed reports, analytics, and insights about your business performance and trends.
        </div>
    </a>
</div>

<!-- Quick Search Section -->
<div class="search-section">
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;">
        <i class="fa-solid fa-magnifying-glass" style="color: var(--main-color);"></i>
        Quick Bilty Search
    </h2>
    
    <?php echo form_open('', array('method' => 'post')); ?>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <input 
                    type="text" 
                    name="bilty_no" 
                    class="form-control" 
                    placeholder="Enter bilty number..." 
                    value="<?php echo htmlspecialchars($bilty_no); ?>"
                    required
                />
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-search"></i> Search
            </button>
        </div>
    <?php echo form_close(); ?>
    
    <?php if ($this->input->post('bilty_no')): ?>
        <?php if ($bilty_row): ?>
            <div style="margin-top: 1.5rem; padding: 1.5rem; background: #f9fafb; border-radius: 10px;">
                <h3 style="margin-bottom: 1rem;">Bilty Details</h3>
                <table class="table table-bordered">
                    <tr>
                        <th>Bilty No</th>
                        <td><strong><?php echo htmlspecialchars($bilty_row->bilty_no); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <td><?php echo htmlspecialchars($bilty_row->date); ?></td>
                    </tr>
                    <tr>
                        <th>Company</th>
                        <td><?php echo htmlspecialchars($bilty_row->company_name); ?></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td><?php echo htmlspecialchars($bilty_row->from_city); ?> → <?php echo htmlspecialchars($bilty_row->to_city); ?></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td><strong><?php echo format_currency($bilty_row->amount); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Balance</th>
                        <td><?php echo format_currency($bilty_row->balance); ?></td>
                    </tr>
                </table>
                <a href="<?php echo site_url('bilty/view/' . $bilty_row->id); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-eye"></i> View Full Details
                </a>
            </div>
        <?php else: ?>
            <div style="margin-top: 1.5rem; padding: 1.5rem; background: #fee2e2; border-radius: 10px; color: #991b1b;">
                <i class="fa-solid fa-exclamation-circle"></i>
                No bilty found for number <strong><?php echo htmlspecialchars($bilty_no); ?></strong>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
