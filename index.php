<?php
include 'config.php';

$total_mahasiswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM mahasiswa"))['jumlah'];
$total_prodi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM program_studi"))['jumlah'];
$total_fakultas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM fakultas"))['jumlah'];

$daftar_prodi = mysqli_query($koneksi, "
    SELECT ps.kode_prodi, ps.nama_prodi, COUNT(m.npm) AS jumlah_mahasiswa
    FROM program_studi ps
    LEFT JOIN mahasiswa m ON m.kode_prodi = ps.kode_prodi
    GROUP BY ps.kode_prodi, ps.nama_prodi
    ORDER BY ps.nama_prodi
");

// Palet warna + ikon yang dirotasi untuk kartu program studi
$palet = [
    ['bg' => '#dcfce7', 'fg' => '#166534', 'icon' => '<path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/>'],
    ['bg' => '#dbeafe', 'fg' => '#1544ab', 'icon' => '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>'],
    ['bg' => '#ede9fe', 'fg' => '#5b21b6', 'icon' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>'],
    ['bg' => '#fee2e2', 'fg' => '#b91c1c', 'icon' => '<path d="M19 14c1.5-1.5 3-3.6 3-6a5 5 0 0 0-10-1 5 5 0 0 0-10 1c0 2.4 1.5 4.5 3 6l7 7Z"/>'],
    ['bg' => '#ffedd5', 'fg' => '#c2410c', 'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
    ['bg' => '#e0e7ff', 'fg' => '#3730a3', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>'],
    ['bg' => '#fce7f3', 'fg' => '#9d174d', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01"/>'],
    ['bg' => '#f3f4f6', 'fg' => '#374151', 'icon' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Universitas Semantik - Data Mahasiswa Per Program Studi</title>
<style>
    :root {
        --primary: #1a56db;
        --primary-dark: #1544ab;
        --text-dark: #1f2937;
        --text-muted: #6b7280;
        --bg-light: #f9fafb;
        --border: #e5e7eb;
    }
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden; max-width: 100%; }
    body { margin: 0; font-family: 'Segoe UI', Arial, sans-serif; color: var(--text-dark); background: #fff; }
    a { text-decoration: none; color: inherit; }

    nav { display: flex; justify-content: space-between; align-items: center; padding: 16px 48px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 12px; }
    .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 18px; }
    .brand-icon { width: 36px; height: 36px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 16px; }
    .nav-links { display: flex; gap: 28px; font-size: 14px; color: var(--text-muted); }
    .btn-login { background: var(--primary); color: #fff; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; }
    .btn-login:hover { background: var(--primary-dark); }

    .hero { padding: 56px 48px; background: linear-gradient(180deg, #eff6ff 0%, #fff 100%); display: flex; align-items: center; gap: 40px; flex-wrap: wrap; overflow: hidden; }
    .hero-text { flex: 1; min-width: 320px; }
    .hero-text h1 { font-size: 42px; line-height: 1.2; color: var(--primary-dark); margin: 0 0 16px; }
    .hero-text p { color: var(--text-muted); max-width: 480px; font-size: 16px; margin-bottom: 24px; }
    .hero-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
    .btn-primary { background: var(--primary); color: #fff; padding: 12px 22px; border-radius: 6px; font-weight: 600; font-size: 14px; }
    .btn-outline { border: 1px solid var(--primary); color: var(--primary); padding: 12px 22px; border-radius: 6px; font-weight: 600; font-size: 14px; }

    .hero-visual { flex: 1; min-width: 300px; display: flex; justify-content: center; position: relative; }
    .mock-card { position: relative; width: 320px; background: #fff; border-radius: 18px; box-shadow: 0 12px 32px rgba(26,86,219,0.14); padding: 20px; }
    .mock-card .dots { display: flex; gap: 6px; margin-bottom: 18px; }
    .mock-card .dots span { width: 9px; height: 9px; border-radius: 50%; background: #e5e7eb; }
    .mock-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .mock-tile { background: var(--bg-light); border-radius: 12px; padding: 18px 12px; text-align: center; }
    .mock-tile .circle { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
    .mock-tile .circle svg { width: 22px; height: 22px; stroke-width: 2; fill: none; stroke-linecap: round; stroke-linejoin: round; }
    .mock-tile span.txt { font-size: 12.5px; font-weight: 600; color: var(--text-dark); }
    .blob { position: absolute; border-radius: 50%; z-index: -1; opacity: 0.5; }
    .blob-1 { width: 140px; height: 140px; background: #dbeafe; top: -30px; right: 10px; }
    .blob-2 { width: 90px; height: 90px; background: #fef3c7; bottom: -20px; left: -10px; opacity: 0.6; }

    .stats { display: flex; justify-content: space-around; text-align: center; padding: 40px 48px; background: var(--bg-light); flex-wrap: wrap; gap: 24px; }
    .stat-item { display: flex; flex-direction: column; align-items: center; }
    .stat-item .icon-wrap { width: 40px; height: 40px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; }
    .stat-item .icon-wrap svg { width: 20px; height: 20px; stroke: var(--primary-dark); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .stat-item .num { min-height: 38px; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: 700; color: var(--primary-dark); }
    .stat-item .num.text-num { font-size: 15px; }
    .stat-item .label { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

    .features { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 32px; padding: 56px 48px; text-align: center; }
    .feature-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
    .feature-icon svg { width: 28px; height: 28px; }
    .feature h3 { font-size: 16px; margin: 0 0 8px; }
    .feature p { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin: 0; }

    .section { padding: 56px 48px; background: var(--bg-light); }
    .section-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 28px; flex-wrap: wrap; gap: 10px; }
    .section h2 { font-size: 26px; margin-bottom: 4px; }
    .section .sub { color: var(--text-muted); font-size: 14px; }
    .link-all { color: var(--primary); font-weight: 600; font-size: 14px; }
    .prodi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
    .prodi-card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 18px; display: flex; align-items: center; gap: 14px; }
    .prodi-icon { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .prodi-icon svg { width: 20px; height: 20px; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .prodi-name { font-weight: 600; font-size: 15px; }
    .prodi-count { color: var(--text-muted); font-size: 13px; margin-top: 2px; }
    .empty-note { color: var(--text-muted); font-size: 14px; }

    .badge-semantik { display: inline-flex; align-items: center; gap: 8px; background: #eff6ff; color: var(--primary-dark); padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
    .badge-semantik svg { width: 16px; height: 16px; stroke: var(--primary-dark); fill: none; stroke-width: 2; }

    .ekosistem { padding: 56px 48px; display: flex; gap: 40px; flex-wrap: wrap; align-items: flex-start; }
    .ekosistem-text { flex: 1; min-width: 300px; }
    .ekosistem-text h2 { font-size: 26px; margin-bottom: 14px; }
    .ekosistem-text p { color: var(--text-muted); line-height: 1.7; font-size: 15px; margin-bottom: 20px; }
    .quote-box { flex: 1; min-width: 280px; border-left: 4px solid var(--primary); padding: 8px 24px; font-style: italic; color: var(--text-dark); font-size: 15px; line-height: 1.7; }
    .quote-box .author { display: block; margin-top: 12px; color: var(--text-muted); font-style: normal; font-size: 13px; }

    footer { background: #111827; color: #d1d5db; padding: 44px 48px 24px; font-size: 13px; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1.4fr; gap: 24px; padding-bottom: 28px; border-bottom: 1px solid #1f2937; }
    .footer-brand { display: flex; align-items: center; gap: 10px; color: #fff; font-weight: 700; margin-bottom: 8px; }
    .footer-col h4 { color: #fff; font-size: 14px; margin: 0 0 12px; }
    .footer-col a { display: block; color: #9ca3af; margin-bottom: 8px; }
    .footer-contact div { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; color: #9ca3af; }
    .footer-contact svg { width: 15px; height: 15px; stroke: #9ca3af; fill: none; stroke-width: 2; flex-shrink: 0; }
    .footer-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 18px; flex-wrap: wrap; gap: 10px; }
    .socials { display: flex; gap: 10px; }
    .socials a { width: 32px; height: 32px; border-radius: 50%; background: #1f2937; display: flex; align-items: center; justify-content: center; }
    .socials svg { width: 14px; height: 14px; fill: #d1d5db; }
    .copyright { color: #6b7280; }
</style>
</head>
<body>

<nav>
    <div class="brand">
        <div class="brand-icon">US</div>
        Universitas Semantik
    </div>
    <div class="nav-links">
        <a href="#">Beranda</a>
        <a href="#program-studi">Program Studi</a>
        <a href="#">Tentang</a>
        <a href="#">Kontak</a>
    </div>
    <a href="login.php" class="btn-login">Login</a>
</nav>

<section class="hero">
    <div class="hero-text">
        <h1>Data Mahasiswa Per Program Studi</h1>
        <p>Akses, eksplorasi, dan kelola data mahasiswa secara terpusat dan terstruktur untuk mendukung tata kelola universitas yang lebih baik.</p>
        <div class="hero-buttons">
            <a href="login.php" class="btn-primary">Lihat Data Mahasiswa</a>
            <a href="#program-studi" class="btn-outline">Lihat Program Studi</a>
        </div>
    </div>
    <div class="hero-visual">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="mock-card">
            <div class="dots"><span></span><span></span><span></span></div>
            <div class="mock-grid">
                <div class="mock-tile">
                    <div class="circle" style="background:#dbeafe;">
                        <svg viewBox="0 0 24 24" stroke="#1544ab"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <span class="txt">Mahasiswa</span>
                </div>
                <div class="mock-tile">
                    <div class="circle" style="background:#ede9fe;">
                        <svg viewBox="0 0 24 24" stroke="#5b21b6"><path d="M22 10 12 5 2 10l10 5 10-5Z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>
                    </div>
                    <span class="txt">Program Studi</span>
                </div>
                <div class="mock-tile">
                    <div class="circle" style="background:#dcfce7;">
                        <svg viewBox="0 0 24 24" stroke="#166534"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                    </div>
                    <span class="txt">Mata Kuliah</span>
                </div>
                <div class="mock-tile">
                    <div class="circle" style="background:#fef3c7;">
                        <svg viewBox="0 0 24 24" stroke="#92400e"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M9 13h.01M15 9h.01M15 13h.01M12 21v-5"/></svg>
                    </div>
                    <span class="txt">Universitas</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="stats">
    <div class="stat-item">
        <div class="icon-wrap"><svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5-10-5Z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg></div>
        <div class="num"><?php echo $total_mahasiswa; ?></div>
        <div class="label">Total Mahasiswa</div>
    </div>
    <div class="stat-item">
        <div class="icon-wrap"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="num"><?php echo $total_prodi; ?></div>
        <div class="label">Program Studi</div>
    </div>
    <div class="stat-item">
        <div class="icon-wrap"><svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h.01M9 13h.01M15 9h.01M15 13h.01"/></svg></div>
        <div class="num"><?php echo $total_fakultas; ?></div>
        <div class="label">Fakultas</div>
    </div>
</div>

<section class="features">
    <div class="feature">
        <div class="feature-icon" style="background:#dbeafe;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#1544ab" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.7-4 3-9 3s-9-1.3-9-3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/></svg>
        </div>
        <h3>Data Terintegrasi</h3>
        <p>Data mahasiswa terhubung dengan program studi dan fakultas dalam satu sistem.</p>
    </div>
    <div class="feature">
        <div class="feature-icon" style="background:#ede9fe;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#5b21b6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="10.5" x2="15.4" y2="6.5"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/></svg>
        </div>
        <h3>Login Berbasis Peran</h3>
        <p>Admin, Prodi, dan Mahasiswa memiliki akses yang berbeda sesuai kebutuhan.</p>
    </div>
    <div class="feature">
        <div class="feature-icon" style="background:#dcfce7;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#166534" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
        </div>
        <h3>Mudah Diakses</h3>
        <p>Statistik dan data program studi bisa dilihat kapan saja secara ringkas.</p>
    </div>
    <div class="feature">
        <div class="feature-icon" style="background:#fef3c7;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </div>
        <h3>Password Aman</h3>
        <p>Password akun disimpan dalam bentuk hash, bukan teks biasa.</p>
    </div>
</section>

<section class="section" id="program-studi">
    <div class="section-head">
        <div>
            <h2>Daftar Program Studi</h2>
            <div class="sub">Pilih program studi untuk melihat data mahasiswa secara detail</div>
        </div>
        <a href="login.php" class="link-all">Lihat Semua Program Studi &rarr;</a>
    </div>
    <div class="prodi-grid">
        <?php if (mysqli_num_rows($daftar_prodi) > 0): $i = 0; ?>
            <?php while ($row = mysqli_fetch_assoc($daftar_prodi)): $w = $palet[$i % count($palet)]; $i++; ?>
                <div class="prodi-card">
                    <div class="prodi-icon" style="background:<?php echo $w['bg']; ?>;">
                        <svg viewBox="0 0 24 24" stroke="<?php echo $w['fg']; ?>"><?php echo $w['icon']; ?></svg>
                    </div>
                    <div>
                        <div class="prodi-name"><?php echo htmlspecialchars($row['nama_prodi']); ?></div>
                        <div class="prodi-count"><?php echo $row['jumlah_mahasiswa']; ?> Mahasiswa</div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-note">Belum ada data program studi. Silakan tambahkan melalui dashboard admin.</div>
        <?php endif; ?>
    </div>
</section>

<section class="ekosistem">
    <div class="ekosistem-text">
        <div class="badge-semantik">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>
            Data Terhubung dengan Web Semantik
        </div>
        <h2>Membangun Ekosistem Data Terbuka</h2>
        <p>Dengan pendekatan Web Semantik, data mahasiswa tidak hanya disimpan, tetapi juga dapat dipahami, dihubungkan, dan dimanfaatkan oleh berbagai aplikasi untuk mendukung pendidikan, penelitian, dan tata kelola universitas yang lebih baik.</p>
        <a href="#" class="btn-primary">Tentang Web Semantik</a>
    </div>
    <div class="quote-box">
        &ldquo;Data yang terhubung dan terstruktur membuka lebih banyak kemungkinan untuk dipahami, bukan hanya oleh manusia, tapi juga oleh sistem.&rdquo;
        <span class="author">&mdash; Menuju Universitas yang Lebih Cerdas</span>
    </div>
</section>

<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-brand"><div class="brand-icon">US</div> Universitas Semantik</div>
            <div style="color:#9ca3af; max-width:280px; line-height:1.6;">Unggul &middot; Terbuka &middot; Berkemajuan</div>
        </div>
        <div class="footer-col">
            <h4>Tautan Cepat</h4>
            <a href="#">Beranda</a>
            <a href="login.php">Login</a>
            <a href="#program-studi">Program Studi</a>
        </div>
        <div class="footer-col">
            <h4>Sumber Daya</h4>
            <a href="#">RDF</a>
            <a href="#">OWL</a>
            <a href="#">SPARQL</a>
        </div>
        <div class="footer-col footer-contact">
            <h4>Kontak</h4>
            <div><svg viewBox="0 0 24 24"><path d="M21 10c0 6-9 12-9 12s-9-6-9-12a9 9 0 0 1 18 0Z"/><circle cx="12" cy="10" r="3"/></svg> Jl. Pendidikan No. 1, Bengkulu</div>
            <div><svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/></svg> info@universitassemantik.ac.id</div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="copyright">&copy; <?php echo date('Y'); ?> Universitas Semantik. Semua hak dilindungi.</div>
        <div class="socials">
            <a href="#"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm2.5 9.5-4 2.5v-5l4 2.5Z"/></svg></a>
            <a href="#"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4" fill="#111827"/><circle cx="17.5" cy="6.5" r="1" fill="#111827"/></svg></a>
            <a href="#"><svg viewBox="0 0 24 24"><path d="M15 3h-3a5 5 0 0 0-5 5v3H4v4h3v9h4v-9h3l1-4h-4V8a1 1 0 0 1 1-1h3Z"/></svg></a>
        </div>
    </div>
</footer>

</body>
</html>