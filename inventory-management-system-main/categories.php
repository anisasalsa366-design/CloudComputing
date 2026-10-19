<?php

$active = 'categories';
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/includes/helpers.php";

$errors = [];
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'create_category') {

    if (!verify_csrf()) {
        set_flash('error', 'Invalid form submission. Please try again.');
        header('Location: categories.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            set_flash('success', 'Category created successfully.');
            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Something went wrong while saving the category.';
        }
    }
}

$categories = $pdo->query("
    SELECT categories.*, COUNT(products.id) AS product_count
    FROM categories
    LEFT JOIN products ON products.category_id = categories.id
    GROUP BY categories.id
    ORDER BY categories.name ASC
")->fetchAll();

require_once __DIR__ . "/includes/header.php";

?>

<section class="page-header">
    <div>
        <p class="eyebrow">INVENTORY</p>
        <h1>Categories</h1>
        <p class="subtitle">Organize your products into categories.</p>
    </div>
</section>


<section class="content-card">

    <div class="card-header">
        <div>
            <h2>Add Category</h2>
            <p>Create a new category for your products</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-inline">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form" value="create_category">

        <input type="text" name="name" placeholder="Category name" value="<?php echo htmlspecialchars($name); ?>" required>
        <input type="text" name="description" placeholder="Description (optional)" value="<?php echo htmlspecialchars($description); ?>">

        <button type="submit" class="primary-button">+ Add Category</button>
    </form>

</section>


<section class="content-card">

    <div class="card-header">
        <div>
            <h2>All Categories</h2>
            <p><?php echo count($categories); ?> categories</p>
        </div>
    </div>

    <div class="category-grid">

        <?php foreach ($categories as $category): ?>

            <div class="category-card">

                <div class="category-icon">#</div>

                <div class="category-info">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p><?php echo htmlspecialchars($category['description']); ?></p>
                    <span class="category-count"><?php echo $category['product_count']; ?> product(s)</span>
                </div>

                <form
                    method="POST"
                    action="category_delete.php"
                    class="inline-form"
                    onsubmit="return confirm('Delete this category? Products using it must be reassigned first.');"
                >
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                    <button type="submit" class="action-link action-danger">Delete</button>
                </form>

            </div>

        <?php endforeach; ?>

    </div>

</section>

<?php require_once __DIR__ . "/includes/footer.php"; ?>