<?php
require_once __DIR__ . '/config/app.php';
$user = require_login();
$pdo = db();
$role = $user['role'];
$view = (string) ($_GET['view'] ?? 'overview');
$allowedViews = [
    'admin' => ['overview', 'places', 'classes', 'teachers', 'students', 'reports', 'settings'],
    'guru' => ['overview', 'attendance', 'logbooks', 'students', 'settings'],
    'siswa' => ['overview', 'attendance', 'logbook', 'grades', 'settings'],
];
if ($view === 'security') {
    $view = 'settings';
}
if (!in_array($view, $allowedViews[$role] ?? [], true)) {
    $view = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'logout') {
            $_SESSION = [];
            session_destroy();
            redirect('index.php');
        }

        if ($action === 'delete_account') {
            if ($role !== 'admin') {
                throw new DomainException('Hanya akun Admin yang dapat dihapus dari menu ini.');
            }
            $statement = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
            $statement->execute([$user['id']]);
            if ($statement->rowCount() !== 1) {
                throw new DomainException('Akun Admin tidak ditemukan atau sudah dihapus.');
            }
            unset($_SESSION['user']);
            session_regenerate_id(true);
            flash('success', 'Akun Admin berhasil dihapus.');
            redirect('index.php');
        }

        if ($action === 'change_password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmation = (string) ($_POST['confirm_password'] ?? '');
            $statement = $pdo->prepare('SELECT password FROM users WHERE id = ? AND aktif = 1');
            $statement->execute([$user['id']]);
            $storedPassword = $statement->fetchColumn();
            if (!$storedPassword || !password_verify($currentPassword, $storedPassword)) {
                throw new DomainException('Kata sandi saat ini tidak sesuai.');
            }
            if (strlen($newPassword) < 8 || $newPassword !== $confirmation) {
                throw new DomainException('Kata sandi baru minimal 8 karakter dan konfirmasinya harus sama.');
            }
            $statement = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Kata sandi berhasil diperbarui.');
            redirect(dashboard_url('settings'));
        }

        if ($action === 'create_place' && $role === 'admin') {
            $name = trim((string) ($_POST['nama_industri'] ?? ''));
            $address = trim((string) ($_POST['alamat'] ?? ''));
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $radius = filter_var($_POST['radius_meter'] ?? 100, FILTER_VALIDATE_INT);
            if ($name === '' || $address === '' || $latitude === false || $latitude < -90 || $latitude > 90 || $longitude === false || $longitude < -180 || $longitude > 180 || $radius === false || $radius < 1 || $radius > 5000) {
                throw new DomainException('Data tempat PKL tidak valid. Periksa koordinat dan radius.');
            }
            $statement = $pdo->prepare('INSERT INTO tempat_pkl (nama_industri, alamat, latitude, longitude, radius_meter) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$name, $address, $latitude, $longitude, $radius]);
            flash('success', 'Tempat PKL berhasil ditambahkan.');
            redirect(dashboard_url('places'));
        }

        if ($action === 'update_place' && $role === 'admin') {
            $placeId = filter_var($_POST['place_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['nama_industri'] ?? ''));
            $address = trim((string) ($_POST['alamat'] ?? ''));
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $radius = filter_var($_POST['radius_meter'] ?? 100, FILTER_VALIDATE_INT);
            if (!$placeId || $name === '' || $address === '' || $latitude === false || $latitude < -90 || $latitude > 90 || $longitude === false || $longitude < -180 || $longitude > 180 || $radius === false || $radius < 1 || $radius > 5000) {
                throw new DomainException('Data tempat PKL tidak valid. Periksa koordinat dan radius.');
            }
            $statement = $pdo->prepare('SELECT id FROM tempat_pkl WHERE id = ?');
            $statement->execute([$placeId]);
            if (!$statement->fetchColumn()) {
                throw new DomainException('Tempat PKL tidak ditemukan.');
            }
            $statement = $pdo->prepare('UPDATE tempat_pkl SET nama_industri = ?, alamat = ?, latitude = ?, longitude = ?, radius_meter = ? WHERE id = ?');
            $statement->execute([$name, $address, $latitude, $longitude, $radius, $placeId]);
            flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Tempat PKL berhasil diperbarui.' : 'Tidak ada perubahan pada tempat PKL.');
            redirect(dashboard_url('places'));
        }

        if ($action === 'delete_place' && $role === 'admin') {
            $placeId = filter_var($_POST['place_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$placeId) {
                throw new DomainException('Tempat PKL tidak valid.');
            }
            $statement = $pdo->prepare('SELECT COUNT(*) FROM kelas_pkl WHERE tempat_pkl_id = ?');
            $statement->execute([$placeId]);
            if ((int) $statement->fetchColumn() > 0) {
                throw new DomainException('Tempat PKL tidak dapat dihapus karena masih digunakan oleh kelas. Ubah tempat pada kelas terkait terlebih dahulu.');
            }
            $statement = $pdo->prepare('DELETE FROM tempat_pkl WHERE id = ?');
            $statement->execute([$placeId]);
            flash($statement->rowCount() ? 'success' : 'danger', $statement->rowCount() ? 'Tempat PKL berhasil dihapus.' : 'Tempat PKL tidak ditemukan.');
            redirect(dashboard_url('places'));
        }

        if ($action === 'create_class' && $role === 'admin') {
            $name = trim((string) ($_POST['nama_kelas'] ?? ''));
            $code = strtoupper(trim((string) ($_POST['kode_kelas'] ?? '')));
            $placeId = filter_var($_POST['tempat_pkl_id'] ?? null, FILTER_VALIDATE_INT);
            $teacherQuota = filter_var($_POST['kuota_guru'] ?? 1, FILTER_VALIDATE_INT);
            $studentQuota = filter_var($_POST['kuota_siswa'] ?? 20, FILTER_VALIDATE_INT);
            if ($name === '' || !preg_match('/^[A-Z0-9-]{3,40}$/', $code) || !$placeId || $teacherQuota < 1 || $teacherQuota > 10 || $studentQuota < 1 || $studentQuota > 500) {
                throw new DomainException('Lengkapi data kelas. Kode hanya boleh berisi huruf, angka, dan tanda hubung.');
            }
            $statement = $pdo->prepare('INSERT INTO kelas_pkl (nama_kelas, kode_kelas, tempat_pkl_id, kuota_guru, kuota_siswa) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$name, $code, $placeId, $teacherQuota, $studentQuota]);
            flash('success', 'Kelas PKL berhasil dibuat. Kode kelas: ' . $code);
            redirect(dashboard_url('classes'));
        }

        if ($action === 'update_class' && $role === 'admin') {
            $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['nama_kelas'] ?? ''));
            $code = strtoupper(trim((string) ($_POST['kode_kelas'] ?? '')));
            $placeId = filter_var($_POST['tempat_pkl_id'] ?? null, FILTER_VALIDATE_INT);
            $teacherQuota = filter_var($_POST['kuota_guru'] ?? null, FILTER_VALIDATE_INT);
            $studentQuota = filter_var($_POST['kuota_siswa'] ?? null, FILTER_VALIDATE_INT);
            if (!$classId || $name === '' || !preg_match('/^[A-Z0-9-]{3,40}$/', $code) || !$placeId || $teacherQuota === false || $teacherQuota < 1 || $teacherQuota > 10 || $studentQuota === false || $studentQuota < 1 || $studentQuota > 500) {
                throw new DomainException('Lengkapi data kelas. Kode hanya boleh berisi huruf, angka, dan tanda hubung.');
            }
            $statement = $pdo->prepare('SELECT id FROM kelas_pkl WHERE id = ?');
            $statement->execute([$classId]);
            if (!$statement->fetchColumn()) {
                throw new DomainException('Kelas PKL tidak ditemukan.');
            }
            $statement = $pdo->prepare('SELECT id FROM tempat_pkl WHERE id = ?');
            $statement->execute([$placeId]);
            if (!$statement->fetchColumn()) {
                throw new DomainException('Tempat PKL yang dipilih tidak ditemukan.');
            }
            $statement = $pdo->prepare("SELECT SUM(role = 'guru' AND aktif = 1) AS guru_aktif, SUM(role = 'siswa' AND aktif = 1) AS siswa_aktif FROM users WHERE kelas_id = ?");
            $statement->execute([$classId]);
            $members = $statement->fetch();
            if ($teacherQuota < (int) ($members['guru_aktif'] ?? 0) || $studentQuota < (int) ($members['siswa_aktif'] ?? 0)) {
                throw new DomainException('Kuota tidak boleh lebih kecil dari jumlah guru atau siswa aktif di kelas ini.');
            }
            $statement = $pdo->prepare('UPDATE kelas_pkl SET nama_kelas = ?, kode_kelas = ?, tempat_pkl_id = ?, kuota_guru = ?, kuota_siswa = ? WHERE id = ?');
            $statement->execute([$name, $code, $placeId, $teacherQuota, $studentQuota, $classId]);
            flash($statement->rowCount() ? 'success' : 'warning', $statement->rowCount() ? 'Data kelas PKL berhasil diperbarui.' : 'Tidak ada perubahan pada kelas PKL.');
            redirect(dashboard_url('classes'));
        }

        if ($action === 'delete_class' && $role === 'admin') {
            $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$classId) {
                throw new DomainException('Kelas PKL tidak valid.');
            }
            $statement = $pdo->prepare('DELETE FROM kelas_pkl WHERE id = ?');
            $statement->execute([$classId]);
            flash($statement->rowCount() ? 'success' : 'danger', $statement->rowCount() ? 'Kelas PKL berhasil dihapus. Akun anggota tetap ada, tetapi tidak lagi terhubung ke kelas; presensi dan logbook kelas ikut terhapus.' : 'Kelas PKL tidak ditemukan.');
            redirect(dashboard_url('classes'));
        }

        if ($action === 'delete_user' && $role === 'admin') {
            $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$targetId || $targetId === (int) $user['id']) {
                throw new DomainException('Akun sendiri tidak dapat dihapus.');
            }
            $statement = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('guru', 'siswa')");
            $statement->execute([$targetId]);
            flash($statement->rowCount() ? 'success' : 'danger', $statement->rowCount() ? 'Akun pengguna dan data presensi/logbook terkait berhasil dihapus.' : 'Akun tidak ditemukan.');
            redirect(dashboard_url(($_POST['return_view'] ?? '') === 'teachers' ? 'teachers' : 'students'));
        }

        if ($action === 'attendance' && $role === 'siswa') {
            $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 || empty($user['kelas_id'])) {
                throw new DomainException('Lokasi GPS tidak terbaca. Izinkan akses lokasi lalu coba lagi.');
            }
            $statement = $pdo->prepare('SELECT k.id, t.latitude, t.longitude, t.radius_meter FROM kelas_pkl k JOIN tempat_pkl t ON t.id = k.tempat_pkl_id WHERE k.id = ?');
            $statement->execute([$user['kelas_id']]);
            $place = $statement->fetch();
            if (!$place) {
                throw new DomainException('Tempat PKL belum tersedia untuk kelas Anda.');
            }
            $distance = haversine_meters((float) $place['latitude'], (float) $place['longitude'], (float) $latitude, (float) $longitude);
            $status = $distance <= (int) $place['radius_meter'] ? 'valid' : 'luar_radius';
            $statement = $pdo->prepare('INSERT INTO presensi (siswa_id, kelas_pkl_id, tanggal, jam, latitude_siswa, longitude_siswa, jarak_meter, status) VALUES (?, ?, CURDATE(), CURTIME(), ?, ?, ?, ?) ON DUPLICATE KEY UPDATE jam = VALUES(jam), latitude_siswa = VALUES(latitude_siswa), longitude_siswa = VALUES(longitude_siswa), jarak_meter = VALUES(jarak_meter), status = VALUES(status)');
            $statement->execute([$user['id'], $place['id'], $latitude, $longitude, round($distance, 2), $status]);
            flash($status === 'valid' ? 'success' : 'warning', $status === 'valid' ? 'Presensi valid. Lokasi Anda berada dalam radius PKL.' : 'Presensi tercatat, tetapi lokasi Anda berada di luar radius PKL.');
            redirect(dashboard_url('attendance'));
        }

        if ($action === 'create_logbook' && $role === 'siswa') {
            $activity = trim((string) ($_POST['kegiatan'] ?? ''));
            $date = (string) ($_POST['tanggal'] ?? date('Y-m-d'));
            $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
            if ($activity === '' || mb_strlen($activity) > 5000 || !$parsedDate || $parsedDate->format('Y-m-d') !== $date || $date > date('Y-m-d') || empty($user['kelas_id'])) {
                throw new DomainException('Isi kegiatan dan tanggal dengan benar.');
            }
            $photoPath = null;
            if (isset($_FILES['foto_bukti']) && $_FILES['foto_bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
                $photo = $_FILES['foto_bukti'];
                if ($photo['error'] !== UPLOAD_ERR_OK || $photo['size'] > 5 * 1024 * 1024) {
                    throw new DomainException('Foto gagal diunggah atau melebihi batas 5 MB.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($photo['tmp_name']);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) {
                    throw new DomainException('Foto harus berformat JPG, PNG, atau WebP.');
                }
                $directory = __DIR__ . '/uploads';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('Folder upload tidak dapat dibuat.');
                }
                $photoPath = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($photo['tmp_name'], $directory . '/' . $photoPath)) {
                    throw new RuntimeException('Foto gagal disimpan. Periksa izin folder uploads.');
                }
            }
            $pdo->beginTransaction();
            $statement = $pdo->prepare('SELECT id, status FROM logbook WHERE siswa_id = ? AND tanggal = ? FOR UPDATE');
            $statement->execute([$user['id'], $date]);
            $existing = $statement->fetch();
            if ($existing && $existing['status'] !== 'revision') {
                throw new DomainException('Logbook tanggal ini sudah dikirim dan belum dapat diubah.');
            }
            if ($existing) {
                $statement = $pdo->prepare("UPDATE logbook SET kegiatan = ?, foto_bukti = COALESCE(?, foto_bukti), status = 'pending', nilai = NULL, catatan_guru = NULL WHERE id = ? AND siswa_id = ?");
                $statement->execute([$activity, $photoPath, $existing['id'], $user['id']]);
                $message = 'Revisi logbook berhasil dikirim kembali untuk ditinjau.';
            } else {
                $statement = $pdo->prepare('INSERT INTO logbook (siswa_id, kelas_pkl_id, tanggal, kegiatan, foto_bukti) VALUES (?, ?, ?, ?, ?)');
                $statement->execute([$user['id'], $user['kelas_id'], $date, $activity, $photoPath]);
                $message = 'Jurnal kegiatan berhasil dikirim untuk ditinjau.';
            }
            $pdo->commit();
            flash('success', $message);
            redirect(dashboard_url('logbook'));
        }

        if ($action === 'review_logbook' && $role === 'guru') {
            $logbookId = filter_var($_POST['logbook_id'] ?? null, FILTER_VALIDATE_INT);
            $status = (string) ($_POST['status'] ?? '');
            $score = trim((string) ($_POST['nilai'] ?? ''));
            $note = trim((string) ($_POST['catatan_guru'] ?? ''));
            if (!$logbookId || !in_array($status, ['approved', 'revision'], true) || ($score !== '' && (!is_numeric($score) || (float) $score < 0 || (float) $score > 100)) || mb_strlen($note) > 2000) {
                throw new DomainException('Data penilaian tidak valid. Nilai harus berada di antara 0 dan 100.');
            }
            $statement = $pdo->prepare("UPDATE logbook SET status = ?, nilai = ?, catatan_guru = ? WHERE id = ? AND kelas_pkl_id = ? AND EXISTS (SELECT 1 FROM users WHERE id = ? AND role = 'siswa' AND kelas_id = logbook.kelas_pkl_id)");
            $statement->execute([$status, $score === '' ? null : $score, $note ?: null, $logbookId, $user['kelas_id'], $user['id']]);
            flash($statement->rowCount() ? 'success' : 'danger', $statement->rowCount() ? 'Ulasan logbook berhasil disimpan.' : 'Logbook tidak ditemukan di kelas Anda.');
            redirect(dashboard_url('logbooks'));
        }

        throw new DomainException('Aksi tidak tersedia untuk akun ini.');
    } catch (DomainException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $exception->getMessage());
        redirect(dashboard_url($view));
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = $exception->getCode() === '23000' ? 'Data sudah ada atau masih digunakan oleh data lain.' : 'Operasi gagal. Periksa data dan koneksi database.';
        flash('danger', $message);
        redirect(dashboard_url($view));
    }
}

