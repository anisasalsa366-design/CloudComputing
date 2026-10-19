<?php

$active = 'products';
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/helpers.php";

$search = trim($_GET['q'] ?? '');
$categoryFilter = $_GET['category'] ?? '';
$stockFilter = $_GET['stock'] ?? '';

$sql = "SELECT products.*, categories.name AS category_name
        FROM products
        INNER JOIN categories ON products.category_id = categories.id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (products.name LIKE :search OR products.sku LIKE :search)";
    $params['search'] = "%$search%";
}

if ($categoryFilter !== '') {
    $sql .= " AND products.category_id = :category_id";
    $params['category_id'] = $categoryFilter;
}

if ($stockFilter === 'low') {
    $sql .= " AND products.stock <= products.minimum_stock";
}

$sql .= " ORDER BY products.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

require_once __DIR__ . "/includes/header.php";

?>

<section class="page-header">

    <div>
        <p class="eyebrow">INVENTORY</p>
        <h1>Products</h1>
        <p class="subtitle">Create, edit and manage every product in stock.</p>
    </div>

    <a href="product_form.php" class="primary-button">+ Add Product</a>

</section>


<section class="content-card">

    <form method="GET" class="filter-bar">

        <input
            type="text"
            name="q"
            placeholder="Search by name or SKU..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="category">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="stock">
            <option value="">All stock levels</option>
            <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low stock only</option>
        </select>

        <button type="submit" class="secondary-button">Filter</button>
        <a href="products.php" class="link-reset">Reset</a>

    </form>

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
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if (empty($products)): ?>

                <tr>
                    <td colspan="7" class="empty-state">No products match your filters.</td>
                </tr>

            <?php else: ?>

                <?php foreach ($products as $product): ?>

                    <?php $isLowStock = $product['stock'] <= $product['minimum_stock']; ?>

                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <?php if (!empty($product['description'])): ?>
                                <small><?php echo htmlspecialchars($product['description']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="sku"><?php echo htmlspecialchars($product['sku']); ?></span></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <?php if ($isLowStock): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <a href="product_form.php?id=<?php echo $product['id']; ?>" class="action-link">Edit</a>
                            <form
                                method="POST"
                                action="product_delete.php"
                                class="inline-form"
                                onsubmit="return confirm('Delete this product? This cannot be undone.');"
                            >
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="action-link action-danger">Delete</button>
                            </form>
                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>