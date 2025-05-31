document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functionality
    initDeleteButtons();
    initToggleButtons();
    initPrintButtons();
    initExportButtons();
    initCompleteSale();
    initAddProductToCart();
    initFormSubmissions();
    initQuantityAdjusters();
    initializeDarkMode();
});

// Initialize dark mode

function initializeDarkMode() {
    const darkModeStyle = document.getElementById('darkModeStyle');
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Check for saved preference or system preference
    const savedMode = localStorage.getItem('darkMode');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Determine initial mode
    const darkMode = savedMode ? (savedMode === 'enabled') : systemPrefersDark;
    
    // Apply initial mode
    if (darkMode) {
        enableDarkMode();
    } else {
        disableDarkMode();
    }
    
    // Set up toggle button
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', toggleDarkMode);
    }
    
    // Watch for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (localStorage.getItem('darkMode') === null) {
            if (e.matches) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        }
    });

    function enableDarkMode() {
        const style = document.getElementById('darkModeStyle');
        const toggle = document.getElementById('darkModeToggle');
        style.disabled = false;
        body.classList.add('dark-mode');
        if (toggle) {
            toggle.innerHTML = '<i class="fas fa-sun me-1"></i> Light Mode';
        }
        localStorage.setItem('darkMode', 'enabled');
    }

    function disableDarkMode() {
        const style = document.getElementById('darkModeStyle');
        const toggle = document.getElementById('darkModeToggle');
        style.disabled = true;
        body.classList.remove('dark-mode');
        if (toggle) {
            toggle.innerHTML = '<i class="fas fa-moon me-1"></i> Dark Mode';
        }
        localStorage.setItem('darkMode', 'disabled');
    }

    function toggleDarkMode() {
        if (document.getElementById('darkModeStyle').disabled) {
            enableDarkMode();
        } else {
            disableDarkMode();
        }
    }
}
// Initialize delete buttons
function initDeleteButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-btn') || e.target.classList.contains('delete-btn')) {
            e.preventDefault();
            const button = e.target.closest('.delete-btn') || e.target;
            const itemId = button.dataset.id;
            const itemType = button.dataset.type;
            
            if (confirm(`Are you sure you want to delete this ${itemType}?`)) {
                fetch('../../includes/delete_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&type=${itemType}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Item deleted successfully');
                        // Remove the row or refresh the page
                        if (button.closest('tr')) {
                            button.closest('tr').remove();
                        } else {
                            setTimeout(() => location.reload(), 1500);
                        }
                    } else {
                        showAlert('danger', data.message || 'Error deleting item');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'An error occurred while deleting');
                    console.error('Error:', error);
                });
            }
        }
    });
}

// Initialize toggle buttons (activate/deactivate)
function initToggleButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.toggle-btn') || e.target.classList.contains('toggle-btn')) {
            e.preventDefault();
            const button = e.target.closest('.toggle-btn') || e.target;
            const itemId = button.dataset.id;
            const itemType = button.dataset.type;
            const action = button.dataset.action;
            
            if (confirm(`Are you sure you want to ${action} this ${itemType}?`)) {
                fetch('../../includes/toggle_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&type=${itemType}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Status updated successfully');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showAlert('danger', data.message || 'Error updating status');
                    }
                })
                .catch(error => {
                    showAlert('danger', 'An error occurred');
                    console.error('Error:', error);
                });
            }
        }
    });
}

// Initialize print buttons
function initPrintButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.print-btn') || e.target.classList.contains('print-btn')) {
            e.preventDefault();
            const button = e.target.closest('.print-btn') || e.target;
            const elementId = button.dataset.target || 'printable-area';
            const printContents = document.getElementById(elementId).innerHTML;
            const originalContents = document.body.innerHTML;
            
            document.body.innerHTML = printContents;
            window.print();
            document.body.innerHTML = originalContents;
        }
    });
}

