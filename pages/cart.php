
<?php
session_start();

// Redirect users who are not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$cart_items = [];
$total_cost = 0;
$cart_error = '';

// Handle cart updates and removals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add a product to the cart
    if (isset($_POST['add_to_cart'])) {
        $product_id = filter_input(
            INPUT_POST,
            'product_id',
            FILTER_VALIDATE_INT
        );


        if ($product_id && $product_id > 0) {
            // Confirm the product exists
            $stmt = $conn->prepare(
                "SELECT id FROM products WHERE id = ?"
            );
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // Check whether this product is already in the user's cart
                $stmt = $conn->prepare(
                    "SELECT id, quantity FROM cart
                     WHERE user_id = ? AND product_id = ?"
                );
                $stmt->execute([$user_id, $product_id]);
                $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing_item) {
                    // Increase quantity if it is already in the cart
                    $stmt = $conn->prepare(
                        "UPDATE cart
                         SET quantity = quantity + 1
                         WHERE id = ? AND user_id = ?"
                    );
                    $stmt->execute([
                        $existing_item['id'],
                        $user_id
                    ]);
                } else {
                    // Insert a new product into the cart
                    $stmt = $conn->prepare(
                        "INSERT INTO cart (user_id, product_id, quantity)
                         VALUES (?, ?, 1)"
                    );
                    $stmt->execute([$user_id, $product_id]);
                }
                // Remove this product from the wishlist
                $stmt = $conn->prepare(
                    "DELETE FROM wishlist
                    WHERE user_id = ? AND product_id = ?"
                );

                $stmt->execute([$user_id, $product_id]);
            }
        }

        header("Location: cart.php");
        exit();
    }

    // Remove an item from the cart
    if (isset($_POST['remove_from_cart'])) {

        $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);

        if ($cart_id && $cart_id > 0) {
            $stmt = $conn->prepare(
                "DELETE FROM cart WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([$cart_id, $user_id]);
        }

        header("Location: cart.php");
        exit();
    }

    // Update item quantity
    if (isset($_POST['update_quantity'])) {

        $cart_id = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if ($cart_id && $cart_id > 0 && $quantity !== false
            && $quantity !== null && $quantity >= 1 && $quantity <= 99) {

            $stmt = $conn->prepare(
                "UPDATE cart
                 SET quantity = ?
                 WHERE id = ? AND user_id = ?"
            );

            $stmt->execute([$quantity, $cart_id, $user_id]);
        }

        header("Location: cart.php");
        exit();
    }
}

// Fetch cart items
try {
    $stmt = $conn->prepare(
        "SELECT
            cart.id AS cart_id,
            products.id AS product_id,
            products.name,
            products.price,
            products.image,
            cart.quantity
         FROM cart
         JOIN products ON cart.product_id = products.id
         WHERE cart.user_id = ?"
    );

    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    foreach ($cart_items as $item) {
        $total_cost += (float) $item['price'] * (int) $item['quantity'];
    }

} catch (PDOException $e) {
    error_log("Cart error: " . $e->getMessage());
    $cart_error = "Unable to load your cart. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Cart | ShopEase</title>

    <link rel="stylesheet" href="../css/styles.css">

    <style>


            
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        color: #24352c;
        background: linear-gradient(135deg, #e8f8ef, #f0edff);
    }

header {
    background: rgba(255, 255, 255, 0.92);
    border-bottom: 1px solid #e5ece7;
    padding: 18px 0;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(12px);
}

.header-container {
    width: min(1200px, 92%);
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

header h1 {
    margin: 0;
    font-size: 29px;
    font-weight: 800;
}


    header a {
        color: #087d47;
        text-decoration: none;
        font-weight: 600;
        margin-left: 16px;
    }

    header a:hover {
        color: #07965b;
    }

    .cart-container {
        width: 92%;
        max-width: 1100px;
        margin: 45px auto;
        padding: 32px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.06);
    }

    .cart-container h2 {
        margin-top: 0;
        font-size: 30px;
        color: #173b2c;
    }

    .subtitle {
        color: #68776e;
        margin-bottom: 30px;
    }

    .cart-item {
        display: flex;
        align-items: center;
        gap: 22px;
        padding: 24px 0;
        border-bottom: 1px solid #e5eee8;
    }

    .cart-item img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        padding: 8px;
        background: #f4faf6;
        border-radius: 14px;
    }

    .cart-item-details {
        flex: 1;
    }

    .cart-item-details h3 {
        margin: 0 0 10px;
        font-size: 20px;
        color: #24352c;
    }

    .cart-item-details p {
        margin: 6px 0;
        color: #68776e;
    }

    .cart-item-details .subtotal {
        color: #087d47;
        font-weight: bold;
    }

    input[type="number"] {
        width: 85px;
        padding: 10px;
        border: 1px solid #d9e1dc;
        border-radius: 8px;
        font-size: 15px;
    }

    button,
    .btn {
        display: inline-block;
        padding: 11px 18px;
        border: none;
        border-radius: 9px;
        background: #0b9f5a;
        color: white;
        text-decoration: none;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
    }

    button:hover,
    .btn:hover {
        background: #087d47;
    }

    .remove-btn {
        background: #fff0f0;
        color: #c83232;
    }

    .remove-btn:hover {
        background: #ffe0e0;
    }

    .cart-summary {
    margin-top: 26px;
    padding: 25px 30px;
    border-radius: 16px;
    background: #f3faf5;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.cart-total {
    margin-left: auto;
    text-align: right;
    font-size: 28px;
    font-weight: bold;
    color: #087d47;
}

    .cart-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        width: 100%;
    }

    .continue-shopping {
        color: #087d47;
        text-decoration: none;
        font-weight: bold;
    }
    .site-logo {
    color: #059669;
    letter-spacing: -1px;
    text-decoration: none;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 29px;
    font-weight: 800;
}

