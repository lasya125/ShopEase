
<?php
session_start();

require_once 'includes/db.php';

// Check whether the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_email = $_SESSION['email'] ?? '';

// Initialize product variables
$products = [];
$product_error = '';
$search = trim($_GET['search'] ?? '');

$allowed_categories = [
    'Bottles',
    'Handbags',
    'Skincare',
    'Sneakers',
    'T-Shirts'
];

$selected_category = $_GET['category'] ?? 'All';

if (
    $selected_category !== 'All' &&
    !in_array($selected_category, $allowed_categories, true)
) {
    $selected_category = 'All';
}


try {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    // Filter by category
    if ($selected_category !== 'All') {
        $sql .= " AND category = ?";
        $params[] = $selected_category;
    }

    // Filter by product name or description
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $search_term = '%' . $search . '%';
        $params[] = $search_term;
        $params[] = $search_term;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Product fetch error: " . $e->getMessage());
    $product_error = "Products are temporarily unavailable. Please try again later.";
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShopEase | Online Store</title>

    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <!-- Header and Navigation -->
    <header>
        <div class="header-container">

            <h1>
                <a href="index.php" class="site-logo">ShopEase</a>
            </h1>

            <nav>
                <a href="index.php">Home</a>

                <?php if ($is_logged_in): ?>

                    <span class="welcome-user">
                        Welcome,
                        <?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                <?php else: ?>

                    <a href="pages/login.php">Login</a>
                    <a href="pages/register.php">Register</a>

                <?php endif; ?>

                <a href="pages/cart.php" class="cart-link">
                    <span class="cart-icon" aria-hidden="true">🛒</span>
                    <span>Cart</span>
                </a>
                <a href="pages/orders.php" class="orders-nav-link">
                    My Orders
                </a>
                <a href="pages/wishlist.php" class="wishlist-nav-link">
                    <span aria-hidden="true">♡</span>
                    <span>Wishlist</span>
                </a>

                <?php if ($is_logged_in): ?>

                    <form
                        method="POST"
                        action="pages/logout.php"
                        class="logout-form"
                    >
                        <button type="submit" class="logout-button">
                            Logout
                        </button>
                    </form>

                <?php endif; ?>

            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">

        <main>

            <!-- Hero Section -->
            <section class="hero">

                <h2>
                    <?= $is_logged_in
                        ? 'Welcome Back to ShopEase!'
                        : 'Discover Something You Love'; ?>
                </h2>

                <p>
                    Explore our collection of fashion, accessories,
                    and lifestyle essentials.
                </p>

                <a href="#products" class="shop-button">
                    Shop Now &rarr;
                </a>

            </section>

            <!-- Products Section -->
            <section id="products" class="products-section">

                <h2>Explore Our Products</h2>

                <p class="section-subtitle">
                    Find your favourites all in one place.
                </p>
                
                <form method="GET" action="index.php" class="product-search">
                    <input
                        type="search"
                        name="search"
                        placeholder="Search bottles, handbags, skincare..."
                        value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        aria-label="Search products"
                    >

                    <?php if ($selected_category !== 'All'): ?>
                        <input
                            type="hidden"
                            name="category"
                            value="<?= htmlspecialchars($selected_category, ENT_QUOTES, 'UTF-8') ?>"
                        >
                    <?php endif; ?>

                    <button type="submit">Search</button>
                </form>

                <div class="category-filters">
                    <a
                        href="index.php#products"
                        class="<?= $selected_category === 'All' ? 'active' : '' ?>"
                    >All Products</a>

                    <?php foreach ($allowed_categories as $category): ?>
                        <a
                            href="index.php?category=<?= urlencode($category) ?>#products"
                            class="<?= $selected_category === $category ? 'active' : '' ?>"
                        ><?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endforeach; ?>
                </div>


                <div class="product-list">

                    <?php if (!empty($product_error)): ?>

                        <p class="empty-products">
                            <?= htmlspecialchars(
                                $product_error,
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </p>

                    <?php elseif (empty($products)): ?>

                        <p class="empty-products">
                            No products available right now.
                            Please check back soon!
                        </p>

                    <?php else: ?>

                        <?php foreach ($products as $product): ?>

                            <div class="product-card">

                                <!-- Product Image -->
                                <?php if (!empty($product['image'])): ?>

                                    <div class="product-image-container">

                                        <img
                                            src="images/<?= htmlspecialchars(
                                                $product['image'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>"
                                            alt="<?= htmlspecialchars(
                                                $product['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>"
                                            class="product-image"
                                            loading="lazy"
                                        >

                                    </div>

                                <?php endif; ?>

                                <!-- Product Details -->
                                <div class="product-details">

                                    <h3>
                                        <?= htmlspecialchars(
                                            $product['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </h3>

                                    <p class="product-description">
                                        <?= htmlspecialchars(
                                            $product['description'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>
                                    </p>

                                    <p class="product-price">
                                        ₹<?= number_format(
                                            (float) $product['price'],
                                            2
                                        ); ?>
                                    </p>

                                    <!-- Product Actions -->
                                    <div class="product-actions">

                                        <!-- Wishlist Heart Button -->
                                        <form method="POST" action="wishlist_action.php"
                                            class="wishlist-form">

                                            <input type="hidden" name="product_id"
                                                value="<?= (int) $product['id']; ?>">

                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="return_to" value="index.php">

                                            <button type="submit"
                                                    class="wishlist-icon-button"
                                                    title="Add to Wishlist"
                                                    aria-label="Add to Wishlist">
                                                ♡
                                            </button>
                                        </form>

                                        <!-- Add to Cart Button -->
                                        <form method="POST" action="pages/cart.php"
                                            class="cart-form">

                                            <input type="hidden" name="product_id"
                                                value="<?= (int) $product['id']; ?>">

                                            <button type="submit" name="add_to_cart"
                                                    class="add-to-cart-button">
                                                Add to Cart
                                            </button>
                                        </form>

</div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

        </main>

    </div>

    <!-- Footer -->
    <footer>
        <p>
            &copy; <?= date('Y'); ?> ShopEase. All rights reserved.
        </p>
    </footer>

</body>

</html>
