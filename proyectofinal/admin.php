<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
requireLogin();

$db  = getDB();
$msg = $tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'crear_docente') {
        $cod    = trim($_POST['d_cod']    ?? '');
        $nombre = trim($_POST['d_nombre'] ?? '');
        $clave  = trim($_POST['d_clave']  ?? '');
        if (!$cod || !$nombre || !$clave) {
            $msg = 'Complete todos los campos del docente.'; $tipo = 'error';
        } else {
            try {
                $db->prepare('INSERT INTO docentes (cod_doc, nomb_doc, clave) VALUES (?,?,?)')
                   ->execute([$cod, $nombre, $clave]);
                $msg = 'Docente creado.'; $tipo = 'success';
            } catch (PDOException $e) {
                $msg = 'Error: ' . $e->getMessage(); $tipo = 'error';
            }
        }
    } elseif ($_POST['accion'] === 'del_docente') {
        try {
            $db->prepare('DELETE FROM docentes WHERE cod_doc=?')->execute([trim($_POST['cod'])]);
            $msg = 'Docente eliminado.'; $tipo = 'success';
        } catch (PDOException $e) {
            $msg = 'No se puede eliminar: tiene cursos asignados.'; $tipo = 'error';
        }

    } elseif ($_POST['accion'] === 'crear_curso') {
        $cod     = trim($_POST['c_cod']    ?? '');
        $nombre  = trim($_POST['c_nombre'] ?? '');
        $cod_doc = trim($_POST['c_doc']    ?? '');
        if (!$cod || !$nombre || !$cod_doc) {
            $msg = 'Complete todos los campos del curso.'; $tipo = 'error';
        } else {
            try {
                $db->prepare('INSERT INTO cursos (cod_cur, nomb_cur, cod_doc) VALUES (?,?,?)')
                   ->execute([$cod, $nombre, $cod_doc]);
                $msg = 'Curso creado.'; $tipo = 'success';
            } catch (PDOException $e) {
                $msg = 'Error: ' . $e->getMessage(); $tipo = 'error';
            }
        }
    } elseif ($_POST['accion'] === 'del_curso') {
        $db->prepare('DELETE FROM cursos WHERE cod_cur=?')->execute([trim($_POST['cod'])]);
        $msg = 'Curso eliminado.'; $tipo = 'success';

    } elseif ($_POST['accion'] === 'crear_estudiante') {
        $cod    = trim($_POST['e_cod']    ?? '');
        $nombre = trim($_POST['e_nombre'] ?? '');
        if (!$cod || !$nombre) {
            $msg = 'Complete todos los campos del estudiante.'; $tipo = 'error';
        } else {
            try {
                $db->prepare('INSERT INTO estudiantes (cod_est, nomb_est) VALUES (?,?)')
                   ->execute([$cod, $nombre]);
                $msg = 'Estudiante creado.'; $tipo = 'success';
            } catch (PDOException $e) {
                $msg = 'Error: ' . $e->getMessage(); $tipo = 'error';
            }
        }
    } elseif ($_POST['accion'] === 'del_est') {
        $db->prepare('DELETE FROM estudiantes WHERE cod_est=?')->execute([trim($_POST['cod'])]);
        $msg = 'Estudiante eliminado.'; $tipo = 'success';
    }
}

