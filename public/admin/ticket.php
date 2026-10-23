<?php
/**
 * DETALLE DE TICKET Y SEGUIMIENTO DE SOPORTE
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$loggedUser = getLoggedUser();
$pdo = getDB();

$ticketId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ticketId <= 0) {
    header('Location: index.php');
    exit;
}

// Consultar datos completos del ticket
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    die('<div class="container py-5 text-center"><h3>Ticket no encontrado</h3><a href="index.php" class="btn btn-primary mt-3">Volver al listado</a></div>');
}

// Consultar datos adicionales del equipo por IP si existen
$equipoInfo = buscarEquipoPorIP($ticket['ip'], $pdo);

// Consultar historial de comentarios y evidencias
$stmtComentarios = $pdo->prepare("
    SELECT c.*, u.nombre AS autor_nombre, u.usuario AS autor_usuario
    FROM comentarios c
    LEFT JOIN usuarios_soporte u ON c.usuario_soporte_id = u.id
    WHERE c.ticket_id = ?
    ORDER BY c.fecha_creacion DESC
");
$stmtComentarios->execute([$ticketId]);
$comentarios = $stmtComentarios->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Ticket <?= htmlspecialchars($ticket['folio']) ?> - <?= APP_NAME ?></title>
    
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

        /* Reglas de impresión para garantizar 1 sola hoja */
        @page {
            size: letter portrait;
            margin: 8mm 10mm;
        }

        @media print {
            html, body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, Arial, sans-serif !important;
                font-size: 8.5pt !important;
                line-height: 1.25 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Ocultar elementos interactivos de pantalla */
            .d-print-none,
            nav,
            .navbar,
            .navbar-custom,
            button,
            .btn,
            form,
            #formActualizarEstadoDetalle,
            #formAgregarComentario {
                display: none !important;
            }

            .d-print-block {
                display: block !important;
            }

            .ficha-impresion {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .ficha-impresion .print-card {
                border: 1px solid #cbd5e1 !important;
                border-radius: 6px !important;
                background: #ffffff !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .ficha-impresion .bg-light-print {
                background-color: #f8fafc !important;
            }

            .ficha-impresion .table-print td,
            .ficha-impresion .table-print th {
                padding: 2px 4px !important;
                vertical-align: middle !important;
                font-size: 8pt !important;
                border-color: #e2e8f0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Navegación Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3 d-print-none">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                <div>
                    <div class="lh-1 fw-bold"><?= APP_NAME ?></div>
                    <small class="text-secondary" style="font-size: 0.75rem;">Detalle de Atención</small>
                </div>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-arrow-left"></i> Volver al Listado
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-3">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenedor Principal Interactivo (Oculto en Impresión) -->
    <div class="container py-4 d-print-none">
        
        <!-- Header del Folio -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-4 rounded-3 shadow-sm border">
            <div>
                <span class="badge bg-light text-dark border mb-1">Folio del Requerimiento</span>
                <h2 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <?= htmlspecialchars($ticket['folio']) ?>
                    <?= obtenerBadgeEstado($ticket['estado']) ?>
                    <?= obtenerBadgePrioridad($ticket['prioridad']) ?>
                </h2>
                <small class="text-muted">Registrado el <?= date('d/m/Y \a \l\a\s H:i', strtotime($ticket['fecha_creacion'])) ?> (<?= tiempoTranscurrido($ticket['fecha_creacion']) ?>)</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print();">
                    <i class="bi bi-printer"></i> Imprimir Ficha
                </button>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- Columna Izquierda: Información del Requerimiento y Estación -->
            <div class="col-lg-7">
                
                <!-- Descripción del Problema -->
                <div class="card card-custom mb-4 shadow-sm">
                    <div class="card-custom-header text-dark">
                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>Incidencia Reportada
                    </div>
                    <div class="card-body p-4">
                        <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($ticket['problema']) ?></h5>
                        <p class="text-secondary bg-light p-3 rounded border mb-3" style="white-space: pre-wrap; font-size: 1rem;"><?= htmlspecialchars($ticket['descripcion']) ?></p>
                        
                        <div class="row g-2 small text-muted">
                            <div class="col-sm-6"><strong>Área Solicitante:</strong> <span class="text-dark fw-semibold"><?= htmlspecialchars($ticket['area']) ?></span></div>
                            <div class="col-sm-6"><strong>Extensión:</strong> <span class="text-dark fw-semibold"><?= htmlspecialchars($ticket['extension'] ?: 'No especificada') ?></span></div>
                            <div class="col-sm-6"><strong>Última Actualización:</strong> <?= date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])) ?></div>
                            <div class="col-sm-6"><strong>Fecha Cierre:</strong> <?= $ticket['fecha_cierre'] ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])) : 'Abierto' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Datos de la Estación de Trabajo -->
                <div class="card card-custom mb-4 shadow-sm">
                    <div class="card-custom-header text-dark">
                        <i class="bi bi-pc-display text-primary me-2"></i>Datos de la Estación de Trabajo
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="text-muted small fw-semibold">Dirección IP</label>
                                <div class="fw-bold font-monospace text-dark"><?= htmlspecialchars($ticket['ip']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small fw-semibold">Hostname</label>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ticket['hostname'] ?: ($equipoInfo['hostname'] ?? 'No especificado')) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small fw-semibold">Número de Inventario</label>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ticket['inventario'] ?: ($equipoInfo['inventario'] ?? 'No registrado')) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <label class="text-muted small fw-semibold">Modelo / Serie</label>
                                <div class="fw-bold text-dark"><?= htmlspecialchars(($equipoInfo['modelo'] ?? '') . ' / ' . ($equipoInfo['serie'] ?? 'N/A')) ?></div>
                            </div>
                            <div class="col-sm-12">
                                <label class="text-muted small fw-semibold">Ubicación Física</label>
                                <div class="text-dark"><?= htmlspecialchars($equipoInfo['ubicacion'] ?? 'Ubicación estándar del área') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Seguimiento y Comentarios -->
                <div class="card card-custom shadow-sm">
                    <div class="card-custom-header text-dark d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-chat-left-text text-info me-2"></i>Bitácora de Seguimiento y Evidencias</span>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Formulario para Agregar Nota / Evidencia -->
                        <form id="formAgregarComentario" class="mb-4 bg-light p-3 rounded border" enctype="multipart/form-data">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                            <label class="form-label fw-semibold text-dark small mb-1">Nueva Nota o Avance Técnico</label>
                            <textarea name="texto" id="texto_comentario" class="form-control mb-2" rows="2" placeholder="Escriba aquí los avances, piezas solicitadas, pruebas realizadas..." required></textarea>
                            
                            <div class="row g-2 align-items-center">
                                <div class="col-md-8">
                                    <input type="file" name="archivo" id="archivo_comentario" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.doc,.docx,.zip">
                                    <small class="text-muted" style="font-size: 0.72rem;">Adjuntar evidencia (Imágenes, PDF, Documentos max 10MB)</small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="submit" id="btnEnviarComentario" class="btn btn-sm btn-primary w-100 fw-semibold">
                                        <i class="bi bi-plus-circle"></i> Agregar Nota
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Lista Timeline de Comentarios -->
                        <ul class="timeline" id="timelineComentarios">
                            <?php if (empty($comentarios)): ?>
                                <li id="sinComentariosAlerta" class="text-muted small py-2">No hay notas de seguimiento registradas aún.</li>
                            <?php else: ?>
                                <?php foreach ($comentarios as $c): ?>
                                    <li class="timeline-item">
                                        <div class="timeline-card">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <strong class="text-primary"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($c['autor_nombre'] ?: 'Soporte TI') ?></strong>
                                                <small class="text-muted"><i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($c['fecha_creacion'])) ?></small>
                                            </div>
                                            <p class="mb-0 text-secondary"><?= nl2br(htmlspecialchars($c['texto'])) ?></p>
                                            <?php if (!empty($c['archivo'])): ?>
                                                <div class="mt-2">
                                                    <a href="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($c['archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-paperclip"></i> Ver Evidencia Adjunta (<?= htmlspecialchars($c['archivo']) ?>)
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                    </div>
                </div>

            </div>

            <!-- Columna Derecha: Cambio de Estado y Acciones de Soporte -->
            <div class="col-lg-5">
                
                <!-- Tarjeta de Gestión de Estado -->
                <div class="card card-custom shadow-sm mb-4 border-top border-4 border-primary">
                    <div class="card-custom-header text-dark">
                        <i class="bi bi-sliders text-primary me-2"></i>Actualizar Estado del Ticket
                    </div>
                    <div class="card-body p-4">
                        <form id="formActualizarEstadoDetalle">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                            <div class="mb-3">
                                <label for="select_estado" class="form-label fw-semibold">Estado de Atención</label>
                                <select name="estado" id="select_estado" class="form-select form-select-lg fw-bold" required>
                                    <option value="PENDIENTE" <?= $ticket['estado'] === 'PENDIENTE' ? 'selected' : '' ?>>🔴 PENDIENTE (Nueva Solicitud)</option>
                                    <option value="EN PROCESO" <?= $ticket['estado'] === 'EN PROCESO' ? 'selected' : '' ?>>🟡 EN PROCESO (Atendiendo)</option>
                                    <option value="EN ESPERA" <?= $ticket['estado'] === 'EN ESPERA' ? 'selected' : '' ?>>🔵 EN ESPERA (Falta Refacción / Proveedor)</option>
                                    <option value="ATENDIDO" <?= $ticket['estado'] === 'ATENDIDO' ? 'selected' : '' ?>>🟢 ATENDIDO (Resuelto)</option>
                                    <option value="CANCELADO" <?= $ticket['estado'] === 'CANCELADO' ? 'selected' : '' ?>>⚪ CANCELADO (No Procede)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="usuario_soporte" class="form-label fw-semibold">Técnico Asignado</label>
                                <input type="text" name="usuario_soporte" id="usuario_soporte" class="form-control" value="<?= htmlspecialchars($ticket['usuario_soporte'] ?: $loggedUser['nombre']) ?>" required>
                            </div>

                            <div class="mb-4">
                                <label for="comentario_cambio" class="form-label fw-semibold">Comentario del Cambio (Opcional)</label>
                                <textarea name="comentario" id="comentario_cambio" class="form-control" rows="3" placeholder="Motivo o explicación del cambio de estado..."></textarea>
                            </div>

                            <button type="submit" id="btnGuardarEstado" class="btn btn-primary-custom text-white w-100 py-3 fw-bold shadow">
                                <i class="bi bi-check2-circle me-1"></i> Guardar y Aplicar Estado
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Resumen de Tiempos y SLA -->
                <div class="card card-custom shadow-sm bg-light">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-secondary me-2"></i>Métricas de Tiempo</h6>
                        
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Tiempo desde creación:</span>
                            <span class="fw-bold text-dark"><?= tiempoTranscurrido($ticket['fecha_creacion']) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Duración de atención:</span>
                            <span class="fw-bold text-success">
                                <?= $ticket['fecha_cierre'] ? calcularDuracion($ticket['fecha_creacion'], $ticket['fecha_cierre']) : 'En curso...' ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Prioridad asignada:</span>
                            <span><?= obtenerBadgePrioridad($ticket['prioridad']) ?></span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- FICHA TÉCNICA OFICIAL PARA IMPRESIÓN (EXCLUSIVA 1 HOJA CARTA/A4) -->
    <div class="d-none d-print-block ficha-impresion">
        
        <!-- Encabezado Institucional -->
        <div class="d-flex justify-content-between align-items-center border-bottom border-2 border-dark pb-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <div class="border border-2 border-dark rounded p-1 text-center" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: #0f172a; color: #fff;">
                    <i class="bi bi-shield-fill-check fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-uppercase" style="font-size: 13pt; letter-spacing: 0.5px;"><?= APP_NAME ?></h5>
                    <div class="fw-semibold text-secondary text-uppercase" style="font-size: 7.5pt;">Mesa de Ayuda TI - Ficha de Requerimiento y Servicio Técnico</div>
                </div>
            </div>
            <div class="text-end">
                <div class="border border-2 border-dark rounded px-3 py-1 bg-light d-inline-block">
                    <span style="font-size: 6.5pt; display: block; text-transform: uppercase; font-weight: bold; color: #475569;">Folio de Atención</span>
                    <span class="fw-bold text-dark" style="font-size: 13pt; font-family: monospace;"><?= htmlspecialchars($ticket['folio']) ?></span>
                </div>
                <div class="text-muted mt-1" style="font-size: 6.5pt;">Fecha impresión: <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <!-- Barra de Estado y Metadatos -->
        <div class="print-card p-2 mb-2 bg-light-print d-flex justify-content-between align-items-center" style="font-size: 8pt;">
            <div>
                <span class="text-secondary fw-semibold">ESTADO:</span>
                <span class="badge border border-dark text-dark fw-bold px-2 py-1 ms-1" style="font-size: 8pt;">
                    <?= htmlspecialchars($ticket['estado']) ?>
                </span>
            </div>
            <div>
                <span class="text-secondary fw-semibold">PRIORIDAD:</span>
                <span class="badge border border-secondary text-dark fw-bold px-2 py-1 ms-1" style="font-size: 8pt;">
                    <?= htmlspecialchars($ticket['prioridad']) ?>
                </span>
            </div>
            <div>
                <span class="text-secondary fw-semibold">FECHA REGISTRO:</span>
                <strong class="ms-1 text-dark"><?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) ?></strong>
            </div>
            <div>
                <span class="text-secondary fw-semibold">TÉCNICO:</span>
                <strong class="ms-1 text-dark"><?= htmlspecialchars($ticket['usuario_soporte'] ?: 'Sin asignar') ?></strong>
            </div>
        </div>

        <!-- 2 Columnas: Datos del Solicitante y Datos del Equipo -->
        <div class="row g-2 mb-2">
            <!-- Datos del Solicitante -->
            <div class="col-6">
                <div class="print-card p-2 h-100">
                    <div class="fw-bold border-bottom pb-1 mb-1 text-uppercase text-dark" style="font-size: 7.5pt;">
                        <i class="bi bi-person-badge me-1"></i> Datos del Solicitante
                    </div>
                    <table class="table table-sm table-borderless table-print mb-0">
                        <tr>
                            <td class="text-secondary" style="width: 32%;">Área:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticket['area']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Extensión:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticket['extension'] ?: 'No especificada') ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Ubicación:</td>
                            <td class="text-dark"><?= htmlspecialchars($equipoInfo['ubicacion'] ?? 'Área solicitante') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Datos del Equipo / Estación -->
            <div class="col-6">
                <div class="print-card p-2 h-100">
                    <div class="fw-bold border-bottom pb-1 mb-1 text-uppercase text-dark" style="font-size: 7.5pt;">
                        <i class="bi bi-pc-display me-1"></i> Datos de la Estación de Trabajo
                    </div>
                    <table class="table table-sm table-borderless table-print mb-0">
                        <tr>
                            <td class="text-secondary" style="width: 32%;">Dirección IP:</td>
                            <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($ticket['ip']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Hostname:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticket['hostname'] ?: ($equipoInfo['hostname'] ?? 'No especificado')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Inventario:</td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($ticket['inventario'] ?: ($equipoInfo['inventario'] ?? 'No registrado')) ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Modelo/Serie:</td>
                            <td class="text-dark"><?= htmlspecialchars(($equipoInfo['modelo'] ?? '') . ' / ' . ($equipoInfo['serie'] ?? 'N/A')) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Incidencia Reportada -->
        <div class="print-card p-2 mb-2">
            <div class="fw-bold border-bottom pb-1 mb-1 text-uppercase text-dark" style="font-size: 7.5pt;">
                <i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i> Requerimiento / Falla Reportada
            </div>
            <div class="mb-1" style="font-size: 8pt;">
                <span class="text-secondary fw-semibold">Problema:</span>
                <strong class="text-dark ms-1"><?= htmlspecialchars($ticket['problema']) ?></strong>
            </div>
            <div class="p-2 border rounded bg-light-print" style="white-space: pre-wrap; font-size: 7.5pt; line-height: 1.3; max-height: 75px; overflow: hidden;">
                <?= htmlspecialchars($ticket['descripcion']) ?>
            </div>
        </div>

        <!-- Seguimiento y Cierre de Servicio -->
        <div class="print-card p-2 mb-2">
            <div class="fw-bold border-bottom pb-1 mb-1 text-uppercase text-dark d-flex justify-content-between align-items-center" style="font-size: 7.5pt;">
                <span><i class="bi bi-clock-history me-1"></i> Seguimiento Técnico y Resolución</span>
                <span class="text-secondary fw-normal">
                    Fecha Cierre: <?= $ticket['fecha_cierre'] ? date('d/m/Y H:i', strtotime($ticket['fecha_cierre'])) : 'Abierto' ?>
                </span>
            </div>
            <div class="row g-2 mb-1" style="font-size: 8pt;">
                <div class="col-4">
                    <span class="text-secondary">Duración:</span>
                    <strong class="text-dark ms-1"><?= $ticket['fecha_cierre'] ? calcularDuracion($ticket['fecha_creacion'], $ticket['fecha_cierre']) : 'En proceso (' . tiempoTranscurrido($ticket['fecha_creacion']) . ')' ?></strong>
                </div>
                <div class="col-4">
                    <span class="text-secondary">Última act.:</span>
                    <span class="ms-1 text-dark"><?= date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])) ?></span>
                </div>
                <div class="col-4 text-end">
                    <span class="text-secondary">Notas en bitácora:</span>
                    <strong class="text-dark ms-1"><?= count($comentarios) ?></strong>
                </div>
            </div>

            <?php if (!empty($comentarios)): ?>
                <div class="mt-1 pt-1 border-top">
                    <span class="text-secondary fw-bold d-block mb-1" style="font-size: 7pt;">ÚLTIMAS NOTAS REGISTRADAS:</span>
                    <?php 
                    $notasImprimir = array_slice($comentarios, 0, 2); // Hasta 2 notas para garantizar 1 sola hoja
                    foreach ($notasImprimir as $ni): 
                    ?>
                        <div class="mb-1" style="font-size: 7.5pt; line-height: 1.25;">
                            <strong><?= htmlspecialchars($ni['autor_nombre'] ?: 'Soporte') ?> (<?= date('d/m/Y H:i', strtotime($ni['fecha_creacion'])) ?>):</strong>
                            <span class="text-secondary"><?= htmlspecialchars(substr($ni['texto'], 0, 150)) ?><?= strlen($ni['texto']) > 150 ? '...' : '' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-muted fst-italic" style="font-size: 7pt;">Sin notas técnicas adicionales registradas.</div>
            <?php endif; ?>
        </div>

        <!-- Diagnóstico / Observaciones Técnicas en Sitio -->
        <div class="print-card p-2 mb-3">
            <div class="fw-bold border-bottom pb-1 mb-1 text-uppercase text-dark" style="font-size: 7.5pt;">
                <i class="bi bi-pencil-square me-1"></i> Observaciones / Diagnóstico Técnico en Sitio
            </div>
            <div style="height: 28px; border-bottom: 1px dashed #cbd5e1; margin-bottom: 4px;"></div>
            <div style="height: 14px; border-bottom: 1px dashed #cbd5e1;"></div>
        </div>

        <!-- Sección de Firmas de Entrega y Conformidad -->
        <div class="row text-center mt-3 pt-2" style="font-size: 8pt;">
            <div class="col-6">
                <div style="border-top: 1.5px solid #000; width: 85%; margin: 0 auto; padding-top: 4px;">
                    <strong class="text-dark d-block"><?= htmlspecialchars($ticket['usuario_soporte'] ?: 'Técnico de Soporte TI') ?></strong>
                    <span class="text-secondary" style="font-size: 7pt;">Firma y Sello Soporte Técnico</span>
                </div>
            </div>
            <div class="col-6">
                <div style="border-top: 1.5px solid #000; width: 85%; margin: 0 auto; padding-top: 4px;">
                    <strong class="text-dark d-block"><?= htmlspecialchars($ticket['area']) ?></strong>
                    <span class="text-secondary" style="font-size: 7pt;">Nombre y Firma de Conformidad (Usuario Solicitante)</span>
                </div>
            </div>
        </div>

        <!-- Pie de Ficha -->
        <div class="text-center mt-3 text-secondary" style="font-size: 6.5pt; border-top: 1px solid #e2e8f0; padding-top: 4px;">
            Documento de control interno de Mesa de Ayuda TI. Conservar esta ficha para seguimiento, garantía o auditoría del servicio.
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        $('#formActualizarEstadoDetalle').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#btnGuardarEstado');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            $.ajax({
                url: '../api/actualizar_estado.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    btn.prop('disabled', false).html(originalText);
                    if (res.success) {
                        alert(res.message);
                        location.reload();
                    } else {
                        alert('Error: ' + res.message);
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    alert(xhr.responseJSON ? xhr.responseJSON.message : 'Error al actualizar el estado.');
                }
            });
        });
    </script>
</body>
</html>
