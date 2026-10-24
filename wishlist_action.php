
<?php
session_start();

require_once 'includes/db.php';

// Only logged-in customers can use the wishlist
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$return_to = $_POST['return_to'] ?? 'index.php';

// Allow redirects only to these local pages
if (!in_array($return_to, ['index.php', 'pages/wishlist.php'], true)) {
    $return_to = 'index.php';
}

if (!$product_id || !in_array($action, ['add', 'remove'], true)) {
    header('Location: ' . $return_to);
    exit();
}

try {
    if ($action === 'add') {
        // Save the product; ignore duplicates
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO wishlist (user_id, product_id)
             SELECT ?, id FROM products WHERE id = ?"
        );

        $stmt->execute([$user_id, $product_id]);

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare(
            "DELETE FROM wishlist
             WHERE user_id = ? AND product_id = ?"
        );

        $stmt->execute([$user_id, $product_id]);
    }

} catch (PDOException $e) {
    error_log('Wishlist error: ' . $e->getMessage());
}

header('Location: ' . $return_to);
exit();
?>
