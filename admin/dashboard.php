<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM mahasiswa"))['jumlah'];
$total_prodi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM program_studi"))['jumlah'];
$total_fakultas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM fakultas"))['jumlah'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin - Universitas Semantik</title>
<style>
    :root { --primary: #1a56db; --primary-dark: #1544ab; --bg-light: #f9fafb; --border: #e5e7eb; --text-muted: #6b7280; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg-light); display: flex; min-height: 100vh; }
    a { text-decoration: none; color: inherit; }

    /* Sidebar */
    .sidebar { width: 220px; background: #111827; color: #d1d5db; padding: 24px 0; flex-shrink: 0; }
    .sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; font-weight: 700; color: #fff; }
    .brand-icon { width: 34px; height: 34px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; }
    .sidebar nav a { display: block; padding: 12px 20px; font-size: 14px; }
    .sidebar nav a.active { background: var(--primary); color: #fff; }
    .sidebar nav a:hover:not(.active) { background: #1f2937; }
    .sidebar .logout { display: block; padding: 12px 20px; font-size: 14px; color: #f87171; margin-top: 16px; }

    /* Main content */
    .main { flex: 1; padding: 32px 40px; }
    .main h1 { margin: 0 0 4px; font-size: 24px; color: #111827; }
    .main .welcome { color: var(--text-muted); margin-bottom: 28px; font-size: 14px; }

    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
    .card .num { font-size: 28px; font-weight: 700; color: var(--primary-dark); }
    .card .label { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

    .panel { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; color: var(--text-muted); font-size: 14px; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">US</div> Universitas Semantik</div>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="mahasiswa.php">Kelola Mahasiswa</a>
        <a href="prodi.php">Kelola Program Studi</a>
        <a href="fakultas.php">Kelola Fakultas</a>
        <a href="akun_prodi.php">Akun Prodi</a>
        <a href="import_mahasiswa.php">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Dashboard Admin</h1>
    <div class="welcome">Selamat datang, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>

    <div class="cards">
        <div class="card">
            <div class="num"><?php echo $total_mahasiswa; ?></div>
            <div class="label">Total Mahasiswa</div>
        </div>
        <div class="card">
            <div class="num"><?php echo $total_prodi; ?></div>
            <div class="label">Program Studi</div>
        </div>
        <div class="card">
            <div class="num"><?php echo $total_fakultas; ?></div>
            <div class="label">Fakultas</div>
        </div>
    </div>

    <div class="panel">
        Menu pengelolaan data (Kelola Mahasiswa, Program Studi, Fakultas) akan ditambahkan pada tahap berikutnya.
    </div>
</main>

</body>
</html>