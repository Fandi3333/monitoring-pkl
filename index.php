<?php
require_once __DIR__ . '/config/app.php';

if (current_user()) {
    redirect('dashboard.php');
}

$databaseReady = true;
$adminExists = false;
$adminUsername = null;
try {
    $adminUsername = db()->query("SELECT username FROM users WHERE role = 'admin' ORDER BY id LIMIT 1")->fetchColumn();
    $adminExists = $adminUsername !== false;
} catch (Throwable $exception) {
    $databaseReady = false;
}
$notice = take_flash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-aside">
            <a class="brand brand-light" href="index.php"><span class="brand-mark">R</span><span>Ruang<span class="brand-accent">PKL</span></span></a>
            <div class="auth-intro">
                <p class="eyebrow">MONITORING PRAKTIK KERJA LAPANGAN</p>
                <h1>Belajar di lapangan.<br><span>Tumbuh terpantau.</span></h1>
                <p>Satu ruang untuk presensi, jurnal kegiatan, dan pendampingan PKL.</p>
            </div>
            <div class="auth-aside-foot"><span class="status-dot"></span> Sistem monitoring terintegrasi</div>
        </section>
        <section class="auth-main">
            <div class="auth-form-wrap">
                <a class="brand brand-mobile" href="index.php"><span class="brand-mark">R</span><span>Ruang<span class="brand-accent">PKL</span></span></a>
                <div class="auth-heading">
                    <p class="eyebrow">SELAMAT DATANG</p>
                    <h2>Masuk ke akun</h2>
                    <p>Gunakan NIP, NISN, atau username terdaftar.</p>
                </div>
                <?php if (!$databaseReady): ?>
                    <div class="notice notice-danger">Database belum siap. Impor <code>database/db_monitoring_pkl.sql</code> sesuai petunjuk README.</div>
                <?php endif; ?>
                <?php if ($notice): ?><div class="notice notice-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
                <div class="auth-tabs" role="tablist" aria-label="Autentikasi">
                    <button class="auth-tab is-active" type="button" role="tab" aria-selected="true" data-auth-tab="login">Masuk</button>
                    <button class="auth-tab" type="button" role="tab" aria-selected="false" data-auth-tab="register">Daftar</button>
                </div>
                <form class="auth-form" method="post" action="auth_action.php" data-auth-panel="login">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="login">
                    <label>Username / NIP / NISN<input name="username" autocomplete="username" required autofocus></label>
                    <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
                    <button class="button button-primary button-wide" type="submit">Masuk ke dashboard <span aria-hidden="true">→</span></button>
                </form>
                <?php if ($databaseReady && $adminExists): ?><div class="notice notice-success" role="status">Akun Admin sudah tersedia dengan username <strong><?= e($adminUsername) ?></strong>. Masuk melalui formulir ini untuk membuat tempat PKL dan kode kelas.</div><?php endif; ?>
                <form class="auth-form" method="post" action="auth_action.php" data-auth-panel="register" hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="register">
                    <label>Daftar sebagai<select name="role" data-role-select required>
                        <option value="" selected disabled>Pilih peran</option>
                        <?php if ($adminExists): ?><option value="admin" disabled>Admin / Ketua Jurusan (sudah terdaftar)</option><?php else: ?><option value="admin">Admin / Ketua Jurusan</option><?php endif; ?>
                        <option value="guru">Guru pembimbing</option><option value="siswa">Siswa PKL</option>
                    </select></label>
                    <label>Nama lengkap<input name="nama" autocomplete="name" maxlength="120" required></label>
                    <label>Username / NIP / NISN<input name="username" autocomplete="username" maxlength="60" required></label>
                    <label class="register-email" hidden>Email<input type="email" name="email" autocomplete="email" maxlength="190" data-admin-field disabled></label>
                    <label>Kata sandi<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
                    <label class="register-class" hidden>Kode kelas<input name="kode_kelas" maxlength="40" data-class-field disabled></label>
                    <button class="button button-primary button-wide" type="submit">Buat akun <span aria-hidden="true">→</span></button>
                </form>
                <p class="auth-footnote">Data akun digunakan untuk kebutuhan monitoring kegiatan PKL.</p>
            </div>
        </section>
    </main>
</body>
</html>