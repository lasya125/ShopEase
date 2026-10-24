
<?php
session_start();

// Only logged-in admins can delete products
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: manage_products.php");
    exit();
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    header("Location: manage_products.php");
    exit();
}

try {
    // Find the product's image before deleting the record
    $stmt = $conn->prepare(
        "SELECT image FROM products WHERE id = ?"
    );
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Delete the product from the database
        $stmt = $conn->prepare(
            "DELETE FROM products WHERE id = ?"
        );
        $stmt->execute([$product_id]);

        // Remove the image file if it exists
        if (!empty($product['image'])) {
            $image_folder = realpath(__DIR__ . '/../images');

            if ($image_folder !== false) {
                $image_path = $image_folder . DIRECTORY_SEPARATOR
                    . basename($product['image']);

                if (is_file($image_path)) {
                    unlink($image_path);
                }
            }
        }

        header("Location: manage_products.php?deleted=1");
        exit();
    }

    header("Location: manage_products.php?deleted=0");
    exit();

} catch (PDOException $e) {
    error_log("Delete product error: " . $e->getMessage());
    http_response_code(500);
    exit("Could not delete the product. Check whether it is being used in another table.");
}
?>
