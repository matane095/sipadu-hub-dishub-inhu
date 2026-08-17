<?php
session_start();
include 'config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (!empty($_POST['alamat_situs'])) {
        header("Location: index.php?error=honeypot");
        exit;
    }

    $jawaban_user = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    $jawaban_benar = isset($_SESSION['captcha_answer']) ? $_SESSION['captcha_answer'] : null;
    unset($_SESSION['captcha_answer']);

    if ($jawaban_benar === null || (int)$jawaban_user !== (int)$jawaban_benar) {
        header("Location: index.php?error=captcha");
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $batas_waktu = date('Y-m-d H:i:s', strtotime('-10 minutes'));

    $stmt_cek = mysqli_prepare($koneksi,
        "SELECT COUNT(*) AS jumlah FROM pengaduan WHERE ip_pelapor = ? AND created_at > ?");
    mysqli_stmt_bind_param($stmt_cek, "ss", $ip, $batas_waktu);
    mysqli_stmt_execute($stmt_cek);
    $hasil_cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek));

    if ($hasil_cek['jumlah'] >= 100) {
        header("Location: index.php?error=limit");
        exit;
    }

    // Proses upload foto (opsional)
    $foto_path = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $tipe = mime_content_type($_FILES['foto']['tmp_name']);
        $ukuran_max = 5 * 1024 * 1024;

        if (!in_array($tipe, $allowed) || $_FILES['foto']['size'] > $ukuran_max) {
            header("Location: index.php?error=foto");
            exit;
        }

        $ekstensi = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nama_file = 'pengaduan_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi;
        $tujuan = __DIR__ . '/uploads/pengaduan/' . $nama_file;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $tujuan)) {
            $foto_path = 'uploads/pengaduan/' . $nama_file;
        }
    }

    $nama = $_POST['nama'];
    $kontak = $_POST['kontak'];
    $id_kategori = (int) $_POST['id_kategori'];
    $lokasi = $_POST['lokasi'];
    $deskripsi = $_POST['deskripsi'];
    $id_pelapor = isset($_SESSION['id_pelapor']) ? $_SESSION['id_pelapor'] : null;
    $latitude = !empty($_POST['latitude']) ? (float) $_POST['latitude'] : null;
    $longitude = !empty($_POST['longitude']) ? (float) $_POST['longitude'] : null;

    $tanggal = date('ymd');
    $random = strtoupper(substr(md5(uniqid()), 0, 4));
    $ticket = "HUB-$tanggal-$random";

    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO pengaduan (ticket, nama_pelapor, kontak, id_kategori, lokasi, deskripsi, status, id_pelapor, ip_pelapor, foto, latitude, longitude)
         VALUES (?, ?, ?, ?, ?, ?, 'diterima', ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssississdd",
        $ticket, $nama, $kontak, $id_kategori, $lokasi, $deskripsi, $id_pelapor, $ip, $foto_path, $latitude, $longitude);

    if (mysqli_stmt_execute($stmt)) {
        $id_pengaduan = mysqli_insert_id($koneksi);

        $stmt2 = mysqli_prepare($koneksi,
            "INSERT INTO riwayat_status (id_pengaduan, status, catatan) VALUES (?, 'diterima', 'Pengaduan diterima sistem')");
        mysqli_stmt_bind_param($stmt2, "i", $id_pengaduan);
        mysqli_stmt_execute($stmt2);

        header("Location: index.php?tiket=" . $ticket);
        exit;
    } else {
        echo "Gagal menyimpan data: " . mysqli_stmt_error($stmt);
    }
}
?>