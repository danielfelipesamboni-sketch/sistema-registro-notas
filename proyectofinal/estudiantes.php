<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
requireLogin();

$db      = getDB();
$cod_cur = trim($_GET['cod_cur'] ?? '');
$year    = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$periodo = trim($_GET['periodo'] ?? '');

if (!$cod_cur || $year <= 0 || !in_array($periodo, ['I', 'II'])) {
    header('Location: index.php'); exit;
}

$stmt = $db->prepare('SELECT nomb_cur FROM cursos WHERE cod_cur=? AND cod_doc=?');
$stmt->execute([$cod_cur, $_SESSION['cod_doc']]);
$info = $stmt->fetch();
if (!$info) { header('Location: index.php'); exit; }

$params = "cod_cur=" . urlencode($cod_cur) . "&year=$year&periodo=" . urlencode($periodo);
$msg = $tipo = '';

// --- Eliminar inscripción ---
if (isset($_GET['del'])) {
    $cod_est_del = trim($_GET['del']);
    try {
        $db->prepare(
            'DELETE FROM inscripciones WHERE cod_cur=? AND cod_est=? AND year=? AND periodo=?'
        )->execute([$cod_cur, $cod_est_del, $year, $periodo]);
        $msg = 'Estudiante eliminado correctamente.'; $tipo = 'success';
    } catch (PDOException $e) {
        $msg = 'Error al eliminar: ' . $e->getMessage(); $tipo = 'error';
    }
    header("Location: estudiantes.php?$params&msg=" . urlencode($msg) . "&tipo=$tipo"); exit;
}

// --- Inscribir estudiante ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cod_est = trim($_POST['cod_est'] ?? '');
    if ($cod_est === '') {
        $msg = 'Ingrese el código del estudiante.'; $tipo = 'error';
    } else {
        $est = $db->prepare('SELECT cod_est FROM estudiantes WHERE cod_est=?');
        $est->execute([$cod_est]);
        if (!$est->fetch()) {
            $msg = 'No existe un estudiante con ese código.'; $tipo = 'error';
        } else {
            try {
                $db->prepare(
                    'INSERT INTO inscripciones (cod_cur, cod_est, year, periodo) VALUES (?,?,?,?)'
                )->execute([$cod_cur, $cod_est, $year, $periodo]);
                $msg = 'Estudiante inscrito correctamente.'; $tipo = 'success';
            } catch (PDOException $e) {
                $msg = 'El estudiante ya está inscrito en este curso/periodo.'; $tipo = 'error';
            }
        }
    }
}

if (isset($_GET['msg'])) { $msg = $_GET['msg']; $tipo = $_GET['tipo'] ?? 'info'; }

$inscritos = $db->prepare(
    'SELECT i.cod_est, e.nomb_est
     FROM inscripciones i JOIN estudiantes e ON e.cod_est = i.cod_est
     WHERE i.cod_cur=? AND i.year=? AND i.periodo=?
     ORDER BY e.nomb_est'
);
$inscritos->execute([$cod_cur, $year, $periodo]);
$lista = $inscritos->fetchAll();

$subtitulo = 'CURSO: ' . $info['nomb_cur'] . ' | ' . $year . ' - Periodo ' . $periodo;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estudiantes Inscritos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', $subtitulo); ?>
    <?php if ($msg): renderAlert($tipo, $msg); endif; ?>

    <div class="form-box">
        <h3>INSCRIBIR ESTUDIANTE</h3>
        <form method="POST">
            <div class="form-row">
                <label>Código</label>
                <input type="text" name="cod_est" placeholder="Ej: 160005380" required>
                <button type="submit" class="btn btn-success" style="margin-left:10px">Inscribir</button>
            </div>
        </form>
    </div>

    <div class="form-box">
        <h3>ESTUDIANTES INSCRITOS</h3>
        <?php if (empty($lista)): ?>
            <p style="color:#888">No hay estudiantes inscritos aún.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>No.</th><th>Código</th><th>Nombre</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $i => $est): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($est['cod_est']) ?></td>
                    <td><?= htmlspecialchars($est['nomb_est']) ?></td>
                    <td>
                        <a href="estudiantes.php?<?= $params ?>&del=<?= urlencode($est['cod_est']) ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Eliminar estudiante? Se borrarán sus calificaciones.')">	
                           Eliminar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="page-actions">
        <a href="index.php" class="btn btn-info">&#8592; Volver</a>
        <a href="cohortes.php?<?= $params ?>" class="btn btn-primary">Gestionar Notas &#8594;</a>
    </div>
</div>
</body>
</html>
