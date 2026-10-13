<?php
require_once __DIR__ . '/config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf();
$pdo = db();
$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $statement = $pdo->prepare('SELECT id, nama, username, password, role, kelas_id FROM users WHERE username = ? AND aktif = 1 LIMIT 1');
    $statement->execute([$username]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        flash('danger', 'Username atau kata sandi tidak sesuai.');
        redirect('index.php');
    }
    session_regenerate_id(true);
    unset($user['password']);
    $_SESSION['user'] = $user;
    redirect('dashboard.php');
}

if ($action !== 'register') {
    flash('danger', 'Permintaan tidak dikenali.');
    redirect('index.php');
}

$nama = trim((string) ($_POST['nama'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role = (string) ($_POST['role'] ?? '');
$email = trim((string) ($_POST['email'] ?? ''));
$kodeKelas = strtoupper(trim((string) ($_POST['kode_kelas'] ?? '')));

if ($nama === '' || !preg_match('/^[A-Za-z0-9._-]{3,60}$/', $username) || strlen($password) < 8 || !in_array($role, ['admin', 'guru', 'siswa'], true)) {
    flash('danger', 'Lengkapi data dengan benar. Username 3–60 karakter dan kata sandi minimal 8 karakter.');
    redirect('index.php');
}
if ($role === 'admin' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('danger', 'Email admin harus valid.');
    redirect('index.php');
}

$adminLockAcquired = false;
try {
    if ($role === 'admin') {
        $adminLockAcquired = (int) $pdo->query("SELECT GET_LOCK('monitoring_pkl_first_admin', 10)")->fetchColumn() === 1;
        if (!$adminLockAcquired) {
            throw new DomainException('Sistem belum dapat mengunci pendaftaran admin. Silakan coba lagi.');
        }
        $adminExists = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
        if ($adminExists) {
            throw new DomainException('Akun Admin sudah dibuat. Silakan masuk menggunakan akun Admin.');
        }
        $statement = $pdo->prepare('INSERT INTO users (nama, username, email, password, role, kelas_id) VALUES (?, ?, ?, ?, \'admin\', NULL)');
        $statement->execute([$nama, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    } else {
        $pdo->beginTransaction();
        $statement = $pdo->prepare('SELECT id, kuota_guru, kuota_siswa FROM kelas_pkl WHERE kode_kelas = ? FOR UPDATE');
        $statement->execute([$kodeKelas]);
        $kelas = $statement->fetch();
        if (!$kelas) {
            throw new DomainException('Kode kelas tidak ditemukan. Periksa kembali kode dari Admin.');
        }
        $capacity = $role === 'guru' ? (int) $kelas['kuota_guru'] : (int) $kelas['kuota_siswa'];
        $count = $pdo->prepare('SELECT COUNT(*) FROM users WHERE kelas_id = ? AND role = ? AND aktif = 1');
        $count->execute([$kelas['id'], $role]);
        if ((int) $count->fetchColumn() >= $capacity) {
            throw new DomainException($role === 'guru' ? 'Kuota guru untuk kelas ini sudah penuh.' : 'Maaf, kuota kelas sudah penuh!');
        }

        $statement = $pdo->prepare('INSERT INTO users (nama, username, email, password, role, kelas_id) VALUES (?, ?, NULL, ?, ?, ?)');
        $statement->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role, $kelas['id']]);
        $pdo->commit();
    }

    flash('success', 'Akun berhasil dibuat. Silakan masuk.');
} catch (DomainException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('danger', $exception->getMessage());
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $message = $exception->getCode() === '23000' ? 'Username sudah digunakan.' : 'Pendaftaran gagal. Periksa koneksi dan struktur database.';
    flash('danger', $message);
} finally {
    if ($adminLockAcquired) {
        $pdo->query("SELECT RELEASE_LOCK('monitoring_pkl_first_admin')");
    }
}
redirect('index.php');