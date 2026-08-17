<?php
include 'config/koneksi.php';
$judul_halaman = "Lacak Pengaduan";
$base_path = "";
include 'includes/header.php';

$hasil = null;
$tidak_ketemu = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ticket = $_POST['ticket'];

    $stmt = mysqli_prepare($koneksi,
        "SELECT p.*, k.nama_kategori FROM pengaduan p
         LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
         WHERE p.ticket = ?");
    mysqli_stmt_bind_param($stmt, "s", $ticket);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $hasil = mysqli_fetch_assoc($result);

        $stmt2 = mysqli_prepare($koneksi,
            "SELECT * FROM riwayat_status WHERE id_pengaduan = ? ORDER BY waktu ASC");
        mysqli_stmt_bind_param($stmt2, "i", $hasil['id_pengaduan']);
        mysqli_stmt_execute($stmt2);
        $riwayat = mysqli_stmt_get_result($stmt2);
    } else {
        $tidak_ketemu = true;
    }
}

$label_status = [
    'diterima' => 'Diterima',
    'diproses' => 'Diproses',
    'selesai'  => 'Selesai',
    'ditolak'  => 'Ditolak'
];
?>

<div class="container py-5">
  <a href="index.php" class="btn btn-outline-secondary btn-sm mb-3">&larr; Kembali</a>
  <h2 class="mb-4">Lacak Pengaduan</h2>

  <form method="POST" class="card p-4 shadow-sm mb-4">
    <div class="mb-3">
      <label class="form-label">Nomor Tiket</label>
      <input type="text" name="ticket" class="form-control" placeholder="cth. HUB-260816-A1B2"
             value="<?= isset($_POST['ticket']) ? htmlspecialchars($_POST['ticket']) : '' ?>" required>
    </div>
    <button type="submit" class="btn btn-primary">Lacak Status</button>
  </form>

  <?php if ($tidak_ketemu): ?>
    <div class="alert alert-danger">Nomor tiket tidak ditemukan. Periksa kembali penulisannya.</div>
  <?php endif; ?>

  <?php if ($hasil): ?>
    <div class="card p-4 shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong><?= htmlspecialchars($hasil['ticket']) ?></strong>
        <span class="badge bg-primary"><?= $label_status[$hasil['status']] ?></span>
      </div>
      <p class="text-muted mb-3"><?= htmlspecialchars($hasil['nama_kategori']) ?> &middot; <?= htmlspecialchars($hasil['lokasi']) ?></p>

      <?php if ($hasil['foto']): ?>
        <img src="<?= htmlspecialchars($hasil['foto']) ?>" class="img-fluid rounded mb-2" style="max-height:280px;">
      <?php endif; ?>
      <?php if ($hasil['latitude'] && $hasil['longitude']): ?>
        <a href="https://www.openstreetmap.org/?mlat=<?= $hasil['latitude'] ?>&mlon=<?= $hasil['longitude'] ?>#map=17/<?= $hasil['latitude'] ?>/<?= $hasil['longitude'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm mb-2">🗺️ Lihat Titik Lokasi di Peta</a>
      <?php endif; ?>

      <h6 class="mt-3">Riwayat Status</h6>
      <ul class="list-group">
        <?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
          <li class="list-group-item">
            <strong><?= $label_status[$r['status']] ?></strong>
            <div class="text-muted small"><?= date('d M Y, H:i', strtotime($r['waktu'])) ?></div>
            <?php if ($r['catatan']): ?>
              <div class="mt-1"><?= htmlspecialchars($r['catatan']) ?></div>
            <?php endif; ?>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>