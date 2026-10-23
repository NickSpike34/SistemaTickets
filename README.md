# Sistema de Soporte Técnico

Sistema integral de gestión de tickets de soporte técnico con interfaz tipo semáforo en tiempo real para intranets corporativas. Diseñado para ejecutarse de manera inmediata en entornos **XAMPP / Laragon** sin dependencias externas complejas ni frameworks pesados.

---

## Tecnologías Utilizadas

- **Backend:** PHP nativo 7.4+ / 8.x (sin frameworks, código limpio estructurado)
- **Base de Datos:** MySQL / MariaDB utilizando **PDO** con consultas preparadas (*Prepared Statements*)
- **Frontend:** HTML5, CSS3, **Bootstrap 5.3** (UI moderna y responsive), Bootstrap Icons
- **Interactividad:** **jQuery 3.7** + **AJAX** asíncrono
- **Reportes:** **FPDF 1.86** nativo (generación directa de PDF sin Composer) y exportador a CSV con BOM UTF-8 para Excel
- **Servidor:** Apache con soporte `.htaccess`

---

## Estructura del Proyecto

```text
soporte/
├── config/
│   └── config.php               # Parámetros de BD, URLs y paleta de semáforo
├── database/
│   └── schema.sql               # Script SQL con estructura y datos demo
├── includes/
│   ├── auth.php                 # Control de sesiones y autenticación
│   ├── db.php                   # Conexión PDO optimizada
│   ├── functions.php            # Utilidades, IP, formato ST-AAAA-XXXX, badges
│   └── fpdf/
│       └── fpdf.php             # Librería PDF standalone
├── public/
│   ├── index.php                # Formulario público de solicitud (Auto IP)
│   ├── admin/
│   │   ├── index.php            # Gestión, búsqueda y filtros de tickets
│   │   ├── login.php            # Inicio de sesión para técnicos
│   │   ├── logout.php           # Cierre seguro de sesión
│   │   └── ticket.php           # Detalle, bitácora y cambio de estado
│   ├── api/
│   │   ├── actualizar_estado.php# AJAX: actualización de estado
│   │   ├── agregar_comentario.php# AJAX: notas y archivos adjuntos
│   │   ├── crear_ticket.php     # AJAX: alta de tickets y folios
│   │   ├── dashboard_data.php   # AJAX: datos en vivo para monitor TV
│   │   └── get_equipo.php       # AJAX: detección por IP
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css        # Paleta oficial semáforo y modo TV
│   │   └── js/
│   │       └── script.js        # Lógica frontend y llamadas AJAX
│   └── dashboard/
│       └── index.php            # Monitor Semáforo para pantalla TV (12s auto-refresh)
├── reportes/
│   ├── index.php                # Métricas, KPIs, tiempos y distribución
│   └── exportar.php             # Exportador a PDF y CSV
├── uploads/                     # Almacén de evidencias y adjuntos
├── .htaccess                    # Reglas de servidor Apache
├── index.php                    # Redirección raíz a /public/
└── README.md                    # Documentación de instalación
```

---

## Paleta Oficial del Semáforo

| Estado | Color Hex | Significado |
| :--- | :--- | :--- |
| **PENDIENTE** | `#dc3545` (Rojo) | Nueva solicitud en espera de asignación |
| **EN PROCESO** | `#ffc107` (Amarillo) | Técnico asignado trabajando en el equipo |
| **EN ESPERA** | `#0d6efd` (Azul) | Detenido por compras, refacción o proveedor |
| **ATENDIDO** | `#198754` (Verde) | Requerimiento resuelto exitosamente |
| **CANCELADO** | `#6c757d` (Gris) | Solicitud duplicada o improcedente |

---

## Guía de Instalación Rápida (XAMPP / Laragon)

### 1. Ubicación de los Archivos
Copie o clone la carpeta del proyecto dentro del directorio raíz de su servidor web local:
- En **XAMPP:** `C:\xampp\htdocs\soporte`
- En **Laragon:** `C:\laragon\www\soporte`

