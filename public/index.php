<?php
/**
 * PÁGINA PÚBLICA: FORMULARIO DE SOLICITUD DE SOPORTE TÉCNICO
 * Detecta automáticamente la IP del usuario, precarga equipo y genera folio
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$ipCliente = getClientIP();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Generar Solicitud</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Estilos Personalizados -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body>

    <!-- Barra de Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <i class="bi bi-shield-check text-primary fs-3"></i>
                <div>
                    <div class="lh-1 fw-bold"><?= APP_NAME ?></div>
                    <small class="text-secondary" style="font-size: 0.75rem;">Mesa de Ayuda e Intranet Corporativa</small>
                </div>
            </a>
            <div class="d-flex align-items-center gap-2">
                <a href="admin/login.php" class="btn btn-outline-light btn-sm px-3">
                    <i class="bi bi-person-lock"></i> Acceso Soporte
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                
                <!-- Encabezado de la página -->
                <div class="text-center mb-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-2">
                        <i class="bi bi-broadcast"></i> Detección Automática de Red
                    </span>
                    <h2 class="fw-bold text-dark mb-1">Registrar Solicitud de Asistencia</h2>
                    <p class="text-muted">El equipo de TI atenderá su requerimiento a la brevedad conforme a la prioridad seleccionada.</p>
                </div>

                <!-- Alerta de Detección de IP -->
                <div id="alertaDeteccion" class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <div>Detectando estación de trabajo...</div>
                </div>

                <!-- Tarjeta de Información del Equipo (Precargada si existe IP) -->
                <div id="info_equipo_card" class="card card-custom mb-4 border-start border-4 border-success shadow-sm" style="display: none;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-success"><i class="bi bi-pc-display"></i> Equipo Registrado en Red</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">IP: <span id="span_ip"><?= htmlspecialchars($ipCliente) ?></span></span>
                        </div>
                        <div class="row g-2 text-secondary small">
                            <div class="col-sm-6"><strong>Hostname:</strong> <span id="badge_hostname">-</span></div>
                            <div class="col-sm-6"><strong>Inventario:</strong> <span id="badge_inventario">-</span></div>
                            <div class="col-sm-6"><strong>Área:</strong> <span id="badge_area">-</span></div>
                            <div class="col-sm-6"><strong>Ubicación:</strong> <span id="badge_ubicacion">-</span></div>
                            <div class="col-sm-6"><strong>Extensión:</strong> <span id="badge_extension">-</span></div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Solicitud -->
                <div class="card card-custom shadow-sm">
                    <div class="card-custom-header d-flex align-items-center justify-content-between">
                        <span class="text-dark"><i class="bi bi-file-earmark-medical-fill text-primary me-2"></i>Detalles del Requerimiento</span>
                        <span class="badge bg-danger pulse-indicator"></span>
                    </div>
                    <div class="card-body p-4">
                        <form id="formNuevoTicket" autocomplete="off">
                            <!-- Campos Ocultos de Red -->
                            <input type="hidden" name="ip" id="ip_detectada" value="<?= htmlspecialchars($ipCliente) ?>">
                            <input type="hidden" name="hostname" id="hostname" value="">
                            <input type="hidden" name="inventario" id="inventario" value="">

                            <div class="row g-3">
                                
                                <!-- Área / Departamento -->
                                <div class="col-md-8">
                                    <label for="area" class="form-label fw-semibold">Área / Departamento <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                        <input type="text" class="form-control" id="area" name="area" placeholder="Ej. Contabilidad, Recursos Humanos, Ventas" required>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.8rem;">Precargada automáticamente si la IP está registrada.</small>
                                </div>

                                <!-- Extensión Telefónica -->
                                <div class="col-md-4">
                                    <label for="extension" class="form-label fw-semibold">Extensión / Teléfono</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                        <input type="text" class="form-control" id="extension" name="extension" placeholder="Ej. 1204 o Celular">
                                    </div>
                                </div>

                                <!-- Tipo de Problema -->
                                <div class="col-md-12">
                                    <label for="problema" class="form-label fw-semibold">Problema o Falla Principal <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-exclamation-octagon"></i></span>
                                        <select class="form-select" id="problema" name="problema" required>
                                            <option value="" selected disabled>Seleccione el tipo de incidencia...</option>
                                            <optgroup label="Hardware y Equipos">
                                                <option value="Equipo no enciende o se apaga">Equipo no enciende o se apaga</option>
                                                <option value="Lentitud extrema y congelamiento">Lentitud extrema y congelamiento</option>
                                                <option value="Falla en teclado, mouse o monitor">Falla en teclado, mouse o monitor</option>
                                                <option value="Falla de disco duro / Almacenamiento">Falla de disco duro / Almacenamiento</option>
                                            </optgroup>
                                            <optgroup label="Redes e Impresión">
                                                <option value="Sin conexión a Internet o Red Local">Sin conexión a Internet o Red Local</option>
                                                <option value="Falla de impresión o escáner en red">Falla de impresión o escáner en red</option>
                                                <option value="Falta de tóner en la impresora">Falta de tóner en la impresora</option>
                                                <option value="Acceso bloqueado a carpetas compartidas">Acceso bloqueado a carpetas compartidas</option>
                                            </optgroup>
                                            <optgroup label="Telefonía y Comunicación">
                                                <option value="Teléfono sin tono o sin línea">Teléfono sin tono o sin línea</option>
                                                <option value="Falla para realizar o recibir llamadas">Falla para realizar o recibir llamadas</option>
                                                <option value="Teléfono IP no enciende o sin conexión">Teléfono IP no enciende o sin conexión</option>
                                                <option value="Mala calidad de audio o interferencia en llamada">Mala calidad de audio o interferencia en llamada</option>
                                                <option value="Falla en auricular, bocina o diadema">Falla en auricular, bocina o diadema</option>
                                                <option value="Problema con buzón de voz o desvío de llamadas">Problema con buzón de voz o desvío de llamadas</option>
                                                <option value="Configuración o cambio de extensión">Configuración o cambio de extensión</option>
                                            </optgroup>
                                            <optgroup label="Software y Accesos">
                                                <option value="Error en Sistema ERP / Nómina / Facturación">Error en Sistema ERP / Nómina / Facturación</option>
                                                <option value="Problema con Correo Electrónico (Outlook/Web)">Problema con Correo Electrónico (Outlook/Web)</option>
                                                <option value="Instalación / Actualización de Software">Instalación / Actualización de Software</option>
                                                <option value="Restablecimiento de contraseña / Acceso">Restablecimiento de contraseña / Acceso</option>
                                            </optgroup>
                                            <optgroup label="Otros Requerimientos">
                                                <option value="Solicitud de equipo / Movimiento físico">Solicitud de equipo / Movimiento físico</option>
                                                <option value="Otro problema no listado">Otro problema no listado</option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <!-- Nivel de Prioridad -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold mb-2">Nivel de Prioridad <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="prioridad" id="prioridad_alta" value="Alta">
                                            <label class="btn btn-outline-danger w-100 py-2 d-flex flex-column align-items-center" for="prioridad_alta">
                                                <strong class="fs-6"><i class="bi bi-lightning-fill"></i> Alta</strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">Detiene la operación crítica</small>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="prioridad" id="prioridad_media" value="Media" checked>
                                            <label class="btn btn-outline-warning text-dark w-100 py-2 d-flex flex-column align-items-center" for="prioridad_media">
                                                <strong class="fs-6"><i class="bi bi-clock-history"></i> Media</strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">Afecta pero permite trabajar</small>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="radio" class="btn-check" name="prioridad" id="prioridad_baja" value="Baja">
                                            <label class="btn btn-outline-info text-dark w-100 py-2 d-flex flex-column align-items-center" for="prioridad_baja">
                                                <strong class="fs-6"><i class="bi bi-info-circle"></i> Baja</strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">Consulta o mejora general</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción Detallada -->
                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label fw-semibold">Descripción del Problema <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" placeholder="Explique qué sucedió, mensajes de error en pantalla o detalles relevantes para ayudar al técnico..." required></textarea>
                                </div>

                                <!-- Botón de Envío -->
                                <div class="col-12 mt-4 text-center">
                                    <button type="submit" id="btnEnviarTicket" class="btn btn-primary-custom text-white px-5 py-3 fs-5 w-100 shadow">
                                        <i class="bi bi-send-fill me-2"></i> Generar Ticket de Soporte
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN CON NÚMERO DE FOLIO -->
    <div class="modal fade" id="modalTicketCreado" tabindex="-1" aria-labelledby="modalTicketCreadoLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="modalTicketCreadoLabel">
                        <i class="bi bi-check2-circle me-1"></i> ¡Solicitud Registrada con Éxito!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <i class="bi bi-ticket-perforated text-success" style="font-size: 4rem;"></i>
                    </div>
                    <p class="text-muted mb-1">Guarde su número de folio para dar seguimiento a su reporte:</p>
                    <div class="p-3 bg-light rounded-3 border border-success border-2 mb-3">
                        <h2 class="fw-bold text-success mb-0" id="modalFolioTexto">ST-2026-0000</h2>
                    </div>
                    
                    <div class="text-start bg-light-subtle p-3 rounded small mb-3 border">
                        <div><strong>Área:</strong> <span id="modalAreaTexto">-</span></div>
                        <div><strong>Problema:</strong> <span id="modalProblemaTexto">-</span></div>
                        <div><strong>Prioridad:</strong> <span id="modalPrioridadTexto">-</span></div>
                        <div><strong>Estado Inicial:</strong> <span class="badge bg-danger">PENDIENTE</span></div>
                    </div>

                    <p class="small text-muted mb-0">Un técnico de la Mesa de Ayuda ha sido notificado a través del semáforo central.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center bg-light">
                    <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">
                        <i class="bi bi-plus-lg"></i> Crear Otra Solicitud
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js?v=<?= time() ?>"></script>
</body>
</html>
