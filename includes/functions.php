<?php
/**
 * FUNCIONES REUTILIZABLES DEL SISTEMA
 * Sistema de Soporte Técnico tipo Semáforo
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

/**
 * Detecta la dirección IP real del cliente
 */
function getClientIP(): string {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // En caso de proxies múltiples, tomar la primera IP
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ipList[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Normalizar IPv6 localhost
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }

    // Sanitizar IP
    $filteredIP = filter_var($ip, FILTER_VALIDATE_IP);
    return $filteredIP !== false ? $filteredIP : '127.0.0.1';
}

/**
 * Genera un Folio automático y secuencial por año: ST-AAAA-XXXX
 * Ejemplo: ST-2026-0001, ST-2026-0002
 */
function generarFolioTicket(PDO $pdo): string {
    $year = date('Y');
    $prefijo = "ST-{$year}-";

    // Bloqueo o búsqueda del máximo correlativo del año en curso
    $stmt = $pdo->prepare("
        SELECT folio FROM tickets 
        WHERE folio LIKE :prefijo 
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([':prefijo' => $prefijo . '%']);
    $ultimoTicket = $stmt->fetch();

    $secuencia = 1;
    if ($ultimoTicket && !empty($ultimoTicket['folio'])) {
        $partes = explode('-', $ultimoTicket['folio']);
        if (isset($partes[2]) && is_numeric($partes[2])) {
            $secuencia = intval($partes[2]) + 1;
        }
    }

    return sprintf("ST-%s-%04d", $year, $secuencia);
}

/**
 * Consulta la tabla equipos por IP
 */
function buscarEquipoPorIP(string $ip, PDO $pdo): ?array {
    $stmt = $pdo->prepare("SELECT * FROM equipos WHERE ip = ? LIMIT 1");
    $stmt->execute([$ip]);
    $equipo = $stmt->fetch();
    return $equipo ?: null;
}

/**
 * Devuelve el código hexadecimal del color según el estado
 */
function obtenerColorEstado(string $estado): string {
    switch (strtoupper(trim($estado))) {
        case ESTADO_PENDIENTE:
            return COLOR_PENDIENTE;   // #dc3545 - Rojo
        case ESTADO_EN_PROCESO:
            return COLOR_EN_PROCESO;  // #ffc107 - Amarillo
        case ESTADO_EN_ESPERA:
            return COLOR_EN_ESPERA;   // #0d6efd - Azul
        case ESTADO_ATENDIDO:
            return COLOR_ATENDIDO;    // #198754 - Verde
        case ESTADO_CANCELADO:
            return COLOR_CANCELADO;   // #6c757d - Gris
        default:
            return '#6c757d';
    }
}

/**
 * Devuelve una etiqueta (badge) HTML con el color correspondiente
 */
function obtenerBadgeEstado(string $estado): string {
    $estado = strtoupper(trim($estado));
    $color = obtenerColorEstado($estado);
    $textColor = ($estado === ESTADO_EN_PROCESO) ? '#000000' : '#ffffff';

    return sprintf(
        '<span class="badge" style="background-color: %s; color: %s; font-weight: 600; font-size: 0.85rem; padding: 6px 12px; border-radius: 6px;">%s</span>',
        $color,
        $textColor,
        htmlspecialchars($estado)
    );
}

/**
 * Devuelve una etiqueta badge para la prioridad
 */
function obtenerBadgePrioridad(string $prioridad): string {
    $p = ucfirst(strtolower(trim($prioridad)));
    switch ($p) {
        case 'Alta':
            return '<span class="badge bg-danger">Alta</span>';
        case 'Media':
            return '<span class="badge bg-warning text-dark">Media</span>';
        case 'Baja':
            return '<span class="badge bg-info text-dark">Baja</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($p) . '</span>';
    }
}

/**
 * Calcula el tiempo transcurrido desde una fecha dada hasta ahora
 */
function tiempoTranscurrido(string $fecha): string {
    if (empty($fecha)) return '-';

    $tiempo = strtotime($fecha);
    if (!$tiempo) return '-';

    $ahora = time();
    $diferencia = $ahora - $tiempo;

    if ($diferencia < 0) return 'Hace un momento';
    if ($diferencia < 60) return "Hace {$diferencia} seg";
    
    $minutos = round($diferencia / 60);
    if ($minutos < 60) return "Hace {$minutos} min";
    
    $horas = round($diferencia / 3600);
    if ($horas < 24) return "Hace {$horas} h";
    
    $dias = round($diferencia / 86400);
    if ($dias < 30) return "Hace {$dias} d";
    
    return date('d/m/Y H:i', $tiempo);
}

/**
 * Calcula la duración legible entre dos marcas de tiempo
 */
function calcularDuracion(?string $fechaInicio, ?string $fechaFin): string {
    if (empty($fechaInicio) || empty($fechaFin)) return '-';

    $t1 = strtotime($fechaInicio);
    $t2 = strtotime($fechaFin);
    if (!$t1 || !$t2 || $t2 < $t1) return '-';

    $segundos = $t2 - $t1;
    $horas = floor($segundos / 3600);
    $minutos = floor(($segundos % 3600) / 60);

    if ($horas > 0) {
        return "{$horas}h {$minutos}m";
    }
    return "{$minutos} min";
}

/**
 * Envía una respuesta JSON estándar y termina la ejecución
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
