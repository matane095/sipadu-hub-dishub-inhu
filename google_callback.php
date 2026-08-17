<?php
session_start();
include 'config/koneksi.php';
include 'config/google_config.php';

if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== $_SESSION['oauth_state']) {
    die("Login gagal: permintaan tidak valid. <a href='index.php'>Kembali</a>");
}

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'code' => $_GET['code'],
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$token_result = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!isset($token_result['access_token'])) {
    die("Gagal mendapatkan token dari Google. <a href='index.php'>Kembali</a>");
}

$ch2 = curl_init('https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_result['access_token']);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$userinfo = json_decode(curl_exec($ch2), true);
curl_close($ch2);

if (!isset($userinfo['id'])) {
    die("Gagal mengambil profil Google. <a href='index.php'>Kembali</a>");
}

$google_id = $userinfo['id'];
$email = $userinfo['email'];
$nama = $userinfo['name'];
$foto = isset($userinfo['picture']) ? $userinfo['picture'] : null;

$stmt = mysqli_prepare($koneksi, "SELECT * FROM pelapor_google WHERE google_id = ?");
mysqli_stmt_bind_param($stmt, "s", $google_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    $pelapor = mysqli_fetch_assoc($result);
    $id_pelapor = $pelapor['id_pelapor'];
} else {
    $stmt2 = mysqli_prepare($koneksi, "INSERT INTO pelapor_google (google_id, email, nama, foto) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt2, "ssss", $google_id, $email, $nama, $foto);
    mysqli_stmt_execute($stmt2);
    $id_pelapor = mysqli_insert_id($koneksi);
}

$_SESSION['id_pelapor'] = $id_pelapor;
$_SESSION['pelapor_nama'] = $nama;
$_SESSION['pelapor_email'] = $email;
$_SESSION['pelapor_foto'] = $foto;

header("Location: index.php");
exit;
?>