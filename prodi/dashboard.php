<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'prodi') {
    header('Location: ../login.php');
    exit;
}

$kode_prodi = $_SESSION['ref_id'];

$stmt = mysqli_prepare($koneksi, "SELECT nama_prodi FROM program_studi WHERE kode_prodi = ?");
mysqli_stmt_bind_param($stmt, "s", $kode_prodi);
mysqli_stmt_execute($stmt);
$prodi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt2 = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jumlah FROM mahasiswa WHERE kode_prodi = ?");
mysqli_stmt_bind_param($stmt2, "s", $kode_prodi);
mysqli_stmt_execute($stmt2);
$total_mahasiswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2))['jumlah'];
mysqli_stmt_close($stmt2);

$stmt3 = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jumlah FROM mahasiswa WHERE kode_prodi = ? AND jenis_kelamin = 'L'");
mysqli_stmt_bind_param($stmt3, "s", $kode_prodi);
mysqli_stmt_execute($stmt3);
$total_l = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3))['jumlah'];
mysqli_stmt_close($stmt3);

$total_p = $total_mahasiswa - $total_l;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Prodi - Universitas Semantik</title>
<style>
    :root { --primary: #1a56db; --primary-dark: #1544ab; --bg-light: #f9fafb; --border: #e5e7eb; --text-muted: #6b7280; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg-light); display: flex; min-height: 100vh; }
    a { text-decoration: none; color: inherit; }
    .sidebar { width: 220px; background: #111827; color: #d1d5db; padding: 24px 0; flex-shrink: 0; }
    .sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; font-weight: 700; color: #fff; font-size: 15px; }
    .brand-icon { width: 34px; height: 34px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; flex-shrink: 0; }
    .sidebar nav a { display: block; padding: 12px 20px; font-size: 14px; }
    .sidebar nav a.active { background: var(--primary); color: #fff; }
    .sidebar nav a:hover:not(.active) { background: #1f2937; }
    .sidebar .logout { display: block; padding: 12px 20px; font-size: 14px; color: #f87171; margin-top: 16px; }
    .main { flex: 1; padding: 32px 40px; }
    .main h1 { margin: 0 0 4px; font-size: 24px; color: #111827; }
    .main .welcome { color: var(--text-muted); margin-bottom: 28px; font-size: 14px; }
    .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    .card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 20px; }
    .card .num { font-size: 28px; font-weight: 700; color: var(--primary-dark); }
    .card .label { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">US</div> Universitas Semantik</div>
    <nav>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="mahasiswa.php">Kelola Mahasiswa</a>
        <a href="import_mahasiswa.php">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Dashboard Prodi</h1>
    <div class="welcome">
        <?php echo htmlspecialchars($prodi['nama_prodi'] ?? '-'); ?> &middot;
        Login sebagai <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
    </div>

    <div class="cards">
        <div class="card">
            <div class="num"><?php echo $total_mahasiswa; ?></div>
            <div class="label">Total Mahasiswa</div>
        </div>
        <div class="card">
            <div class="num"><?php echo $total_l; ?></div>
            <div class="label">Mahasiswa Laki-laki</div>
        </div>
        <div class="card">
            <div class="num"><?php echo $total_p; ?></div>
            <div class="label">Mahasiswa Perempuan</div>
        </div>
    </div>
</main>

</body>
</html>