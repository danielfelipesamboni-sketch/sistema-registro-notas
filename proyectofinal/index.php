<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
requireLogin();

$db = getDB();
$stmt = $db->prepare('SELECT cod_cur, nomb_cur FROM cursos WHERE cod_doc=? ORDER BY nomb_cur');
$stmt->execute([$_SESSION['cod_doc']]);
$cursos = $stmt->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_cur = trim($_POST['cod_cur'] ?? '');
    $year    = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    $periodo = $_POST['periodo'] ?? '';

    if (!$cod_cur || $year <= 0 || !in_array($periodo, ['I', 'II'])) {
        $error = 'Complete todos los campos correctamente.';
    } else {
        header("Location: estudiantes.php?cod_cur=" . urlencode($cod_cur)
            . "&year=$year&periodo=" . urlencode($periodo));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Notas</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', 'INFORMACIÓN DE DOCENTES'); ?>

    <?php if ($error): renderAlert('error', $error); endif; ?>

    <div class="form-box">
        <h3>CURSOS DE DOCENTE</h3>
        <form method="POST">
            <div class="form-row">
                <label>Curso</label>
                <select name="cod_cur" required>
                    <option value="">-- Seleccione --</option>
                    <?php foreach ($cursos as $c): ?>
                        <option value="<?= htmlspecialchars($c['cod_cur']) ?>"
                            <?= (isset($_POST['cod_cur']) && $_POST['cod_cur'] === $c['cod_cur']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nomb_cur']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Año</label>
                <input type="number" name="year" min="2000" max="2100"
                       value="<?= htmlspecialchars($_POST['year'] ?? date('Y')) ?>" required>
            </div>
            <div class="form-row">
                <label>Período</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="periodo" value="I"
                               <?= (!isset($_POST['periodo']) || $_POST['periodo'] === 'I') ? 'checked' : '' ?>>
                        Periodo I
                    </label>
                    <label>
                        <input type="radio" name="periodo" value="II"
                               <?= (isset($_POST['periodo']) && $_POST['periodo'] === 'II') ? 'checked' : '' ?>>
                        Periodo II
                    </label>
                </div>
            </div>
            <div class="form-row">
                <label></label>
                <button type="submit" class="btn btn-primary">Ver listado</button>
            </div>
        </form>
    </div>

    <div class="page-actions">
        <a href="admin.php" class="btn btn-info">Administración</a>
    </div>
</div>
</body>
</html>