### 2. Importar la Base de Datos
1. Inicie los servicios de **Apache** y **MySQL** desde el panel de control de XAMPP / Laragon.
2. Abra su navegador e ingrese a **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Vaya a la pestaña **Importar**.
4. Seleccione el archivo: `database/schema.sql` y presione **Continuar** (creará automáticamente la base de datos `soporte_tickets` y sus datos iniciales).
   > *Alternativa por consola:*
   > ```bash
   > mysql -u root -p < database/schema.sql
   > ```

### 3. Verificar Configuración (`config/config.php`)
Los valores predeterminados funcionan de forma automática:
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'soporte_tickets');
define('DB_USER', 'root');
define('DB_PASS', ''); // Dejar vacío en XAMPP estándar o colocar su contraseña
```

---

## Credenciales de Acceso (Usuarios de Soporte)

El script SQL incluye 2 usuarios preconfigurados (la contraseña inicial está documentada en el comentario de `database/schema.sql`; cámbiela tras el primer acceso):

| Usuario | Nombre del Técnico | Rol |
| :--- | :--- | :--- |
| **`Guizardonio`** | Guizar | Técnico TI |
| **`Checo`** | Sergio | Técnico TI |

*(Las contraseñas se almacenan con cifrado seguro `bcrypt` y cuentan con regeneración dinámica de sesión).*

---

## URLs de Acceso al Sistema

Suponiendo que el proyecto se encuentra en `http://localhost/soporte/`:

- **Formulario Público de Solicitudes:**  
  `http://localhost/soporte/` o `http://localhost/soporte/public/`  
  *Detecta la IP del usuario, precarga datos si el equipo existe en el inventario o solicita el área manualmente. Al enviar, genera el folio correlativo `ST-2026-XXXX`.*

- **Monitor Semáforo Público para Pantalla TV:**  
  `http://localhost/soporte/public/dashboard/`  
  *Diseñado para televisores de 50"+ en modo oscuro, con reloj digital, contadores de semáforo y actualización automática cada 12 segundos vía AJAX.*

- **Panel de Administración y Soporte:**  
  `http://localhost/soporte/public/admin/` (Login: `public/admin/login.php`)  
  *Permite filtrar tickets, cambiar estados, asignar técnicos, agregar notas y subir evidencias.*

- **Módulo de Reportes y Estadísticas:**  
  `http://localhost/soporte/reportes/`  
  *Muestra KPIs, tiempo promedio de atención, distribución de tickets por área, top de fallas y exportación directa a PDF y CSV.*

---

## Datos de Prueba Incluidos

El script `schema.sql` inicializa:
1. **5 Equipos en inventario:** Incluyendo `127.0.0.1` y `::1` (para prueba inmediata en localhost), así como IPs de ejemplo (`192.168.1.45`, `192.168.1.60`, `192.168.1.88`).
2. **3 Tickets activos de ejemplo:**
   - `ST-2026-0001` (🔴 Pendiente - Falla de impresión)
   - `ST-2026-0002` (🟡 En Proceso - Lentitud de equipo)
   - `ST-2026-0003` (🔵 En Espera - Reemplazo de fuente de poder)
3. **2 Comentarios y notas de avance técnico** registrados en la bitácora.

---

## Características de Seguridad

- Prepared Statements con PDO para prevenir inyecciones SQL.
- Sanitización de entradas y escape de caracteres para prevenir XSS.
- Control de subida de archivos (solo extensiones autorizadas: imágenes, PDF, documentos; límite de 10 MB).
- Protección de directorio `uploads/` y archivos `.sql`/`.log` mediante `.htaccess` e `index.php` de bloqueo.
- Manejo de sesiones con cookies `HttpOnly` y protección contra Session Fixation (`session_regenerate_id`).
"# SistemaTickets" 
"# SistemaTickets" 
"# SistemaTickets" 
