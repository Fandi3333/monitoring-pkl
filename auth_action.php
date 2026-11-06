<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();
$pdo = db();
$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT u.id, u.nama, u.username, u.password, u.role, u.kelas_id, k.kode_kelas FROM users u LEFT JOIN kelas_pkl k ON k.id = u.kelas_id WHERE u.username = ? AND u.aktif = 1 LIMIT 1');
    $statement->execute([$username]);
    $user = $statement->fetch();
    $authenticated = false;
    if ($user && $user['role'] === 'admin') {
        $authenticated = password_verify($password, $user['password']);
    } elseif ($user && in_array($user['role'], ['guru', 'siswa'], true) && is_string($user['kode_kelas'])) {
        $enteredClassCode = strtoupper(trim($password));
        $storedClassCode = strtoupper($user['kode_kelas']);
        $authenticated = $enteredClassCode !== '' && hash_equals($storedClassCode, $enteredClassCode);
    }
    if (!$user || !$authenticated) {
        flash('danger', 'Username atau kata sandi/kode kelas tidak sesuai.');
        redirect('index.php');
    }
    session_regenerate_id(true);
    unset($user['password'], $user['kode_kelas']);
    $_SESSION['user'] = $user;
    redirect('dashboard.php');
}

if ($action === 'register_admin') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 120
        || !preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username)
        || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($newPassword) < 8 || $newPassword !== $confirmation) {
        flash('danger', 'Periksa kembali data. Username harus 3–60 karakter, email valid, dan kata sandi minimal 8 karakter serta harus sama.');
        redirect('index.php?register=1');
    }

    $existingAccount = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $existingAccount->execute([$username, $email]);
    if ($existingAccount->fetchColumn() !== false) {
        flash('danger', 'Username atau email sudah digunakan. Silakan gunakan yang lain.');
        redirect('index.php?register=1');
    }

    try {
        $statement = $pdo->prepare('INSERT INTO users (nama, username, email, password, role, kelas_id) VALUES (?, ?, ?, ?, ?, NULL)');
        $statement->execute([$name, $username, $email, password_hash($newPassword, PASSWORD_DEFAULT), 'admin']);
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '23000') {
            throw $exception;
        }
        flash('danger', 'Username atau email sudah digunakan. Silakan gunakan yang lain.');
        redirect('index.php?register=1');
    }

    flash('success', 'Akun kaprodi berhasil dibuat. Silakan masuk menggunakan username dan kata sandi.');
    redirect('index.php');
}

if ($action === 'request_password_reset' || $action === 'resend_password_reset') {
    $sessionReset = $_SESSION['password_reset_pending'] ?? null;
    $email = $action === 'resend_password_reset' && is_array($sessionReset)
        ? (string) ($sessionReset['email'] ?? '')
        : trim((string) ($_POST['email'] ?? ''));
    $redirectToReset = is_array($sessionReset) || $action === 'resend_password_reset';
    $lastRequest = (int) ($_SESSION['password_reset_requested_at'] ?? 0);
    if ($lastRequest > time() - 60) {
        flash('warning', 'Tunggu satu menit sebelum meminta kode OTP lagi.');
        redirect($redirectToReset ? 'index.php?reset=1' : 'index.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('danger', 'Masukkan alamat email yang valid.');
        redirect($redirectToReset ? 'index.php?reset=1' : 'index.php');
    }

    $reset = [
        'account_id' => null,
        'email' => $email,
        'otp_hash' => null,
        'expires_at' => time() + 600,
        'attempts' => 0,
    ];
    $statement = $pdo->prepare('SELECT id FROM users WHERE email = ? AND aktif = 1 LIMIT 1');
    $statement->execute([$email]);
    $accountId = $statement->fetchColumn();

    try {
        if ($accountId !== false) {
            $otp = (string) random_int(100000, 999999);
            send_password_reset_email($email, $otp);
            $reset['account_id'] = (int) $accountId;
            $reset['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
        }
        $_SESSION['password_reset_pending'] = $reset;
        $_SESSION['password_reset_requested_at'] = time();
        flash('success', 'Jika email tersebut terdaftar, kode OTP akan dikirim. Periksa kotak masuk dan folder spam.');
    } catch (MailDeliveryException $exception) {
        error_log('Password reset email delivery failed: ' . $exception->getMessage());
        flash('danger', 'OTP belum dapat dikirim. Layanan email belum siap; silakan hubungi administrator.');
    }
    redirect('index.php?reset=1');
}

if ($action === 'reset_password') {
    $reset = $_SESSION['password_reset_pending'] ?? null;
    if (!is_array($reset) || empty($reset['account_id']) || empty($reset['otp_hash'])) {
        flash('danger', 'Permintaan pemulihan tidak valid. Minta kode OTP baru.');
        redirect('index.php');
    }
    if ((int) ($reset['expires_at'] ?? 0) < time()) {
        unset($_SESSION['password_reset_pending']);
        flash('danger', 'Kode OTP sudah kedaluwarsa. Minta kode baru.');
        redirect('index.php');
    }
    if ((int) ($reset['attempts'] ?? 0) >= 5) {
        unset($_SESSION['password_reset_pending']);
        flash('danger', 'Batas percobaan kode OTP tercapai. Minta kode baru.');
        redirect('index.php');
    }

    $otp = trim((string) ($_POST['otp'] ?? ''));
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');
    if (!preg_match('/^\d{6}$/', $otp) || !password_verify($otp, (string) $reset['otp_hash'])) {
        $_SESSION['password_reset_pending']['attempts'] = (int) $reset['attempts'] + 1;
        flash('danger', 'Kode OTP tidak sesuai.');
        redirect('index.php?reset=1');
    }
    if (strlen($newPassword) < 8 || $newPassword !== $confirmation) {
        flash('danger', 'Kata sandi baru minimal 8 karakter dan konfirmasinya harus sama.');
        redirect('index.php?reset=1');
    }

    $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ? AND aktif = 1');
    $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int) $reset['account_id']]);
    unset($_SESSION['password_reset_pending'], $_SESSION['password_reset_requested_at']);
    if ($statement->rowCount() !== 1) {
        flash('danger', 'Akun tidak dapat diperbarui. Minta kode OTP baru atau hubungi administrator.');
        redirect('index.php');
    }
    session_regenerate_id(true);
    flash('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');
    redirect('index.php');
}

flash('danger', 'Permintaan tidak dikenali.');
redirect('index.php');