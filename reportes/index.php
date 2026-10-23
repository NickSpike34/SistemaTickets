<?php
/**
 * MÓDULO DE REPORTES Y CONSULTAS ESTADÍSTICAS
 * Sistema de Soporte Técnico tipo Semáforo
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();
$loggedUser = getLoggedUser();
$pdo = getDB();

// Filtros de fecha y criterios
$desde     = isset($_GET['desde']) ? trim($_GET['desde']) : date('Y-m-01'); // Primer día del mes actual
$hasta     = isset($_GET['hasta']) ? trim($_GET['hasta']) : date('Y-m-d');
$filtroArea   = isset($_GET['area']) ? trim($_GET['area']) : '';
$filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtroEquipo = isset($_GET['equipo']) ? trim($_GET['equipo']) : '';

// 1. Métricas Generales en el rango
$paramsGeneral = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
$sqlGeneral = "
    SELECT 
        COUNT(*) AS total_tickets,
        SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS total_pendientes,
        SUM(CASE WHEN estado = 'EN PROCESO' THEN 1 ELSE 0 END) AS total_proceso,
        SUM(CASE WHEN estado = 'EN ESPERA' THEN 1 ELSE 0 END) AS total_espera,
        SUM(CASE WHEN estado = 'ATENDIDO' THEN 1 ELSE 0 END) AS total_atendidos,
        SUM(CASE WHEN estado = 'CANCELADO' THEN 1 ELSE 0 END) AS total_cancelados,
        AVG(CASE WHEN estado = 'ATENDIDO' AND fecha_cierre IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, fecha_creacion, fecha_cierre) ELSE NULL END) AS tiempo_promedio_minutos
    FROM tickets
    WHERE fecha_creacion BETWEEN :desde AND :hasta
";
$stmtG = $pdo->prepare($sqlGeneral);
$stmtG->execute($paramsGeneral);
$metricas = $stmtG->fetch();

$tiempoPromedioMin = round($metricas['tiempo_promedio_minutos'] ?? 0);
$tiempoPromedioTexto = '-';
if ($tiempoPromedioMin > 0) {
    $hrs = floor($tiempoPromedioMin / 60);
    $mins = $tiempoPromedioMin % 60;
    $tiempoPromedioTexto = ($hrs > 0 ? "{$hrs}h " : "") . "{$mins} min";
}

// 2. Tickets por Área
$sqlArea = "
    SELECT area, COUNT(*) AS total
    FROM tickets
    WHERE fecha_creacion BETWEEN :desde AND :hasta
    GROUP BY area
    ORDER BY total DESC
";
$stmtArea = $pdo->prepare($sqlArea);
$stmtArea->execute($paramsGeneral);
$ticketsPorArea = $stmtArea->fetchAll();

// 3. Problemas más frecuentes
$sqlProblemas = "
    SELECT problema, COUNT(*) AS total
    FROM tickets
    WHERE fecha_creacion BETWEEN :desde AND :hasta
    GROUP BY problema
    ORDER BY total DESC
    LIMIT 6
";
$stmtProb = $pdo->prepare($sqlProblemas);
$stmtProb->execute($paramsGeneral);
$topProblemas = $stmtProb->fetchAll();

// 4. Listado Detallado de Tickets Filtrados
$whereFiltros = ["fecha_creacion BETWEEN :desde AND :hasta"];
$paramsLista = $paramsGeneral;

if (!empty($filtroArea)) {
    $whereFiltros[] = "area = :area";
    $paramsLista[':area'] = $filtroArea;
}
if (!empty($filtroEstado)) {
    $whereFiltros[] = "estado = :estado";
    $paramsLista[':estado'] = $filtroEstado;
}
if (!empty($filtroEquipo)) {
    $whereFiltros[] = "(hostname LIKE :eq1 OR inventario LIKE :eq2 OR ip LIKE :eq3)";
    foreach (['eq1', 'eq2', 'eq3'] as $k) {
        $paramsLista[":{$k}"] = "%{$filtroEquipo}%";
    }
}

$sqlLista = "SELECT * FROM tickets WHERE " . implode(" AND ", $whereFiltros) . " ORDER BY fecha_creacion DESC";
$stmtLista = $pdo->prepare($sqlLista);
$stmtLista->execute($paramsLista);
$ticketsListado = $stmtLista->fetchAll();

// Obtener todas las áreas únicas para el dropdown
$areasDisponibles = $pdo->query("SELECT DISTINCT area FROM tickets WHERE area != '' ORDER BY area ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>

    <!-- Barra de Navegación Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../public/admin/index.php">
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
                        <a class="nav-link" href="../public/admin/index.php"><i class="bi bi-ticket-detailed"></i> Tickets</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../public/dashboard/index.php" target="_blank"><i class="bi bi-tv"></i> Monitor TV</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold" href="index.php"><i class="bi bi-bar-chart-line"></i> Reportes & Métricas</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="text-white text-end d-none d-sm-block">
                        <div class="fw-bold small"><?= htmlspecialchars($loggedUser['nombre']) ?></div>
                        <small class="text-info" style="font-size: 0.75rem;">@<?= htmlspecialchars($loggedUser['usuario']) ?></small>
                    </div>
                    <a href="../public/admin/logout.php" class="btn btn-outline-danger btn-sm px-3">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Encabezado y Filtros de Fecha -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">Reportes de Rendimiento y Consulta de Tickets</h3>
                <p class="text-muted small mb-0">Análisis operativo, tiempo promedio de atención y distribución departamental.</p>
            </div>

            <!-- Botones de Exportación -->
            <div class="d-flex gap-2">
                <a href="exportar.php?tipo=csv&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&area=<?= urlencode($filtroArea) ?>&estado=<?= urlencode($filtroEstado) ?>&equipo=<?= urlencode($filtroEquipo) ?>" class="btn btn-success fw-semibold">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Exportar CSV
                </a>
                <a href="exportar.php?tipo=pdf&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&area=<?= urlencode($filtroArea) ?>&estado=<?= urlencode($filtroEstado) ?>&equipo=<?= urlencode($filtroEquipo) ?>" class="btn btn-danger fw-semibold" target="_blank">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Exportar PDF
                </a>
            </div>
        </div>

        <!-- Barra de Filtros -->
        <div class="card card-custom shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="index.php" class="row g-2 align-items-center">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Fecha Desde</label>
                        <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Fecha Hasta</label>
                        <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold mb-1">Filtrar por Área</label>
                        <select name="area" class="form-select">
                            <option value="">Todas las Áreas</option>
                            <?php foreach ($areasDisponibles as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filtroArea === $a ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold mb-1">Estado</label>
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
                        <label class="form-label small fw-bold mb-1">Buscar Equipo/IP</label>
                        <input type="text" name="equipo" class="form-control" placeholder="Hostname o IP..." value="<?= htmlspecialchars($filtroEquipo) ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="margin-top: 24px;">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tarjetas de Métricas KPI -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card card-custom p-3 border-start border-4 border-primary h-100 shadow-sm">
                    <span class="text-muted small fw-bold text-uppercase">Total en el Período</span>
                    <div class="fs-2 fw-bold text-dark"><?= intval($metricas['total_tickets']) ?></div>
                    <small class="text-secondary">Solicitudes registradas</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 border-start border-4 border-success h-100 shadow-sm">
                    <span class="text-success small fw-bold text-uppercase">Tickets Resueltos</span>
                    <div class="fs-2 fw-bold text-success"><?= intval($metricas['total_atendidos']) ?></div>
                    <small class="text-secondary">Estado ATENDIDO</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 border-start border-4 border-warning h-100 shadow-sm">
                    <span class="text-warning-emphasis small fw-bold text-uppercase">En Curso (Pend/Proc/Esp)</span>
                    <div class="fs-2 fw-bold text-warning-emphasis">
                        <?= intval($metricas['total_pendientes'] + $metricas['total_proceso'] + $metricas['total_espera']) ?>
                    </div>
                    <small class="text-secondary">Atención activa</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom p-3 border-start border-4 border-info h-100 shadow-sm">
                    <span class="text-info-emphasis small fw-bold text-uppercase">Tiempo Promedio Atención</span>
                    <div class="fs-2 fw-bold text-primary"><?= $tiempoPromedioTexto ?></div>
                    <small class="text-secondary">Desde reporte hasta cierre</small>
                </div>
            </div>
        </div>

        <!-- Distribución por Área y Top Problemas -->
        <div class="row g-4 mb-4">
            
            <!-- Tickets por Área -->
            <div class="col-lg-6">
                <div class="card card-custom shadow-sm h-100">
                    <div class="card-custom-header text-dark">
                        <i class="bi bi-pie-chart text-primary me-2"></i>Tickets por Área Solicitante
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($ticketsPorArea)): ?>
                            <p class="text-muted text-center py-4">No hay datos en el rango seleccionado.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Área</th>
                                            <th class="text-center">Tickets</th>
                                            <th style="width: 40%;">Proporción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalPeriodo = max(1, intval($metricas['total_tickets']));
                                        foreach ($ticketsPorArea as $ta): 
                                            $porc = round(($ta['total'] / $totalPeriodo) * 100, 1);
                                        ?>
                                            <tr>
                                                <td class="fw-semibold text-dark"><?= htmlspecialchars($ta['area']) ?></td>
                                                <td class="text-center fw-bold"><?= $ta['total'] ?></td>
                                                <td>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $porc ?>%;" aria-valuenow="<?= $porc ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.72rem;"><?= $porc ?>% del total</small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Problemas Más Frecuentes -->
            <div class="col-lg-6">
                <div class="card card-custom shadow-sm h-100">
                    <div class="card-custom-header text-dark">
                        <i class="bi bi-bar-chart-fill text-danger me-2"></i>Incidencias y Fallas Más Frecuentes
                    </div>
                    <div class="card-body p-3">
                        <?php if (empty($topProblemas)): ?>
                            <p class="text-muted text-center py-4">No hay datos en el rango seleccionado.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topProblemas as $index => $tp): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center px-2 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-secondary rounded-circle px-2 py-1"><?= $index + 1 ?></span>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($tp['problema']) ?></div>
                                            </div>
                                        </div>
                                        <span class="badge bg-primary rounded-pill px-3 py-2 fs-6"><?= $tp['total'] ?> tickets</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- Listado Detallado de Resultados -->
        <div class="card card-custom shadow-sm">
            <div class="card-custom-header text-dark d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-text text-primary me-2"></i>Historial Detallado de Solicitudes (<?= count($ticketsListado) ?>)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Folio</th>
                            <th>Área</th>
                            <th>Equipo / IP</th>
                            <th>Problema</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Creación</th>
                            <th>Cierre</th>
                            <th>Tiempo Atención</th>
                            <th>Técnico</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ticketsListado)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No se encontraron tickets con los filtros aplicados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ticketsListado as $t): ?>
                                <tr>
                                    <td>
                                        <a href="../public/admin/ticket.php?id=<?= $t['id'] ?>" class="fw-bold text-decoration-none">
                                            <?= htmlspecialchars($t['folio']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($t['area']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($t['hostname'] ?: $t['inventario'] ?: 'N/A') ?></div>
                                        <small class="text-muted font-monospace"><?= htmlspecialchars($t['ip']) ?></small>
                                    </td>
                                    <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($t['problema']) ?>">
                                        <?= htmlspecialchars($t['problema']) ?>
                                    </td>
                                    <td><?= obtenerBadgePrioridad($t['prioridad']) ?></td>
                                    <td><?= obtenerBadgeEstado($t['estado']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($t['fecha_creacion'])) ?></td>
                                    <td><?= $t['fecha_cierre'] ? date('d/m/Y H:i', strtotime($t['fecha_cierre'])) : '-' ?></td>
                                    <td><?= calcularDuracion($t['fecha_creacion'], $t['fecha_cierre']) ?></td>
                                    <td><?= htmlspecialchars($t['usuario_soporte'] ?: 'Sin asignar') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
