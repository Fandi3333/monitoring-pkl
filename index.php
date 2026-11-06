<?php
require_once __DIR__ . '/config/app.php';

if (current_user()) {
    redirect('dashboard.php');
}

$databaseReady = true;
try {
    db()->query('SELECT 1 FROM users LIMIT 1');
} catch (Throwable $exception) {
    $databaseReady = false;
}
$notice = take_flash();
$resetPending = isset($_SESSION['password_reset_pending']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/app.css') ?>">
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
        </section>
        <section class="auth-main">
            <div class="auth-form-wrap">
                <a class="brand brand-mobile" href="index.php"><span class="brand-mark">R</span><span>Ruang<span class="brand-accent">PKL</span></span></a>
                <div class="auth-heading">
                    <p class="eyebrow">SELAMAT DATANG</p>
                    <h2 data-auth-title><?= $resetPending ? 'Atur ulang kata sandi' : 'Masuk ke akun' ?></h2>
                    <p data-auth-subtitle><?= $resetPending ? 'Masukkan kode OTP yang dikirim ke email Anda.' : 'Gunakan NIP, NISN, atau username terdaftar.' ?></p>
                </div>
                <?php if (!$databaseReady): ?>
                    <div class="notice notice-danger">Database belum siap. Impor <code>database/db_monitoring_pkl.sql</code> sesuai petunjuk README.</div>
                <?php endif; ?>
                <?php if ($notice): ?><div class="notice notice-<?= e($notice['type']) ?>" role="status" data-auto-dismiss><?= e($notice['message']) ?></div><?php endif; ?>
                <div class="auth-tabs" role="tablist" aria-label="Autentikasi" <?= $resetPending ? 'hidden' : '' ?>>
                    <button class="auth-tab is-active" type="button" role="tab" aria-selected="true" data-auth-tab="login">Masuk</button>
                    <button class="auth-tab" type="button" role="tab" aria-selected="false" data-auth-tab="register">Daftar</button>
                </div>
                <form class="auth-form" method="post" action="auth_action.php" data-auth-panel="login" <?= $resetPending ? 'hidden' : '' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="login">
                    <label>Username / NIP / NISN<input name="username" autocomplete="username" required autofocus></label>
                    <label>Kata sandi<input type="password" name="password" autocomplete="current-password" required></label>
                    <button class="button button-primary button-wide" type="submit">Masuk ke dashboard <span aria-hidden="true">→</span></button>
                    <button class="auth-inline-link" type="button" data-forgot-password>Lupa kata sandi?</button>
                </form>
                <form class="auth-form" method="post" action="auth_action.php" data-auth-panel="register" hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="register">
                    <label>Daftar sebagai<select name="role" data-role-select required>
                        <option value="" selected disabled>Pilih peran</option>
                        <option value="admin">Admin / Ketua Jurusan</option>
                        <option value="guru">Guru pembimbing</option><option value="siswa">Siswa PKL</option>
                    </select></label>
                    <label>Nama lengkap<input name="nama" autocomplete="name" maxlength="120" required></label>
                    <label>Username / NIP / NISN<input name="username" autocomplete="username" maxlength="60" required></label>
                    <label class="register-email" hidden>Email<input type="email" name="email" autocomplete="email" maxlength="190" data-admin-field disabled></label>
                    <label>Kata sandi<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
                    <label class="register-class" hidden>Kode kelas<input name="kode_kelas" maxlength="40" data-class-field disabled></label>
                    <button class="button button-primary button-wide" type="submit">Buat akun <span aria-hidden="true">→</span></button>
                </form>
                <form class="auth-form" method="post" action="auth_action.php" data-recovery-panel="request" hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="request_password_reset">
                    <label>Email akun<input type="email" name="email" autocomplete="email" maxlength="190" required value="<?= $resetPending ? e($_SESSION['password_reset_pending']['email'] ?? '') : '' ?>"></label>
                    <button class="button button-primary button-wide" type="submit">Kirim kode OTP</button>
                    <?php if ($resetPending): ?><button class="auth-inline-link" type="button" data-return-login>Kembali ke masuk</button><?php else: ?><button class="auth-inline-link" type="button" data-return-login>Kembali</button><?php endif; ?>
                </form>
                <form class="auth-form" method="post" action="auth_action.php" data-recovery-panel="verify" <?= $resetPending ? '' : 'hidden' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <label>Kode OTP 6 digit<input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></label>
                    <label>Kata sandi baru<input type="password" name="new_password" autocomplete="new-password" minlength="8" required></label>
                    <label>Ulangi kata sandi baru<input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></label>
                    <button class="button button-primary button-wide" type="submit">Simpan kata sandi baru</button>
                    <button class="auth-inline-link" type="button" data-resend-otp>Kirim ulang kode OTP</button>
                </form>
                <form class="auth-form" method="post" action="auth_action.php" data-resend-form hidden>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="resend_password_reset">
                </form>
            </div>
        </section>
    </main>
</body>
</html>