if ($view === 'reports' && $role === 'admin' && ($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-monitoring-pkl-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Tanggal', 'Kode kelas', 'Siswa', 'Username', 'Kehadiran', 'Jarak (m)', 'Status logbook', 'Nilai']);
    $rows = $pdo->query("SELECT k.kode_kelas, u.nama, u.username, p.tanggal, p.status AS presensi_status, p.jarak_meter, l.status AS logbook_status, l.nilai FROM users u JOIN kelas_pkl k ON k.id = u.kelas_id LEFT JOIN presensi p ON p.siswa_id = u.id LEFT JOIN logbook l ON l.siswa_id = u.id AND l.tanggal = p.tanggal WHERE u.role = 'siswa' ORDER BY p.tanggal DESC, k.kode_kelas, u.nama");
    while ($row = $rows->fetch()) {
        $values = [$row['tanggal'], $row['kode_kelas'], $row['nama'], $row['username'], $row['presensi_status'], $row['jarak_meter'], $row['logbook_status'], $row['nilai']];
        $values = array_map(static function ($value) {
            return is_string($value) && preg_match('/^[=+@\-\t\r]/', $value) ? "'" . $value : $value;
        }, $values);
        fputcsv($output, $values);
    }
    fclose($output);
    exit;
}

$flashMessage = take_flash();
$pageTitles = ['overview' => 'Ringkasan', 'places' => 'Tempat PKL', 'classes' => 'Kelas PKL', 'teachers' => 'Kelola guru', 'students' => 'Kelola siswa', 'reports' => 'Laporan', 'attendance' => 'Presensi', 'logbook' => 'E-logbook', 'logbooks' => 'Review logbook', 'grades' => 'Nilai saya', 'settings' => 'Pengaturan'];
$pageTitle = $pageTitles[$view] ?? 'Ringkasan';
$navigation = [
    'admin' => ['overview' => 'Ringkasan', 'places' => 'Tempat PKL', 'classes' => 'Kelas PKL', 'teachers' => 'Kelola guru', 'students' => 'Kelola siswa', 'reports' => 'Laporan', 'settings' => 'Pengaturan'],
    'guru' => ['overview' => 'Ringkasan', 'attendance' => 'Presensi siswa', 'logbooks' => 'Review logbook', 'students' => 'Siswa bimbingan', 'settings' => 'Pengaturan'],
    'siswa' => ['overview' => 'Ringkasan', 'attendance' => 'Presensi', 'logbook' => 'E-logbook', 'grades' => 'Nilai & catatan', 'settings' => 'Pengaturan'],
][$role];
$classSql = $role === 'admin'
    ? 'SELECT k.*, t.nama_industri, (SELECT COUNT(*) FROM users u WHERE u.kelas_id = k.id AND u.role = \'guru\' AND u.aktif = 1) AS jumlah_guru, (SELECT COUNT(*) FROM users u WHERE u.kelas_id = k.id AND u.role = \'siswa\' AND u.aktif = 1) AS jumlah_siswa FROM kelas_pkl k JOIN tempat_pkl t ON t.id = k.tempat_pkl_id ORDER BY k.created_at DESC'
    : 'SELECT k.*, t.nama_industri FROM kelas_pkl k JOIN tempat_pkl t ON t.id = k.tempat_pkl_id WHERE k.id = ' . (int) ($user['kelas_id'] ?? 0);
$classes = $pdo->query($classSql)->fetchAll();
$currentClass = $classes[0] ?? null;
$places = $role === 'admin' ? $pdo->query('SELECT * FROM tempat_pkl ORDER BY nama_industri')->fetchAll() : [];
$stats = [];
if ($role === 'admin') {
    $stats = [
        ['label' => 'Tempat PKL', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM tempat_pkl')->fetchColumn(), 'tone' => 'mint'],
        ['label' => 'Kelas aktif', 'value' => (int) $pdo->query('SELECT COUNT(*) FROM kelas_pkl')->fetchColumn(), 'tone' => 'blue'],
        ['label' => 'Guru pembimbing', 'value' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guru' AND aktif = 1")->fetchColumn(), 'tone' => 'amber'],
        ['label' => 'Siswa PKL', 'value' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa' AND aktif = 1")->fetchColumn(), 'tone' => 'rose'],
    ];
} elseif ($role === 'guru') {
    $statement = $pdo->prepare("SELECT COUNT(*) FROM users WHERE kelas_id = ? AND role = 'siswa' AND aktif = 1");
    $statement->execute([$user['kelas_id']]);
    $studentCount = (int) $statement->fetchColumn();
    $statement = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE kelas_pkl_id = ? AND tanggal = CURDATE() AND status = 'valid'");
    $statement->execute([$user['kelas_id']]);
    $stats = [
        ['label' => 'Siswa bimbingan', 'value' => $studentCount, 'tone' => 'mint'],
        ['label' => 'Presensi hari ini', 'value' => (int) $statement->fetchColumn(), 'tone' => 'blue'],
    ];
    $statement = $pdo->prepare("SELECT COUNT(*) FROM logbook WHERE kelas_pkl_id = ? AND status = 'pending'");
    $statement->execute([$user['kelas_id']]);
    $stats[] = ['label' => 'Logbook menunggu', 'value' => (int) $statement->fetchColumn(), 'tone' => 'amber'];
} else {
    $statement = $pdo->prepare("SELECT COUNT(*) FROM presensi WHERE siswa_id = ? AND tanggal = CURDATE() AND status = 'valid'");
    $statement->execute([$user['id']]);
    $stats[] = ['label' => 'Presensi hari ini', 'value' => (int) $statement->fetchColumn(), 'tone' => 'mint'];
    $statement = $pdo->prepare("SELECT COUNT(*) FROM logbook WHERE siswa_id = ? AND status = 'pending'");
    $statement->execute([$user['id']]);
    $stats[] = ['label' => 'Logbook menunggu', 'value' => (int) $statement->fetchColumn(), 'tone' => 'amber'];
    $statement = $pdo->prepare("SELECT COUNT(*) FROM logbook WHERE siswa_id = ? AND status = 'approved'");
    $statement->execute([$user['id']]);
    $stats[] = ['label' => 'Logbook disetujui', 'value' => (int) $statement->fetchColumn(), 'tone' => 'blue'];
}

$dataRows = [];
if ($view === 'places' && $role === 'admin') {
    $dataRows = $places;
} elseif ($view === 'classes') {
    $dataRows = $classes;
} elseif (($view === 'teachers' || $view === 'students') && $role === 'admin') {
    $targetRole = $view === 'teachers' ? 'guru' : 'siswa';
    $statement = $pdo->prepare('SELECT u.id, u.nama, u.username, u.created_at, k.nama_kelas, k.kode_kelas, t.nama_industri FROM users u LEFT JOIN kelas_pkl k ON k.id = u.kelas_id LEFT JOIN tempat_pkl t ON t.id = k.tempat_pkl_id WHERE u.role = ? AND u.aktif = 1 ORDER BY u.nama');
    $statement->execute([$targetRole]);
    $dataRows = $statement->fetchAll();
} elseif ($view === 'students' && $role === 'guru') {
    $statement = $pdo->prepare("SELECT id, nama, username, created_at FROM users WHERE role = 'siswa' AND kelas_id = ? AND aktif = 1 ORDER BY nama");
    $statement->execute([$user['kelas_id']]);
    $dataRows = $statement->fetchAll();
} elseif ($view === 'attendance') {
    if ($role === 'siswa') {
        $statement = $pdo->prepare('SELECT * FROM presensi WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 30');
        $statement->execute([$user['id']]);
    } else {
        $statement = $pdo->prepare("SELECT p.*, u.nama, u.username FROM presensi p JOIN users u ON u.id = p.siswa_id WHERE p.kelas_pkl_id = ? ORDER BY p.tanggal DESC, p.jam DESC LIMIT 100");
        $statement->execute([$user['kelas_id']]);
    }
    $dataRows = $statement->fetchAll();
} elseif (in_array($view, ['logbooks', 'logbook', 'grades'], true)) {
    if ($role === 'guru') {
        $statement = $pdo->prepare('SELECT l.*, u.nama AS nama_siswa, u.username FROM logbook l JOIN users u ON u.id = l.siswa_id WHERE l.kelas_pkl_id = ? ORDER BY l.tanggal DESC, l.created_at DESC LIMIT 100');
        $statement->execute([$user['kelas_id']]);
    } else {
        $statement = $pdo->prepare('SELECT * FROM logbook WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 50');
        $statement->execute([$user['id']]);
    }
    $dataRows = $statement->fetchAll();
} elseif ($view === 'reports') {
    $dataRows = $pdo->query("SELECT k.kode_kelas, u.nama AS nama_siswa, u.username, p.tanggal, p.jam, p.jarak_meter, p.status AS presensi_status, l.status AS logbook_status, l.nilai FROM users u JOIN kelas_pkl k ON k.id = u.kelas_id LEFT JOIN presensi p ON p.siswa_id = u.id LEFT JOIN logbook l ON l.siswa_id = u.id AND l.tanggal = p.tanggal WHERE u.role = 'siswa' ORDER BY p.tanggal DESC, k.kode_kelas, u.nama LIMIT 300")->fetchAll();
}
$editPlace = null;
if ($view === 'places' && $role === 'admin' && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $statement = $pdo->prepare('SELECT * FROM tempat_pkl WHERE id = ?');
        $statement->execute([$editId]);
        $editPlace = $statement->fetch() ?: null;
    }
}
$editClass = null;
if ($view === 'classes' && $role === 'admin' && isset($_GET['edit'])) {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($editId) {
        $statement = $pdo->prepare('SELECT * FROM kelas_pkl WHERE id = ?');
        $statement->execute([$editId]);
        $editClass = $statement->fetch() ?: null;
    }
}
$recentRows = [];
if ($view === 'overview') {
    if ($role === 'admin') {
        $recentRows = $pdo->query("SELECT p.tanggal, p.jam, p.status, p.jarak_meter, u.nama, k.kode_kelas FROM presensi p JOIN users u ON u.id = p.siswa_id JOIN kelas_pkl k ON k.id = p.kelas_pkl_id ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
    } elseif ($role === 'guru') {
        $statement = $pdo->prepare("SELECT l.id, l.tanggal, l.status, l.kegiatan, u.nama AS nama_siswa FROM logbook l JOIN users u ON u.id = l.siswa_id WHERE l.kelas_pkl_id = ? ORDER BY l.created_at DESC LIMIT 6");
        $statement->execute([$user['kelas_id']]);
        $recentRows = $statement->fetchAll();
    } else {
        $statement = $pdo->prepare('SELECT tanggal, status, kegiatan FROM logbook WHERE siswa_id = ? ORDER BY tanggal DESC LIMIT 5');
        $statement->execute([$user['id']]);
        $recentRows = $statement->fetchAll();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/app.css?v=<?= (int) filemtime(__DIR__ . '/assets/css/app.css') ?>">
    <?php if ($view === 'places' && $role === 'admin'): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
    <?php endif; ?>
    <script src="assets/js/app.js?v=<?= (int) filemtime(__DIR__ . '/assets/js/app.js') ?>" defer></script>
</head>
<body class="app-page">
<div class="app-shell">
    <aside class="sidebar" data-sidebar>
        <a class="brand" href="dashboard.php"><span class="brand-mark">R</span><span>Ruang<span class="brand-accent">PKL</span></span></a>
        <div class="sidebar-label">MENU UTAMA</div>
        <nav class="side-nav" aria-label="Navigasi utama">
            <?php foreach ($navigation as $key => $label): ?>
                <a class="nav-link <?= $view === $key ? 'is-active' : '' ?>" href="<?= e(dashboard_url($key)) ?>"><span class="nav-icon" aria-hidden="true"><?= e(['overview' => '⌂', 'places' => '⌖', 'classes' => '▦', 'teachers' => '◉', 'students' => '◎', 'reports' => '▤', 'attendance' => '◷', 'logbooks' => '▧', 'logbook' => '▧', 'grades' => '☆', 'settings' => '⚙'][$key] ?? '·') ?></span><span><?= e($label) ?></span><?= $key === 'overview' ? '<span class="nav-arrow">›</span>' : '' ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><span class="help-mark">i</span><div><strong>Ruang pendampingan</strong><span>PKL tercatat, langkah terarah.</span></div></div>
            <div class="profile-mini"><span class="avatar avatar-green"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span><span><strong><?= e($user['nama']) ?></strong><small><?= e(role_label($role)) ?></small></span><span class="profile-more">···</span></div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button class="icon-button menu-toggle" type="button" data-menu-toggle aria-label="Buka navigasi">☰</button>
            <div class="breadcrumb"><span>Ruang PKL</span><span class="crumb-divider">/</span><strong><?= e($pageTitle) ?></strong></div>
            <div class="topbar-right"><span class="topbar-date" data-local-date></span><span class="topbar-divider"></span><span class="avatar avatar-small"><?= e(strtoupper(substr($user['nama'], 0, 1))) ?></span></div>
        </header>
        <div class="content-wrap">
            <section class="welcome-row"><div><p class="eyebrow"><?= e(role_label($role)) ?></p><h1>Hai, <?= e($user['nama']) ?> <span class="wave">!</span></h1><p class="muted">Pantau kegiatan PKL Anda hari ini.</p></div><div class="clock-block"><span data-local-day></span><strong data-local-clock></strong><small data-local-zone></small></div></section>
            <?php if ($flashMessage): ?><div class="notice notice-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div><?php endif; ?>

            <?php if ($role === 'siswa' && !$currentClass): ?><div class="notice notice-warning">Akun ini belum terhubung ke kelas PKL. Hubungi Admin untuk mendapatkan kode kelas.</div><?php endif; ?>

            <?php if ($view === 'overview'): ?>
                <?php if ($role === 'admin'): ?><section class="hero-strip"><div><span class="hero-kicker">PUSAT MONITORING</span><h2>PKL bergerak,<br>perkembangannya terlihat.</h2><p>Kelola tempat, kelas, dan aktivitas siswa dari satu ruang kerja.</p><a class="button button-light" href="<?= e(dashboard_url('classes')) ?>">Lihat kelas <span>→</span></a></div><div class="hero-illustration" aria-hidden="true"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="hero-leaf">✳</div><div class="hero-chart"><span></span><span></span><span></span><span></span><span></span><span></span></div><div class="hero-tag">PKL<br><strong>2026</strong></div></div></section><?php endif; ?>
                <?php if ($role === 'guru'): ?><section class="class-banner"><div><span class="eyebrow">KELAS BIMBINGAN</span><h2><?= e($currentClass['nama_kelas'] ?? 'Belum ada kelas') ?></h2><p><?= e($currentClass['nama_industri'] ?? 'Tentukan kelas saat registrasi dengan kode dari Admin.') ?></p></div><?php if ($currentClass): ?><span class="code-chip"><?= e($currentClass['kode_kelas']) ?></span><?php endif; ?></section><?php endif; ?>
                <?php if ($role === 'siswa'): ?><section class="class-banner student-banner"><div><span class="eyebrow">TEMPAT PKL SAYA</span><h2><?= e($currentClass['nama_industri'] ?? 'Kelas belum terhubung') ?></h2><p><?= e($currentClass['nama_kelas'] ?? 'Kode kelas akan tampil setelah akun terdaftar.') ?></p></div><?php if ($currentClass): ?><span class="code-chip"><?= e($currentClass['kode_kelas']) ?></span><?php endif; ?></section><?php endif; ?>
                <section class="stat-grid"><?php foreach ($stats as $stat): ?><article class="stat-card"><span class="stat-icon tone-<?= e($stat['tone']) ?>" aria-hidden="true"><?= e(['mint' => '↗', 'blue' => '▤', 'amber' => '◷', 'rose' => '◎'][$stat['tone']]) ?></span><div><p><?= e($stat['label']) ?></p><strong><?= e($stat['value']) ?></strong></div><span class="stat-mark" aria-hidden="true">↗</span></article><?php endforeach; ?></section>
                <section class="section-heading"><div><p class="eyebrow"><?= $role === 'siswa' ? 'AKTIVITAS SAYA' : 'AKTIVITAS TERBARU' ?></p><h2><?= $role === 'guru' ? 'Jurnal untuk ditinjau' : ($role === 'siswa' ? 'Jurnal kegiatan terakhir' : 'Presensi terbaru') ?></h2></div><?php if ($role === 'admin'): ?><a class="text-link" href="<?= e(dashboard_url('reports')) ?>">Buka laporan <span>→</span></a><?php elseif ($role === 'guru'): ?><a class="text-link" href="<?= e(dashboard_url('logbooks')) ?>">Semua jurnal <span>→</span></a><?php else: ?><a class="text-link" href="<?= e(dashboard_url('logbook')) ?>">Semua jurnal <span>→</span></a><?php endif; ?></section>
                <?php if ($role === 'admin' && $classes): ?><section class="class-overview"><div class="mini-section-title"><div><h3>Ringkasan kuota kelas</h3><p>Penggunaan tiket pendaftaran saat ini</p></div><a class="text-link" href="<?= e(dashboard_url('classes')) ?>">Kelola kelas <span>→</span></a></div><div class="table-scroll"><table><thead><tr><th>Kelas</th><th>Tempat PKL</th><th>Guru</th><th>Siswa</th><th>Kapasitas</th></tr></thead><tbody><?php foreach (array_slice($classes, 0, 5) as $class): ?><tr><td><strong><?= e($class['nama_kelas']) ?></strong><small class="cell-sub"><?= e($class['kode_kelas']) ?></small></td><td><?= e($class['nama_industri']) ?></td><td><?= e($class['jumlah_guru']) ?> / <?= e($class['kuota_guru']) ?></td><td><?= e($class['jumlah_siswa']) ?> / <?= e($class['kuota_siswa']) ?></td><td><div class="capacity-track"><span style="width:<?= e(min(100, (int) round(((int) $class['jumlah_siswa'] / max(1, (int) $class['kuota_siswa'])) * 100))) ?>%"></span></div></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
                <section class="table-panel"><div class="table-scroll"><table><thead><tr><?php if ($role === 'admin'): ?><th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Jarak</th><th>Status</th><?php elseif ($role === 'guru'): ?><th>Siswa</th><th>Tanggal</th><th>Ringkasan kegiatan</th><th>Status</th><?php else: ?><th>Tanggal</th><th>Ringkasan kegiatan</th><th>Status jurnal</th><?php endif; ?></tr></thead><tbody>
                    <?php if (!$recentRows): ?><tr><td colspan="5" class="empty-cell">Belum ada aktivitas yang tercatat.</td></tr><?php endif; ?>
                    <?php foreach ($recentRows as $row): ?><tr><?php if ($role === 'admin'): ?><td><strong><?= e($row['nama']) ?></strong></td><td><?= e($row['kode_kelas']) ?></td><td><?= e(id_date($row['tanggal'])) ?> <small class="cell-sub"><?= e($row['jam']) ?></small></td><td><?= e(number_format((float) $row['jarak_meter'], 0, ',', '.')) ?> m</td><td><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></td><?php elseif ($role === 'guru'): ?><td><strong><?= e($row['nama_siswa']) ?></strong></td><td><?= e(id_date($row['tanggal'])) ?></td><td class="truncate-cell"><?= e(mb_substr($row['kegiatan'], 0, 90)) ?></td><td><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></td><?php else: ?><td><?= e(id_date($row['tanggal'])) ?></td><td class="truncate-cell"><?= e(mb_substr($row['kegiatan'], 0, 100)) ?></td><td><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></td><?php endif; ?></tr><?php endforeach; ?>
                </tbody></table></div></section>
            <?php endif; ?>

            <?php if ($view === 'places' && $role === 'admin'): ?>
                <section class="workspace-grid">
                    <div class="form-panel">
                        <div class="panel-heading"><span class="eyebrow">LOKASI PKL</span><h2><?= $editPlace ? 'Edit tempat industri' : 'Tambah tempat industri' ?></h2><p>Koordinat menjadi titik acuan validasi radius presensi.</p></div>
                        <form method="post" class="stack-form" data-place-form data-guide="Isi nama dan alamat tempat PKL. Tentukan titik pada peta atau gunakan GPS, lalu atur radius presensi sebelum menyimpan.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="<?= $editPlace ? 'update_place' : 'create_place' ?>">
                            <?php if ($editPlace): ?><input type="hidden" name="place_id" value="<?= e($editPlace['id']) ?>"><?php endif; ?>
                            <label>Nama bengkel / industri<input name="nama_industri" required maxlength="150" placeholder="Contoh: Bengkel Sejahtera Motor" value="<?= e($editPlace['nama_industri'] ?? '') ?>"></label>
                            <label>Alamat<textarea name="alamat" rows="3" required><?= e($editPlace['alamat'] ?? '') ?></textarea></label>
                            <div class="coordinate-tools">
                                <button class="button button-outline" type="button" data-get-device-location>Gunakan lokasi perangkat</button>
                                <span class="form-feedback" data-coordinate-feedback aria-live="polite">Pilih titik pada peta atau isi koordinat secara manual.</span>
                            </div>
                            <div class="form-row">
                                <label>Latitude<input name="latitude" type="number" step="any" min="-90" max="90" required placeholder="-6.200000" data-coordinate-latitude value="<?= e($editPlace['latitude'] ?? '') ?>"></label>
                                <label>Longitude<input name="longitude" type="number" step="any" min="-180" max="180" required placeholder="106.816666" data-coordinate-longitude value="<?= e($editPlace['longitude'] ?? '') ?>"></label>
                            </div>
                            <p class="map-help">Klik peta untuk menentukan titik lokasi. GPS memerlukan izin lokasi browser.</p>
                            <div class="place-picker-map" data-place-picker-map role="application" aria-label="Peta pemilihan koordinat tempat PKL"></div>
                            <label>Radius geofence (meter)<input name="radius_meter" type="number" min="1" max="5000" value="<?= e($editPlace['radius_meter'] ?? '100') ?>" required></label>
                            <div class="form-actions"><button class="button button-primary" type="submit"><?= $editPlace ? 'Simpan perubahan' : 'Simpan tempat' ?> <span>→</span></button><?php if ($editPlace): ?><a class="button button-outline" href="<?= e(dashboard_url('places')) ?>">Batal</a><?php endif; ?></div>
                        </form>
                    </div>
                    <div class="list-panel">
                        <div class="panel-heading"><span class="eyebrow">DAFTAR LOKASI</span><h2><?= count($dataRows) ?> tempat PKL</h2></div>
                        <?php if (!$dataRows): ?><div class="empty-state">Belum ada tempat. Tambahkan lokasi pertama di formulir.</div><?php endif; ?>
                        <?php foreach ($dataRows as $place): ?><article class="location-item"><span class="location-pin">⌖</span><div class="location-details"><strong><?= e($place['nama_industri']) ?></strong><p><?= e($place['alamat']) ?></p><small><?= e($place['latitude']) ?>, <?= e($place['longitude']) ?> <span class="dot-separator">·</span> radius <?= e($place['radius_meter']) ?> m</small><div class="item-actions"><a class="button button-outline button-small" href="<?= e(dashboard_url('places')) ?>&amp;edit=<?= e($place['id']) ?>">Edit</a><form method="post" data-confirm-title="Yakin ingin menghapus tempat PKL <?= e($place['nama_industri']) ?>?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_place"><input type="hidden" name="place_id" value="<?= e($place['id']) ?>"><button class="button button-danger-soft button-small" type="submit">Hapus</button></form></div></div></article><?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($view === 'classes' && $role === 'admin'): ?>
                <section class="workspace-grid">
                    <div class="form-panel">
                        <div class="panel-heading"><span class="eyebrow">KUOTA PKL</span><h2><?= $editClass ? 'Edit kelas PKL' : 'Buat kelas PKL' ?></h2><p>Kode kelas dipakai guru dan siswa saat mendaftar.</p></div>
                        <?php if (!$places): ?><div class="notice notice-warning">Tambahkan tempat PKL sebelum membuat kelas.</div><?php else: ?>
                            <form method="post" class="stack-form" data-guide="Isi nama dan kode kelas, pilih tempat PKL, lalu tentukan kuota guru dan siswa. Kode kelas digunakan saat pendaftaran.">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="<?= $editClass ? 'update_class' : 'create_class' ?>">
                                <?php if ($editClass): ?><input type="hidden" name="class_id" value="<?= e($editClass['id']) ?>"><?php endif; ?>
                                <label>Nama kelas<input name="nama_kelas" required maxlength="100" placeholder="Teknik Otomotif A" value="<?= e($editClass['nama_kelas'] ?? '') ?>"></label>
                                <label>Kode kelas<input name="kode_kelas" required maxlength="40" pattern="[A-Za-z0-9-]{3,40}" placeholder="TOYOTA-01" value="<?= e($editClass['kode_kelas'] ?? '') ?>"></label>
                                <label>Tempat PKL<select name="tempat_pkl_id" required><option value="">Pilih tempat</option><?php foreach ($places as $place): ?><option value="<?= e($place['id']) ?>" <?= $editClass && (int) $editClass['tempat_pkl_id'] === (int) $place['id'] ? 'selected' : '' ?>><?= e($place['nama_industri']) ?></option><?php endforeach; ?></select></label>
                                <div class="form-row"><label>Kuota guru<input name="kuota_guru" type="number" min="1" max="10" value="<?= e($editClass['kuota_guru'] ?? '1') ?>" required></label><label>Kuota siswa<input name="kuota_siswa" type="number" min="1" max="500" value="<?= e($editClass['kuota_siswa'] ?? '20') ?>" required></label></div>
                                <div class="form-actions"><button class="button button-primary" type="submit"><?= $editClass ? 'Simpan perubahan' : 'Buat kelas' ?> <span>→</span></button><?php if ($editClass): ?><a class="button button-outline" href="<?= e(dashboard_url('classes')) ?>">Batal</a><?php endif; ?></div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="list-panel">
                        <div class="panel-heading"><span class="eyebrow">DAFTAR KELAS</span><h2><?= count($dataRows) ?> kelas terdaftar</h2></div>
                        <div class="table-scroll"><table><thead><tr><th>Kelas & kode</th><th>Tempat PKL</th><th>Kuota guru</th><th>Kuota siswa</th><th>Aksi</th></tr></thead><tbody>
                            <?php if (!$dataRows): ?><tr><td colspan="5" class="empty-cell">Belum ada kelas.</td></tr><?php endif; ?>
                            <?php foreach ($dataRows as $class): ?><tr><td><strong><?= e($class['nama_kelas']) ?></strong><small class="cell-sub"><span class="code-chip code-chip-small"><?= e($class['kode_kelas']) ?></span></small></td><td><?= e($class['nama_industri']) ?></td><td><?= e($class['jumlah_guru'] ?? '0') ?> / <?= e($class['kuota_guru']) ?></td><td><?= e($class['jumlah_siswa'] ?? '0') ?> / <?= e($class['kuota_siswa']) ?><div class="capacity-track"><span style="width:<?= e(min(100, (int) round(((int) ($class['jumlah_siswa'] ?? 0) / max(1, (int) $class['kuota_siswa'])) * 100))) ?>%"></span></div></td><td><div class="item-actions"><a class="button button-outline button-small" href="<?= e(dashboard_url('classes')) ?>&amp;edit=<?= e($class['id']) ?>">Edit</a><form method="post" data-confirm-title="Yakin ingin menghapus kelas <?= e($class['nama_kelas']) ?>?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_class"><input type="hidden" name="class_id" value="<?= e($class['id']) ?>"><button class="button button-danger-soft button-small" type="submit">Hapus</button></form></div></td></tr><?php endforeach; ?>
                        </tbody></table></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (in_array($view, ['teachers', 'students'], true)): ?><section class="section-heading section-heading-list"><div><p class="eyebrow"><?= $view === 'teachers' ? 'PEMBIMBING TERDAFTAR' : 'PESERTA PKL' ?></p><h2><?= count($dataRows) ?> <?= $view === 'teachers' ? 'guru pembimbing' : 'siswa terdaftar' ?></h2></div></section><section class="table-panel"><div class="table-scroll"><table><thead><tr><th>Nama</th><th>Username</th><th>Kelas</th><th>Tempat PKL</th><?php if ($role === 'admin'): ?><th>Aksi</th><?php endif; ?></tr></thead><tbody><?php if (!$dataRows): ?><tr><td colspan="5" class="empty-cell">Belum ada data pengguna.</td></tr><?php endif; ?><?php foreach ($dataRows as $row): ?><tr><td><span class="user-cell"><span class="avatar avatar-table"><?= e(strtoupper(substr($row['nama'], 0, 1))) ?></span><strong><?= e($row['nama']) ?></strong></span></td><td><?= e($row['username']) ?></td><td><?= e($row['nama_kelas'] ?? 'Belum terhubung') ?><?php if (!empty($row['kode_kelas'])): ?><small class="cell-sub"><?= e($row['kode_kelas']) ?></small><?php endif; ?></td><td><?= e($row['nama_industri'] ?? '—') ?></td><?php if ($role === 'admin'): ?><td><form method="post" data-confirm-title="Yakin ingin menghapus akun <?= $view === 'teachers' ? 'guru' : 'siswa' ?> <?= e($row['nama']) ?>?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= e($row['id']) ?>"><input type="hidden" name="return_view" value="<?= e($view) ?>"><button class="icon-button delete-button" type="submit" aria-label="Hapus <?= e($row['nama']) ?>" title="Hapus pengguna">⌫</button></form></td><?php endif; ?></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

            <?php if ($view === 'attendance'): ?><section class="section-heading section-heading-list"><div><p class="eyebrow">GEOTAGGING</p><h2><?= $role === 'siswa' ? 'Catat kehadiran hari ini' : 'Rekap presensi siswa' ?></h2></div></section><?php if ($role === 'siswa'): ?><section class="attendance-panel"><div class="attendance-copy"><span class="attendance-icon">⌖</span><div><h3>Presensi berbasis lokasi</h3><p>Lokasi Anda dibandingkan dengan titik tempat PKL. Pastikan GPS perangkat aktif.</p></div></div><form method="post" data-geolocation-form data-guide="Aktifkan GPS dan izinkan akses lokasi pada browser. Tekan tombol untuk mencatat presensi; sistem akan memeriksa jarak Anda dari tempat PKL."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="attendance"><input type="hidden" name="latitude"><input type="hidden" name="longitude"><button class="button button-primary" type="submit" data-location-button>Ambil lokasi & presensi <span>→</span></button><p class="form-feedback" data-location-feedback aria-live="polite"></p></form></section><?php endif; ?><section class="table-panel"><div class="table-scroll"><table><thead><tr><?php if ($role === 'guru'): ?><th>Siswa</th><?php endif; ?><th>Tanggal</th><th>Jam</th><th>Jarak dari lokasi</th><th>Status</th></tr></thead><tbody><?php if (!$dataRows): ?><tr><td colspan="5" class="empty-cell">Belum ada presensi tercatat.</td></tr><?php endif; ?><?php foreach ($dataRows as $row): ?><tr><?php if ($role === 'guru'): ?><td><strong><?= e($row['nama']) ?></strong><small class="cell-sub"><?= e($row['username']) ?></small></td><?php endif; ?><td><?= e(id_date($row['tanggal'])) ?></td><td><?= e($row['jam']) ?></td><td><?= e(number_format((float) $row['jarak_meter'], 1, ',', '.')) ?> m</td><td><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

            <?php if ($view === 'logbook' && $role === 'siswa'): ?><section class="workspace-grid logbook-grid"><div class="form-panel"><div class="panel-heading"><span class="eyebrow">JURNAL HARIAN</span><h2 data-logbook-heading>Ceritakan kegiatanmu</h2><p>Catatan dikirim ke guru pembimbing untuk ditinjau.</p></div><form method="post" enctype="multipart/form-data" class="stack-form" data-logbook-form data-guide="Pilih tanggal kegiatan, ceritakan pekerjaan atau keterampilan yang dipelajari, lalu lampirkan foto bukti jika ada. Pastikan deskripsi sesuai kegiatan PKL Anda."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="create_logbook"><label>Tanggal kegiatan<input type="date" name="tanggal" max="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d')) ?>" required></label><label>Deskripsi kegiatan<textarea name="kegiatan" rows="6" maxlength="5000" required placeholder="Tuliskan pekerjaan, proses, atau keterampilan yang dipelajari..."></textarea></label><label>Foto bukti <span class="label-note">JPG, PNG, WebP · maks. 5 MB</span><input type="file" name="foto_bukti" accept="image/jpeg,image/png,image/webp" capture="environment"></label><button class="button button-primary" type="submit">Kirim logbook <span>→</span></button></form></div><div class="list-panel"><div class="panel-heading"><span class="eyebrow">RIWAYAT SAYA</span><h2><?= count($dataRows) ?> catatan</h2></div><?php if (!$dataRows): ?><div class="empty-state">Logbook yang Anda kirim akan muncul di sini.</div><?php endif; ?><?php foreach ($dataRows as $row): ?><article class="journal-item"><div class="journal-head"><strong><?= e(id_date($row['tanggal'])) ?></strong><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></div><p><?= nl2br(e($row['kegiatan'])) ?></p><?php if ($row['foto_bukti']): ?><a class="photo-link" href="uploads/<?= e(rawurlencode($row['foto_bukti'])) ?>" target="_blank" rel="noopener">Lihat foto bukti ↗</a><?php endif; ?><?php if ($row['status'] === 'revision'): ?><p><button class="text-link" type="button" data-revise-logbook data-date="<?= e($row['tanggal']) ?>" data-activity="<?= e($row['kegiatan']) ?>">Edit & kirim ulang ↗</button></p><?php endif; ?><?php if ($row['catatan_guru']): ?><div class="teacher-note"><strong>Catatan guru<?= $row['nilai'] !== null ? ' · Nilai ' . e($row['nilai']) : '' ?></strong><p><?= nl2br(e($row['catatan_guru'])) ?></p></div><?php endif; ?></article><?php endforeach; ?></div></section><?php endif; ?>

            <?php if ($view === 'logbooks' && $role === 'guru'): ?><section class="section-heading section-heading-list"><div><p class="eyebrow">PENDAMPINGAN</p><h2>Jurnal kegiatan siswa</h2></div><span class="result-count"><?= count($dataRows) ?> catatan</span></section><div class="journal-list"><?php if (!$dataRows): ?><section class="empty-state">Belum ada logbook dari siswa kelas Anda.</section><?php endif; ?><?php foreach ($dataRows as $row): ?><article class="review-card"><div class="review-main"><div class="journal-head"><div><strong><?= e($row['nama_siswa']) ?></strong><small class="cell-sub"><?= e($row['username']) ?> · <?= e(id_date($row['tanggal'])) ?></small></div><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span></div><p class="review-activity"><?= nl2br(e($row['kegiatan'])) ?></p><?php if ($row['foto_bukti']): ?><a class="photo-link" href="uploads/<?= e(rawurlencode($row['foto_bukti'])) ?>" target="_blank" rel="noopener">Buka foto bukti ↗</a><?php endif; ?></div><form method="post" class="review-form" data-guide="Periksa kegiatan dan foto bukti. Isi nilai 0–100 bila diperlukan, tambahkan catatan, lalu pilih Setujui atau Minta revisi."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="review_logbook"><input type="hidden" name="logbook_id" value="<?= e($row['id']) ?>"><label>Nilai<input name="nilai" type="number" min="0" max="100" step="0.01" value="<?= e($row['nilai'] ?? '') ?>" placeholder="0–100"></label><label>Catatan<textarea name="catatan_guru" rows="3" maxlength="2000" placeholder="Catatan atau arahan revisi"><?= e($row['catatan_guru'] ?? '') ?></textarea></label><div class="review-actions"><button class="button button-outline" type="submit" name="status" value="revision">Minta revisi</button><button class="button button-primary" type="submit" name="status" value="approved">Setujui</button></div></form></article><?php endforeach; ?></div><?php endif; ?>

            <?php if ($view === 'grades' && $role === 'siswa'): ?><section class="section-heading section-heading-list"><div><p class="eyebrow">EVALUASI PEMBIMBING</p><h2>Nilai & catatan guru</h2></div></section><section class="grade-list"><?php if (!$dataRows): ?><div class="empty-state">Penilaian dan catatan dari guru akan muncul di sini.</div><?php endif; ?><?php foreach ($dataRows as $row): ?><article class="grade-row"><div class="grade-date"><span class="eyebrow">TANGGAL</span><strong><?= e(id_date($row['tanggal'])) ?></strong></div><div class="grade-detail"><span class="badge badge-<?= e(status_class($row['status'])) ?>"><i></i><?= e(status_label($row['status'])) ?></span><p><?= e($row['kegiatan']) ?></p><?php if ($row['catatan_guru']): ?><small><?= e($row['catatan_guru']) ?></small><?php endif; ?></div><div class="grade-score"><span>Nilai</span><strong><?= $row['nilai'] !== null ? e(number_format((float) $row['nilai'], 0)) : '—' ?></strong></div></article><?php endforeach; ?></section><?php endif; ?>

            <?php if ($view === 'reports' && $role === 'admin'): ?><section class="section-heading section-heading-list"><div><p class="eyebrow">REKAP MONITORING</p><h2>Presensi dan kegiatan siswa</h2><p class="muted">Maksimal 300 baris terbaru. Unduh CSV untuk dibuka di Excel.</p></div><a class="button button-primary" href="<?= e(dashboard_url('reports')) ?>&format=csv">Unduh CSV <span>↓</span></a></section><section class="table-panel"><div class="table-scroll"><table><thead><tr><th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Presensi</th><th>Logbook</th><th>Nilai</th></tr></thead><tbody><?php if (!$dataRows): ?><tr><td colspan="6" class="empty-cell">Belum ada data monitoring.</td></tr><?php endif; ?><?php foreach ($dataRows as $row): ?><tr><td><strong><?= e($row['nama_siswa']) ?></strong><small class="cell-sub"><?= e($row['username']) ?></small></td><td><?= e($row['kode_kelas']) ?></td><td><?= $row['tanggal'] ? e(id_date($row['tanggal'])) : '—' ?></td><td><?php if ($row['presensi_status']): ?><span class="badge badge-<?= e(status_class($row['presensi_status'])) ?>"><i></i><?= e(status_label($row['presensi_status'])) ?></span><small class="cell-sub"><?= e($row['jarak_meter']) ?> m</small><?php else: ?>—<?php endif; ?></td><td><?= $row['logbook_status'] ? e(status_label($row['logbook_status'])) : '—' ?></td><td><?= $row['nilai'] !== null ? e($row['nilai']) : '—' ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

            <?php if ($view === 'settings'): ?>
                <section class="section-heading section-heading-list"><div><p class="eyebrow">PREFERENSI APLIKASI</p><h2>Pengaturan</h2><p class="muted">Atur tampilan dan panduan sesuai kebutuhan Anda.</p></div></section>
                <section class="settings-page-grid">
                    <div class="form-panel settings-panel">
                        <div class="panel-heading"><span class="eyebrow">TAMPILAN</span><h2>Tema aplikasi</h2><p>Tema terang digunakan secara default. Aktifkan untuk menggunakan tema gelap.</p></div>
                        <label class="setting-toggle" title="Gunakan tema gelap"><input type="checkbox" role="switch" aria-label="Gunakan tema gelap" data-theme-toggle><span class="setting-switch-track" aria-hidden="true"></span></label>
                    </div>
                    <div class="form-panel settings-panel">
                        <div class="panel-heading"><span class="eyebrow">BANTUAN</span><h2>Panduan penggunaan</h2><p>Tampilkan petunjuk singkat pada formulir agar lebih mudah digunakan.</p></div>
                        <label class="setting-toggle" title="Tampilkan panduan penggunaan"><input type="checkbox" role="switch" aria-label="Tampilkan panduan penggunaan" data-guide-toggle><span class="setting-switch-track" aria-hidden="true"></span></label>
                    </div>
                    <p class="settings-status settings-page-status" data-settings-status role="status" aria-live="polite" hidden></p>
                </section>
                <section class="workspace-grid settings-security">
                    <div class="form-panel"><div class="panel-heading"><span class="eyebrow">KEAMANAN AKUN</span><h2>Ubah kata sandi</h2><p>Gunakan kata sandi unik minimal 8 karakter. Perubahan berlaku setelah disimpan.</p></div><form method="post" class="stack-form" data-guide="Masukkan kata sandi saat ini, lalu buat kata sandi baru minimal 8 karakter dan ulangi dengan ejaan yang sama."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_password"><label>Kata sandi saat ini<input type="password" name="current_password" autocomplete="current-password" required></label><label>Kata sandi baru<input type="password" name="new_password" autocomplete="new-password" minlength="8" required></label><label>Ulangi kata sandi baru<input type="password" name="confirm_password" autocomplete="new-password" minlength="8" required></label><button class="button button-primary" type="submit">Simpan kata sandi <span>→</span></button></form></div>
                    <div class="list-panel"><div class="panel-heading"><span class="eyebrow">AKUN AKTIF</span><h2><?= e($user['nama']) ?></h2></div><div class="location-item"><span class="location-pin">◎</span><div><strong><?= e(role_label($role)) ?></strong><p>Username: <?= e($user['username']) ?></p><small>Keluar untuk mengakhiri sesi pada perangkat ini.</small></div></div><div class="account-actions"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button class="button button-logout" type="submit"><span aria-hidden="true">↪</span> Keluar dari akun</button></form><?php if ($role === 'admin'): ?><form method="post" data-confirm-title="Yakin ingin menghapus akun Admin <?= e($user['username']) ?>?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete_account"><button class="button button-danger-soft" type="submit"><span aria-hidden="true">⌫</span> Hapus akun</button></form><?php endif; ?></div></div>
                </section>
            <?php endif; ?>
            <?php if ($role === 'admin'): ?><dialog class="confirm-dialog" data-confirm-dialog aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-description"><header class="confirm-dialog-header"><span class="confirm-dialog-label">PERINGATAN</span></header><div class="confirm-dialog-content"><h2 id="confirm-dialog-title" data-confirm-dialog-title></h2><p class="confirm-dialog-note" id="confirm-dialog-description">Periksa kembali data yang dipilih. Penghapusan permanen tidak dapat dibatalkan.</p><div class="confirm-dialog-actions"><button class="button confirm-dialog-cancel" type="button" data-cancel-confirm>Batal</button><button class="button confirm-dialog-submit" type="button" data-submit-confirm>Ya, hapus</button></div></div></dialog><?php endif; ?>
            <footer class="app-footer"><span>Ruang PKL</span><span>Monitoring Praktik Kerja Lapangan · <?= e(date('Y')) ?></span></footer>
        </div>
    </main>
</div>
</body>
</html>