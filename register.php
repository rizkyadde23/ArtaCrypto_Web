<?php
include 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
        header("Location: login.php");
    } else {
        $error = "Gagal registrasi, mungkin email sudah terdaftar.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register | ArtaCrypto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0f1115;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            color: #fff;
        }

        .register-card {
            background: #1a1d24cc;
            padding: 40px;
            border-radius: 18px;
            width: 100%;
            max-width: 380px;
            backdrop-filter: blur(8px);

            /* Glow */
            box-shadow:
                0 0 18px rgba(75, 139, 255, 0.40),
                0 0 35px rgba(75, 139, 255, 0.25),
                inset 0 0 10px rgba(255, 255, 255, 0.05);

            border: 1px solid rgba(75, 139, 255, 0.35);
            transition: 0.35s ease;
        }

        .register-card:hover {
            transform: translateY(-4px);
            box-shadow:
                0 0 25px rgba(75, 139, 255, 0.55),
                0 0 55px rgba(75, 139, 255, 0.35),
                inset 0 0 12px rgba(255, 255, 255, 0.07);
        }

        .register-title {
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

    <div class="register-card">
        <h3 class="register-title">Buat Akun</h3>

        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-4" placeholder="Password" required>

            <button type="submit" class="btn btn-primary w-100">Register</button>

            <p class="text-center mt-3">
                Sudah punya akun? <a href="login.php">Login</a>
            </p>
        </form>
    </div>

</body>

</html>
