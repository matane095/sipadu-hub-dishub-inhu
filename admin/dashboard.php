<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}
include '../config/koneksi.php';
$judul_halaman = "Dashboard Petugas";
$base_path = "../";
include '../includes/header.php';

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_kategori = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT p.*, k.nama_kategori FROM pengaduan p 
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori WHERE 1=1";
$types = "";
$params = [];

if ($filter_status != '') { $sql .= " AND p.status = ?"; $types .= "s"; $params[] = $filter_status; }
if ($filter_kategori > 0) { $sql .= " AND p.id_kategori = ?"; $types .= "i"; $params[] = $filter_kategori; }
if ($search != '') {
    $sql .= " AND (p.ticket LIKE ? OR p.nama_pelapor LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = mysqli_prepare($koneksi, $sql);
if ($types != '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori");

$label_status = ['diterima'=>'Diterima','diproses'=>'Diproses','selesai'=>'Selesai','ditolak'=>'Ditolak'];
$warna_status = ['diterima'=>'primary','diproses'=>'warning','selesai'=>'success','ditolak'=>'danger'];
?>

<nav class="navbar navbar-petugas navbar-dark">
  <div class="container">
    <span class="navbar-brand mb-0">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
    <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
  </div>
</nav>

<div class="container py-4">
  <h4 class="mb-4">Dashboard Pengaduan</h4>

  <form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="Cari tiket / nama..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">Semua Status</option>
        <?php foreach ($label_status as $key => $label): ?>
          <option value="<?= $key ?>" <?= $filter_status == $key ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="kategori" class="form-select">
        <option value="">Semua Kategori</option>
        <?php mysqli_data_seek($kategori_list, 0); while ($k = mysqli_fetch_assoc($kategori_list)): ?>
          <option value="<?= $k['id_kategori'] ?>" <?= $filter_kategori == $k['id_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100">Filter</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <table class="table table-hover mb-0">
      <thead class="table-dark">
        <tr>
          <th>Tiket</th><th>Tanggal</th><th>Pelapor</th><th>Kategori</th><th>Lokasi</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($data) == 0): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengaduan yang cocok.</td></tr>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><code><?= htmlspecialchars($row['ticket']) ?></code></td>
              <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
              <td><?= htmlspecialchars($row['nama_pelapor']) ?></td>
              <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
              <td><?= htmlspecialchars($row['lokasi']) ?></td>
              <td><span class="badge bg-<?= $warna_status[$row['status']] ?>"><?= $label_status[$row['status']] ?></span></td>
              <td><a href="detail.php?id=<?= $row['id_pengaduan'] ?>" class="btn btn-sm btn-outline-primary">Kelola</a></td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>