<?php
/**
 * AUTENTICACIÓN Y MANEJO DE SESIONES
 * Sistema de Soporte Técnico tipo Semáforo
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configuración segura de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Autentica a un usuario de soporte
 * @param string $usuario
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser(string $usuario, string $password): array {
    $usuario = trim($usuario);
    $password = trim($password);

    if (empty($usuario) || empty($password)) {
        return ['success' => false, 'message' => 'Por favor complete todos los campos.'];
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios_soporte WHERE usuario = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'Usuario no encontrado o inactivo.'];
    }

    // Verificar contraseña (soporta hash bcrypt y compatibilidad)
    $valid = password_verify($password, $user['password']) || ($password === $user['password']);

    if (!$valid) {
        return ['success' => false, 'message' => 'Contraseña incorrecta.'];
    }

    // Si la contraseña estaba en texto plano o necesita nuevo costo de hash, actualizarla
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT) || $password === $user['password']) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE usuarios_soporte SET password = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $user['id']]);
    }

    // Regenerar ID de sesión para prevenir Session Fixation
    session_regenerate_id(true);

    $_SESSION['soporte_user_id'] = $user['id'];
    $_SESSION['soporte_usuario'] = $user['usuario'];
    $_SESSION['soporte_nombre']  = $user['nombre'];

    return [
        'success' => true,
        'message' => 'Inicio de sesión exitoso.',
        'user' => [
            'id' => $user['id'],
            'usuario' => $user['usuario'],
            'nombre' => $user['nombre']
        ]
    ];
}

/**
 * Verifica si hay una sesión activa de soporte
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['soporte_user_id']);
}

/**
 * Obtiene los datos del usuario logueado
 */
function getLoggedUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'      => $_SESSION['soporte_user_id'],
        'usuario' => $_SESSION['soporte_usuario'] ?? '',
        'nombre'  => $_SESSION['soporte_nombre'] ?? ''
    ];
}

/**
 * Protege vistas administrativas y de reportes. Redirige a login si no está autenticado.
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $loginUrl = BASE_URL . '/public/admin/login.php';
        header("Location: " . $loginUrl);
        exit;
    }
}

/**
 * Cierra la sesión activa
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
