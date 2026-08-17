<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "db_sipadu_dishub";

$koneksi = mysqli_connect($host, $user, $pass, $dbname);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>