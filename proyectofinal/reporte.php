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

$stmtNotas = $db->prepare(
    'SELECT * FROM notas WHERE cod_cur=? AND year=? AND periodo=? ORDER BY posicion'
);
$stmtNotas->execute([$cod_cur, $year, $periodo]);
$notas = $stmtNotas->fetchAll();

$stmtEst = $db->prepare(
    'SELECT i.cod_est, e.nomb_est
     FROM inscripciones i JOIN estudiantes e ON e.cod_est = i.cod_est
     WHERE i.cod_cur=? AND i.year=? AND i.periodo=?
     ORDER BY e.nomb_est'
);
$stmtEst->execute([$cod_cur, $year, $periodo]);
$estudiantes = $stmtEst->fetchAll();

// Calificaciones indexadas [cod_est][nota_id]
$stmtCals = $db->prepare(
    'SELECT cal.cod_est, cal.nota, cal.valor
     FROM calificaciones cal
     JOIN notas n ON n.nota = cal.nota
     WHERE n.cod_cur=? AND n.year=? AND n.periodo=?'
);
$stmtCals->execute([$cod_cur, $year, $periodo]);
$cals = [];
foreach ($stmtCals->fetchAll() as $c) {
    $cals[$c['cod_est']][$c['nota']] = $c['valor'];
}

function calcDefinitiva(array $notas, array $cals_est): float {
    $suma = 0.0;
    foreach ($notas as $n) {
        $suma += (float)($cals_est[$n['nota']] ?? 0) * ((float)$n['porcentaje'] / 100);
    }
    return round($suma, 2);
}

$subtitulo   = 'CURSO: ' . $info['nomb_cur'] . ' | ' . $year . ' - Periodo ' . $periodo;
$exportarPDF = isset($_GET['pdf']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Calificaciones</title>
    <link rel="stylesheet" href="css/style.css">
    <?php if ($exportarPDF): ?>
    <script>window.onload = function(){ window.print(); }</script> 							
    <style>.no-print { display:none !important; } body { background:white; }</style>
    <?php endif; ?>
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', $subtitulo); ?>

    <div class="page-actions no-print">
        <a href="reporte.php?<?= $params ?>&pdf=1" class="btn btn-primary" target="_blank">Generar PDF</a>
        <a href="cohortes.php?<?= $params ?>" class="btn btn-info">&#8592; Volver</a>
    </div>

    <?php if (empty($notas)): ?>
        <div class="form-box"><p style="color:#888">No hay notas definidas para este curso/periodo.</p></div>
    <?php elseif (empty($estudiantes)): ?>
        <div class="form-box"><p style="color:#888">No hay estudiantes inscritos en este periodo.</p></div>
    <?php else: ?>
    <div style="padding:0 20px 20px;">
        <table class="reporte-header">
            <thead>
                <tr>
                    <th rowspan="2">CÓDIGO</th>
                    <th rowspan="2">NOMBRE</th>
                    <?php foreach ($notas as $n): ?>
                        <th><?= htmlspecialchars($n['desc_nota']) ?></th>
                    <?php endforeach; ?>
                    <th rowspan="2">DEFINITIVA<br><small>100%</small></th>
                </tr>
                <tr>
                    <?php foreach ($notas as $n): ?>
                        <th class="pct"><?= $n['porcentaje'] ?>%</th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estudiantes as $est): ?>
                <?php $cals_est = $cals[$est['cod_est']] ?? []; ?>
                <tr>
                    <td><?= htmlspecialchars($est['cod_est']) ?></td>
                    <td><?= htmlspecialchars($est['nomb_est']) ?></td>
                    <?php foreach ($notas as $n): ?>
                        <td style="text-align:center">
                            <?= isset($cals_est[$n['nota']]) ? $cals_est[$n['nota']] : '—' ?>
                        </td>
                    <?php endforeach; ?>
                    <td style="text-align:center" class="definitiva">
                        <?= calcDefinitiva($notas, $cals_est) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
