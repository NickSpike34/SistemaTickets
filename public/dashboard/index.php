<?php
/**
 * DASHBOARD PÚBLICO (MONITOR TV)
 * Optimizado para pantallas grandes e intranets con actualización automática cada 12 segundos
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAuth();
$loggedUser = getLoggedUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Semáforo - <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b1120;
            color: #f8fafc;
        }
        .digital-clock {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>
</head>
<body class="tv-mode">

    <div id="dashboardTV" class="tv-container">
        
        <!-- Header del Dashboard para TV -->
        <div class="row align-items-center tv-header">
            <div class="col-md-6 d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-25 rounded-3 border border-primary border-opacity-50">
                    <i class="bi bi-display text-info fs-1"></i>
                </div>
                <div>
                    <h2 class="fw-bold text-white mb-0 d-flex align-items-center gap-2">
                        MONITOR CENTRAL DE ATENCIÓN TI
                        <span id="indicadorCarga" class="spinner-grow spinner-grow-sm text-info" role="status" style="display: none;"></span>
                    </h2>
                    <div class="text-secondary small d-flex align-items-center gap-3 mt-1">
                        <span><i class="bi bi-arrow-repeat text-info"></i> Auto-actualización activa (12s)</span>
                        <span>•</span>
                        <a href="../admin/index.php" class="text-info text-decoration-none"><i class="bi bi-speedometer2"></i> Panel Tickets</a>
                        <span>•</span>
                        <a href="../../reportes/index.php" class="text-secondary text-decoration-none"><i class="bi bi-bar-chart-line"></i> Reportes</a>
                        <span>•</span>
                        <span class="text-secondary"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($loggedUser['nombre']) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 text-md-end mt-3 mt-md-0 d-flex align-items-center justify-content-md-end gap-3">
                <div class="text-end me-2">
                    <div class="text-secondary small text-uppercase fw-semibold"><?= date('l, d F Y') ?></div>
                    <div class="digital-clock" id="relojTV">--:--:--</div>
                </div>
            </div>
        </div>

        <!-- FILA DE SEMÁFORO (TARJETAS DE CONTADORES) -->
        <div class="row g-3 mb-4">
            
            <!-- 1. PENDIENTES (ROJO) -->
            <div class="col-md-3">
                <div class="semaforo-card bg-pendiente">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="label"><i class="bi bi-exclamation-triangle-fill"></i> Pendientes</span>
                        <span class="badge bg-white text-danger fw-bold">Nuevos</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="count" id="countPendientes">0</div>
                        <i class="bi bi-hourglass-top fs-2 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- 2. EN PROCESO (AMARILLO) -->
            <div class="col-md-3">
                <div class="semaforo-card bg-en-proceso">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="label text-dark"><i class="bi bi-gear-wide-connected"></i> En Proceso</span>
                        <span class="badge bg-dark text-warning fw-bold">Atendiendo</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="count text-dark" id="countEnProceso">0</div>
                        <i class="bi bi-tools fs-2 opacity-50 text-dark"></i>
                    </div>
                </div>
            </div>

            <!-- 3. EN ESPERA (AZUL) -->
            <div class="col-md-3">
                <div class="semaforo-card bg-en-espera">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="label"><i class="bi bi-pause-circle-fill"></i> En Espera</span>
                        <span class="badge bg-white text-primary fw-bold">Refacción/Terceros</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="count" id="countEnEspera">0</div>
                        <i class="bi bi-clock-history fs-2 opacity-50"></i>
                    </div>
                </div>
            </div>

            <!-- 4. ATENDIDOS HOY (VERDE) -->
            <div class="col-md-3">
                <div class="semaforo-card bg-atendido">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="label"><i class="bi bi-check-circle-fill"></i> Atendidos Hoy</span>
                        <span class="badge bg-white text-success fw-bold">Resueltos</span>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="count" id="countAtendidos">0</div>
                        <i class="bi bi-trophy fs-2 opacity-50"></i>
                    </div>
                </div>
            </div>

        </div>

        <!-- TABLA DE SOLICITUDES ACTIVAS -->
        <div class="card bg-dark border-secondary border-opacity-50 shadow-lg">
            <div class="card-header bg-dark bg-opacity-75 border-secondary border-opacity-50 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-white fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-list-task text-info"></i> Cola de Solicitudes Activas en Espera de Atención
                </h5>
                <span class="badge bg-secondary bg-opacity-50 text-light px-3 py-2">
                    <i class="bi bi-broadcast"></i> Enlace en Vivo
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0 tv-table" id="tablaTicketsTV">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Folio / Registro</th>
                            <th style="width: 15%;">Área Solicitante</th>
                            <th style="width: 15%;">Equipo / Estación</th>
                            <th style="width: 34%;">Problema y Descripción</th>
                            <th style="width: 8%;">Prioridad</th>
                            <th style="width: 8%;">Estado</th>
                            <th style="width: 8%;" class="text-end">Tiempo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-info" role="status"></div>
                                <div class="mt-2 text-secondary">Cargando cola de tickets...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
