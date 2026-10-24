
<?php
session_start();
require_once('../includes/db.php');

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($password === '') {
        $error_message = 'Please enter your password.';
    } else {
        try {
            // Find the user by email
            $stmt = $conn->prepare(
                "SELECT id, email, password, role
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify the password against the stored hash
            if ($user && password_verify($password, $user['password'])) {

                // Prevent session fixation
                session_regenerate_id(true);

                // Store user information in the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                // Redirect to the homepage
                header('Location: ../index.php');
                exit();

            } else {
                $error_message = 'Invalid email or password.';
            }

        } catch (PDOException $e) {
            // Keep technical database details out of the browser
            error_log('Login database error: ' . $e->getMessage());

            $error_message = 'Unable to log in right now. Please try again later.';
        }
    }
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ShopEase</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px;
            background: linear-gradient(135deg, #e0f2e9, #e8eaff, #fdf2f8);
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            width: 100%;
            max-width: 440px;
            padding: 42px 38px;
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 22px;
            box-shadow: 0 20px 60px rgba(35, 48, 65, 0.12);
        }

        .logo {
            width: 58px;
            height: 58px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 18px;
            background: linear-gradient(135deg, #16a34a, #059669);
            color: white;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.22);
        }

        h2 {
            text-align: center;
            color: #172b24;
            font-size: 29px;
            font-weight: 700;
            letter-spacing: -0.7px;
            margin-bottom: 9px;
        }

        .subtitle {
            text-align: center;
            color: #78847e;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            color: #34463d;
            font-size: 14px;
            font-weight: 600;
        }

        input[type="email"],
        input[type="password"] {
            display: block;
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #dce5df;
            border-radius: 11px;
            background-color: #fbfdfb;
            color: #24352b;
            font-size: 15px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }

        input::placeholder {
            color: #a1aaa4;
        }

        input:focus {
            border-color: #16a34a;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.10);
        }

        button {
            display: block;
            width: 100%;
            padding: 15px;
            margin-top: 8px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, #16a34a, #059669);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 7px 16px rgba(22, 163, 74, 0.20);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(22, 163, 74, 0.28);
        }

        button:active {
            transform: translateY(0);
        }

        .error-message {
            margin-top: 18px;
            padding: 12px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 13px;
            line-height: 1.5;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .register-text {
            margin-top: 25px;
            text-align: center;
            color: #78847e;
            font-size: 14px;
        }

        .register-text a {
            color: #15803d;
            font-weight: 700;
            text-decoration: none;
        }

        .register-text a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .login-container {
                padding: 32px 24px;
                border-radius: 18px;
            }

            h2 {
                font-size: 26px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">

        <div class="logo">S</div>

        <h2>Welcome Back</h2>
        <p class="subtitle">
            Log in to your ShopEase account and continue shopping.
        </p>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" name="login">
                Log In &rarr;
            </button>
        </form>

        <?php if (!empty($error_message)): ?>
            <p class="error-message">
                <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <p class="register-text">
            Don't have an account?
            <a href="register.php">Create an account</a>
        </p>

    </div>
</body>
</html>
