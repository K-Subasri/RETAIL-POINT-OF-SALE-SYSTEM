<?php
$pageTitle = "Sales Receipt";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

$saleId = isset($_GET['id']) ? $_GET['id'] : null;

if(!$saleId) {
    header("Location: index.php");
    exit;
}

// Get sale data
$stmt = $db->prepare("SELECT s.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, 
                      u.full_name as cashier_name, pm.name as payment_method 
                      FROM sales s 
                      LEFT JOIN customers c ON s.customer_id = c.customer_id 
                      JOIN users u ON s.user_id = u.user_id 
                      JOIN payment_methods pm ON s.payment_method_id = pm.method_id 
                      WHERE s.sale_id = :id");
$stmt->bindParam(':id', $saleId);
$stmt->execute();
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$sale) {
    header("Location: index.php");
    exit;
}

// Get sale items
$stmt = $db->prepare("SELECT si.*, p.name as product_name 
                      FROM sale_items si 
                      JOIN products p ON si.product_id = p.product_id 
                      WHERE si.sale_id = :id");
$stmt->bindParam(':id', $saleId);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4>Sales Receipt</h4>
            <div>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
                <a href="<?php echo BASE_URL; ?>modules/sales/" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Sales
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="receipt-container" id="receipt">
            <div class="text-center mb-4">
                <img src="<?php echo BASE_URL; ?>logo.png" alt="Company Logo" style="height: 80px;">
                <h2 class="mt-3">Retail POS</h2>
                <p>123 Main Street, Cityville</p>
                <p>Phone: (123) 456-7890 | Tax ID: 123-456-789</p>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Sale Information</h5>
                    <p><strong>Receipt #:</strong> <?php echo $sale['sale_id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y h:i A', strtotime($sale['transaction_date'])); ?></p>
                    <p><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Customer Information</h5>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?></p>
                    <?php if($sale['customer_phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?></p>
                    <?php endif; ?>
                    <?php if($sale['customer_email']): ?>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($sale['customer_email']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive mb-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <table class="table">
                        <tr>
                            <th>Subtotal:</th>
                            <td class="text-end">$<?php echo number_format($sale['total_amount'] - $sale['tax_amount'] + $sale['discount_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Tax (10%):</th>
                            <td class="text-end">$<?php echo number_format($sale['tax_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Discount:</th>
                            <td class="text-end">$<?php echo number_format($sale['discount_amount'], 2); ?></td>
                        </tr>
                        <tr class="table-active">
                            <th>Total:</th>
                            <td class="text-end"><strong>$<?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Payment Method:</th>
                            <td class="text-end"><?php echo htmlspecialchars($sale['payment_method']); ?></td>
                        </tr>
                        <?php if($sale['payment_method_id'] == 3): ?>
                        <tr>
                            <th>Payment Status:</th>
                            <td class="text-end text-success">Paid via QR Code</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <?php if($sale['notes']): ?>
            <div class="mb-3">
                <h5>Notes:</h5>
                <p><?php echo htmlspecialchars($sale['notes']); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <p class="mb-1">Thank you for your purchase!</p>
                <p class="text-muted small">For returns or exchanges, please present this receipt within 14 days</p>
                <div class="mt-3 pt-3 border-top">
                    <p class="small text-muted mb-1">Transaction ID: <?php echo uniqid('TXN-'); ?></p>
                    <p class="small text-muted">POS Terminal: <?php echo gethostname(); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #receipt, #receipt * {
        visibility: visible;
    }
    #receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
    }
    .no-print {
        display: none !important;
    }
    .receipt-container {
        border: none;
        box-shadow: none;
    }
    .table {
        page-break-inside: avoid;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>