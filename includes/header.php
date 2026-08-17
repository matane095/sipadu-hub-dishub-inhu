<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($judul_halaman) ? $judul_halaman . ' - ' : '' ?>SIPADU HUB</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">
  <link href="<?= $base_path ?? '' ?>public/css/style.css" rel="stylesheet">
</head>
<body>

<div class="kop-instansi">
  <div class="container d-flex align-items-center gap-3">
    <div class="logo-box">LOGO<br>DISHUB</div>
    <div>
      <p class="instansi-nama">DINAS PERHUBUNGAN<br>KABUPATEN INDRAGIRI HULU</p>
      <p class="instansi-sub">SIPADU HUB &mdash; Sistem Informasi Pengaduan Masyarakat</p>
    </div>
  </div>
</div>