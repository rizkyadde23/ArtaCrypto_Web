<?php
include 'connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header("Location: index.php");
        exit();
    } else {
        $error = "Email atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | ArtaCrypto</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #0f1115;
            font-family: 'Inter', sans-serif;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* CARD WITH GLOW */
        .login-card {
            background: #1a1d24cc;
            padding: 40px;
            border-radius: 18px;
            width: 100%;
            max-width: 360px;
            backdrop-filter: blur(8px);

            /* Glowing Border */
            box-shadow:
                0 0 18px rgba(75, 139, 255, 0.40),
                0 0 35px rgba(75, 139, 255, 0.25),
                inset 0 0 10px rgba(255, 255, 255, 0.05);

            border: 1px solid rgba(75, 139, 255, 0.35);

            transition: 0.35s ease;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 0 25px rgba(75, 139, 255, 0.55),
                0 0 55px rgba(75, 139, 255, 0.35),
                inset 0 0 12px rgba(255, 255, 255, 0.07);
        }

        .login-title {
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 30px;
            color: #e7eaff;
        }

        input.form-control {
            background: #111317;
            border: 1px solid #2a2d35;
            height: 48px;
            color: #fff;
            border-radius: 10px;
            transition: 0.2s;
        }

        input.form-control:focus {
            background: #111317;
            border-color: #4b8bff;
            box-shadow: 0 0 8px rgba(75, 139, 255, 0.35);
            color: #fff;
        }

        .btn-primary {
            background: #4b8bff;
            border: none;
            height: 48px;
            font-weight: 600;
            border-radius: 10px;
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: #3b76dc;
            box-shadow: 0 0 12px rgba(75, 139, 255, 0.5);
        }

        p {
            color: #9fa5b3;
        }

        a {
            color: #4b8bff;
            text-decoration: none;
        }

        a:hover {
            color: #76a7ff;
        }
    </style>


</head>

<body>
    <body>
    <div class="login-card">
        <h3 class="login-title">ArtaCrypto</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
            <button type="submit" class="btn btn-primary w-100">Masuk</button>

            <p class="text-center mt-3">
                Belum punya akun? <a href="register.php">Daftar</a>
            </p>
        </form>
    </div>
</body>
</html>
