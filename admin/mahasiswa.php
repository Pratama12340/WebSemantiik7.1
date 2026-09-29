<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pesan = '';
$edit_data = null;

// ================================
// PROSES SIMPAN (TAMBAH / EDIT)
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $npm = trim($_POST['npm']);
    $nama = trim($_POST['nama_mahasiswa']);
    $jk = $_POST['jenis_kelamin'];
    $tempat_lahir = trim($_POST['tempat_lahir']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $alamat = trim($_POST['alamat']);
    $kode_prodi = trim($_POST['kode_prodi']);
    $password_baru = trim($_POST['password']);
    $mode = $_POST['mode'];

    if ($npm === '' || $nama === '' || $kode_prodi === '') {
        $pesan = 'NPM, Nama, dan Program Studi wajib diisi.';
    } else {
        if ($mode === 'tambah') {
            if ($password_baru === '') {
                $pesan = 'Password wajib diisi untuk mahasiswa baru (dipakai untuk akun login).';
            } else {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO mahasiswa (npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, kode_prodi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssssssss", $npm, $nama, $jk, $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat, $kode_prodi);

                if (mysqli_stmt_execute($stmt)) {
                    // Buat akun login otomatis di tabel users
                    $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    $role = 'mahasiswa';
                    $stmt2 = mysqli_prepare($koneksi, "INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt2, "ssss", $npm, $hash, $role, $npm);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);

                    $pesan = 'Mahasiswa berhasil ditambahkan beserta akun login (username: ' . htmlspecialchars($npm) . ').';
                } else {
                    $pesan = 'Gagal menambahkan (NPM mungkin sudah terdaftar).';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // mode edit
            $npm_asli = trim($_POST['npm_asli']);
            $stmt = mysqli_prepare($koneksi, "UPDATE mahasiswa SET nama_mahasiswa=?, jenis_kelamin=?, tempat_lahir=?, tanggal_lahir=?, tanggal_masuk=?, alamat=?, kode_prodi=? WHERE npm=?");
            mysqli_stmt_bind_param($stmt, "ssssssss", $nama, $jk, $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat, $kode_prodi, $npm_asli);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Kalau admin isi password baru, update juga password login-nya
            if ($password_baru !== '') {
                $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt3 = mysqli_prepare($koneksi, "UPDATE users SET password=? WHERE username=? AND role='mahasiswa'");
                mysqli_stmt_bind_param($stmt3, "ss", $hash, $npm_asli);
                mysqli_stmt_execute($stmt3);
                mysqli_stmt_close($stmt3);
            }
            $pesan = 'Data mahasiswa berhasil diperbarui.';
        }
    }
}

// ================================
// PROSES HAPUS (mahasiswa + akun login-nya)
// ================================
if (isset($_GET['hapus'])) {
    $npm = $_GET['hapus'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM mahasiswa WHERE npm = ?");
    mysqli_stmt_bind_param($stmt, "s", $npm);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM users WHERE username = ? AND role = 'mahasiswa'");
    mysqli_stmt_bind_param($stmt2, "s", $npm);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    $pesan = 'Mahasiswa dan akun login-nya berhasil dihapus.';
}

// ================================
// AMBIL DATA UNTUK MODE EDIT
// ================================
if (isset($_GET['edit'])) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM mahasiswa WHERE npm = ?");
    mysqli_stmt_bind_param($stmt, "s", $_GET['edit']);
    mysqli_stmt_execute($stmt);
    $edit_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// ================================
// AMBIL DATA UNTUK DROPDOWN & TABEL
// ================================
$semua_prodi = mysqli_query($koneksi, "SELECT * FROM program_studi ORDER BY nama_prodi");

$semua_mahasiswa = mysqli_query($koneksi, "
    SELECT m.*, ps.nama_prodi
    FROM mahasiswa m
    JOIN program_studi ps ON ps.kode_prodi = m.kode_prodi
    ORDER BY m.nama_mahasiswa
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Mahasiswa - Admin</title>
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
    .panel h2 { font-size: 16px; margin: 0 0 16px; }

    .pesan { background: #eff6ff; color: var(--primary-dark); padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }
    .warn { background: #fef3c7; color: #92400e; padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }

    form.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
    .field { display: flex; flex-direction: column; }
    .field label { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
    .field input, .field select, .field textarea { padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: inherit; }
    .field.full { grid-column: 1 / -1; }
    .form-actions { grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 4px; }
    .btn { padding: 10px 18px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-cancel { background: #e5e7eb; color: #374151; display: flex; align-items: center; padding: 0 18px; }
    .hint { font-size: 12px; color: var(--text-muted); grid-column: 1 / -1; margin-top: -6px; }

    table { width: 100%; border-collapse: collapse; min-width: 900px; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 13px; white-space: nowrap; }
    th { color: var(--text-muted); font-weight: 600; font-size: 12px; }
    .aksi a { margin-right: 12px; font-size: 13px; font-weight: 600; }
    .aksi .edit { color: var(--primary); }
    .aksi .hapus { color: var(--danger); }
    .kosong { color: var(--text-muted); text-align: center; padding: 20px; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="brand"><div class="brand-icon">US</div> Universitas Semantik</div>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="mahasiswa.php" class="active">Kelola Mahasiswa</a>
        <a href="prodi.php">Kelola Program Studi</a>
        <a href="fakultas.php">Kelola Fakultas</a>
        <a href="akun_prodi.php">Akun Prodi</a>
        <a href="import_mahasiswa.php">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Kelola Mahasiswa</h1>

    <?php if (mysqli_num_rows($semua_prodi) === 0): ?>
        <div class="warn">Belum ada data program studi. Tambahkan program studi terlebih dahulu sebelum menambah mahasiswa.</div>
    <?php endif; ?>

    <?php if ($pesan): ?>
        <div class="pesan"><?php echo htmlspecialchars($pesan); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2><?php echo $edit_data ? 'Edit Data Mahasiswa' : 'Tambah Mahasiswa Baru'; ?></h2>
        <form class="form-grid" method="POST" action="mahasiswa.php">
            <input type="hidden" name="mode" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
            <?php if ($edit_data): ?>
                <input type="hidden" name="npm_asli" value="<?php echo htmlspecialchars($edit_data['npm']); ?>">
            <?php endif; ?>

            <div class="field">
                <label>NPM</label>
                <input type="text" name="npm" maxlength="20"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['npm']) : ''; ?>"
                    <?php echo $edit_data ? 'readonly style="background:#f3f4f6"' : ''; ?> required>
            </div>
            <div class="field">
                <label>Nama Mahasiswa</label>
                <input type="text" name="nama_mahasiswa" maxlength="100"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['nama_mahasiswa']) : ''; ?>" required>
            </div>
            <div class="field">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin" required>
                    <option value="L" <?php echo ($edit_data && $edit_data['jenis_kelamin'] === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                    <option value="P" <?php echo ($edit_data && $edit_data['jenis_kelamin'] === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                </select>
            </div>
            <div class="field">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir" maxlength="100"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['tempat_lahir']) : ''; ?>">
            </div>
            <div class="field">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['tanggal_lahir']) : ''; ?>">
            </div>
            <div class="field">
                <label>Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['tanggal_masuk']) : ''; ?>">
            </div>
            <div class="field">
                <label>Program Studi</label>
                <select name="kode_prodi" required>
                    <option value="">-- Pilih Prodi --</option>
                    <?php mysqli_data_seek($semua_prodi, 0); ?>
                    <?php while ($p = mysqli_fetch_assoc($semua_prodi)): ?>
                        <option value="<?php echo htmlspecialchars($p['kode_prodi']); ?>"
                            <?php echo ($edit_data && $edit_data['kode_prodi'] === $p['kode_prodi']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['nama_prodi']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="field">
                <label><?php echo $edit_data ? 'Reset Password (opsional)' : 'Password Login'; ?></label>
                <input type="text" name="password" placeholder="<?php echo $edit_data ? 'Kosongkan jika tidak diubah' : 'Untuk akun login mahasiswa'; ?>">
            </div>
            <div class="field full">
                <label>Alamat</label>
                <textarea name="alamat" rows="2"><?php echo $edit_data ? htmlspecialchars($edit_data['alamat']) : ''; ?></textarea>
            </div>
            <div class="hint">Username login mahasiswa otomatis mengikuti NPM.</div>

            <div class="form-actions">
                <button type="submit" name="simpan" class="btn btn-primary"><?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah Mahasiswa'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="mahasiswa.php" class="btn btn-cancel">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="panel">
        <h2>Daftar Mahasiswa</h2>
        <table>
            <tr>
                <th>NPM</th>
                <th>Nama</th>
                <th>JK</th>
                <th>Tempat, Tgl Lahir</th>
                <th>Tgl Masuk</th>
                <th>Program Studi</th>
                <th>Aksi</th>
            </tr>
            <?php if (mysqli_num_rows($semua_mahasiswa) > 0): ?>
                <?php while ($m = mysqli_fetch_assoc($semua_mahasiswa)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['npm']); ?></td>
                        <td><?php echo htmlspecialchars($m['nama_mahasiswa']); ?></td>
                        <td><?php echo htmlspecialchars($m['jenis_kelamin']); ?></td>
                        <td><?php echo htmlspecialchars($m['tempat_lahir']) . ', ' . htmlspecialchars($m['tanggal_lahir']); ?></td>
                        <td><?php echo htmlspecialchars($m['tanggal_masuk']); ?></td>
                        <td><?php echo htmlspecialchars($m['nama_prodi']); ?></td>
                        <td class="aksi">
                            <a class="edit" href="mahasiswa.php?edit=<?php echo urlencode($m['npm']); ?>">Edit</a>
                            <a class="hapus" href="mahasiswa.php?hapus=<?php echo urlencode($m['npm']); ?>"
                                onclick="return confirm('Yakin ingin menghapus mahasiswa ini beserta akun loginnya?');">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="kosong">Belum ada data mahasiswa.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</main>

</body>
</html>