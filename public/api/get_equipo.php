<?php
/**
 * API: DETECTAR Y CONSULTAR EQUIPO POR IP
 * Devuelve datos del equipo si está registrado en la base de datos
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDB();
    
    // Obtener IP enviada por parámetro o detectar automáticamente
    $ip = isset($_GET['ip']) && !empty($_GET['ip']) ? trim($_GET['ip']) : getClientIP();

    // Normalizar IPv6 localhost
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    $equipo = buscarEquipoPorIP($ip, $pdo);

    if ($equipo) {
        jsonResponse([
            'success' => true,
            'found'   => true,
            'ip'      => $ip,
            'data'    => [
                'hostname'    => $equipo['hostname'],
                'inventario'  => $equipo['inventario'],
                'area'        => $equipo['area'],
                'ubicacion'   => $equipo['ubicacion'],
                'extension'   => $equipo['extension'] ?? ''
            ],
            'message' => 'Equipo detectado exitosamente en el inventario.'
        ]);
    } else {
        jsonResponse([
            'success' => true,
            'found'   => false,
            'ip'      => $ip,
            'data'    => null,
            'message' => 'IP no registrada previamente. Capture el área manualmente.'
        ]);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al consultar equipo: ' . $e->getMessage()
    ], 500);
}
