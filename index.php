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
$registrationPending = isset($_GET['register']) && !$resetPending;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/app.css') ?>">
    <script src="assets/js/app.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>
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
                    <h2 data-auth-title><?= $resetPending ? 'Atur ulang kata sandi' : ($registrationPending ? 'Daftar sebagai kaprog' : 'Login') ?></h2>
                    <p data-auth-subtitle><?= $resetPending ? 'Masukkan kode OTP yang dikirim ke email Anda.' : '' ?></p>
                </div>
                <?php if (!$databaseReady): ?>
                    <div class="notice notice-danger">Database belum siap. Impor <code>database/db_monitoring_pkl.sql</code> sesuai petunjuk README.</div>
                <?php endif; ?>
                <?php if ($notice): ?><div class="notice notice-<?= e($notice['type']) ?>" role="status" data-auto-dismiss><?= e($notice['message']) ?></div><?php endif; ?>
                <form class="auth-form" method="post" action="auth_action.php" data-auth-panel="login" <?= $resetPending || $registrationPending ? 'hidden' : '' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="login">
                    <label>Username / NIP / NISN<input name="username" autocomplete="username" required autofocus></label>
                    <label>Kata sandi / kode kelas<input type="password" name="password" autocomplete="current-password" required></label>
                    <button class="auth-inline-link auth-inline-link-right" type="button" data-forgot-password>Lupa kata sandi?</button>
                    <button class="button button-primary button-wide" type="submit">Masuk ke dashboard <span aria-hidden="true">→</span></button>
                    <button class="auth-inline-link auth-inline-link-right" type="button" data-show-registration>Daftar sebagai kaprog?</button>
                </form>
                <form class="auth-form" method="post" action="auth_action.php" data-registration-panel <?= $registrationPending ? '' : 'hidden' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="register_admin">
                    <label>Nama lengkap<input name="name" maxlength="120" autocomplete="name" required></label>
                    <label>Username<input name="username" maxlength="60" pattern="[A-Za-z0-9._\-]{3,60}" autocomplete="username" required></label>
                    <label>Email<input type="email" name="email" maxlength="190" autocomplete="email" required></label>
                    <label>Kata sandi baru<span class="password-input-wrap"><input type="password" name="new_password" autocomplete="new-password" minlength="8" required><button class="password-visibility-toggle" type="button" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></span></label>
                    <label>Konfirmasi sandi<span class="password-input-wrap"><input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required><button class="password-visibility-toggle" type="button" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button></span></label>
                    <button class="button button-primary button-wide" type="submit">Buat akun kaprog</button>
                    <button class="auth-inline-link auth-inline-link-right" type="button" data-return-login>Kembali</button>
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