<?php
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['cod_doc'])) {
        header('Location: login.php');
        exit;
    }
}
