<?php
session_start();
if (isset($_SESSION['usuarioID'])) {
    header("Location: Principal.php");
} else {
    header("Location: login.php");
}
exit();
?>