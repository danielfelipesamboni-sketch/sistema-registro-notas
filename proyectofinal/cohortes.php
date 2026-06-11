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

// --- Eliminar nota ---
if (isset($_GET['del'])) {
    $del_id = filter_input(INPUT_GET, 'del', FILTER_VALIDATE_INT);
    if ($del_id) {
        $db->prepare('DELETE FROM notas WHERE nota=? AND cod_cur=?')->execute([$del_id, $cod_cur]);
        $msg = 'Nota eliminada.'; $tipo = 'success';
    }
    header("Location: cohortes.php?$params"); exit;
}

// --- Guardar nota ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota_id      = filter_input(INPUT_POST, 'nota_id',      FILTER_VALIDATE_INT);
    $posicion     = filter_input(INPUT_POST, 'posicion',     FILTER_VALIDATE_INT);
    $desc_nota    = trim($_POST['desc_nota']    ?? '');
    $porcentaje   = filter_input(INPUT_POST, 'porcentaje',   FILTER_VALIDATE_FLOAT);
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin    = trim($_POST['fecha_fin']    ?? '');

    if (!$posicion || $posicion < 1 || !$desc_nota || !$porcentaje || $porcentaje <= 0
        || !$fecha_inicio || !$fecha_fin) {
        $msg = 'Complete todos los campos correctamente.'; $tipo = 'error';
    } elseif ($fecha_fin < $fecha_inicio) {
        $msg = 'La fecha de cierre no puede ser anterior a la fecha de inicio.'; $tipo = 'error';
    } else {
        try {
            if ($nota_id) {
                $db->prepare(
                    'UPDATE notas SET posicion=?, desc_nota=?, porcentaje=?, fecha_inicio=?, fecha_fin=?
                     WHERE nota=? AND cod_cur=?'
                )->execute([$posicion, $desc_nota, $porcentaje, $fecha_inicio, $fecha_fin, $nota_id, $cod_cur]);
                $msg = 'Nota actualizada.';
            } else {
                $db->prepare(
                    'INSERT INTO notas (desc_nota, porcentaje, posicion, cod_cur, year, periodo, fecha_inicio, fecha_fin)
                     VALUES (?,?,?,?,?,?,?,?)'
                )->execute([$desc_nota, $porcentaje, $posicion, $cod_cur, $year, $periodo, $fecha_inicio, $fecha_fin]);
                $msg = 'Nota agregada.';
            }
            $tipo = 'success';
        } catch (PDOException $e) {
if (preg_match('/ERROR:\s+(.+?)(\s+CONTEXT:|$)/s', $e->getMessage(), $m)) {
$msg = trim($m[1]);
} else {
$msg = 'No se pudo guardar la nota.';
}
$tipo = 'error';
}
        }
    }

// Cargar nota para editar
$editando = null;
if (isset($_GET['edit'])) {
    $edit_id  = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    $stmtEdit = $db->prepare('SELECT * FROM notas WHERE nota=? AND cod_cur=?');
    $stmtEdit->execute([$edit_id, $cod_cur]);
    $editando = $stmtEdit->fetch();
}

// Listar notas de este curso/año/periodo
$lista_notas = $db->prepare(
    'SELECT * FROM notas WHERE cod_cur=? AND year=? AND periodo=? ORDER BY posicion'
);
$lista_notas->execute([$cod_cur, $year, $periodo]);
$lista = $lista_notas->fetchAll();

$total_pct = array_sum(array_column($lista, 'porcentaje'));
$subtitulo = 'CURSO: ' . $info['nomb_cur'] . ' | ' . $year . ' - Periodo ' . $periodo;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Notas del Curso</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', $subtitulo); ?>
    <?php if ($msg): renderAlert($tipo, $msg); endif; ?>

    <div class="form-box">
        <h3><?= $editando ? 'EDITAR NOTA' : 'AGREGAR NOTA' ?></h3>
        <form method="POST">
            <input type="hidden" name="nota_id" value="<?= $editando['nota'] ?? '' ?>">
            <div class="form-row">
                <label>Posición</label>
                <input type="number" name="posicion" min="1" max="20"
                       value="<?= htmlspecialchars((string)($editando['posicion'] ?? (count($lista) + 1))) ?>" required>
            </div>
            <div class="form-row">
                <label>Descripción</label>
                <input type="text" name="desc_nota" maxlength="100" style="width:300px"
                       value="<?= htmlspecialchars($editando['desc_nota'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <label>Porcentaje</label>
                <input type="number" name="porcentaje" min="1" max="100" step="0.01"
                       value="<?= htmlspecialchars((string)($editando['porcentaje'] ?? '')) ?>" required>
                <span style="margin-left:8px;color:#888">% (acumulado: <?= $total_pct ?>%)</span>
            </div>
            <div class="form-row">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio"
                       value="<?= htmlspecialchars($editando['fecha_inicio'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <label>Fecha cierre</label>
                <input type="date" name="fecha_fin"
                       value="<?= htmlspecialchars($editando['fecha_fin'] ?? '') ?>" required>
            </div>
            <div class="form-row">
                <label></label>
                <button type="submit" class="btn btn-success"><?= $editando ? 'Actualizar' : 'Agregar' ?></button>
                <?php if ($editando): ?>
                    <a href="cohortes.php?<?= $params ?>" class="btn btn-info" style="margin-left:8px">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="form-box">
        <h3>ESTRUCTURA DE EVALUACIÓN</h3>
        <?php if (empty($lista)): ?>
            <p style="color:#888">No hay notas definidas para este curso/periodo.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Posición</th><th>Descripción</th><th>Porcentaje</th><th>Fecha inicio</th><th>Fecha cierre</th><th>Acciones</th></tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $n): ?>
                <tr>
                    <td><?= $n['posicion'] ?></td>
                    <td><?= htmlspecialchars($n['desc_nota']) ?></td>
                    <td><?= $n['porcentaje'] ?>%</td>
                    <td><?= htmlspecialchars($n['fecha_inicio'] ?? '') ?></td>
                    <td><?= htmlspecialchars($n['fecha_fin'] ?? '') ?></td>
                    <td class="actions">
                        <a href="cohortes.php?<?= $params ?>&edit=<?= $n['nota'] ?>"
                           class="btn btn-warning btn-sm">Editar</a>
                        <a href="cohortes.php?<?= $params ?>&del=<?= $n['nota'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Eliminar? Se borrarán las calificaciones asociadas.')">Borrar</a>		
                        <a href="notas.php?nota_id=<?= $n['nota'] ?>&<?= $params ?>"
                           class="btn btn-primary btn-sm">Registrar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="nota-info">Total porcentaje: <strong><?= $total_pct ?>%</strong></div>
        <?php endif; ?>
    </div>

    <div class="page-actions">
        <a href="estudiantes.php?<?= $params ?>" class="btn btn-info">&#8592; Estudiantes</a>
        <a href="reporte.php?<?= $params ?>" class="btn btn-primary">Ver Reporte &#8594;</a>
    </div>
</div>
</body>
</html>
