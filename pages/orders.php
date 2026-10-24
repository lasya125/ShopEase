
<?php
session_start();

require_once '../includes/db.php';

// Only logged-in users can view their orders
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$orders = [];
$order_error = '';

try {
    // Fetch only the logged-in user's orders
    $stmt = $conn->prepare(
        "SELECT id, total_amount, delivery_address, status, created_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC, id DESC"
    );

    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items belonging to each order
    $item_stmt = $conn->prepare(
        "SELECT product_name, price, quantity
         FROM order_items
         WHERE order_id = ?
    ");

    foreach ($orders as &$order) {
        $item_stmt->execute([$order['id']]);
        $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);

} catch (PDOException $e) {
    error_log('Order history error: ' . $e->getMessage());
    $order_error = 'Unable to load your orders right now. Please try again later.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | ShopEase</title>

    <link rel="stylesheet" href="../css/styles.css">

    <style>
        .orders-container {
            width: min(1000px, 92%);
            margin: 40px auto;
            min-height: 55vh;
        }

        .orders-heading {
            text-align: center;
            margin-bottom: 30px;
        }

        .orders-heading h2 {
            font-size: 32px;
            color: #172b24;
        }

        .orders-heading p {
            color: #78847e;
            margin-top: 8px;
        }

        .order-card {
            background: #fff;
            border: 1px solid #e5ece7;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 25px rgba(35, 48, 65, 0.06);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            border-bottom: 1px solid #e5ece7;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }

        .order-header h3 {
            color: #172b24;
        }

        .order-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            background: #e0f2e9;
            color: #087d47;
            font-weight: 700;
            font-size: 13px;
        }

        .order-meta {
            color: #66756d;
            margin: 5px 0;
            overflow-wrap: anywhere;
        }

        .order-products {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .order-products th,
        .order-products td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #edf1ee;
        }

        .order-products th {
            color: #52645a;
            font-size: 14px;
        }

        .order-total {
            text-align: right;
            margin-top: 18px;
            color: #15803d;
            font-size: 21px;
            font-weight: 800;
        }

        .orders-empty {
            background: #fff;
            border-radius: 16px;
            padding: 45px 20px;
            text-align: center;
        }

        .orders-empty h3 {
            margin-bottom: 10px;
        }

        .orders-empty p {
            color: #78847e;
            margin-bottom: 20px;
        }

        .orders-back {
            display: inline-block;
            margin-top: 24px;
            color: #087d47;
            font-weight: 700;
        }

        .orders-message {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
        }

        @media (max-width: 600px) {
            .order-card {
                padding: 16px;
            }

            .order-products th,
            .order-products td {
                padding: 9px 4px;
                font-size: 13px;
            }

            .orders-heading h2 {
                font-size: 26px;
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
            <a href="cart.php" class="cart-link">🛒 Cart</a>
            <a href="wishlist.php">♡ Wishlist</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
</header>

<main class="orders-container">

    <div class="orders-heading">
        <h2>My Orders</h2>
        <p>Track your purchases and view your order details.</p>
    </div>

    <?php if ($order_error !== ''): ?>

        <div class="orders-message">
            <?= htmlspecialchars($order_error, ENT_QUOTES, 'UTF-8') ?>
        </div>

    <?php elseif (empty($orders)): ?>

        <div class="orders-empty">
            <h3>You haven't placed any orders yet.</h3>
            <p>Your purchases will appear here after you check out.</p>
            <a href="../index.php" class="shop-button">
                Start Shopping &rarr;
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>

            <section class="order-card">

                <div class="order-header">
                    <div>
                        <h3>
                            Order #<?= (int) $order['id'] ?>
                        </h3>

                        <p class="order-meta">
                            Placed on
                            <?= htmlspecialchars(
                                date('d M Y, h:i A', strtotime($order['created_at'])),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    </div>

                    <span class="order-status">
                        <?= htmlspecialchars(
                            $order['status'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>
                </div>

                <p class="order-meta">
                    <strong>Delivery address:</strong>
                    <?= htmlspecialchars(
                        $order['delivery_address'] ?? 'Not provided',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

                <table class="order-products">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(
                                        $item['product_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    ₹<?= number_format((float) $item['price'], 2) ?>
                                </td>

                                <td>
                                    <?= (int) $item['quantity'] ?>
                                </td>

                                <td>
                                    ₹<?= number_format(
                                        (float) $item['price'] * (int) $item['quantity'],
                                        2
                                    ) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="order-total">
                    Order Total: ₹<?= number_format(
                        (float) $order['total_amount'],
                        2
                    ) ?>
                </p>

            </section>

        <?php endforeach; ?>

    <?php endif; ?>

    <a href="../index.php" class="orders-back">
        &larr; Continue Shopping
    </a>

</main>

<footer>
    <p>&copy; <?= date('Y') ?> ShopEase. All rights reserved.</p>
</footer>

</body>
</html>
