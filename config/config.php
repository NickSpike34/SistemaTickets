<?php
/**
 * ARCHIVO DE CONFIGURACIÓN GLOBAL
 * Sistema de Soporte Técnico tipo Semáforo
 */

// Evitar doble inclusión
if (!defined('APP_CONFIG_LOADED')) {
    define('APP_CONFIG_LOADED', true);

    // Configuración de Zona Horaria y Codificación
    date_default_timezone_set('America/Mexico_City');
    mb_internal_encoding('UTF-8');

    // Configuración de la Base de Datos (XAMPP / Laragon por defecto)
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'soporte_tickets');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');

    // Detección dinámica de la URL base
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Directorio relativo del proyecto
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalizar la ruta raíz del proyecto
    $subfolders = ['/public/admin', '/public/dashboard', '/public/api', '/public', '/reportes'];
    $basePath = $scriptDir;
    foreach ($subfolders as $sub) {
        if (substr($basePath, -strlen($sub)) === $sub) {
            $basePath = substr($basePath, 0, -strlen($sub));
            break;
        }
    }
    $basePath = rtrim($basePath, '/');

    define('BASE_URL', $protocol . $host . $basePath);
    define('ROOT_PATH', realpath(__DIR__ . '/..'));

    // Paleta Oficial de Colores Semáforo
    define('COLOR_PENDIENTE', '#dc3545');   // Rojo
    define('COLOR_EN_PROCESO', '#ffc107');  // Amarillo
    define('COLOR_EN_ESPERA', '#0d6efd');   // Azul
    define('COLOR_ATENDIDO', '#198754');    // Verde
    define('COLOR_CANCELADO', '#6c757d');   // Gris

    // Constantes de Estados
    define('ESTADO_PENDIENTE', 'PENDIENTE');
    define('ESTADO_EN_PROCESO', 'EN PROCESO');
    define('ESTADO_EN_ESPERA', 'EN ESPERA');
    define('ESTADO_ATENDIDO', 'ATENDIDO');
    define('ESTADO_CANCELADO', 'CANCELADO');

    // Nombre del Sistema
    define('APP_NAME', 'Mesa de Ayuda TI - Semáforo');
}
