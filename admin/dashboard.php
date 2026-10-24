
<?php
session_start();

// Allow only logged-in admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_email = $_SESSION['admin_email'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | ShopEase</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f8ef, #f4f7fa);
            color: #24352c;
            min-height: 100vh;
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
            font-size: 28px;
        }

        header a {
            text-decoration: none;
            color: #087d47;
            font-weight: bold;
        }

        .container {
            max-width: 900px;
            width: 90%;
            margin: 70px auto;
            padding: 40px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
        }

        h2 {
            text-align: center;
            color: #173b2c;
            margin-top: 0;
        }

        .welcome {
            text-align: center;
            color: #68776e;
            margin-bottom: 35px;
        }

        nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }

        nav a {
            display: block;
            padding: 18px;
            text-align: center;
            text-decoration: none;
            background: #0b9f5a;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            transition: background 0.2s;
        }

        nav a:hover {
            background: #087d47;
        }

        nav .logout {
            background: #d94848;
        }

        nav .logout:hover {
            background: #b83232;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #68776e;
        }

        @media (max-width: 500px) {
            .container {
                padding: 25px 18px;
                margin: 35px auto;
            }

            header h1 {
                font-size: 22px;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>ShopEase Admin</h1>
        <a href="../index.php">View Store</a>
    </header>

    <main class="container">
        <h2>Admin Dashboard</h2>

        <p class="welcome">
            Welcome, <?= htmlspecialchars($admin_email, ENT_QUOTES, 'UTF-8') ?>!
            Manage your ShopEase store from here.
        </p>

        <nav>
            <a href="add_product.php">Add Product</a>

            <a href="manage_products.php">Manage Products</a>

            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </main>

    <footer>
        &copy; <?= date('Y') ?> ShopEase. All rights reserved.
    </footer>

</body>
</html>
