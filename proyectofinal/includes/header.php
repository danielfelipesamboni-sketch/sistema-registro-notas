<?php
function renderHeader(string $titulo, string $subtitulo = ''): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $fecha   = date('d/m/Y');
    $docente = $_SESSION['nomb_doc'] ?? '';
    echo '<div class="header">';
    echo '  <div style="display:flex;justify-content:space-between;align-items:center;padding:0 15px">';
    echo '    <h2>' . htmlspecialchars($titulo) . '</h2>';
    echo '    <h2>' . htmlspecialchars($fecha) . '</h2>';
    echo '  </div>';
    if ($docente) {
        echo '<div style="text-align:right;padding:2px 15px;font-size:12px;color:#aed6f1">';
        echo 'Docente: ' . htmlspecialchars($docente);
        echo ' &nbsp;|&nbsp; <a href="logout.php" style="color:#aed6f1">[Cerrar sesión]</a>';
        echo '</div>';
    }
    if ($subtitulo) {
        echo '<div class="subheader">' . htmlspecialchars($subtitulo) . '</div>';
    }
    echo '</div>';
}

function renderAlert(string $tipo, string $mensaje): void {
    echo '<div class="alert alert-' . $tipo . '">' . htmlspecialchars($mensaje) . '</div>';
}
