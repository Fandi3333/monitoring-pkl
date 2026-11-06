<?php
require_once __DIR__ . '/config/app.php';
require_login();
require_role(['admin', 'guru']); // hanya admin & guru

$pdo = db();
$user = current_user();

// Integrasi helper dari functions.php
// haversine_meters() untuk hitung jarak
// status_class() untuk warna status

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $lat = (float)($_POST['latitude'] ?? 0);
    $lng = (float)($_POST['longitude'] ?? 0);
    $radius = (int)($_POST['radius'] ?? 100);

    if ($nama && $lat && $lng) {
        $stmt = $pdo->prepare("INSERT INTO tempat_pkl (nama_tempat, alamat, latitude, longitude, radius_meter, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $alamat, $lat, $lng, $radius, $user['id']]);
        flash('success', 'Tempat PKL berhasil ditambahkan!');
    } else {
        flash('danger', 'Lengkapi data koordinat mas!');
    }
    redirect('tempat-pkl.php');
}

$tempat = $pdo->query("SELECT t.*, u.nama as pembuat FROM tempat_pkl t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id DESC")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<h3>Daftar Tempat PKL - Geofencing</h3>
<p>Fitur ini pakai rumus <code>haversine_meters()</code> dari <code>functions.php</code> untuk validasi jarak siswa.</p>

<form method="POST" class="card p-3 mb-4">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <div class="row">
        <div class="col-md-4"><input name="nama" class="form-control" placeholder="Nama Perusahaan" required></div>
        <div class="col-md-4"><input name="alamat" class="form-control" placeholder="Alamat"></div>
        <div class="col-md-2"><input name="latitude" type="number" step="any" class="form-control" placeholder="Lat -7.xxx" required></div>
        <div class="col-md-2"><input name="longitude" type="number" step="any" class="form-control" placeholder="Long 109.xxx" required></div>
    </div>
    <div class="mt-2">
        <input name="radius" type="number" class="form-control d-inline w-auto" value="100"> meter
        <button class="btn btn-primary">Simpan Tempat</button>
    </div>
</form>

<table class="table table-bordered">
<tr><th>Nama</th><th>Koordinat</th><th>Radius</th><th>Pembuat</th><th>Status</th></tr>
<?php foreach($tempat as $t): ?>
<tr>
    <td><?= e($t['nama_tempat']) ?></td>
    <td><?= $t['latitude'] ?>, <?= $t['longitude'] ?></td>
    <td><?= $t['radius_meter'] ?>m</td>
    <td><?= e($t['pembuat']) ?></td>
    <td><span class="badge bg-<?= status_class('aktif') ?>">Aktif</span></td>
</tr>
<?php endforeach; ?>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
