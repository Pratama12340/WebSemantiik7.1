<?php
include '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$hasil = [];
$berhasil = 0;
$gagal = 0;

// ================================
// AMBIL DAFTAR PRODI (untuk validasi kode_prodi)
// ================================
$prodi_valid = [];
$q = mysqli_query($koneksi, "SELECT kode_prodi, nama_prodi FROM program_studi");
while ($row = mysqli_fetch_assoc($q)) {
    $prodi_valid[$row['kode_prodi']] = $row['nama_prodi'];
}

// ================================
// PROSES UPLOAD CSV
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_csv'])) {
    $file = $_FILES['file_csv']['tmp_name'];

    if ($_FILES['file_csv']['error'] !== 0 || !$file) {
        $hasil[] = ['baris' => '-', 'status' => 'gagal', 'pesan' => 'File gagal diupload.'];
    } else {
        $handle = fopen($file, 'r');
        $baris_ke = 0;

        // Deteksi delimiter otomatis dari baris pertama
        $baris_pertama = fgets($handle);
        $delimiter = (substr_count($baris_pertama, ';') > substr_count($baris_pertama, ',')) ? ';' : ',';
        rewind($handle);

        while (($data = fgetcsv($handle, 2000, $delimiter)) !== false) {
            $baris_ke++;
            if ($baris_ke === 1) continue; // lewati baris header

            // Format kolom CSV yang diharapkan:
            // npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, kode_prodi
            if (count($data) < 8) {
                $hasil[] = ['baris' => $baris_ke, 'status' => 'gagal', 'pesan' => 'Kolom tidak lengkap (butuh 8 kolom).'];
                $gagal++;
                continue;
            }

            [$npm, $nama, $jk, $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat, $kode_prodi] = array_map('trim', $data);

            if ($npm === '' || $nama === '') {
                $hasil[] = ['baris' => $baris_ke, 'status' => 'gagal', 'pesan' => 'NPM atau nama kosong.'];
                $gagal++;
                continue;
            }
            if (!isset($prodi_valid[$kode_prodi])) {
                $hasil[] = ['baris' => $baris_ke, 'status' => 'gagal', 'pesan' => "Kode prodi '$kode_prodi' tidak ditemukan."];
                $gagal++;
                continue;
            }
            $jk = (strtoupper($jk) === 'P' || stripos($jk, 'perempuan') !== false) ? 'P' : 'L';

            // Insert ke tabel mahasiswa
            $stmt = mysqli_prepare($koneksi, "INSERT INTO mahasiswa (npm, nama_mahasiswa, jenis_kelamin, tempat_lahir, tanggal_lahir, tanggal_masuk, alamat, kode_prodi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssssss", $npm, $nama, $jk, $tempat_lahir, $tanggal_lahir, $tanggal_masuk, $alamat, $kode_prodi);

            if (mysqli_stmt_execute($stmt)) {
                // Buat akun login otomatis, password default = NPM (mahasiswa wajib ganti nanti)
                $hash = password_hash($npm, PASSWORD_DEFAULT);
                $role = 'mahasiswa';
                $stmt2 = mysqli_prepare($koneksi, "INSERT INTO users (username, password, role, ref_id) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, "ssss", $npm, $hash, $role, $npm);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);

                $hasil[] = ['baris' => $baris_ke, 'status' => 'ok', 'pesan' => "NPM $npm ($nama) berhasil ditambahkan."];
                $berhasil++;
            } else {
                $hasil[] = ['baris' => $baris_ke, 'status' => 'gagal', 'pesan' => "NPM $npm gagal (kemungkinan sudah terdaftar)."];
                $gagal++;
            }
            mysqli_stmt_close($stmt);
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Import Massal Mahasiswa - Admin</title>
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
    .main h1 { margin: 0 0 20px; font-size: 24px; color: #111827; }
    .panel { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 24px; margin-bottom: 24px; }
    .panel h2 { font-size: 16px; margin: 0 0 16px; }
    .panel code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
    .contoh { background: #f3f4f6; padding: 12px; border-radius: 6px; font-size: 12px; overflow-x: auto; white-space: pre; font-family: monospace; margin-top: 10px; }
    .btn { padding: 10px 18px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; background: var(--primary); color: #fff; }
    .btn:hover { background: var(--primary-dark); }
    input[type="file"] { margin-bottom: 16px; display: block; }
    .ringkasan { display: flex; gap: 20px; margin-bottom: 16px; }
    .ringkasan .ok { color: #059669; font-weight: 600; }
    .ringkasan .gagal { color: #dc2626; font-weight: 600; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--border); font-size: 13px; }
    tr.gagal td { color: #dc2626; }
    tr.ok td { color: #059669; }
    .hint { font-size: 13px; color: var(--text-muted); margin-top: 8px; }
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
        <a href="akun_prodi.php">Akun Prodi</a>
        <a href="import_mahasiswa.php" class="active">Import Massal</a>
    </nav>
    <a href="../logout.php" class="logout">Logout</a>
</aside>

<main class="main">
    <h1>Import Massal Data Mahasiswa</h1>

    <div class="panel">
        <h2>Format File CSV</h2>
        <p>Urutan kolom harus tepat seperti ini (baris pertama adalah judul kolom dan akan dilewati otomatis):</p>
        <div class="contoh">npm,nama_mahasiswa,jenis_kelamin,tempat_lahir,tanggal_lahir,tanggal_masuk,alamat,kode_prodi
2024010001,Andi Saputra,L,Bengkulu,2005-03-12,2024-08-01,Jl. Merdeka No. 1,TI
2024010002,Siti Aminah,P,Curup,2005-07-20,2024-08-01,Jl. Sudirman No. 5,TI</div>
        <p class="hint">
            &bull; Format tanggal: YYYY-MM-DD (contoh: 2005-03-12).<br>
            &bull; Kolom "kode_prodi" harus sesuai dengan kode yang sudah ada di menu Kelola Program Studi.<br>
            &bull; Password login setiap mahasiswa otomatis dibuat <strong>sama dengan NPM-nya</strong> — sarankan mereka menggantinya lewat menu "Ganti Password" setelah login pertama.<br>
            &bull; Kalau membuat file dari Excel, simpan dengan "Save As" &rarr; pilih format <strong>CSV (Comma delimited)</strong>.
        </p>
    </div>

    <div class="panel">
        <h2>Upload File</h2>
        <form method="POST" action="import_mahasiswa.php" enctype="multipart/form-data">
            <input type="file" name="file_csv" accept=".csv" required>
            <button type="submit" class="btn">Import Sekarang</button>
        </form>
    </div>

    <?php if (!empty($hasil)): ?>
    <div class="panel">
        <h2>Hasil Import</h2>
        <div class="ringkasan">
            <div class="ok">Berhasil: <?php echo $berhasil; ?></div>
            <div class="gagal">Gagal: <?php echo $gagal; ?></div>
        </div>
        <table>
            <tr><th>Baris</th><th>Keterangan</th></tr>
            <?php foreach ($hasil as $h): ?>
                <tr class="<?php echo $h['status']; ?>">
                    <td><?php echo $h['baris']; ?></td>
                    <td><?php echo htmlspecialchars($h['pesan']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</main>

</body>
</html>
