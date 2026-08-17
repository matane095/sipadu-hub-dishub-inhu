<?php
session_start();
include 'config/koneksi.php';
$judul_halaman = "Beranda";
$base_path = "";
include 'includes/header.php';

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori");
$tiket_baru = isset($_GET['tiket']) ? $_GET['tiket'] : null;
$sudah_login = isset($_SESSION['id_pelapor']);

$angka1 = rand(1, 9);
$angka2 = rand(1, 9);
$_SESSION['captcha_answer'] = $angka1 + $angka2;

$pesan_error = [
    'honeypot' => 'Pengiriman terdeteksi sebagai bot dan ditolak.',
    'captcha'  => 'Jawaban captcha salah, silakan coba lagi.',
    'limit'    => 'Kamu sudah mengirim terlalu banyak pengaduan dalam waktu singkat. Coba lagi beberapa menit lagi.',
    'foto'     => 'Foto tidak valid. Gunakan format JPG/PNG/WebP, maksimal 5MB.',
];
$error_code = isset($_GET['error']) ? $_GET['error'] : null;
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h2 class="mb-1">Form Pengaduan Masyarakat</h2>
      <p class="text-muted mb-0">Sampaikan keluhan terkait transportasi dan lalu lintas di wilayah Kabupaten Indragiri Hulu.</p>
    </div>

    <?php if ($sudah_login): ?>
      <div class="text-end">
        <div class="small">Masuk sebagai <strong><?= htmlspecialchars($_SESSION['pelapor_nama']) ?></strong></div>
        <a href="riwayat_saya.php" class="btn btn-outline-primary btn-sm mt-1">Riwayat Pengaduan Saya</a>
        <a href="logout_pelapor.php" class="btn btn-outline-secondary btn-sm mt-1">Keluar</a>
      </div>
    <?php else: ?>
      <a href="login_google.php" class="btn btn-outline-dark btn-sm mt-1">Login dengan Google</a>
    <?php endif; ?>
  </div>

  <a href="lacak.php" class="btn btn-outline-primary btn-sm mb-4 mt-3">Lacak Pengaduan Saya</a>

  <?php if ($tiket_baru): ?>
    <div class="alert alert-success">
      Pengaduan berhasil dikirim! Simpan nomor tiket kamu: <strong><?= htmlspecialchars($tiket_baru) ?></strong>
    </div>
  <?php endif; ?>

  <?php if ($error_code && isset($pesan_error[$error_code])): ?>
    <div class="alert alert-danger"><?= $pesan_error[$error_code] ?></div>
  <?php endif; ?>

  <form action="simpan.php" method="POST" enctype="multipart/form-data" class="card p-4 shadow-sm">
    <div class="mb-3">
      <label class="form-label">Nama Pelapor</label>
      <input type="text" name="nama" class="form-control" value="<?= $sudah_login ? htmlspecialchars($_SESSION['pelapor_nama']) : '' ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Kontak (HP / Email)</label>
      <input type="text" name="kontak" class="form-control" value="<?= $sudah_login ? htmlspecialchars($_SESSION['pelapor_email']) : '' ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Kategori</label>
      <select name="id_kategori" class="form-select" required>
        <option value="">Pilih kategori</option>
        <?php while ($row = mysqli_fetch_assoc($kategori_list)): ?>
          <option value="<?= $row['id_kategori'] ?>"><?= htmlspecialchars($row['nama_kategori']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Lokasi Kejadian (alamat/keterangan)</label>
      <input type="text" name="lokasi" class="form-control" placeholder="Jl. ... / Kelurahan ..." required>
    </div>

    <div class="mb-3">
      <label class="form-label">Titik Lokasi di Peta (opsional, bantu petugas menemukan lokasi lebih cepat)</label>
      <div class="mb-2">
        <button type="button" id="btn-lokasi-saya" class="btn btn-outline-primary btn-sm">📍 Gunakan Lokasi Saya</button>
        <span class="text-muted small ms-2">atau klik langsung di peta</span>
      </div>
      <div id="peta-lokasi" style="height:280px;border-radius:8px;border:1px solid #DFDCCE;"></div>
      <div id="koordinat-text" class="small text-muted mt-1">Belum ada titik dipilih.</div>
      <input type="hidden" name="latitude" id="latitude">
      <input type="hidden" name="longitude" id="longitude">
    </div>

    <div class="mb-3">
      <label class="form-label">Deskripsi Kejadian</label>
      <textarea name="deskripsi" class="form-control" rows="4" required></textarea>
    </div>

    <div class="mb-3">
      <label class="form-label">Foto Kejadian (opsional, JPG/PNG/WebP maks 5MB)</label>
      <input type="file" name="foto" class="form-control" accept="image/*">
    </div>

    <div style="position:absolute; left:-9999px;" aria-hidden="true">
      <label>Alamat Situs (kosongkan)</label>
      <input type="text" name="alamat_situs" tabindex="-1" autocomplete="off">
    </div>

    <div class="mb-3">
      <label class="form-label">Verifikasi: berapa hasil dari <?= $angka1 ?> + <?= $angka2 ?>?</label>
      <input type="text" name="captcha" class="form-control" style="max-width:120px;" required>
    </div>

    <button type="submit" class="btn btn-success">Kirim Pengaduan</button>
  </form>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var petaAwal = [-0.4031, 102.5661]; // titik tengah default: Rengat, Indragiri Hulu
  var peta = L.map('peta-lokasi').setView(petaAwal, 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(peta);

  var marker = null;

  function pasangTitik(lat, lng) {
    if (marker) { peta.removeLayer(marker); }
    marker = L.marker([lat, lng]).addTo(peta);
    peta.setView([lat, lng], 16);
    document.getElementById('latitude').value = lat;
    document.getElementById('longitude').value = lng;
    document.getElementById('koordinat-text').innerText =
      'Titik dipilih: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
  }

  peta.on('click', function(e) {
    pasangTitik(e.latlng.lat, e.latlng.lng);
  });

  document.getElementById('btn-lokasi-saya').addEventListener('click', function() {
    if (!navigator.geolocation) {
      alert('Browser tidak mendukung deteksi lokasi otomatis.');
      return;
    }
    document.getElementById('koordinat-text').innerText = 'Mendeteksi lokasi...';
    navigator.geolocation.getCurrentPosition(function(pos) {
      pasangTitik(pos.coords.latitude, pos.coords.longitude);
    }, function() {
      document.getElementById('koordinat-text').innerText = 'Gagal mendeteksi lokasi. Coba klik langsung di peta.';
    });
  });
</script>

<?php include 'includes/footer.php'; ?>