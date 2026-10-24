
<?php
session_start();

require_once '../includes/db.php';

// Require customer login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$wishlist_items = [];
$error_message = '';

try {
    $stmt = $conn->prepare(
        "SELECT p.id, p.name, p.description, p.price, p.image
         FROM wishlist w
         JOIN products p ON w.product_id = p.id
         WHERE w.user_id = ?
         ORDER BY w.created_at DESC"
    );

    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Wishlist page error: ' . $e->getMessage());
    $error_message = 'Unable to load your wishlist. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | ShopEase</title>
    <link rel="stylesheet" href="../css/styles.css">

    <style>
        .wishlist-container {
            width: min(1200px, 92%);
            margin: 40px auto;
            min-height: 55vh;
        }

        .wishlist-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .wishlist-heading h2 {
            font-size: 32px;
            color: #172b24;
        }

        .wishlist-heading p {
            color: #78847e;
            margin-top: 8px;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 24px;
        }

        .wishlist-card {
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: white;
            border: 1px solid #e8eee9;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(35, 48, 65, 0.07);
        }

        .wishlist-card img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            padding: 12px;
            background: #f6f8f6;
        }

        .wishlist-details {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 18px;
        }

        .wishlist-details h3 {
            color: #172b24;
            margin-bottom: 8px;
        }

        .wishlist-description {
            color: #78847e;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .wishlist-price {
            color: #15803d;
            font-size: 21px;
            font-weight: 800;
            margin-top: auto;
            margin-bottom: 15px;
        }

        .wishlist-actions {
            display: grid;
            gap: 10px;
        }

        .wishlist-actions form {
            margin: 0;
        }

        .wishlist-actions button,
        .wishlist-actions a {
            display: block;
            width: 100%;
            padding: 11px;
            border: none;
            border-radius: 9px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .wishlist-add {
            background: #0b9f5a;
            color: white;
        }

        .wishlist-remove {
            background: #fff1f0;
            color: #c0392b;
        }

        .wishlist-empty {
            background: white;
            padding: 35px 20px;
            border-radius: 15px;
            text-align: center;
            color: #78847e;
        }

        .wishlist-empty a {
            display: inline-block;
            margin-top: 15px;
            color: #087d47;
            font-weight: 700;
        }

        .wishlist-back {
            display: inline-block;
            margin-top: 28px;
            color: #087d47;
            font-weight: 700;
        }

        @media (max-width: 950px) {
            .wishlist-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .wishlist-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 13px;
            }

            .wishlist-card img {
                height: 155px;
            }

            .wishlist-details {
                padding: 12px;
            }

            .wishlist-details h3 {
                font-size: 15px;
            }
        }

        @media (max-width: 360px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="header-container">
        <h1>
            <a href="../index.php" class="site-logo">ShopEase</a>
        </h1>

        <nav>
            <a href="../index.php">Home</a>
            <a href="cart.php" class="cart-link">
                <span class="cart-icon" aria-hidden="true">🛒</span>
                <span>Cart</span>
            </a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="wishlist-container">

    <div class="wishlist-heading">
        <h2>♡ My Wishlist</h2>
        <p>Your favourite products, all in one place.</p>
    </div>

    <?php if ($error_message !== ''): ?>

        <div class="wishlist-empty">
            <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php elseif (empty($wishlist_items)): ?>

        <div class="wishlist-empty">
            <h3>Your wishlist is empty ♡</h3>
            <p>Save products you love and find them here later.</p>
            <a href="../index.php#products">Explore Products →</a>
        </div>

    <?php else: ?>

        <div class="wishlist-grid">

            <?php foreach ($wishlist_items as $product): ?>

                <article class="wishlist-card">

                    <img
                        src="../images/<?= htmlspecialchars(
                            $product['image'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        alt="<?= htmlspecialchars(
                            $product['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="wishlist-details">

                        <h3>
                            <?= htmlspecialchars(
                                $product['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </h3>

                        <p class="wishlist-description">
                            <?= htmlspecialchars(
                                $product['description'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p class="wishlist-price">
                            ₹<?= number_format((float) $product['price'], 2) ?>
                        </p>

                        <div class="wishlist-actions">

                            <form method="POST" action="cart.php">
                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $product['id'] ?>"
                                >
                                <button
                                    type="submit"
                                    name="add_to_cart"
                                    class="wishlist-add"
                                >
                                    Add to Cart
                                </button>
                            </form>

                            <form
                                method="POST"
                                action="../wishlist_action.php"
                            >
                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int) $product['id'] ?>"
                                >
                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove"
                                >
                                <input
                                    type="hidden"
                                    name="return_to"
                                    value="pages/wishlist.php"
                                >
                                <button
                                    type="submit"
                                    class="wishlist-remove"
                                >
                                    ♥ Remove
                                </button>
                            </form>

                        </div>
                    </div>
                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <a class="wishlist-back" href="../index.php#products">
        ← Continue Shopping
    </a>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> ShopEase. All rights reserved.</p>
</footer>

</body>
</html>
