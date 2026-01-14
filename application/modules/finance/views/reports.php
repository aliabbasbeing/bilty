<style>
    .report-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .stat-box {
        background: linear-gradient(135deg, #97113a 0%, #c91f4f 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
</style>

<div class="container-fluid" style="max-width: 1400px;">
    <!-- Header -->
    <div class="mb-4">
        <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin: 0 0 0.5rem 0;">
            <i class="fa-solid fa-chart-line" style="color: #97113a;"></i>
            Reports & Analytics
        </h1>
        <p style="color: #6b7280; font-size: 0.95rem; margin: 0;">View detailed reports and insights</p>
    </div>

    <!-- Filters -->
    <div class="report-card">
        <?php echo form_open('', array('method' => 'get', 'class' => 'd-flex gap-3 flex-wrap')); ?>
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" name="start" class="form-control" value="<?php echo $filters['start_date']; ?>" />
            </div>
            <div>
                <label class="form-label">End Date</label>
                <input type="date" name="end" class="form-control" value="<?php echo $filters['end_date']; ?>" />
            </div>
            <div style="flex: 1;">
                <label class="form-label">Company</label>
                <select name="company_id" class="form-select">
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
                    <i class="fa-solid fa-filter"></i> Apply Filters
                </button>
            </div>
        <?php echo form_close(); ?>
    </div>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-box">
                <h6 class="opacity-75 mb-2">Total Bilties</h6>
                <h2 class="mb-0"><?php echo format_number($summary->total_bilties); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <h6 class="opacity-75 mb-2">Total Amount</h6>
                <h2 class="mb-0"><?php echo format_currency($summary->total_amount); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <h6 class="opacity-75 mb-2">Total Advance</h6>
                <h2 class="mb-0"><?php echo format_currency($summary->total_advance); ?></h2>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <h6 class="opacity-75 mb-2">Total Balance</h6>
                <h2 class="mb-0"><?php echo format_currency($summary->total_balance); ?></h2>
            </div>
        </div>
    </div>

    <!-- Daily Summary -->
    <div class="report-card">
        <h3 class="mb-3">Daily Summary</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Bilties</th>
                        <th>Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_summary)): ?>
                        <tr><td colspan="5" class="text-center">No data available</td></tr>
                    <?php else: ?>
                        <?php foreach ($daily_summary as $day): ?>
                            <tr>
                                <td><?php echo format_date($day->date); ?></td>
                                <td><?php echo $day->count; ?></td>
                                <td><?php echo format_currency($day->total_amount); ?></td>
                                <td><?php echo format_currency($day->total_advance); ?></td>
                                <td><?php echo format_currency($day->total_balance); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Company Breakdown -->
    <div class="report-card">
        <h3 class="mb-3">Company Breakdown</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Bilties</th>
                        <th>Amount</th>
                        <th>Advance</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($company_breakdown)): ?>
                        <tr><td colspan="5" class="text-center">No data available</td></tr>
                    <?php else: ?>
                        <?php foreach ($company_breakdown as $company): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($company->company_name); ?></strong></td>
                                <td><?php echo $company->count; ?></td>
                                <td><?php echo format_currency($company->total_amount); ?></td>
                                <td><?php echo format_currency($company->total_advance); ?></td>
                                <td><?php echo format_currency($company->total_balance); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Routes -->
    <div class="report-card">
        <h3 class="mb-3">Top Routes</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Route</th>
                        <th>Bilties</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_routes)): ?>
                        <tr><td colspan="3" class="text-center">No data available</td></tr>
                    <?php else: ?>
                        <?php foreach ($top_routes as $route): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($route->from_city); ?>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <?php echo htmlspecialchars($route->to_city); ?>
                                    </strong>
                                </td>
                                <td><?php echo $route->count; ?></td>
                                <td><?php echo format_currency($route->total_amount); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
