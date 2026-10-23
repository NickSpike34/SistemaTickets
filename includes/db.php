<?php
/**
 * CONEXIÓN PDO A LA BASE DE DATOS
 * Sistema de Soporte Técnico tipo Semáforo
 */

require_once __DIR__ . '/../config/config.php';

function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Si es una petición AJAX, responder JSON limpio
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' 
                || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false)) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error crítico de conexión a la Base de Datos: ' . $e->getMessage()
                ]);
                exit;
            }

            // Vista de error visual para navegación web
            die('<div style="font-family:Arial,sans-serif;max-width:600px;margin:50px auto;padding:20px;border:1px solid #dc3545;border-radius:8px;background:#f8d7da;color:#721c24;">
                <h3 style="margin-top:0;">⚠️ Error de Conexión a la Base de Datos</h3>
                <p>No se pudo conectar con el servidor MySQL/MariaDB.</p>
                <p><strong>Detalle técnico:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                <hr>
                <p style="font-size:13px;">Verifica que MySQL esté activo en XAMPP/Laragon y que la base de datos <code>' . htmlspecialchars(DB_NAME) . '</code> haya sido importada con <code>database/schema.sql</code>.</p>
            </div>');
        }
    }

    return $pdo;
}
