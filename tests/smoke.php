<?php
require_once __DIR__ . '/../includes/functions.php';

$passwordHash = password_hash('smoke-password', PASSWORD_DEFAULT);
if (!password_verify('smoke-password', $passwordHash)) {
    throw new RuntimeException('Pemeriksaan hash password gagal.');
}

$zeroDistance = haversine_meters(-6.2, 106.8, -6.2, 106.8);
$oneLongitudeDegree = haversine_meters(0, 0, 0, 1);
if ($zeroDistance !== 0.0 || $oneLongitudeDegree < 111000 || $oneLongitudeDegree > 112000) {
    throw new RuntimeException('Perhitungan Haversine tidak sesuai nilai acuan.');
}

echo "Smoke test lulus: hash password dan Haversine.\n";