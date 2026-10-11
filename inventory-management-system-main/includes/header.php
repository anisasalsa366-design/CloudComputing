<?php

require_once __DIR__ . "/helpers.php";

$flash = get_flash();
$active = $active ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InventoryPro - a lightweight inventory management system built with PHP and MySQL.">
    <title>Inventory Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<header class="navbar">
    <div class="container navbar-content">

        <a href="index.php" class="brand">
            Inventory<span>Pro</span>
        </a>

        <nav>
            <a href="index.php" class="<?php echo $active === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="products.php" class="<?php echo $active === 'products' ? 'active' : ''; ?>">Products</a>
            <a href="categories.php" class="<?php echo $active === 'categories' ? 'active' : ''; ?>">Categories</a>
        </nav>

    </div>
</header>

<main class="container">

<?php if ($flash): ?>
    <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>