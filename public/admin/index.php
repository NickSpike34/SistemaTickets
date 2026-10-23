<?php
/**
 * PANEL DE CONTROL - LISTADO GENERAL DE TICKETS
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$loggedUser = getLoggedUser();
$pdo = getDB();

// Filtros de búsqueda
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtroPrioridad = isset($_GET['prioridad']) ? trim($_GET['prioridad']) : '';
$filtroFecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : '';

// Construir consulta dinámica con Prepared Statements
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(folio LIKE :s1 OR area LIKE :s2 OR hostname LIKE :s3 OR inventario LIKE :s4 OR problema LIKE :s5 OR usuario_soporte LIKE :s6)";
    foreach (['s1', 's2', 's3', 's4', 's5', 's6'] as $k) {
        $params[":{$k}"] = "%{$search}%";
    }
}

if (!empty($filtroEstado)) {
    $whereClauses[] = "estado = :estado";
    $params[':estado'] = $filtroEstado;
}

if (!empty($filtroPrioridad)) {
    $whereClauses[] = "prioridad = :prioridad";
    $params[':prioridad'] = $filtroPrioridad;
}

if (!empty($filtroFecha)) {
    $whereClauses[] = "DATE(fecha_creacion) = :fecha";
    $params[':fecha'] = $filtroFecha;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$sql = "SELECT * FROM tickets {$whereSql} ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Obtener contadores globales
$stmtCounts = $pdo->query("
    SELECT 
        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS c_pendientes,
        SUM(CASE WHEN estado = 'EN PROCESO' THEN 1 ELSE 0 END) AS c_proceso,
        SUM(CASE WHEN estado = 'EN ESPERA' THEN 1 ELSE 0 END) AS c_espera,
        SUM(CASE WHEN estado = 'ATENDIDO' THEN 1 ELSE 0 END) AS c_atendidos,
        SUM(CASE WHEN estado = 'CANCELADO' THEN 1 ELSE 0 END) AS c_cancelados,
        COUNT(*) AS total
    FROM tickets
");
$contadores = $stmtCounts->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Tickets - <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>

    <!-- Barra de Navegación Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                <div>
                    <div class="lh-1 fw-bold"><?= APP_NAME ?></div>
                    <small class="text-secondary" style="font-size: 0.75rem;">Panel de Control y Soporte TI</small>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navAdminContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navAdminContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="index.php"><i class="bi bi-ticket-detailed"></i> Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/index.php" target="_blank"><i class="bi bi-tv"></i> Monitor TV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../reportes/index.php"><i class="bi bi-bar-chart-line"></i> Reportes & Métricas</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-white text-end d-none d-sm-block">
                        <div class="fw-bold small"><?= htmlspecialchars($loggedUser['nombre']) ?></div>
                        <small class="text-info" style="font-size: 0.75rem;">@<?= htmlspecialchars($loggedUser['usuario']) ?></small>
                    </div>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm px-3">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Tarjetas de Resumen Semáforo -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-dark h-100">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Total Registrados</small>
                        <div class="fs-3 fw-bold text-dark"><?= intval($contadores['total']) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php?estado=PENDIENTE" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-danger h-100">
                        <small class="text-danger text-uppercase fw-bold" style="font-size: 0.75rem;">Pendientes</small>
                        <div class="fs-3 fw-bold text-danger"><?= intval($contadores['c_pendientes']) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php?estado=EN+PROCESO" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-warning h-100">
                        <small class="text-warning-emphasis text-uppercase fw-bold" style="font-size: 0.75rem;">En Proceso</small>
                        <div class="fs-3 fw-bold text-warning-emphasis"><?= intval($contadores['c_proceso']) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php?estado=EN+ESPERA" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-primary h-100">
                        <small class="text-primary text-uppercase fw-bold" style="font-size: 0.75rem;">En Espera</small>
                        <div class="fs-3 fw-bold text-primary"><?= intval($contadores['c_espera']) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php?estado=ATENDIDO" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-success h-100">
                        <small class="text-success text-uppercase fw-bold" style="font-size: 0.75rem;">Atendidos</small>
                        <div class="fs-3 fw-bold text-success"><?= intval($contadores['c_atendidos']) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <a href="index.php?estado=CANCELADO" class="text-decoration-none">
                    <div class="card card-custom p-3 border-start border-4 border-secondary h-100">
                        <small class="text-secondary text-uppercase fw-bold" style="font-size: 0.75rem;">Cancelados</small>
                        <div class="fs-3 fw-bold text-secondary"><?= intval($contadores['c_cancelados']) ?></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="card card-custom mb-4 shadow-sm">
            <div class="card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Buscar por folio, área, equipo o problema..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <select name="estado" class="form-select">
                            <option value="">Todos los Estados</option>
                            <option value="PENDIENTE" <?= $filtroEstado === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="EN PROCESO" <?= $filtroEstado === 'EN PROCESO' ? 'selected' : '' ?>>En Proceso</option>
                            <option value="EN ESPERA" <?= $filtroEstado === 'EN ESPERA' ? 'selected' : '' ?>>En Espera</option>
                            <option value="ATENDIDO" <?= $filtroEstado === 'ATENDIDO' ? 'selected' : '' ?>>Atendido</option>
                            <option value="CANCELADO" <?= $filtroEstado === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <select name="prioridad" class="form-select">
                            <option value="">Todas las Prioridades</option>
                            <option value="Alta" <?= $filtroPrioridad === 'Alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="Media" <?= $filtroPrioridad === 'Media' ? 'selected' : '' ?>>Media</option>
                            <option value="Baja" <?= $filtroPrioridad === 'Baja' ? 'selected' : '' ?>>Baja</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($filtroFecha) ?>">
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary" title="Limpiar Filtros">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>

                </form>
            </div>
        </div>

        <!-- Tabla de Tickets -->
        <div class="card card-custom shadow-sm">
            <div class="card-custom-header d-flex justify-content-between align-items-center">
                <span class="fs-5 text-dark fw-bold">
                    <i class="bi bi-table text-primary me-2"></i>Listado de Solicitudes (<?= count($tickets) ?>)
                </span>
                <div class="d-flex gap-2">
                    <a href="../../reportes/exportar.php?tipo=csv" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                    </a>
                    <a href="../../reportes/exportar.php?tipo=pdf" class="btn btn-sm btn-outline-danger" target="_blank">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Área / Ext</th>
                            <th>Equipo / IP</th>
                            <th>Problema Reportado</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Técnico Asignado</th>
                            <th>Registro</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No se encontraron tickets con los criterios especificados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $t): ?>
                                <tr>
                                    <td>
                                        <a href="ticket.php?id=<?= $t['id'] ?>" class="fw-bold text-decoration-none text-primary">
                                            <?= htmlspecialchars($t['folio']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($t['area']) ?></div>
                                        <?php if (!empty($t['extension'])): ?>
                                            <small class="text-muted"><i class="bi bi-telephone"></i> <?= htmlspecialchars($t['extension']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($t['hostname'] ?: ($t['inventario'] ?: 'No asignado')) ?></div>
                                        <small class="text-secondary font-monospace">IP: <?= htmlspecialchars($t['ip']) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="max-width: 320px; word-break: break-word;">
                                            <?= htmlspecialchars($t['problema']) ?>
                                        </div>
                                        <small class="text-muted d-block mt-1" style="max-width: 320px; word-break: break-word; line-height: 1.35;">
                                            <?= htmlspecialchars($t['descripcion']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= obtenerBadgePrioridad($t['prioridad']) ?>
                                    </td>
                                    <td>
                                        <?= obtenerBadgeEstado($t['estado']) ?>
                                    </td>
                                    <td>
                                        <span class="small <?= !empty($t['usuario_soporte']) ? 'fw-bold text-dark' : 'text-muted fst-italic' ?>">
                                            <?= htmlspecialchars($t['usuario_soporte'] ?: 'Sin asignar') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></div>
                                        <small class="text-muted"><?= tiempoTranscurrido($t['fecha_creacion']) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="ticket.php?id=<?= $t['id'] ?>" class="btn btn-outline-primary" title="Ver Detalle y Seguimiento">
                                                <i class="bi bi-eye"></i> Detalle
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary btn-cambiar-estado" 
                                                    data-id="<?= $t['id'] ?>" 
                                                    data-folio="<?= htmlspecialchars($t['folio']) ?>" 
                                                    data-estado="<?= htmlspecialchars($t['estado']) ?>" 
                                                    title="Cambiar Estado Rápido">
                                                <i class="bi bi-arrow-repeat"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL DE CAMBIO RÁPIDO DE ESTADO -->
    <div class="modal fade" id="modalCambiarEstado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-arrow-repeat text-primary me-2"></i>Actualizar Estado - <span id="modal_ticket_folio">-</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formCambiarEstadoModal">
                    <input type="hidden" name="ticket_id" id="modal_ticket_id" value="0">
                    <div class="modal-body p-4">
                        
                        <div class="mb-3">
                            <label for="modal_nuevo_estado" class="form-label fw-semibold">Nuevo Estado</label>
                            <select name="estado" id="modal_nuevo_estado" class="form-select" required>
                                <option value="PENDIENTE">🔴 PENDIENTE (Nueva Solicitud)</option>
                                <option value="EN PROCESO">🟡 EN PROCESO (Atendiendo)</option>
                                <option value="EN ESPERA">🔵 EN ESPERA (Falta Refacción/Autorización)</option>
                                <option value="ATENDIDO">🟢 ATENDIDO (Resuelto)</option>
                                <option value="CANCELADO">⚪ CANCELADO (No Procede)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="modal_usuario_soporte" class="form-label fw-semibold">Técnico Responsable</label>
                            <input type="text" name="usuario_soporte" id="modal_usuario_soporte" class="form-control" value="<?= htmlspecialchars($loggedUser['nombre']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="modal_comentario_estado" class="form-label fw-semibold">Nota o Motivo del Cambio (Opcional)</label>
                            <textarea name="comentario" id="modal_comentario_estado" class="form-control" rows="3" placeholder="Ej. Se solicitó cambio de tóner a compras..."></textarea>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
