
<?php
session_start();

require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Find a user with the admin role
            $stmt = $conn->prepare(
                "SELECT id, email, password, role
                 FROM users
                 WHERE email = ? AND role = 'admin'
                 LIMIT 1"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];

                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid credentials or you are not an admin.';
            }
        } catch (PDOException $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | ShopEase</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #e8f8ef, #f4f7fa);
        }

        .login-container {
            width: 90%;
            max-width: 400px;
            padding: 35px;
            background: white;
            border-radius: 14px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h2 {
            margin-top: 0;
            text-align: center;
            color: #173b2c;
        }

        .subtitle {
            text-align: center;
            color: #777;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #d9e1dc;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #a7dfbf;
            border-color: #0b9f5a;
        }

        button {
            width: 100%;
            padding: 12px;
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

        .error {
            padding: 10px;
            margin-bottom: 18px;
            border-radius: 6px;
            background: #fff0f0;
            color: #b42318;
            font-size: 14px;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #087d47;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <p class="subtitle">Sign in to manage ShopEase</p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit" name="login">Login</button>
        </form>

        <a class="back-link" href="../index.php">
            Back to ShopEase
        </a>
    </div>
</body>
</html>
