<?php

$active = 'dashboard';
require_once __DIR__ . "/config/database.php";

$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalStock = $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= minimum_stock")->fetchColumn();

$recentProducts = $pdo->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    INNER JOIN categories ON products.category_id = categories.id
    ORDER BY products.id DESC
    LIMIT 5
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

require_once __DIR__ . "/includes/header.php";

?>

<section class="page-header">

    <div>
        <p class="eyebrow">INVENTORY MANAGEMENT</p>
        <h1>Dashboard</h1>
        <p class="subtitle">
            Manage your products, stock and categories from one place.
        </p>
    </div>

    <a href="product_form.php" class="primary-button">+ Add Product</a>

</section>


<section class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div>
            <p>Total Products</p>
            <h2><?php echo $totalProducts; ?></h2>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">🏷️</div>
        <div>
            <p>Categories</p>
            <h2><?php echo $totalCategories; ?></h2>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div>
            <p>Total Stock</p>
            <h2><?php echo $totalStock; ?></h2>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-icon">⚠️</div>
        <div>
            <p>Low Stock</p>
            <h2><?php echo $lowStock; ?></h2>
        </div>
    </div>

</section>


<section class="content-card">

    <div class="card-header">
        <div>
            <h2>Recent Products</h2>
            <p>The last 5 products added to inventory</p>
        </div>
        <a href="products.php" class="secondary-button">View all products</a>
    </div>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($recentProducts)): ?>

                <tr>
                    <td colspan="6" class="empty-state">
                        No products yet. <a href="product_form.php">Add your first product</a>.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($recentProducts as $product): ?>

                    <?php $isLowStock = $product["stock"] <= $product["minimum_stock"]; ?>

                    <tr>
                        <td><strong><?php echo htmlspecialchars($product["name"]); ?></strong></td>
                        <td><span class="sku"><?php echo htmlspecialchars($product["sku"]); ?></span></td>
                        <td><?php echo htmlspecialchars($product["category_name"]); ?></td>
                        <td>$<?php echo number_format($product["price"], 2); ?></td>
                        <td><?php echo $product["stock"]; ?></td>
                        <td>
                            <?php if ($isLowStock): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<section class="content-card">

    <div class="card-header">
        <div>
            <h2>Categories</h2>
            <p>Product categories available in the system</p>
        </div>
        <a href="categories.php" class="secondary-button">Manage categories</a>
    </div>

    <div class="category-grid">

        <?php foreach ($categories as $category): ?>

            <div class="category-card">
                <div class="category-icon">#</div>
                <div>
                    <h3><?php echo htmlspecialchars($category["name"]); ?></h3>
                    <p><?php echo htmlspecialchars($category["description"]); ?></p>
                </div>
            </div>

        <?php endforeach; ?>

    </div>

</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>