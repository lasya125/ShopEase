
<?php
session_start();

// Customers must log in before checkout
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$message = '';
$cart_items = [];
$total_amount = 0;

// Fetch the logged-in user's cart
try {
    $stmt = $conn->prepare(
        "SELECT
            c.product_id,
            c.quantity,
            p.name,
            p.price,
            p.image
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = ?"
    );

    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $total_amount += (float) $item['price'] * (int) $item['quantity'];
    }
} catch (PDOException $e) {
    error_log("Checkout cart error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load your cart. Please try again.");
}

// Process the order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = trim($_POST['delivery_address'] ?? '');

    // Refresh the cart from the database when submitting
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare(
            "SELECT c.product_id, c.quantity, p.name, p.price
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ?
             FOR UPDATE"
        );
        $stmt->execute([$user_id]);
        $checkout_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($checkout_items)) {
            throw new RuntimeException("Your cart is empty. Please add products first.");
        }

        if ($delivery_address === '' || mb_strlen($delivery_address) < 10) {
            throw new RuntimeException("Please enter a complete delivery address (at least 10 characters).");
        }

        $total_amount = 0;

        foreach ($checkout_items as $item) {
            if ((int) $item['quantity'] < 1 || (float) $item['price'] <= 0) {
                throw new RuntimeException("A cart item has invalid details.");
            }

            $total_amount += (float) $item['price'] * (int) $item['quantity'];
        }

        // Create the order
        $stmt = $conn->prepare(
            "INSERT INTO orders
                (user_id, total_amount, delivery_address, status)
             VALUES (?, ?, ?, 'Pending')"
        );
        $stmt->execute([$user_id, $total_amount, $delivery_address]);

        $order_id = (int) $conn->lastInsertId();

        // Save each purchased product
        $stmt = $conn->prepare(
            "INSERT INTO order_items
                (order_id, product_id, product_name, price, quantity)
             VALUES (?, ?, ?, ?, ?)"
        );

        foreach ($checkout_items as $item) {
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['name'],
                $item['price'],
                $item['quantity']
            ]);
        }

        // Empty this user's cart only after saving the order
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $conn->commit();

        header("Location: order_confirmation.php?id=" . $order_id);
        exit();

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        } else {
            error_log("Place order error: " . $e->getMessage());
            $message = "We couldn't place your order. Please try again.";
        }

        // Refresh cart details after an unsuccessful order attempt
        $stmt = $conn->prepare(
            "SELECT c.product_id, c.quantity, p.name, p.price, p.image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             WHERE c.user_id = ?"
        );
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += (float) $item['price'] * (int) $item['quantity'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | ShopEase</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f8ef, #f4f7fa);
            color: #24352c;
        }

        header {
            background: white;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        header a {
            color: #087d47;
            font-weight: bold;
            text-decoration: none;
        }

        header h1 {
            margin: 0;
            color: #087d47;
            font-size: 26px;
        }

        .container {
            width: 90%;
            max-width: 850px;
            margin: 40px auto;
            padding: 32px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #173b2c;
        }

        .subtitle {
            color: #68776e;
            text-align: center;
            margin-bottom: 28px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 15px 0;
            border-bottom: 1px solid #e6ece8;
        }

        .item-name {
            font-weight: bold;
        }

        .item-details {
            margin-top: 5px;
            color: #68776e;
            font-size: 14px;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 24px 0;
            font-size: 20px;
            font-weight: bold;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        textarea {
            width: 100%;
            min-height: 110px;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d9e1dc;
            border-radius: 7px;
            font: inherit;
            resize: vertical;
        }

        .payment {
            padding: 15px;
            margin-bottom: 22px;
            border-radius: 8px;
            background: #e8f8ef;
        }

        .payment strong {
            color: #087d47;
        }

        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 7px;
            background: #0b9f5a;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #087d47;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            color: #b42318;
            background: #fff0f0;
            border-radius: 7px;
        }

        .back-link {
            display: block;
            margin-top: 22px;
            text-align: center;
            color: #087d47;
            text-decoration: none;
        }

        .empty {
            padding: 20px;
            text-align: center;
        }

        @media (max-width: 500px) {
            .container {
                padding: 22px;
            }
        }
    </style>
</head>

<body>
<header>
    <h1>ShopEase</h1>
    <a href="../index.php">Continue Shopping</a>
</header>

<main class="container">
    <h2>Checkout</h2>
    <p class="subtitle">Review your order and enter your delivery address.</p>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div class="empty">
            <h3>Your cart is empty</h3>
            <p>Add some products before checking out.</p>
            <a class="back-link" href="../index.php">Start Shopping</a>
        </div>
    <?php else: ?>

        <h3>Order Summary</h3>

        <?php foreach ($cart_items as $item): ?>
            <div class="item">
                <div>
                    <div class="item-name">
                        <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="item-details">
                        Quantity: <?= (int) $item['quantity'] ?>
                        × ₹<?= number_format((float) $item['price'], 2) ?>
                    </div>
                </div>

                <strong>
                    ₹<?= number_format(
                        (float) $item['price'] * (int) $item['quantity'],
                        2
                    ) ?>
                </strong>
            </div>
        <?php endforeach; ?>

        <div class="total">
            <span>Total Amount</span>
            <span>₹<?= number_format($total_amount, 2) ?></span>
        </div>

        <form method="POST">
            <label for="delivery_address">Delivery Address</label>
            <textarea
                id="delivery_address"
                name="delivery_address"
                placeholder="Enter house number, street, city, state, and PIN code"
                minlength="10"
                required
            ><?= htmlspecialchars($_POST['delivery_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <div class="payment">
                <strong>Payment Method: Cash on Delivery (COD)</strong>
                <p>You will pay when your order is delivered.</p>
            </div>

            <button type="submit" name="place_order">
                Place Order — ₹<?= number_format($total_amount, 2) ?>
            </button>
        </form>

        <a class="back-link" href="cart.php">← Back to Cart</a>
    <?php endif; ?>
</main>
</body>
</html>
