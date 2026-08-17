<?php
session_start();
unset($_SESSION['id_pelapor'], $_SESSION['pelapor_nama'], $_SESSION['pelapor_email'], $_SESSION['pelapor_foto']);
header("Location: index.php");
exit;
?>