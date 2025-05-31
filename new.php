<?php
ob_start(); // Start output buffering

// Make sure session is started if using $_SESSION
$pageTitle = "New Sale";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

// Get products for barcode scanning
$products = $db->query("SELECT product_id, barcode, name, price, quantity FROM products WHERE quantity > 0 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get customers for dropdown
$customers = $db->query("SELECT customer_id, name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get payment methods
$paymentMethods = $db->query("SELECT * FROM payment_methods WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'] ?: null;
    $payment_method_id = $_POST['payment_method_id'];
    $tax_rate = 0.1; // 10% tax
    $discount = (float)$_POST['discount'];
    $notes = trim($_POST['notes']);
    $cart = json_decode($_POST['cart'], true);

    // Validate cart
    if (empty($cart)) {
        $error = "Cannot complete sale with empty cart";
    } else {
        try {
            $db->beginTransaction();

            // Calculate totals
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $tax_amount = $subtotal * $tax_rate;
            $total_amount = $subtotal + $tax_amount - $discount;

            // Insert sale
            $stmt = $db->prepare("INSERT INTO sales (customer_id, user_id, total_amount, tax_amount, discount_amount, payment_method_id, notes) 
                                  VALUES (:customer_id, :user_id, :total_amount, :tax_amount, :discount_amount, :payment_method_id, :notes)");
            $stmt->bindParam(':customer_id', $customer_id);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':total_amount', $total_amount);
            $stmt->bindParam(':tax_amount', $tax_amount);
            $stmt->bindParam(':discount_amount', $discount);
            $stmt->bindParam(':payment_method_id', $payment_method_id);
            $stmt->bindParam(':notes', $notes);
            $stmt->execute();

            $sale_id = $db->lastInsertId();

            // Insert sale items and update inventory
            foreach ($cart as $item) {
                // Insert sale item
                $stmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) 
                                      VALUES (:sale_id, :product_id, :quantity, :unit_price, :subtotal)");
                $stmt->bindParam(':sale_id', $sale_id);
                $stmt->bindParam(':product_id', $item['id']);
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':unit_price', $item['price']);
                $item_subtotal = $item['price'] * $item['quantity'];
                $stmt->bindParam(':subtotal', $item_subtotal);
                $stmt->execute();

                // Update product quantity
                $stmt = $db->prepare("UPDATE products SET quantity = quantity - :quantity WHERE product_id = :product_id");
                $stmt->bindParam(':quantity', $item['quantity']);
                $stmt->bindParam(':product_id', $item['id']);
                $stmt->execute();

                // Log inventory change
                $stmt = $db->prepare("INSERT INTO inventory_logs (product_id, user_id, quantity_change, previous_quantity, new_quantity, reason, reference_id) 
                                      VALUES (:product_id, :user_id, :quantity_change, :previous_quantity, :new_quantity, 'sale', :reference_id)");
                $stmt->bindParam(':product_id', $item['id']);
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $quantity_change = -$item['quantity'];
                $stmt->bindParam(':quantity_change', $quantity_change);
                $stmt->bindParam(':previous_quantity', $item['current_quantity']);
                $new_quantity = $item['current_quantity'] - $item['quantity'];
                $stmt->bindParam(':new_quantity', $new_quantity);
                $stmt->bindParam(':reference_id', $sale_id);
                $stmt->execute();
            }

            $db->commit();

            // Redirect to receipt
            header("Location: receipt.php?id=$sale_id");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Error processing sale: " . $e->getMessage();
        }
    }
}

ob_end_flush(); // Flush output buffer
?>

<div class="card">
    <div class="card-header">
        <h4>New Sale</h4>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="barcodeInput" placeholder="Scan barcode or search product..." autofocus>
                            <button class="btn btn-primary" type="button" id="searchProductBtn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover" id="productTable">
                                <thead>
                                    <tr>
                                        <th>Barcode</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $product): ?>
                                    <tr data-id="<?php echo $product['product_id']; ?>" 
                                        data-barcode="<?php echo htmlspecialchars($product['barcode']); ?>"
                                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        data-quantity="<?php echo $product['quantity']; ?>">
                                        <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['quantity']; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success add-to-cart">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <form id="saleForm" method="POST">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">Walk-in Customer</option>
                                    <?php foreach($customers as $customer): ?>
                                    <option value="<?php echo $customer['customer_id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="table-responsive mb-3">
                                <table class="table" id="cartTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Cart items will be added here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="discount" name="discount" value="0" min="0" step="0.01">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="payment_method_id" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method_id" name="payment_method_id" required>
                                    <?php foreach($paymentMethods as $method): ?>
                                    <option value="<?php echo $method['method_id']; ?>"><?php echo htmlspecialchars($method['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- QR Code Payment Section (hidden by default) -->
                            <div id="qrcode-payment" class="mb-3 text-center" style="display: none;">
                                <div class="alert alert-info">
                                    <p>Scan this QR code to complete payment</p>
                                    <img src="<?php echo BASE_URL; ?>qr.jpg" alt="QR Code" class="img-fluid" style="max-width: 200px;">
                                    <p class="mt-2">Amount: <span id="qrcode-amount">$0.00</span></p>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Subtotal:</h5>
                                <h5 id="subtotal">$0.00</h5>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Tax (10%):</h5>
                                <h5 id="tax">$0.00</h5>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Discount:</h5>
                                <h5 id="discountDisplay">$0.00</h5>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <h4>Total:</h4>
                                <h4 id="total">$0.00</h4>
                            </div>
                            
                            <input type="hidden" name="cart" id="cartInput">
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="completeSaleBtn" disabled>
                                    <i class="fas fa-check-circle me-2"></i> Complete Sale
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Sales Page Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const cart = [];
    const products = <?php echo json_encode($products); ?>;
    
    // Add to cart from product table
    document.addEventListener('click', function(e) {
        if(e.target.closest('.add-to-cart')) {
            const row = e.target.closest('tr');
            const productId = parseInt(row.dataset.id);
            const product = products.find(p => p.product_id == productId);
            
            if(product) {
                addToCart(product);
            }
        }
    });
    
    // Barcode scanning
    const barcodeInput = document.getElementById('barcodeInput');
    if(barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim();
                
                if(barcode) {
                    const product = products.find(p => p.barcode == barcode);
                    
                    if(product) {
                        addToCart(product);
                        this.value = '';
                    } else {
                        showAlert('Product not found!', 'danger');
                    }
                }
            }
        });
    }
    
    // Search product
    const searchProductBtn = document.getElementById('searchProductBtn');
    if(searchProductBtn) {
        searchProductBtn.addEventListener('click', function() {
            const searchTerm = barcodeInput.value.toLowerCase();
            
            if(searchTerm) {
                document.querySelectorAll('#productTable tbody tr').forEach(row => {
                    const name = row.dataset.name.toLowerCase();
                    const barcode = row.dataset.barcode.toLowerCase();
                    
                    if(name.includes(searchTerm) || barcode.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            } else {
                document.querySelectorAll('#productTable tbody tr').forEach(row => {
                    row.style.display = '';
                });
            }
        });
    }
    
    // Payment method change
    const paymentMethodSelect = document.getElementById('payment_method_id');
    if(paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            const qrCodeSection = document.getElementById('qrcode-payment');
            if(this.value === '3') { // Assuming 3 is QR code payment
                qrCodeSection.style.display = 'block';
                updateQRCodeAmount();
            } else {
                qrCodeSection.style.display = 'none';
            }
        });
    }
    
    // Discount change
    const discountInput = document.getElementById('discount');
    if(discountInput) {
        discountInput.addEventListener('change', updateCart);
    }
    
    // Add product to cart
    function addToCart(product) {
        const existingItem = cart.find(item => item.id == product.product_id);
        
        if(existingItem) {
            if(existingItem.quantity < product.quantity) {
                existingItem.quantity++;
                updateCart();
            } else {
                showAlert('Not enough stock available!', 'danger');
            }
        } else {
            if(product.quantity > 0) {
                cart.push({
                    id: product.product_id,
                    barcode: product.barcode,
                    name: product.name,
                    price: parseFloat(product.price),
                    quantity: 1,
                    current_quantity: parseInt(product.quantity)
                });
                updateCart();
            } else {
                showAlert('Product is out of stock!', 'danger');
            }
        }
    }
    
    // Update cart display
    function updateCart() {
        const cartTable = document.getElementById('cartTable').querySelector('tbody');
        cartTable.innerHTML = '';
        
        let subtotal = 0;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            const row = document.createElement('tr');
            row.dataset.id = item.id;
            row.innerHTML = `
                <td>${item.name}</td>
                <td>
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary minus-btn" type="button">-</button>
                        <input type="number" class="form-control text-center" value="${item.quantity}" min="1" max="${item.current_quantity}">
                        <button class="btn btn-outline-secondary plus-btn" type="button">+</button>
                    </div>
                </td>
                <td>$${item.price.toFixed(2)}</td>
                <td>$${itemTotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-sm btn-danger remove-item">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </td>
            `;
            
            cartTable.appendChild(row);
        });
        
        const tax = subtotal * 0.1;
        const discount = parseFloat(discountInput.value) || 0;
        const total = subtotal + tax - discount;
        
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('tax').textContent = '$' + tax.toFixed(2);
        document.getElementById('discountDisplay').textContent = '$' + discount.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
        
        document.getElementById('cartInput').value = JSON.stringify(cart);
        document.getElementById('completeSaleBtn').disabled = cart.length === 0;
        
        // Update QR code amount if visible
        if(document.getElementById('qrcode-payment').style.display === 'block') {
            updateQRCodeAmount();
        }
    }
    
    // Update QR code amount display
    function updateQRCodeAmount() {
        const totalElement = document.getElementById('total');
        if(totalElement) {
            document.getElementById('qrcode-amount').textContent = totalElement.textContent;
        }
    }
    
    // Handle cart quantity changes
    document.addEventListener('click', function(e) {
        if(e.target.classList.contains('plus-btn')) {
            const row = e.target.closest('tr');
            const productId = parseInt(row.dataset.id);
            const input = row.querySelector('input');
            const currentValue = parseInt(input.value);
            const max = parseInt(input.max);
            
            if(currentValue < max) {
                input.value = currentValue + 1;
                updateCartItem(productId, currentValue + 1);
            }
        }
        
        if(e.target.classList.contains('minus-btn')) {
            const row = e.target.closest('tr');
            const productId = parseInt(row.dataset.id);
            const input = row.querySelector('input');
            const currentValue = parseInt(input.value);
            
            if(currentValue > 1) {
                input.value = currentValue - 1;
                updateCartItem(productId, currentValue - 1);
            }
        }
    });
    
    // Handle cart input changes
    document.addEventListener('change', function(e) {
        if(e.target.matches('#cartTable input')) {
            const row = e.target.closest('tr');
            const productId = parseInt(row.dataset.id);
            const newQuantity = parseInt(e.target.value);
            const max = parseInt(e.target.max);
            
            if(newQuantity > max) {
                showAlert('Not enough stock available!', 'danger');
                e.target.value = max;
                updateCartItem(productId, max);
            } else if(newQuantity < 1) {
                e.target.value = 1;
                updateCartItem(productId, 1);
            } else {
                updateCartItem(productId, newQuantity);
            }
        }
    });
    
    // Remove item from cart
    document.addEventListener('click', function(e) {
        if(e.target.classList.contains('remove-item')) {
            const row = e.target.closest('tr');
            const productId = parseInt(row.dataset.id);
            
            const index = cart.findIndex(item => item.id == productId);
            if(index !== -1) {
                cart.splice(index, 1);
                updateCart();
            }
        }
    });
    
    // Update cart item quantity
    function updateCartItem(productId, newQuantity) {
        const item = cart.find(item => item.id == productId);
        if(item) {
            item.quantity = newQuantity;
            updateCart();
        }
    }
    
    // Show alert message
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.role = 'alert';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.alert-container') || document.body;
        container.prepend(alert);
        
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        }, 5000);
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>