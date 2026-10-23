<?php
/**
 * API: DATOS EN TIEMPO REAL PARA EL DASHBOARD PÚBLICO (TV)
 * Actualiza contadores de semáforo y lista de solicitudes activas
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado. Monitor TV exclusivo para soporte.']);
    exit;
}

try {
    $pdo = getDB();

    // 1. Contadores del Semáforo
    $stmtCounts = $pdo->query("
        SELECT 
            SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END) AS pendientes,
            SUM(CASE WHEN estado = 'EN PROCESO' THEN 1 ELSE 0 END) AS en_proceso,
            SUM(CASE WHEN estado = 'EN ESPERA' THEN 1 ELSE 0 END) AS en_espera,
            SUM(CASE WHEN estado = 'ATENDIDO' AND (DATE(fecha_cierre) = CURDATE() OR DATE(fecha_actualizacion) = CURDATE()) THEN 1 ELSE 0 END) AS atendidos_hoy
        FROM tickets
    ");
    $counts = $stmtCounts->fetch() ?: [
        'pendientes' => 0,
        'en_proceso' => 0,
        'en_espera' => 0,
        'atendidos_hoy' => 0
    ];

    // Asegurar enteros
    $resCounts = [
        'pendientes'    => intval($counts['pendientes'] ?? 0),
        'en_proceso'    => intval($counts['en_proceso'] ?? 0),
        'en_espera'     => intval($counts['en_espera'] ?? 0),
        'atendidos_hoy' => intval($counts['atendidos_hoy'] ?? 0),
    ];

    // 2. Solicitudes Activas (PENDIENTE, EN PROCESO, EN ESPERA)
    // Ordenar por prioridad (Alta primero) y fecha más antigua primero (FIFO)
    $stmtTickets = $pdo->query("
        SELECT 
            folio, ip, hostname, inventario, area, problema, descripcion, prioridad, estado,
            fecha_creacion, extension
        FROM tickets 
        WHERE estado IN ('PENDIENTE', 'EN PROCESO', 'EN ESPERA')
        ORDER BY 
            CASE prioridad 
                WHEN 'Alta' THEN 1 
                WHEN 'Media' THEN 2 
                WHEN 'Baja' THEN 3 
                ELSE 4 
            END ASC,
            fecha_creacion ASC
        LIMIT 50
    ");

    $ticketsRaw = $stmtTickets->fetchAll();
    $tickets = [];

    foreach ($ticketsRaw as $t) {
        $equipoDisplay = !empty($t['hostname']) 
            ? $t['hostname'] . (!empty($t['inventario']) ? " ({$t['inventario']})" : "")
            : (!empty($t['inventario']) ? $t['inventario'] : $t['ip']);

        $tickets[] = [
            'folio'              => $t['folio'],
            'area'               => $t['area'],
            'equipo'             => $equipoDisplay,
            'problema'           => $t['problema'],
            'descripcion'        => $t['descripcion'],
            'badge_prioridad'    => obtenerBadgePrioridad($t['prioridad']),
            'estado'             => $t['estado'],
            'color_estado'       => obtenerColorEstado($t['estado']),
            'tiempo_transcurrido'=> tiempoTranscurrido($t['fecha_creacion']),
            'fecha_creacion'     => date('d/m H:i', strtotime($t['fecha_creacion'])),
            'extension'          => $t['extension'] ?? '-'
        ];
    }

    jsonResponse([
        'success'     => true,
        'contadores'  => $resCounts,
        'tickets'     => $tickets
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al obtener datos del dashboard: ' . $e->getMessage()
    ], 500);
}
