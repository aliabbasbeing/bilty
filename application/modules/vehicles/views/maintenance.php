<div class="container-fluid" style="max-width: 1400px;">
    <!-- Header -->
    <div class="mb-4">
        <h1 style="font-size: 2rem; font-weight: 800; color: #111827; margin: 0 0 0.5rem 0;">
            <i class="fa-solid fa-screwdriver-wrench" style="color: #97113a;"></i>
            Vehicle Maintenance
        </h1>
        <p style="color: #6b7280; font-size: 0.95rem; margin: 0;">Track vehicle maintenance and repairs</p>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle"></i>
            <?php echo $this->session->flashdata('success'); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Add Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa-solid fa-plus"></i> Add Maintenance Record</h5>
        </div>
        <div class="card-body">
            <?php echo form_open('vehicles/maintenance/save'); ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_no" class="form-control" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Cost</label>
                        <input type="number" name="cost" class="form-control" step="0.01" min="0" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" class="form-control" required />
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Save Record
                        </button>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <!-- Records List -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fa-solid fa-list"></i> Maintenance Records</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Vehicle No</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fa-solid fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No maintenance records found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo format_date($record->date); ?></td>
                                    <td><strong><?php echo htmlspecialchars($record->vehicle_no); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record->description); ?></td>
                                    <td><?php echo format_currency($record->cost); ?></td>
                                    <td><?php echo htmlspecialchars($record->notes); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
