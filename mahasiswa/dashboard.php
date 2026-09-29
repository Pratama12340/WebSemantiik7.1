<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$npm_saya = $_SESSION['ref_id'];
$pesan = '';
$pesan_error = false;

// ================================
// PROSES UPDATE DATA DIRI
// (NPM, tanggal_masuk, kode_prodi TIDAK BISA diubah mahasiswa)
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama_mahasiswa']);
    $jk = $_POST['jenis_kelamin'];
    $tempat_lahir = trim($_POST['tempat_lahir']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $alamat = trim($_POST['alamat']);

    if ($nama === '') {
        $pesan = 'Nama tidak boleh kosong.';
        $pesan_error = true;
    } else {
        $stmt = mysqli_prepare($koneksi, "UPDATE mahasiswa SET nama_mahasiswa=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, alamat=? WHERE npm=?");
        mysqli_stmt_bind_param($stmt, "ssssss", $nama, $jk, $tempat_lahir, $tanggal_lahir, $alamat, $npm_saya);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $pesan = 'Data diri berhasil diperbarui.';
    }
}

// ================================
// PROSES GANTI PASSWORD
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi = $_POST['konfirmasi_password'];

    $stmt = mysqli_prepare($koneksi, "SELECT password FROM users WHERE username = ? AND role = 'mahasiswa'");
    mysqli_stmt_bind_param($stmt, "s", $npm_saya);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$user || !password_verify($password_lama, $user['password'])) {
        $pesan = 'Password lama tidak sesuai.';
        $pesan_error = true;
    } elseif (strlen($password_baru) < 6) {
        $pesan = 'Password baru minimal 6 karakter.';
        $pesan_error = true;
    } elseif ($password_baru !== $konfirmasi) {
        $pesan = 'Konfirmasi password baru tidak cocok.';
        $pesan_error = true;
    } else {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $stmt2 = mysqli_prepare($koneksi, "UPDATE users SET password = ? WHERE username = ? AND role = 'mahasiswa'");
        mysqli_stmt_bind_param($stmt2, "ss", $hash, $npm_saya);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        $pesan = 'Password berhasil diganti.';
    }
}

// ================================
// AMBIL DATA PROFIL MAHASISWA (data terbaru setelah update)
// ================================
$stmt3 = mysqli_prepare($koneksi, "
    SELECT m.*, ps.nama_prodi, f.nama_fakultas
    FROM mahasiswa m
    JOIN program_studi ps ON ps.kode_prodi = m.kode_prodi
    JOIN fakultas f ON f.kode_fakultas = ps.kode_fakultas
    WHERE m.npm = ?
");
mysqli_stmt_bind_param($stmt3, "s", $npm_saya);
mysqli_stmt_execute($stmt3);
$profil = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3));
mysqli_stmt_close($stmt3);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Mahasiswa - Universitas Semantik</title>
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
    .sidebar .logout { display: block; padding: 12px 20px; font-size: 14px; color: #f87171; margin-top: 16px; }
    .main { flex: 1; padding: 32px 40px; max-width: 700px; }
    .main h1 { margin: 0 0 20px; font-size: 24px; color: #111827; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; margin-bottom: 24px; }
    .panel h2 { font-size: 16px; margin: 0 0 16px; }
    .pesan { padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
    .pesan.ok { background: #ecfdf5; color: #047857; }
    .pesan.err { background: #fde8e8; color: #c81e1e; }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
    .field { display: flex; flex-direction: column; }
    .field label { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
    .field input, .field select, .field textarea { padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: inherit; }
    .field input[readonly], .field input[disabled] { background: #f3f4f6; color: #6b7280; }
    .field.full { grid-column: 1 / -1; }
    .hint { font-size: 12px; color: var(--text-muted); grid-column: 1 / -1; margin-top: -6px; margin-bottom: 4px; }
    .form-actions { grid-column: 1 / -1; margin-top: 4px; }

    .btn { padding: 10px 18px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; background: var(--primary); color: #fff; }
    .btn:hover { background: var(--primary-dark); }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">US</div> Universitas Semantik</div>
    <nav>
        <a href="dashboard.php" class="active">Profil Saya</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Profil Mahasiswa</h1>

    <?php if ($pesan): ?>
        <div class="pesan <?php echo $pesan_error ? 'err' : 'ok'; ?>"><?php echo htmlspecialchars($pesan); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>Data Diri</h2>
        <?php if ($profil): ?>
        <form class="form-grid" method="POST" action="dashboard.php">
            <div class="field">
                <label>NPM</label>
                <input type="text" value="<?php echo htmlspecialchars($profil['npm']); ?>" readonly>
            </div>
            <div class="field">
                <label>Nama</label>
                <input type="text" name="nama_mahasiswa" value="<?php echo htmlspecialchars($profil['nama_mahasiswa']); ?>" required>
            </div>
            <div class="field">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="L" <?php echo $profil['jenis_kelamin'] === 'L' ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?php echo $profil['jenis_kelamin'] === 'P' ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="field">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($profil['tempat_lahir']); ?>">
            </div>
            <div class="field">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($profil['tanggal_lahir']); ?>">
            </div>
            <div class="field">
                <label>Tanggal Masuk</label>
                <input type="text" value="<?php echo htmlspecialchars($profil['tanggal_masuk']); ?>" readonly>
            </div>
            <div class="field">
                <label>Program Studi</label>
                <input type="text" value="<?php echo htmlspecialchars($profil['nama_prodi']); ?>" readonly>
            </div>
            <div class="field">
                <label>Fakultas</label>
                <input type="text" value="<?php echo htmlspecialchars($profil['nama_fakultas']); ?>" readonly>
            </div>
            <div class="field full">
                <label>Alamat</label>
                <textarea name="alamat" rows="2"><?php echo htmlspecialchars($profil['alamat']); ?></textarea>
            </div>
            <div class="hint">NPM, Tanggal Masuk, dan Program Studi tidak dapat diubah sendiri. Hubungi admin/prodi jika perlu koreksi.</div>
            <div class="form-actions">
                <button type="submit" name="update_profil" class="btn">Simpan Perubahan</button>
            </div>
        </form>
        <?php else: ?>
            <p>Data profil tidak ditemukan.</p>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Ganti Password</h2>
        <form method="POST" action="dashboard.php">
            <div class="field">
                <label>Password Lama</label>
                <input type="password" name="password_lama" required>
            </div>
            <div class="field" style="margin-top:14px;">
                <label>Password Baru</label>
                <input type="password" name="password_baru" minlength="6" required>
            </div>
            <div class="field" style="margin-top:14px; margin-bottom:16px;">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="konfirmasi_password" minlength="6" required>
            </div>
            <button type="submit" name="ganti_password" class="btn">Ganti Password</button>
        </form>
    </div>
</main>

</body>
</html>