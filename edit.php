<?php
ob_start(); // Start output buffering



$pageTitle = "Edit Product";
require_once '../../includes/header.php';
require_once '../../includes/db.php';

$productId = isset($_GET['id']) ? $_GET['id'] : null;

if(!$productId) {
    header("Location: index.php");
    exit;
}

// Get product data
$stmt = $db->prepare("SELECT * FROM products WHERE product_id = :id");
$stmt->bindParam(':id', $productId);
$stmt->execute();
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$product) {
    header("Location: index.php");
    exit;
}

// Get categories for dropdown
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $barcode = trim($_POST['barcode']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'] ?: null;
    $price = (float)$_POST['price'];
    $cost = (float)$_POST['cost'];
    $quantity = (int)$_POST['quantity'];
    $reorder_level = (int)$_POST['reorder_level'];
    
    // Handle image upload
    $image_path = $product['image_path'];
    if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        if($image_path && file_exists("../../$image_path")) {
            unlink("../../$image_path");
        }

        $uploadDir = '../../assets/uploads/';
        $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $fileName;

        if(move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image_path = 'assets/uploads/' . $fileName;
        }
    }

    // Handle image removal
    if(isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if($image_path && file_exists("../../$image_path")) {
            unlink("../../$image_path");
        }
        $image_path = null;
    }

    try {
        $quantity_diff = $quantity - $product['quantity'];

        $stmt = $db->prepare("UPDATE products SET 
                             name = :name, 
                             barcode = :barcode, 
                             description = :description, 
                             category_id = :category_id, 
                             price = :price, 
                             cost = :cost, 
                             quantity = :quantity, 
                             reorder_level = :reorder_level, 
                             image_path = :image_path 
                             WHERE product_id = :product_id");

        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':barcode', $barcode);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':cost', $cost);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':reorder_level', $reorder_level);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':product_id', $productId);
        $stmt->execute();

        // Log inventory change
        if($quantity_diff != 0) {
            $stmt = $db->prepare("INSERT INTO inventory_logs (product_id, user_id, quantity_change, previous_quantity, new_quantity, reason) 
                                  VALUES (:product_id, :user_id, :quantity_change, :previous_quantity, :new_quantity, 'adjustment')");
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->bindParam(':quantity_change', $quantity_diff);
            $stmt->bindParam(':previous_quantity', $product['quantity']);
            $stmt->bindParam(':new_quantity', $quantity);
            $stmt->execute();
        }

        $_SESSION['success'] = "Product updated successfully!";
        header("Location: index.php");
        exit;
    } catch(PDOException $e) {
        $error = "Error updating product: " . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4>Edit Product</h4>
    </div>
    <div class="card-body">
        <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode" value="<?php echo htmlspecialchars($product['barcode']); ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="price" class="form-label">Selling Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo number_format($product['price'], 2); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="cost" class="form-label">Cost Price</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0" value="<?php echo number_format($product['cost'], 2); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="<?php echo $product['quantity']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="reorder_level" class="form-label">Reorder Level</label>
                    <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="<?php echo $product['reorder_level']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="image" class="form-label">Product Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>
            </div>
            
            <?php if($product['image_path']): ?>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Current Image</label>
                    <div>
                        <img src="../../<?php echo $product['image_path']; ?>" alt="Product Image" class="img-thumbnail" style="max-height: 150px;">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                            <label class="form-check-label" for="remove_image">Remove Image</label>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Product</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
ob_end_flush(); // Flush output buffer at the very end
?>
