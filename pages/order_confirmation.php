
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id || $order_id <= 0) {
    header("Location: ../index.php");
    exit();
}

// Fetch this order only if it belongs to the logged-in user
$stmt = $conn->prepare(
    "SELECT id, total_amount, delivery_address, status, created_at
     FROM orders
     WHERE id = ? AND user_id = ?"
);
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    exit("Order not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | ShopEase</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f8ef, #f4f7fa);
            color: #24352c;
        }

        header {
            background: white;
            padding: 20px 6%;
        }

        header a {
            color: #087d47;
            text-decoration: none;
            font-weight: bold;
            font-size: 25px;
        }

        .container {
            width: 90%;
            max-width: 650px;
            margin: 60px auto;
            padding: 35px;
            background: white;
            border-radius: 14px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .success {
            color: #087d47;
            font-size: 48px;
        }

        h1 { color: #087d47; }

        .details {
            text-align: left;
            background: #f3faf6;
            padding: 20px;
            border-radius: 8px;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 13px 22px;
            background: #0b9f5a;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<header>
    <a href="../index.php">ShopEase</a>
</header>

<main class="container">
    <div class="success">&#10003;</div>
    <h1>Order Placed Successfully!</h1>
    <p>Thank you for shopping with ShopEase.</p>

    <div class="details">
        <strong>Order ID:</strong>
        #<?= (int) $order['id'] ?><br>

        <strong>Total Amount:</strong>
        ₹<?= number_format((float) $order['total_amount'], 2) ?><br>

        <strong>Payment:</strong>
        Cash on Delivery<br>

        <strong>Status:</strong>
        <?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?><br>

        <strong>Delivery Address:</strong><br>
        <?= nl2br(htmlspecialchars(
            $order['delivery_address'],
            ENT_QUOTES,
            'UTF-8'
        )) ?>
    </div>

    <a class="button" href="../index.php">Continue Shopping</a>
</main>
</body>
</html>
