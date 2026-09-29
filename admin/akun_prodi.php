<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pesan = '';

// ================================
// BUAT AKUN BARU / RESET PASSWORD
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_akun'])) {
    $kode_prodi = trim($_POST['kode_prodi']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($kode_prodi === '' || $username === '' || $password === '') {
        $pesan = 'Semua field wajib diisi.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah prodi ini sudah punya akun
        $cek = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE role='prodi' AND ref_id=?");
        mysqli_stmt_bind_param($cek, "s", $kode_prodi);
        mysqli_stmt_execute($cek);
        $ada = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        if ($ada) {
            // Update akun yang sudah ada (reset username & password)
            $stmt = mysqli_prepare($koneksi, "UPDATE users SET username=?, password=? WHERE role='prodi' AND ref_id=?");
            mysqli_stmt_bind_param($stmt, "sss", $username, $hash, $kode_prodi);
            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Akun prodi berhasil diperbarui.';
            } else {
                $pesan = 'Gagal memperbarui (username mungkin sudah dipakai).';
            }
            mysqli_stmt_close($stmt);
        } else {
            $role = 'prodi';
            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $username, $hash, $role, $kode_prodi);
            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Akun prodi berhasil dibuat.';
            } else {
                $pesan = 'Gagal membuat akun (username mungkin sudah dipakai).';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ================================
// HAPUS AKUN
// ================================
if (isset($_GET['hapus'])) {
    $stmt = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ? AND role = 'prodi'");
    mysqli_stmt_bind_param($stmt, "i", $_GET['hapus']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $pesan = 'Akun prodi berhasil dihapus.';
}

// ================================
// AMBIL DATA: SEMUA PRODI BESERTA STATUS AKUNNYA
// ================================
$data = mysqli_query($koneksi, "
    SELECT ps.kode_prodi, ps.nama_prodi, u.id_user, u.username
    FROM program_studi ps
    LEFT JOIN users u ON u.ref_id = ps.kode_prodi AND u.role = 'prodi'
    ORDER BY ps.nama_prodi
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Akun Prodi - Admin</title>
<style>
    :root { --primary: #1a56db; --primary-dark: #1544ab; --bg-light: #f9fafb; --border: #e5e7eb; --text-muted: #6b7280; --danger: #dc2626; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; background: var(--bg-light); display: flex; min-height: 100vh; }
    a { text-decoration: none; color: inherit; }
    .sidebar { width: 220px; background: #111827; color: #d1d5db; padding: 24px 0; flex-shrink: 0; }
    .sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; font-weight: 700; color: #fff; }
    .brand-icon { width: 34px; height: 34px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; }
    .sidebar nav a { display: block; padding: 12px 20px; font-size: 14px; }
    .sidebar nav a.active { background: var(--primary); color: #fff; }
    .sidebar nav a:hover:not(.active) { background: #1f2937; }
    .sidebar .logout { display: block; padding: 12px 20px; font-size: 14px; color: #f87171; margin-top: 16px; }
    .main { flex: 1; padding: 32px 40px; }
    .main h1 { margin: 0 0 20px; font-size: 24px; color: #111827; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; margin-bottom: 24px; overflow-x: auto; }
    .pesan { background: #eff6ff; color: var(--primary-dark); padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; min-width: 700px; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    th { color: var(--text-muted); font-weight: 600; font-size: 13px; }
    .badge-ada { color: #059669; font-weight: 600; font-size: 13px; }
    .badge-belum { color: var(--text-muted); font-size: 13px; }
    .aksi a, .aksi button { font-size: 13px; font-weight: 600; margin-right: 10px; border: none; background: none; cursor: pointer; padding: 0; }
    .aksi .buat, .aksi .reset { color: var(--primary); }
    .aksi .hapus { color: var(--danger); }
    .modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); align-items: center; justify-content: center; z-index: 10; }
    .modal-bg.show { display: flex; }
    .modal { background: #fff; border-radius: 10px; padding: 24px; width: 320px; }
    .modal h3 { margin: 0 0 16px; font-size: 16px; }
    .field { display: flex; flex-direction: column; margin-bottom: 14px; }
    .field label { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
    .field input { padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
    .btn { padding: 9px 16px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: var(--primary); color: #fff; width: 100%; }
    .btn-cancel { display: block; text-align: center; margin-top: 10px; color: var(--text-muted); font-size: 13px; cursor: pointer; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">US</div> Universitas Semantik</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="mahasiswa.php">Kelola Mahasiswa</a>
        <a href="prodi.php">Kelola Program Studi</a>
        <a href="fakultas.php">Kelola Fakultas</a>
        <a href="akun_prodi.php" class="active">Akun Prodi</a>
        <a href="import_mahasiswa.php">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Akun Login Prodi</h1>
    <?php if ($pesan): ?><div class="pesan"><?php echo htmlspecialchars($pesan); ?></div><?php endif; ?>

    <div class="panel">
        <table>
            <tr><th>Program Studi</th><th>Status Akun</th><th>Username</th><th>Aksi</th></tr>
            <?php if (mysqli_num_rows($data) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nama_prodi']); ?></td>
                        <td>
                            <?php echo $row['id_user'] ? '<span class="badge-ada">Sudah ada</span>' : '<span class="badge-belum">Belum ada</span>'; ?>
                        </td>
                        <td><?php echo $row['username'] ? htmlspecialchars($row['username']) : '-'; ?></td>
                        <td class="aksi">
                            <?php if ($row['id_user']): ?>
                                <button type="button" class="reset" onclick="bukaModal('<?php echo htmlspecialchars($row['kode_prodi']); ?>', '<?php echo htmlspecialchars($row['nama_prodi']); ?>', '<?php echo htmlspecialchars($row['username']); ?>')">Reset</button>
                                <a class="hapus" href="akun_prodi.php?hapus=<?php echo $row['id_user']; ?>" onclick="return confirm('Hapus akun login prodi ini?');">Hapus</a>
                            <?php else: ?>
                                <button type="button" class="buat" onclick="bukaModal('<?php echo htmlspecialchars($row['kode_prodi']); ?>', '<?php echo htmlspecialchars($row['nama_prodi']); ?>', '')">Buat Akun</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center; color:#6b7280;">Belum ada data program studi.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</main>

<div class="modal-bg" id="modalBg">
    <div class="modal">
        <h3 id="modalTitle">Buat Akun</h3>
        <form method="POST" action="akun_prodi.php">
            <input type="hidden" name="kode_prodi" id="modalKodeProdi">
            <div class="field">
                <label>Username</label>
                <input type="text" name="username" id="modalUsername" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="text" name="password" placeholder="Password baru" required>
            </div>
            <button type="submit" name="simpan_akun" class="btn btn-primary">Simpan</button>
        </form>
        <div class="btn-cancel" onclick="tutupModal()">Batal</div>
    </div>
</div>

<script>
function bukaModal(kode, namaProdi, usernameLama) {
    document.getElementById('modalKodeProdi').value = kode;
    document.getElementById('modalUsername').value = usernameLama;
    document.getElementById('modalTitle').innerText = 'Akun Login - ' + namaProdi;
    document.getElementById('modalBg').classList.add('show');
}
function tutupModal() {
    document.getElementById('modalBg').classList.remove('show');
}
</script>

</body>
</html>