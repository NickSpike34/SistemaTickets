<?php
/**
 * API: REGISTRO DE NUEVA SOLICITUD DE SOPORTE (TICKET)
 * Genera folio automático ST-AAAA-XXXX y guarda con estado PENDIENTE
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido. Utilice POST.'], 405);
}

try {
    $pdo = getDB();

    // Obtener campos de la solicitud
    $ip         = isset($_POST['ip']) && !empty(trim($_POST['ip'])) ? trim($_POST['ip']) : getClientIP();
    $hostname   = isset($_POST['hostname']) ? trim($_POST['hostname']) : null;
    $inventario = isset($_POST['inventario']) ? trim($_POST['inventario']) : null;
    $area       = isset($_POST['area']) ? trim($_POST['area']) : '';
    $problema   = isset($_POST['problema']) ? trim($_POST['problema']) : '';
    $descripcion= isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $prioridad  = isset($_POST['prioridad']) ? trim($_POST['prioridad']) : 'Media';
    $extension  = isset($_POST['extension']) ? trim($_POST['extension']) : '';

    // Validaciones obligatorias
    if (empty($area)) {
        jsonResponse(['success' => false, 'message' => 'El campo Área es obligatorio.'], 400);
    }
    if (empty($problema)) {
        jsonResponse(['success' => false, 'message' => 'Por favor seleccione o especifique el problema.'], 400);
    }
    if (empty($descripcion)) {
        jsonResponse(['success' => false, 'message' => 'Por favor ingrese la descripción detallada del problema.'], 400);
    }

    // Validar prioridad
    $prioridadesValidas = ['Alta', 'Media', 'Baja'];
    if (!in_array($prioridad, $prioridadesValidas)) {
        $prioridad = 'Media';
    }

    // Iniciar Transacción para asegurar Folio secuencial único
    $pdo->beginTransaction();

    $folio = generarFolioTicket($pdo);

    $sql = "INSERT INTO tickets 
            (folio, ip, hostname, inventario, area, problema, descripcion, prioridad, extension, estado, fecha_creacion, fecha_actualizacion) 
            VALUES 
            (:folio, :ip, :hostname, :inventario, :area, :problema, :descripcion, :prioridad, :extension, 'PENDIENTE', NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':folio'       => $folio,
        ':ip'          => $ip,
        ':hostname'    => !empty($hostname) ? $hostname : null,
        ':inventario'  => !empty($inventario) ? $inventario : null,
        ':area'        => $area,
        ':problema'    => $problema,
        ':descripcion' => $descripcion,
        ':prioridad'   => $prioridad,
        ':extension'   => !empty($extension) ? $extension : null,
    ]);

    $pdo->commit();

    jsonResponse([
        'success'   => true,
        'folio'     => $folio,
        'message'   => "Solicitud registrada con éxito. Su número de folio es: {$folio}",
        'data'      => [
            'area'       => $area,
            'problema'   => $problema,
            'prioridad'  => $prioridad
        ]
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse([
        'success' => false,
        'message' => 'Error al guardar la solicitud: ' . $e->getMessage()
    ], 500);
}
