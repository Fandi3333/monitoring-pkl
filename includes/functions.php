<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('index.php');
    }
    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses tidak diizinkan.');
    }
    return $user;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba kembali.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function role_label(string $role): string
{
    $labels = ['admin' => 'Admin / Kaprog', 'guru' => 'Guru pembimbing', 'siswa' => 'Siswa PKL'];
    return $labels[$role] ?? 'Pengguna';
}

function haversine_meters(float $lat1, float $lon1, float $lat2, float $lon2): float
{
    $earthRadius = 6371000;
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;
    return 2 * $earthRadius * atan2(sqrt($a), sqrt(max(0, 1 - $a)));
}

function id_date(string $date): string
{
    $time = strtotime($date);
    if (!$time) {
        return $date;
    }
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return date('j', $time) . ' ' . $months[(int) date('n', $time)] . ' ' . date('Y', $time);
}

function status_label(string $status): string
{
    $labels = ['valid' => 'Valid', 'luar_radius' => 'Luar radius', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'revision' => 'Perlu revisi'];
    return $labels[$status] ?? ucfirst($status);
}

function status_class(string $status): string
{
    $classes = ['valid' => 'success', 'approved' => 'success', 'pending' => 'warning', 'revision' => 'danger', 'luar_radius' => 'danger'];
    return $classes[$status] ?? 'neutral';
}

function dashboard_url(string $view = 'overview'): string
{
    return 'dashboard.php?view=' . rawurlencode($view);
}