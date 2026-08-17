<?php
session_start();
if (!isset($_SESSION['id_pelapor'])) {
    header("Location: index.php");
    exit;
}
include 'config/koneksi.php';
$judul_halaman = "Riwayat Pengaduan Saya";
$base_path = "";
include 'includes/header.php';

$id_pelapor = $_SESSION['id_pelapor'];

$stmt = mysqli_prepare($koneksi,
    "SELECT p.*, k.nama_kategori FROM pengaduan p
     LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
     WHERE p.id_pelapor = ? ORDER BY p.created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $id_pelapor);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);

$label_status = ['diterima'=>'Diterima','diproses'=>'Diproses','selesai'=>'Selesai','ditolak'=>'Ditolak'];
$warna_status = ['diterima'=>'primary','diproses'=>'warning','selesai'=>'success','ditolak'=>'danger'];
?>

<div class="container py-5">
  <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Kembali</a>
  <h2 class="mb-1">Riwayat Pengaduan Saya</h2>
  <p class="text-muted mb-4">Masuk sebagai <?= htmlspecialchars($_SESSION['pelapor_nama']) ?></p>

  <?php if (mysqli_num_rows($data) == 0): ?>
    <div class="alert alert-secondary">Kamu belum pernah membuat pengaduan.</div>
  <?php else: ?>
    <div class="card shadow-sm">
      <table class="table table-hover mb-0">
        <thead class="table-dark">
          <tr><th>Tiket</th><th>Tanggal</th><th>Kategori</th><th>Lokasi</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><code><?= htmlspecialchars($row['ticket']) ?></code></td>
              <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
              <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
              <td><?= htmlspecialchars($row['lokasi']) ?></td>
              <td><span class="badge bg-<?= $warna_status[$row['status']] ?>"><?= $label_status[$row['status']] ?></span></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>