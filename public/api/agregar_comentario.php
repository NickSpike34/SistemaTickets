<?php
/**
 * API: AGREGAR COMENTARIO / EVIDENCIA A UN TICKET
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Acceso no autorizado. Inicie sesión.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido.'], 405);
}

try {
    $pdo = getDB();
    $loggedUser = getLoggedUser();

    $ticketId = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
    $texto    = isset($_POST['texto']) ? trim($_POST['texto']) : '';

    if ($ticketId <= 0 || empty($texto)) {
        jsonResponse(['success' => false, 'message' => 'El comentario no puede estar vacío.'], 400);
    }

    // Verificar existencia del ticket
    $stmtT = $pdo->prepare("SELECT id FROM tickets WHERE id = ?");
    $stmtT->execute([$ticketId]);
    if (!$stmtT->fetch()) {
        jsonResponse(['success' => false, 'message' => 'El ticket no existe.'], 404);
    }

    $nombreArchivo = null;

    // Manejo de archivo adjunto (evidencia opcional)
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['archivo']['tmp_name'];
        $fileName = $_FILES['archivo']['name'];
        $fileSize = $_FILES['archivo']['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'doc', 'docx', 'zip'];

        if (!in_array($fileExtension, $extensionesPermitidas)) {
            jsonResponse(['success' => false, 'message' => 'Formato de archivo no permitido. Tipos permitidos: JPG, PNG, PDF, DOCX, ZIP, TXT.'], 400);
        }

        if ($fileSize > 10 * 1024 * 1024) { // 10MB máximo
            jsonResponse(['success' => false, 'message' => 'El archivo supera el tamaño máximo de 10 MB.'], 400);
        }

        $uploadsDir = ROOT_PATH . '/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        // Generar nombre de archivo único
        $nombreArchivo = 'evidencia_' . $ticketId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExtension;
        $destPath = $uploadsDir . '/' . $nombreArchivo;

        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            jsonResponse(['success' => false, 'message' => 'Error al subir el archivo de evidencia.'], 500);
        }
    }

    $stmt = $pdo->prepare("INSERT INTO comentarios (ticket_id, usuario_soporte_id, texto, fecha_creacion, archivo) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute([$ticketId, $loggedUser['id'], $texto, $nombreArchivo]);

    $comentarioId = $pdo->lastInsertId();

    // Actualizar fecha de modificación del ticket
    $stmtUp = $pdo->prepare("UPDATE tickets SET fecha_actualizacion = NOW() WHERE id = ?");
    $stmtUp->execute([$ticketId]);

    jsonResponse([
        'success'    => true,
        'message'    => 'Comentario registrado con éxito.',
        'comentario' => [
            'id'             => $comentarioId,
            'autor'          => $loggedUser['nombre'],
            'texto'          => htmlspecialchars($texto),
            'fecha_creacion' => date('d/m/Y H:i'),
            'archivo'        => $nombreArchivo ? BASE_URL . '/uploads/' . $nombreArchivo : null,
            'archivo_nombre' => $nombreArchivo
        ]
    ]);

} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Error al registrar el comentario: ' . $e->getMessage()
    ], 500);
}
