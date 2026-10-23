<?php
/**
 * API: ACTUALIZAR ESTADO DE UN TICKET
 * Permite a los usuarios de soporte cambiar estados (PENDIENTE, EN PROCESO, EN ESPERA, ATENDIDO, CANCELADO)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Acceso no autorizado. Inicie sesión.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido. Utilice POST.'], 405);
}

try {
    $pdo = getDB();
    $loggedUser = getLoggedUser();

    $ticketId   = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $nuevoEstado = isset($_POST['estado']) ? strtoupper(trim($_POST['estado'])) : '';
    $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
    $soporte    = isset($_POST['usuario_soporte']) && !empty(trim($_POST['usuario_soporte'])) 
                    ? trim($_POST['usuario_soporte']) 
                    : $loggedUser['nombre'];

    $estadosValidos = [
        ESTADO_PENDIENTE, 
        ESTADO_EN_PROCESO, 
        ESTADO_EN_ESPERA, 
        ESTADO_ATENDIDO, 
        ESTADO_CANCELADO
    ];

    if ($ticketId <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID de ticket no válido.'], 400);
    }

    if (!in_array($nuevoEstado, $estadosValidos)) {
        jsonResponse(['success' => false, 'message' => 'Estado seleccionado inválido.'], 400);
    }

    // Consultar ticket actual
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        jsonResponse(['success' => false, 'message' => 'Ticket no encontrado.'], 404);
    }

    $esCierre = in_array($nuevoEstado, [ESTADO_ATENDIDO, ESTADO_CANCELADO]);
    
    // Iniciar transacción
    $pdo->beginTransaction();

    if ($esCierre) {
        $updateSql = "UPDATE tickets SET 
                        estado = :estado, 
                        usuario_soporte = :usuario_soporte, 
                        fecha_actualizacion = NOW(), 
                        fecha_cierre = IF(fecha_cierre IS NULL, NOW(), fecha_cierre) 
                      WHERE id = :id";
    } else {
        $updateSql = "UPDATE tickets SET 
                        estado = :estado, 
                        usuario_soporte = :usuario_soporte, 
                        fecha_actualizacion = NOW(), 
                        fecha_cierre = NULL 
                      WHERE id = :id";
    }

    $stmtUpdate = $pdo->prepare($updateSql);
    $stmtUpdate->execute([
        ':estado'          => $nuevoEstado,
        ':usuario_soporte' => $soporte,
        ':id'              => $ticketId
    ]);

    // Si viene un comentario en el cambio de estado, registrarlo en la tabla de comentarios
    if (!empty($comentario)) {
        $textoComentario = "[CAMBIO DE ESTADO A {$nuevoEstado}]: " . $comentario;
        $stmtCom = $pdo->prepare("INSERT INTO comentarios (ticket_id, usuario_soporte_id, texto, fecha_creacion) VALUES (?, ?, ?, NOW())");
        $stmtCom->execute([$ticketId, $loggedUser['id'], $textoComentario]);
    }

    $pdo->commit();

    jsonResponse([
        'success'      => true,
        'message'      => "El ticket {$ticket['folio']} fue actualizado a estado {$nuevoEstado}.",
        'nuevo_estado' => $nuevoEstado,
        'badge_html'   => obtenerBadgeEstado($nuevoEstado),
        'color'        => obtenerColorEstado($nuevoEstado)
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => 'Error al actualizar el estado: ' . $e->getMessage()
    ], 500);
}
