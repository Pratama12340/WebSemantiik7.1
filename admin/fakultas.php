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
    $kode_fakultas = trim($_POST['kode_fakultas']);
    $nama_fakultas = trim($_POST['nama_fakultas']);
    $mode = $_POST['mode'];

    if ($kode_fakultas === '' || $nama_fakultas === '') {
        $pesan = 'Kode dan nama fakultas wajib diisi.';
    } else {
        if ($mode === 'tambah') {
            $stmt = mysqli_prepare($koneksi, "INSERT INTO fakultas (kode_fakultas, nama_fakultas) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $kode_fakultas, $nama_fakultas);
            if (mysqli_stmt_execute($stmt)) {
                $pesan = 'Fakultas berhasil ditambahkan.';
            } else {
                $pesan = 'Gagal menambahkan (kode fakultas mungkin sudah dipakai).';
            }
            mysqli_stmt_close($stmt);
        } else {
            // mode edit: kode_fakultas tidak diubah (primary key), hanya nama
            $kode_asli = trim($_POST['kode_asli']);
            $stmt = mysqli_prepare($koneksi, "UPDATE fakultas SET nama_fakultas = ? WHERE kode_fakultas = ?");
            mysqli_stmt_bind_param($stmt, "ss", $nama_fakultas, $kode_asli);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $pesan = 'Fakultas berhasil diperbarui.';
        }
    }
}

// ================================
// PROSES HAPUS
// ================================
if (isset($_GET['hapus'])) {
    $kode = $_GET['hapus'];
    $cek = mysqli_prepare($koneksi, "SELECT COUNT(*) AS jumlah FROM program_studi WHERE kode_fakultas = ?");
    mysqli_stmt_bind_param($cek, "s", $kode);
    mysqli_stmt_execute($cek);
    $jumlah_prodi = mysqli_fetch_assoc(mysqli_stmt_get_result($cek))['jumlah'];
    mysqli_stmt_close($cek);

    if ($jumlah_prodi > 0) {
        $pesan = "Tidak bisa menghapus: fakultas ini masih memiliki $jumlah_prodi program studi.";
    } else {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM fakultas WHERE kode_fakultas = ?");
        mysqli_stmt_bind_param($stmt, "s", $kode);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $pesan = 'Fakultas berhasil dihapus.';
    }
}

// ================================
// AMBIL DATA UNTUK MODE EDIT
// ================================
if (isset($_GET['edit'])) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM fakultas WHERE kode_fakultas = ?");
    mysqli_stmt_bind_param($stmt, "s", $_GET['edit']);
    mysqli_stmt_execute($stmt);
    $edit_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

// ================================
// AMBIL SEMUA DATA FAKULTAS
// ================================
$semua_fakultas = mysqli_query($koneksi, "SELECT * FROM fakultas ORDER BY nama_fakultas");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Fakultas - Admin</title>
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

    .panel { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; margin-bottom: 24px; }
    .panel h2 { font-size: 16px; margin: 0 0 16px; }

    .pesan { background: #eff6ff; color: var(--primary-dark); padding: 10px 14px; border-radius: 6px; font-size: 14px; margin-bottom: 20px; }

    form.form-inline { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
    .field { display: flex; flex-direction: column; }
    .field label { font-size: 13px; color: var(--text-muted); margin-bottom: 4px; }
    .field input { padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
    .btn { padding: 10px 18px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-dark); }
    .btn-cancel { background: #e5e7eb; color: #374151; }

    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
    th { color: var(--text-muted); font-weight: 600; font-size: 13px; }
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
        <a href="mahasiswa.php">Kelola Mahasiswa</a>
        <a href="prodi.php">Kelola Program Studi</a>
        <a href="fakultas.php" class="active">Kelola Fakultas</a>
        <a href="akun_prodi.php">Akun Prodi</a>
        <a href="import_mahasiswa.php">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Kelola Fakultas</h1>

    <?php if ($pesan): ?>
        <div class="pesan"><?php echo htmlspecialchars($pesan); ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2><?php echo $edit_data ? 'Edit Fakultas' : 'Tambah Fakultas Baru'; ?></h2>
        <form class="form-inline" method="POST" action="fakultas.php">
            <input type="hidden" name="mode" value="<?php echo $edit_data ? 'edit' : 'tambah'; ?>">
            <?php if ($edit_data): ?>
                <input type="hidden" name="kode_asli" value="<?php echo htmlspecialchars($edit_data['kode_fakultas']); ?>">
            <?php endif; ?>

            <div class="field">
                <label>Kode Fakultas</label>
                <input type="text" name="kode_fakultas" maxlength="10"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['kode_fakultas']) : ''; ?>"
                    <?php echo $edit_data ? 'readonly style="background:#f3f4f6"' : ''; ?> required>
            </div>
            <div class="field">
                <label>Nama Fakultas</label>
                <input type="text" name="nama_fakultas" maxlength="100"
                    value="<?php echo $edit_data ? htmlspecialchars($edit_data['nama_fakultas']) : ''; ?>" required>
            </div>
            <button type="submit" name="simpan" class="btn btn-primary"><?php echo $edit_data ? 'Simpan Perubahan' : 'Tambah'; ?></button>
            <?php if ($edit_data): ?>
                <a href="fakultas.php" class="btn btn-cancel">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="panel">
        <h2>Daftar Fakultas</h2>
        <table>
            <tr>
                <th>Kode</th>
                <th>Nama Fakultas</th>
                <th>Aksi</th>
            </tr>
            <?php if (mysqli_num_rows($semua_fakultas) > 0): ?>
                <?php while ($f = mysqli_fetch_assoc($semua_fakultas)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['kode_fakultas']); ?></td>
                        <td><?php echo htmlspecialchars($f['nama_fakultas']); ?></td>
                        <td class="aksi">
                            <a class="edit" href="fakultas.php?edit=<?php echo urlencode($f['kode_fakultas']); ?>">Edit</a>
                            <a class="hapus" href="fakultas.php?hapus=<?php echo urlencode($f['kode_fakultas']); ?>"
                                onclick="return confirm('Yakin ingin menghapus fakultas ini?');">Hapus</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" class="kosong">Belum ada data fakultas.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</main>

</body>
</html>