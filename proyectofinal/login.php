<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['cod_doc'])) {
    header('Location: index.php'); exit;
}
require_once 'config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_doc = trim($_POST['cod_doc'] ?? '');
    $clave   = trim($_POST['clave']   ?? '');

    if (!$cod_doc || !$clave) {
        $error = 'Ingrese código y clave.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT cod_doc, nomb_doc, clave FROM docentes WHERE cod_doc=?');
        $stmt->execute([$cod_doc]);
        $doc  = $stmt->fetch();

        if (!$doc || $doc['clave'] !== $clave) {
            $error = 'Código o clave incorrectos.';
        } else {
            $_SESSION['cod_doc']  = $doc['cod_doc'];
            $_SESSION['nomb_doc'] = $doc['nomb_doc'];
            header('Location: index.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h2>REGISTRO DE NOTAS</h2>
        <h2><?= date('d/m/Y') ?></h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-box">
        <h3>ACCESO DOCENTES</h3>
        <form method="POST">
            <div class="form-row">
                <label>Código</label>
                <input type="text" name="cod_doc" placeholder="Ej: DOC001"
                       value="<?= htmlspecialchars($_POST['cod_doc'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <label>Clave</label>
                <input type="password" name="clave" required>
            </div>
            <div class="form-row">
                <label></label>
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