// Initialize export buttons (Excel/CSV)
function initExportButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.export-btn') || e.target.classList.contains('export-btn')) {
            e.preventDefault();
            const button = e.target.closest('.export-btn') || e.target;
            const tableId = button.dataset.target;
            const filename = button.dataset.filename || 'export';
            const table = document.getElementById(tableId);
            
            if (!table) {
                showAlert('danger', 'Table not found for export');
                return;
            }
            
            // Get table data
            const rows = table.querySelectorAll('tr');
            const csv = [];
            
            // Get headers
            const headers = [];
            table.querySelectorAll('th').forEach(th => {
                headers.push(th.innerText.trim());
            });
            csv.push(headers.join(','));
            
            // Get data rows
            rows.forEach(row => {
                if (row.querySelector('th')) return; // Skip header row
                
                const rowData = [];
                row.querySelectorAll('td').forEach(td => {
                    rowData.push(td.innerText.trim().replace(/,/g, ';'));
                });
                csv.push(rowData.join(','));
            });
            
            // Create CSV file
            const csvContent = 'data:text/csv;charset=utf-8,' + csv.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', `${filename}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            showAlert('success', 'Export completed successfully');
        }
    });
}

// Initialize complete sale functionality
function initCompleteSale() {
    const completeSaleBtn = document.getElementById('completeSaleBtn');
    if (completeSaleBtn) {
        completeSaleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get form data
            const form = document.getElementById('saleForm');
            const formData = new FormData(form);
            
            // Validate cart
            const cart = JSON.parse(formData.get('cart'));
            if (!cart || cart.length === 0) {
                showAlert('danger', 'Please add products to complete sale');
                return;
            }
            
            // Submit form
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.success) {
                    showAlert('success', 'Sale completed successfully');
                    setTimeout(() => window.location.href = '../../modules/sales/receipt.php?id=' + data.sale_id, 1000);
                } else {
                    showAlert('danger', data.message || 'Error completing sale');
                }
            })
            .catch(error => {
                showAlert('danger', 'An error occurred while completing sale');
                console.error('Error:', error);
            });
        });
    }
}

// Initialize add product to cart functionality
function initAddProductToCart() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart') || e.target.classList.contains('add-to-cart')) {
            e.preventDefault();
            const button = e.target.closest('.add-to-cart') || e.target;
            const productId = button.dataset.id;
            
            // Get product details from data attributes
            const product = {
                id: productId,
                name: button.dataset.name,
                price: parseFloat(button.dataset.price),
                current_quantity: parseInt(button.dataset.quantity),
                barcode: button.dataset.barcode
            };
            
            // Add to cart logic
            addProductToCart(product);
        }
    });
}

// Cart management functions
let cart = [];

function addProductToCart(product) {
    const existingItem = cart.find(item => item.id == product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.current_quantity) {
            existingItem.quantity++;
            updateCartDisplay();
        } else {
            showAlert('danger', 'Not enough stock available');
        }
    } else {
        if (product.current_quantity > 0) {
            cart.push({
                ...product,
                quantity: 1
            });
            updateCartDisplay();
        } else {
            showAlert('danger', 'Product is out of stock');
        }
    }
}

