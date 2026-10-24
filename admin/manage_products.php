
<?php
session_start();

// Allow only logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$products = [];
$error = '';

try {
    // Fetch all products, newest first
    $stmt = $conn->query(
        "SELECT id, name, description, price, image
         FROM products
         ORDER BY id DESC"
    );

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Manage products error: " . $e->getMessage());
    $error = "Unable to load products. Please try again.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Products | ShopEase Admin</title>

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
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        header h1 {
            margin: 0;
            color: #087d47;
            font-size: 26px;
        }

        header a {
            color: #087d47;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            width: 92%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        h2 {
            margin: 0;
            color: #173b2c;
        }

        .add-button,
        .back-button {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .add-button {
            background: #0b9f5a;
            color: white;
        }

        .back-button {
            background: #edf5ef;
            color: #087d47;
            margin-top: 25px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            margin-top: 25px;
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e4ebe6;
            vertical-align: middle;
        }

        th {
            background: #0b9f5a;
            color: white;
        }

        tbody tr:hover {
            background: #f7fbf8;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 6px;
            background: #f5f7f5;
        }

        .description {
            min-width: 180px;
            max-width: 300px;
            overflow-wrap: anywhere;
        }

        .edit-button,
        .delete-button {
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        .edit-button {
            background: #e7f1ff;
            color: #1759a8;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .delete-button {
            background: #fdeaea;
            color: #b42318;
        }

        .empty-message,
        .error-message {
            text-align: center;
            padding: 25px;
            border-radius: 8px;
            margin-top: 25px;
        }

        .empty-message {
            background: #f4f8f5;
            color: #68776e;
        }

        .error-message {
            background: #fff0f0;
            color: #b42318;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #68776e;
        }

        @media (max-width: 600px) {
            .container {
                padding: 18px;
            }

            header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <h1>ShopEase Admin</h1>
    <a href="logout.php">Logout</a>
</header>

<main class="container">

    <div class="page-heading">
        <h2>Manage Products</h2>
        <a href="add_product.php" class="add-button">+ Add Product</a>
    </div>

    <?php if ($error !== ''): ?>

        <p class="error-message">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>

    <?php elseif (empty($products)): ?>

        <div class="empty-message">
            <h3>No products found</h3>
            <p>Add your first product to display it here.</p>
        </div>

    <?php else: ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?= (int) $product['id'] ?>
                            </td>

                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img
                                        class="product-image"
                                        src="../images/<?= rawurlencode(basename($product['image'])) ?>"
                                        alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                ₹<?= number_format((float) $product['price'], 2) ?>
                            </td>

                            <td class="description">
                                <?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <a
                                    class="edit-button"
                                    href="edit_product.php?id=<?= (int) $product['id'] ?>"
                                >Edit</a>

                                <form
                                    method="POST"
                                    action="delete_product.php"
                                    style="display:inline;"
                                    onsubmit="return confirm('Are you sure you want to delete this product?');"
                                >
                                    <input
                                        type="hidden"
                                        name="product_id"
                                        value="<?= (int) $product['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-button"
                                    >Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back-button">
        ← Back to Dashboard
    </a>

</main>

<footer>
    &copy; <?= date('Y') ?> ShopEase. All rights reserved.
</footer>

</body>
</html>
