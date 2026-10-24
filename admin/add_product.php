
<?php
session_start();

// Allow only logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../includes/db.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {

    $name = trim($_POST['name'] ?? '');
    $price_input = $_POST['price'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';

    $allowed_categories = [
        'Bottles',
        'Handbags',
        'Skincare',
        'Sneakers',
        'T-Shirts'
    ];

    // Validate product details
    if ($name === '' || $description === '') {
        $message = 'Please enter the product name and description.';
        $message_type = 'error';

    } elseif (!is_numeric($price_input) || (float)$price_input <= 0) {
        $message = 'Please enter a valid price greater than zero.';
        $message_type = 'error';

    } elseif (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {
        $message = 'Please select an image to upload.';
        $message_type = 'error';

    } else {
        $image = $_FILES['image'];

        // Limit image size to 3 MB
        $max_size = 3 * 1024 * 1024;

        if ($image['size'] > $max_size) {
            $message = 'Image size must be 3 MB or less.';
            $message_type = 'error';

        } else {
            // Check the actual image type
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
                $message = 'Please upload a JPG, PNG, or WebP image.';
                $message_type = 'error';

            } else {
                $extension = $allowed_types[$mime_type];

                // Generate a unique filename
                $image_name = bin2hex(random_bytes(16)) . '.' . $extension;

                // Save image inside the project's images folder
                $image_folder = realpath(__DIR__ . '/../images');

                if ($image_folder === false) {
                    $message = 'The images folder was not found.';
                    $message_type = 'error';

                } else {
                    $image_path = $image_folder . DIRECTORY_SEPARATOR . $image_name;

                    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                        $message = 'Image upload failed. Please try again.';
                        $message_type = 'error';

                    } else {
                        try {
                            // Insert product details into the database
                            $stmt = $conn->prepare(
                                "INSERT INTO products
                                (name, price, description, image)
                                VALUES (?, ?, ?, ?)"
                            );

                            $stmt->execute([
                                $name,
                                (float)$price_input,
                                $description,
                                $image_name
                            ]);

                            $message = 'Product added successfully!';
                            $message_type = 'success';

                        } catch (PDOException $e) {
                            // Remove uploaded file if database insertion fails
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }

                            error_log('Add product error: ' . $e->getMessage());
                            $message = 'Could not save the product. Please check your database structure.';
                            $message_type = 'error';
                        }
                    }
                }
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

    <title>Add Product | ShopEase Admin</title>

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
            margin-bottom: 10px;
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

        input,
        textarea
        select {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #d9e1dc;
            border-radius: 7px;
            font-size: 15px;
            font-family: inherit;
        }

        input:focus,
        textarea:focus
        select:focus {
            outline: 2px solid #a7dfbf;
            border-color: #0b9f5a;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            width: 100%;
        }

        input[type="file"] {
            background: #f8fbf9;
        }

        .help-text {
            display: block;
            margin-top: -12px;
            margin-bottom: 20px;
            color: #68776e;
            font-size: 13px;
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

    <h2>Add New Product</h2>
    <p class="subtitle">Enter the product details below.</p>

    <?php if ($message !== ''): ?>
        <div class="message <?= $message_type === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label for="name">Product Name</label>
        <input
            type="text"
            name="name"
            id="name"
            maxlength="255"
            required
        >

        <label for="price">Price (₹)</label>
        <input
            type="number"
            name="price"
            id="price"
            min="0.01"
            step="0.01"
            required
        >

        <label for="description">Description</label>
        <textarea
            name="description"
            id="description"
            required
        ></textarea>
        
        <label for="category">Product Category</label>
        <select name="category" id="category" required>
            <option value="">Select a category</option>
            <option value="Bottles">Bottles</option>
            <option value="Handbags">Handbags</option>
            <option value="Skincare">Skincare</option>
            <option value="Sneakers">Sneakers</option>
            <option value="T-Shirts">T-Shirts</option>
        </select>


        <label for="image">Product Image</label>
        <input
            type="file"
            name="image"
            id="image"
            accept=".jpg,.jpeg,.png,.webp"
            required
        >

        <span class="help-text">
            Accepted formats: JPG, PNG, WebP. Maximum size: 3 MB.
        </span>

        <button type="submit" name="add_product">
            Add Product
        </button>

    </form>

    <div class="links">
        <a href="manage_products.php">← Manage Products</a>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>

</main>

</body>
</html>