function updateCartDisplay() {
    const cartTable = document.getElementById('cartTable');
    if (!cartTable) return;
    
    const tbody = cartTable.querySelector('tbody');
    tbody.innerHTML = '';
    
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
                    <input type="number" class="form-control text-center" value="${item.quantity}" 
                           min="1" max="${item.current_quantity}">
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
        tbody.appendChild(row);
    });
    
    // Update totals
    const taxRate = 0.1; // 10% tax
    const tax = subtotal * taxRate;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('discountDisplay').textContent = `$${discount.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
    
    // Update hidden cart input
    document.getElementById('cartInput').value = JSON.stringify(cart);
    
    // Enable/disable complete sale button
    document.getElementById('completeSaleBtn').disabled = cart.length === 0;
}

// Initialize quantity adjusters in cart
function initQuantityAdjusters() {
    document.addEventListener('click', function(e) {
        // Plus button
        if (e.target.closest('.plus-btn') || e.target.classList.contains('plus-btn')) {
            const input = e.target.closest('.input-group').querySelector('input');
            const max = parseInt(input.max);
            const newValue = parseInt(input.value) + 1;
            
            if (newValue <= max) {
                input.value = newValue;
                updateCartItemQuantity(input);
            } else {
                showAlert('danger', 'Not enough stock available');
            }
        }
        
        // Minus button
        if (e.target.closest('.minus-btn') || e.target.classList.contains('minus-btn')) {
            const input = e.target.closest('.input-group').querySelector('input');
            const newValue = parseInt(input.value) - 1;
            
            if (newValue >= 1) {
                input.value = newValue;
                updateCartItemQuantity(input);
            }
        }
        
        // Remove item button
        if (e.target.closest('.remove-item') || e.target.classList.contains('remove-item')) {
            const row = e.target.closest('tr');
            const productId = row.dataset.id;
            
            cart = cart.filter(item => item.id != productId);
            updateCartDisplay();
        }
    });
    
    // Handle direct input changes
    document.addEventListener('change', function(e) {
        if (e.target.matches('#cartTable input[type="number"]')) {
            const input = e.target;
            const max = parseInt(input.max);
            const newValue = parseInt(input.value);
            
            if (newValue > max) {
                showAlert('danger', 'Not enough stock available');
                input.value = max;
            } else if (newValue < 1) {
                input.value = 1;
            }
            
            updateCartItemQuantity(input);
        }
        
        // Handle discount changes
        if (e.target.matches('#discount')) {
            updateCartDisplay();
        }
    });
}

function updateCartItemQuantity(input) {
    const row = input.closest('tr');
    const productId = row.dataset.id;
    const newQuantity = parseInt(input.value);
    
    const item = cart.find(item => item.id == productId);
    if (item) {
        item.quantity = newQuantity;
        updateCartDisplay();
    }
}

// Initialize form submissions with proper handling
function initFormSubmissions() {
    document.addEventListener('submit', function(e) {
        if (e.target.matches('form:not(.no-ajax)')) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('[type="submit"]');
            
            // Disable submit button during processing
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }
            
            fetch(form.action, {
                method: form.method,
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (!data) return;
                
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showAlert('success', data.message || 'Operation completed successfully');
                        if (form.dataset.resetOnSuccess) {
                            form.reset();
                        }
                        if (form.dataset.reloadOnSuccess) {
                            setTimeout(() => location.reload(), 1500);
                        }
                    }
                } else {
                    showAlert('danger', data.message || 'Error processing request');
                }
            })
            .catch(error => {
                showAlert('danger', 'An error occurred');
                console.error('Error:', error);
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
                }
            });
        }
    });
}


// Show alert messages
function showAlert(type, message) {
    const alertContainer = document.querySelector('.alert-container') || document.body;
    const alertId = 'alert-' + Date.now();
    
    const alert = document.createElement('div');
    alert.id = alertId;
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.prepend(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
}

function initializeDeleteButtons() {
    document.addEventListener('click', function(e) {
        if(e.target.closest('.delete-btn')) {
            e.preventDefault();
            const button = e.target.closest('.delete-btn');
            const itemId = button.dataset.id;
            const itemType = button.dataset.type;
            
            if(confirm(`Are you sure you want to delete this ${itemType}?`)) {
                fetch('<?php echo BASE_URL; ?>includes/delete_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${itemId}&type=${itemType}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showAlert(`${itemType.charAt(0).toUpperCase() + itemType.slice(1)} deleted successfully`, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(data.message || 'Error deleting item', 'danger');
                    }
                })
                .catch(error => {
                    showAlert('An error occurred', 'danger');
                    console.error('Error:', error);
                });
            }
        }
    });
}

// Delete functionality
document.addEventListener('click', function(e) {
    if(e.target.closest('.delete-btn')) {
        e.preventDefault();
        const button = e.target.closest('.delete-btn');
        const itemId = button.dataset.id;
        const itemType = button.dataset.type;
        
        if(confirm(`Are you sure you want to delete this ${itemType}?`)) {
            fetch('<?php echo BASE_URL; ?>includes/delete_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${itemId}&type=${itemType}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        ${itemType.charAt(0).toUpperCase() + itemType.slice(1)} deleted successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container-fluid').prepend(alert);
                    
                    // Remove the deleted row
                    button.closest('tr').remove();
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete item'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting');
            });
        }
    }
});