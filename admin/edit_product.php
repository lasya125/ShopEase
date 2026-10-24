
<?php
session_start();

// Only logged-in admins can edit products
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$message = '';
$message_type = '';

// Get the product ID from the URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id || $product_id < 1) {
    header("Location: manage_products.php");
    exit();
}

// Fetch the existing product
try {
    $stmt = $conn->prepare(
        "SELECT id, name, price, description, image
         FROM products
         WHERE id = ?"
    );
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Fetch product error: " . $e->getMessage());
    exit("Unable to load this product. Please try again.");
}

if (!$product) {
    http_response_code(404);
    exit("Product not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $name = trim($_POST['name'] ?? '');
    $price_input = $_POST['price'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if ($name === '' || $description === '') {
        $message = 'Please enter the product name and description.';
        $message_type = 'error';

    } elseif (
        !is_numeric($price_input) ||
        !is_finite((float)$price_input) ||
        (float)$price_input <= 0
    ) {
        $message = 'Please enter a valid price greater than zero.';
        $message_type = 'error';

    } else {
        // Keep the current image unless a new one is uploaded
        $new_image_name = $product['image'];
        $uploaded_image_path = null;
        $old_image_name = $product['image'];
        $upload_error = '';

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $image = $_FILES['image'];

            if ($image['error'] !== UPLOAD_ERR_OK) {
                $upload_error = 'Image upload failed. Please try again.';

            } elseif ($image['size'] > 3 * 1024 * 1024) {
                $upload_error = 'Image size must be 3 MB or less.';

            } else {
                $image_info = @getimagesize($image['tmp_name']);

                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                $mime_type = $image_info['mime'] ?? '';

                if (
                    $image_info === false ||
                    !isset($allowed_types[$mime_type])
                ) {
                    $upload_error = 'Please upload a JPG, PNG, or WebP image.';

                } else {
                    $image_folder = realpath(__DIR__ . '/../images');

                    if ($image_folder === false) {
                        $upload_error = 'The images folder was not found.';

                    } else {
                        $extension = $allowed_types[$mime_type];
                        $new_image_name = bin2hex(random_bytes(16)) . '.' . $extension;

                        $uploaded_image_path =
                            $image_folder . DIRECTORY_SEPARATOR . $new_image_name;

                        if (!move_uploaded_file(
                            $image['tmp_name'],
                            $uploaded_image_path
                        )) {
                            $upload_error = 'Could not save the uploaded image.';
                            $uploaded_image_path = null;
                        }
                    }
                }
            }
        }

        if ($upload_error !== '') {
            $message = $upload_error;
            $message_type = 'error';

        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE products
                     SET name = ?, price = ?, description = ?, image = ?
                     WHERE id = ?"
                );

                $stmt->execute([
                    $name,
                    (float)$price_input,
                    $description,
                    $new_image_name,
                    $product_id
                ]);

                // Remove the previous image only after the database update succeeds
                if (
                    $uploaded_image_path !== null &&
                    !empty($old_image_name)
                ) {
                    $old_image_path =
                        realpath(__DIR__ . '/../images') .
                        DIRECTORY_SEPARATOR .
                        basename($old_image_name);

                    if (
                        is_file($old_image_path) &&
                        realpath($old_image_path) !== realpath($uploaded_image_path)
                    ) {
                        unlink($old_image_path);
                    }
                }

                $message = 'Product updated successfully!';
                $message_type = 'success';

                // Refresh displayed product details
                $stmt = $conn->prepare(
                    "SELECT id, name, price, description, image
                     FROM products
                     WHERE id = ?"
                );
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                // Remove the newly uploaded image if saving failed
                if (
                    $uploaded_image_path !== null &&
                    is_file($uploaded_image_path)
                ) {
                    unlink($uploaded_image_path);
                }

                error_log("Update product error: " . $e->getMessage());
                $message = 'Could not update the product. Please try again.';
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | ShopEase Admin</title>

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
            width: 90%;
            max-width: 650px;
            margin: 45px auto;
            padding: 35px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        h2 {
            text-align: center;
            color: #173b2c;
            margin-top: 0;
        }

        .subtitle {
            text-align: center;
            color: #68776e;
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d9e1dc;
            border-radius: 7px;
            font-size: 15px;
            font-family: inherit;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input[type="file"] {
            background: #f8fbf9;
        }

        .current-image {
            display: block;
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 15px;
            padding: 8px;
            border-radius: 8px;
            background: #f5f7f5;
        }

        .help-text {
            display: block;
            color: #68776e;
            font-size: 13px;
            margin-top: -12px;
            margin-bottom: 20px;
        }

        button {
            width: 100%;
            padding: 14px;
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
            border-radius: 7px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            color: #087d47;
            background: #e8f8ef;
        }

        .error {
            color: #b42318;
            background: #fff0f0;
        }

        .links {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }

        .links a {
            color: #087d47;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 500px) {
            .container {
                padding: 22px;
            }

            header h1 {
                font-size: 21px;
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
    <h2>Edit Product</h2>
    <p class="subtitle">Update the product details below.</p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $message_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="name">Product Name</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="255"
            value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="price">Price (₹)</label>
        <input
            type="number"
            id="price"
            name="price"
            min="0.01"
            step="0.01"
            value="<?= htmlspecialchars((string)$product['price'], ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label for="description">Description</label>
        <textarea
            id="description"
            name="description"
            required
        ><?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

        <label>Current Image</label>

        <?php if (!empty($product['image'])): ?>
            <img
                class="current-image"
                src="../images/<?= rawurlencode(basename($product['image'])) ?>"
                alt="Current product image"
            >
        <?php else: ?>
            <p>No image uploaded.</p>
        <?php endif; ?>

        <label for="image">Choose a New Image (Optional)</label>
        <input
            type="file"
            id="image"
            name="image"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <span class="help-text">
            JPG, PNG, or WebP. Maximum size: 3 MB.
            Leave empty to keep the current image.
        </span>

        <button type="submit" name="update_product">
            Update Product
        </button>
    </form>

    <div class="links">
        <a href="manage_products.php">← Manage Products</a>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</main>

</body>
</html>