nav {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 18px;
}

nav a {
    color: #34463d;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
}

nav a:hover {
    color: #059669;
}

    @media (max-width: 600px) {
        .cart-container {
            width: 94%;
            margin: 24px auto;
            padding: 20px;
        }

        .cart-item {
            align-items: flex-start;
            gap: 14px;
            flex-wrap: wrap;
        }

        .cart-item img {
            width: 90px;
            height: 100px;
        }

        .cart-summary {
            align-items: flex-start;
            flex-direction: column;
        }

        header h1 {
    margin: 0;
    color: #07965b;
    font-size: 32px;
    font-weight: 900;
    letter-spacing: -1px;
}
    }

    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="header-container">
            <h1>
                <a href="../index.php" class="site-logo">ShopEase</a>
            </h1>

            <nav>
                <a href="../index.php">Home</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Cart Content -->
    <main class="cart-container">

        <h1 class="cart-title">My Shopping Cart</h1>
        <p class="cart-subtitle">
            Review your items before checking out.
        </p>

        <?php if ($cart_error !== ''): ?>

            <p class="cart-error">
                <?= htmlspecialchars($cart_error, ENT_QUOTES, 'UTF-8'); ?>
            </p>

        <?php elseif (empty($cart_items)): ?>

            
        <div class="empty-cart">
            <div class="empty-cart-icon">🛒</div>

            <h3>Your cart is empty</h3>

            <p>
                Looks like you haven't added anything yet.
                Find something you'll love!
            </p>

            <a href="../index.php#products" class="cart-button">
                Start Shopping &rarr;
            </a>
        </div>


        <?php else: ?>

            <?php foreach ($cart_items as $item): ?>

                <div class="cart-item">

                    <?php if (!empty($item['image'])): ?>
                        <img
                            src="../images/<?= htmlspecialchars(
                                $item['image'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            alt="<?= htmlspecialchars(
                                $item['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            class="cart-item-image"
                        >
                    <?php endif; ?>

                    <div class="cart-item-details">

                        <h3>
                            <?= htmlspecialchars(
                                $item['name'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </h3>

                        <p>
                            Price: ₹<?= number_format(
                                (float) $item['price'],
                                2
                            ); ?>
                        </p>

                        <p>
                            Quantity: <?= (int) $item['quantity']; ?>
                        </p>

                        <p class="cart-item-price">
                            Subtotal: ₹<?= number_format(
                                (float) $item['price'] * (int) $item['quantity'],
                                2
                            ); ?>
                        </p>

                        <!-- Update Quantity -->
                        <form method="POST" action="cart.php"
                              class="quantity-form">

                            <input
                                type="hidden"
                                name="cart_id"
                                value="<?= (int) $item['cart_id']; ?>"
                            >

                            <label for="quantity-<?= (int) $item['cart_id']; ?>">
                                Qty:
                            </label>

                            <input
                                type="number"
                                id="quantity-<?= (int) $item['cart_id']; ?>"
                                name="quantity"
                                class="quantity-input"
                                value="<?= (int) $item['quantity']; ?>"
                                min="1"
                                max="99"
                                required
                            >

                            <button
                                type="submit"
                                name="update_quantity"
                                class="cart-button"
                            >
                                Update
                            </button>

                        </form>

                        <!-- Remove Item -->
                        <form method="POST" action="cart.php"
                              style="margin-top: 10px;">

                            <input
                                type="hidden"
                                name="cart_id"
                                value="<?= (int) $item['cart_id']; ?>"
                            >

                            <button type="submit" name="remove_from_cart" class="remove-btn">
                                Remove Item
                            </button>

                        </form>

                    </div>
                </div>

            <?php endforeach; ?>

            <!-- Cart Total -->
            <div class="cart-summary">

                <p class="cart-total">
                    Total: ₹<?= number_format($total_cost, 2); ?>
                </p>

                <div class="cart-actions">
                    <a href="../index.php" class="continue-shopping">
                        &larr; Continue Shopping
                    </a>

                    <a href="checkout.php" class="btn">
                        Proceed to Checkout &rarr;
                    </a>
                </div>

            </div>

        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; <?= date('Y'); ?> ShopEase. All rights reserved.</p>
    </footer>

</body>
</html>