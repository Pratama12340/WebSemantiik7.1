<?php
include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT id_user, username, password, role, ref_id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ref_id'] = $user['ref_id'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } elseif ($user['role'] === 'prodi') {
                header('Location: prodi/dashboard.php');
                exit;
            } elseif ($user['role'] === 'mahasiswa') {
                header('Location: mahasiswa/dashboard.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Universitas Semantik</title>
    <style>
        :root { --primary: #1a56db; --primary-dark: #1544ab; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(180deg, #eff6ff 0%, #f9fafb 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-box {
            background: #fff;
            padding: 32px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            width: 340px;
        }
        .brand { text-align: center; margin-bottom: 20px; }
        .brand-icon { width: 44px; height: 44px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; margin: 0 auto 8px; }
        h2 { text-align: center; color: var(--primary-dark); margin: 0 0 24px; }
        label { display: block; margin-bottom: 6px; font-size: 14px; color: #333; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px; margin-bottom: 16px;
            border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box;
        }
        button {
            width: 100%; padding: 11px; background: var(--primary); color: #fff;
            border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600;
        }
        button:hover { background: var(--primary-dark); }
        .error { background: #fde8e8; color: #c81e1e; padding: 10px; border-radius: 6px; font-size: 14px; margin-bottom: 16px; }
        .back-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #6b7280; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="brand"><div class="brand-icon">US</div></div>
        <h2>Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
        <a href="index.php" class="back-link">&larr; Kembali ke Beranda</a>
    </div>
</body>
</html>