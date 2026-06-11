<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
requireLogin();

$db      = getDB();
$nota_id = filter_input(INPUT_GET, 'nota_id', FILTER_VALIDATE_INT);
$cod_cur = trim($_GET['cod_cur'] ?? '');
$year    = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$periodo = trim($_GET['periodo'] ?? '');

if (!$nota_id || !$cod_cur || $year <= 0 || !in_array($periodo, ['I', 'II'])) {
    header('Location: index.php'); exit;
}

$stmtN = $db->prepare(
    'SELECT n.*, c.nomb_cur FROM notas n JOIN cursos c ON c.cod_cur = n.cod_cur
     WHERE n.nota=? AND n.cod_cur=? AND n.year=? AND n.periodo=?'
);
$stmtN->execute([$nota_id, $cod_cur, $year, $periodo]);
$nota_info = $stmtN->fetch();
if (!$nota_info) { header('Location: index.php'); exit; }

$params = "cod_cur=" . urlencode($cod_cur) . "&year=$year&periodo=" . urlencode($periodo);
$msg = $tipo = '';

// --- Guardar calificaciones ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errores = 0; $guardadas = 0;
    $mensajes_error = [];

    foreach ($_POST['calificacion'] ?? [] as $cod_est => $valor) {
        $cod_est = trim($cod_est);
        $valor   = trim($valor);
        if ($valor === '') continue;
        if (!is_numeric($valor)) { $errores++; $mensajes_error[] = "Valor no numérico para $cod_est."; continue; }
        $valor = (float)$valor;

        $existe = $db->prepare('SELECT cod_cal FROM calificaciones WHERE nota=? AND cod_est=?');
        $existe->execute([$nota_id, $cod_est]);

        try {
            if ($existe->fetch()) {
                $db->prepare(
                    'UPDATE calificaciones SET valor=?, fecha=CURRENT_DATE WHERE nota=? AND cod_est=?'
                )->execute([$valor, $nota_id, $cod_est]);
            } else {
                $db->prepare(
                    'INSERT INTO calificaciones (nota, cod_est, valor) VALUES (?,?,?)'
                )->execute([$nota_id, $cod_est, $valor]);
            }
            $guardadas++;
        } catch (PDOException $e) {
            $errores++;
            $raw = $e->getMessage();
            if (preg_match('/ERROR:\s+(.+)$/m', $raw, $m)) {
                $mensajes_error[] = trim($m[1]);
            } else {
                $mensajes_error[] = "Error al guardar calificación para $cod_est.";
            }
        }
    }

    $msg  = $errores > 0
        ? "Se guardaron $guardadas calificaciones. " . implode(' | ', $mensajes_error)
        : "Se guardaron $guardadas calificaciones correctamente.";
    $tipo = $errores > 0 ? 'error' : 'success';
}

// --- Eliminar calificación individual ---
if (isset($_GET['clear'])) {
    $cod_est_clear = trim($_GET['clear']);
    $db->prepare('DELETE FROM calificaciones WHERE nota=? AND cod_est=?')
       ->execute([$nota_id, $cod_est_clear]);
    $msg = 'Calificación eliminada.'; $tipo = 'success';
    header("Location: notas.php?nota_id=$nota_id&$params"); exit;
}

// Listar estudiantes inscritos con su calificación actual
$estudiantes = $db->prepare(
    'SELECT i.cod_est, e.nomb_est, cal.valor AS cal_actual
     FROM inscripciones i
     JOIN estudiantes e ON e.cod_est = i.cod_est
     LEFT JOIN calificaciones cal ON cal.nota=? AND cal.cod_est=i.cod_est
     WHERE i.cod_cur=? AND i.year=? AND i.periodo=?
     ORDER BY e.nomb_est'
);
$estudiantes->execute([$nota_id, $cod_cur, $year, $periodo]);
$lista = $estudiantes->fetchAll();

$subtitulo = 'CALIFICACIONES: ' . $nota_info['nomb_cur'] . ' — ' . $nota_info['desc_nota'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Calificaciones</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', $subtitulo); ?>

    <div style="padding:10px 20px;background:#f7f9fb;border-bottom:1px solid #ddd;">
        <strong>DESCRIPCIÓN:</strong> <?= htmlspecialchars($nota_info['desc_nota']) ?>
        &nbsp;&nbsp;
        <strong>PORCENTAJE:</strong> <?= $nota_info['porcentaje'] ?> %
        &nbsp;&nbsp;
        <strong>PERÍODO:</strong> <?= $year ?> - <?= htmlspecialchars($periodo) ?>
    </div>

    <?php if ($msg): renderAlert($tipo, $msg); endif; ?>

    <?php if (empty($lista)): ?>
        <div class="form-box">
            <p style="color:#888">No hay estudiantes inscritos en este curso/periodo.</p>
        </div>
    <?php else: ?>
    <form method="POST">
        <table>
            <thead>
                <tr>
                    <th>Código</th><th>Nombre</th><th>Calificación (0.0 - 5.0)</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $est): ?>
                <tr>
                    <td><?= htmlspecialchars($est['cod_est']) ?></td>
                    <td><?= htmlspecialchars($est['nomb_est']) ?></td>
                    <td>
                        <input type="number" class="input-nota"
                               name="calificacion[<?= htmlspecialchars($est['cod_est']) ?>]"
                               step="0.01"
                               value="<?= $est['cal_actual'] !== null ? htmlspecialchars($est['cal_actual']) : '' ?>"
                               placeholder="0.0">
                    </td>
                    <td>
                        <?php if ($est['cal_actual'] !== null): ?>
                            <a href="notas.php?nota_id=<?= $nota_id ?>&<?= $params ?>&clear=<?= urlencode($est['cod_est']) ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar esta calificación?')">Borrar</a>		
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="page-actions">
            <button type="submit" class="btn btn-success">Guardar Calificaciones</button>
            <a href="cohortes.php?<?= $params ?>" class="btn btn-info">&#8592; Volver</a>
        </div>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
