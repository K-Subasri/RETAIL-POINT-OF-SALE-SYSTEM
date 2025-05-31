<?php
require_once '../../includes/db.php';
require_once '../../includes/header.php';

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch data for both table and chart
$customerReportQuery = "SELECT 
    c.customer_id,
    c.name,
    COUNT(s.sale_id) as total_purchases,
    SUM(s.total_amount) as total_spent,
    MAX(s.transaction_date) as last_purchase
    FROM customers c
    LEFT JOIN sales s ON c.customer_id = s.customer_id
    WHERE (s.transaction_date BETWEEN ? AND ? OR s.transaction_date IS NULL)
    GROUP BY c.customer_id
    ORDER BY total_spent DESC";

$stmt = $db->prepare($customerReportQuery);
$stmt->execute([$start_date, $end_date]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card customer-report-container">
    <div class="customer-report-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="mb-0"><i class="fas fa-users me-2"></i> Customer Reports</h2>
            <div class="report-actions">
                <button id="printCustomerReport" class="btn btn-light">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <button id="exportCustomerReport" class="btn btn-light">
                    <i class="fas fa-file-excel me-1"></i> Export
                </button>
            </div>
        </div>
    </div>
    
    <div class="card-body">
        <div class="report-filter-controls">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="?" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="customer-report-table">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th class="text-numeric">Purchases</th>
                                <th class="text-numeric">Total Spent</th>
                                <th class="text-numeric">Last Purchase</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>modules/customers/add.php?id=<?= $customer['customer_id'] ?>" 
                                           class="text-decoration-none text-primary">
                                            <i class="fas fa-user-circle me-2"></i>
                                            <?= htmlspecialchars($customer['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-numeric"><?= number_format($customer['total_purchases']) ?></td>
                                    <td class="text-numeric">$<?= number_format($customer['total_spent'] ?? 0, 2) ?></td>
                                    <td class="text-numeric">
                                        <?php if ($customer['last_purchase']): ?>
                                            <span class="badge bg-light text-dark">
                                                <?= date('M j, Y', strtotime($customer['last_purchase'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Never</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No customer data found for the selected period
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Spending Distribution</h5>
                    </div>
                    <div class="card-body customer-chart-container">
                        <canvas id="customerSpendingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize chart
    const ctx = document.getElementById('customerSpendingChart').getContext('2d');
    const customerChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($customers, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_map(fn($c) => (float) ($c['total_spent'] ?? 0), $customers)) ?>,
                backgroundColor: [
                    '#4361ee', '#3a0ca3', '#7209b7', '#f72585',
                    '#4cc9f0', '#4895ef', '#f8961e', '#f94144',
                    '#90be6d', '#43aa8b', '#577590'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 20,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            cutout: '65%'
        }
    });

    // Print report
    document.getElementById('printCustomerReport').addEventListener('click', function() {
        const printContent = `
            <style>
                @page { size: landscape; }
                body { font-family: Arial; padding: 20px; }
                h1 { color: #2c3e50; }
                table { width: 100%; border-collapse: collapse; }
                th { background-color: #4361ee; color: white; padding: 10px; text-align: left; }
                td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
                .text-right { text-align: right; }
            </style>
            <h1>Customer Report</h1>
            <p>Period: <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th class="text-right">Purchases</th>
                        <th class="text-right">Total Spent</th>
                        <th class="text-right">Last Purchase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['name']) ?></td>
                        <td class="text-right"><?= number_format($customer['total_purchases']) ?></td>
                        <td class="text-right">$<?= number_format($customer['total_spent'] ?? 0, 2) ?></td>
                        <td class="text-right"><?= $customer['last_purchase'] ? date('M j, Y', strtotime($customer['last_purchase'])) : 'Never' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 20px; text-align: right; font-size: 0.9em;">
                Generated on <?= date('M j, Y H:i') ?>
            </div>
        `;
        
        const win = window.open('', '', 'width=1000,height=600');
        win.document.write(printContent);
        win.document.close();
        win.focus();
        setTimeout(() => win.print(), 500);
    });

    // Export to Excel
    document.getElementById('exportCustomerReport').addEventListener('click', function() {
        // Create CSV content
        let csvContent = "data:text/csv;charset=utf-8,";
        
        // Add headers
        csvContent += "Customer Name,Purchases,Total Spent,Last Purchase\n";
        
        // Add data rows
        <?php foreach ($customers as $customer): ?>
        csvContent += `"<?= addslashes($customer['name']) ?>",<?= $customer['total_purchases'] ?>,<?= number_format($customer['total_spent'] ?? 0, 2) ?>,"<?= $customer['last_purchase'] ? date('M j, Y', strtotime($customer['last_purchase'])) : 'Never' ?>"\n`;
        <?php endforeach; ?>
        
        // Create download link
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "customer_report_<?= date('Y-m-d') ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>