$docentes    = $db->query('SELECT * FROM docentes ORDER BY nomb_doc')->fetchAll();
$cursos      = $db->query(
    'SELECT c.cod_cur, c.nomb_cur, d.nomb_doc FROM cursos c
     JOIN docentes d ON d.cod_doc = c.cod_doc ORDER BY c.nomb_cur'
)->fetchAll();
$estudiantes = $db->query('SELECT * FROM estudiantes ORDER BY nomb_est')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administración</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php renderHeader('REGISTRO DE NOTAS', 'ADMINISTRACIÓN'); ?>
    <?php if ($msg): renderAlert($tipo, $msg); endif; ?>

    <div style="display:flex;gap:20px;padding:20px;flex-wrap:wrap;">

        <!-- DOCENTES -->
        <div style="flex:1;min-width:260px;">
            <div class="form-box" style="padding:0">
                <h3 style="padding:10px 15px">DOCENTES</h3>
                <form method="POST" style="padding:0 15px 15px">
                    <input type="hidden" name="accion" value="crear_docente">
                    <div class="form-row">
                        <label>Código</label>
                        <input type="text" name="d_cod" maxlength="20" placeholder="Ej: DOC003" required>
                    </div>
                    <div class="form-row">
                        <label>Nombre</label>
                        <input type="text" name="d_nombre" maxlength="100" required>
                    </div>
                    <div class="form-row">
                        <label>Clave</label>
                        <input type="password" name="d_clave" maxlength="100" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">Agregar Docente</button>
                </form>
                <table>
                    <thead><tr><th>Código</th><th>Nombre</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($docentes as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['cod_doc']) ?></td>
                        <td><?= htmlspecialchars($d['nomb_doc']) ?></td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar docente?')">
                                <input type="hidden" name="accion" value="del_docente">
                                <input type="hidden" name="cod" value="<?= htmlspecialchars($d['cod_doc']) ?>">
                                <button class="btn btn-danger btn-sm">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CURSOS -->
        <div style="flex:1;min-width:260px;">
            <div class="form-box" style="padding:0">
                <h3 style="padding:10px 15px">CURSOS</h3>
                <form method="POST" style="padding:0 15px 15px">
                    <input type="hidden" name="accion" value="crear_curso">
                    <div class="form-row">
                        <label>Código</label>
                        <input type="text" name="c_cod" maxlength="20" required>
                    </div>
                    <div class="form-row">
                        <label>Nombre</label>
                        <input type="text" name="c_nombre" maxlength="100" required>
                    </div>
                    <div class="form-row">
                        <label>Docente</label>
                        <select name="c_doc" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= htmlspecialchars($d['cod_doc']) ?>">
                                    <?= htmlspecialchars($d['nomb_doc']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">Agregar Curso</button>
                </form>
                <table>
                    <thead><tr><th>Código</th><th>Nombre</th><th>Docente</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($cursos as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['cod_cur']) ?></td>
                        <td><?= htmlspecialchars($c['nomb_cur']) ?></td>
                        <td><?= htmlspecialchars($c['nomb_doc']) ?></td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar curso?')">
                                <input type="hidden" name="accion" value="del_curso">
                                <input type="hidden" name="cod" value="<?= htmlspecialchars($c['cod_cur']) ?>">
                                <button class="btn btn-danger btn-sm">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ESTUDIANTES -->
        <div style="flex:1;min-width:260px;">
            <div class="form-box" style="padding:0">
                <h3 style="padding:10px 15px">ESTUDIANTES</h3>
                <form method="POST" style="padding:0 15px 15px">
                    <input type="hidden" name="accion" value="crear_estudiante">
                    <div class="form-row">
                        <label>Código</label>
                        <input type="text" name="e_cod" maxlength="20" required>
                    </div>
                    <div class="form-row">
                        <label>Nombre completo</label>
                        <input type="text" name="e_nombre" maxlength="200" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm">Agregar Estudiante</button>
                </form>
                <table>
                    <thead><tr><th>Código</th><th>Nombre</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['cod_est']) ?></td>
                        <td><?= htmlspecialchars($e['nomb_est']) ?></td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar estudiante?')">
                                <input type="hidden" name="accion" value="del_est">
                                <input type="hidden" name="cod" value="<?= htmlspecialchars($e['cod_est']) ?>">
                                <button class="btn btn-danger btn-sm">X</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="page-actions">
        <a href="index.php" class="btn btn-primary">&#8594; Ir al Registro de Notas</a>
    </div>
</div>
</body>
</html>
