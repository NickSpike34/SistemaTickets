<?php
/**
 * GENERADOR Y EXPORTADOR DE REPORTES (PDF Y CSV)
 * Sistema de Soporte Técnico tipo Semáforo
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/fpdf/fpdf.php';

requireAuth();
$loggedUser = getLoggedUser();
$pdo = getDB();

$tipo      = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : 'pdf';
$desde     = isset($_GET['desde']) && !empty($_GET['desde']) ? trim($_GET['desde']) : date('Y-m-01');
$hasta     = isset($_GET['hasta']) && !empty($_GET['hasta']) ? trim($_GET['hasta']) : date('Y-m-d');
$filtroArea   = isset($_GET['area']) ? trim($_GET['area']) : '';
$filtroEstado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$filtroEquipo = isset($_GET['equipo']) ? trim($_GET['equipo']) : '';

// Construir consulta filtrada
$whereFiltros = ["fecha_creacion BETWEEN :desde AND :hasta"];
$params = [
    ':desde' => $desde . ' 00:00:00',
    ':hasta' => $hasta . ' 23:59:59'
];

if (!empty($filtroArea)) {
    $whereFiltros[] = "area = :area";
    $params[':area'] = $filtroArea;
}
if (!empty($filtroEstado)) {
    $whereFiltros[] = "estado = :estado";
    $params[':estado'] = $filtroEstado;
}
if (!empty($filtroEquipo)) {
    $whereFiltros[] = "(hostname LIKE :eq1 OR inventario LIKE :eq2 OR ip LIKE :eq3)";
    foreach (['eq1', 'eq2', 'eq3'] as $k) {
        $params[":{$k}"] = "%{$filtroEquipo}%";
    }
}

$sql = "SELECT * FROM tickets WHERE " . implode(" AND ", $whereFiltros) . " ORDER BY fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// ==========================================================
// 1. EXPORTACIÓN A CSV (EXCEL COMPATIBLE)
// ==========================================================
if ($tipo === 'csv') {
    $filename = "reporte_tickets_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Agregar BOM UTF-8 para que Microsoft Excel abra acentos y ñ correctamente
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Encabezados de columna
    fputcsv($output, [
        'ID',
        'Folio',
        'Fecha Creacion',
        'IP Estacion',
        'Hostname',
        'Inventario',
        'Area Solicitante',
        'Extension',
        'Problema Reportado',
        'Descripcion Detallada',
        'Prioridad',
        'Estado',
        'Ultima Actualizacion',
        'Fecha Cierre',
        'Tiempo Atencion',
        'Tecnico Asignado'
    ]);

    foreach ($tickets as $t) {
        $tiempoAtencion = $t['fecha_cierre'] ? calcularDuracion($t['fecha_creacion'], $t['fecha_cierre']) : 'En curso';

        fputcsv($output, [
            $t['id'],
            $t['folio'],
            $t['fecha_creacion'],
            $t['ip'],
            $t['hostname'] ?? 'N/A',
            $t['inventario'] ?? 'N/A',
            $t['area'],
            $t['extension'] ?? '',
            $t['problema'],
            $t['descripcion'],
            $t['prioridad'],
            $t['estado'],
            $t['fecha_actualizacion'],
            $t['fecha_cierre'] ?? 'Abierto',
            $tiempoAtencion,
            $t['usuario_soporte'] ?? 'Sin asignar'
        ]);
    }

    fclose($output);
    exit;
}

// ==========================================================
// 2. EXPORTACIÓN A PDF (CON FPDF)
// ==========================================================
function toIso($str) {
    if ($str === null || $str === '') return '';
    if (function_exists('iconv')) {
        $res = @iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$str);
        if ($res !== false) return $res;
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding((string)$str, 'ISO-8859-1', 'UTF-8');
    }
    return (string)$str;
}

class PDF_Soporte extends FPDF {
    public $rangoFechas = '';
    public $generadoPor = '';

    function Header() {
        // Franja superior de marca
        $this->SetFillColor(15, 23, 42); // #0f172a
        $this->Rect(0, 0, $this->w, 22, 'F');

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('helvetica', 'B', 14);
        $this->SetXY(10, 5);
        $this->Cell(150, 7, toIso('MESA DE AYUDA TI - REPORTE EJECUTIVO DE TICKETS'), 0, 0, 'L');

        $this->SetFont('helvetica', '', 9);
        $this->SetXY(10, 13);
        $this->Cell(150, 5, toIso('Período: ' . $this->rangoFechas . ' | Generado por: ' . $this->generadoPor), 0, 0, 'L');

        $this->SetXY($this->w - 80, 8);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(70, 6, toIso('FECHA: ' . date('d/m/Y H:i')), 0, 0, 'R');

        $this->Ln(18);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), $this->w - 10, $this->GetY());
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, toIso('Página ' . $this->PageNo() . ' de {nb} - Sistema de Soporte Técnico Tipo Semáforo'), 0, 0, 'C');
    }
}

// Crear documento horizontal (Landscape Letter)
$pdf = new PDF_Soporte('L', 'mm', 'Letter');
$pdf->AliasNbPages();
$pdf->rangoFechas = date('d/m/Y', strtotime($desde)) . " al " . date('d/m/Y', strtotime($hasta));
$pdf->generadoPor = $loggedUser['nombre'];
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AddPage();

// Resumen Estadístico en Encabezado
$totalT = count($tickets);
$totalPend = 0; $totalProc = 0; $totalEsp = 0; $totalAtend = 0; $totalCanc = 0;
foreach ($tickets as $t) {
    if ($t['estado'] === 'PENDIENTE') $totalPend++;
    elseif ($t['estado'] === 'EN PROCESO') $totalProc++;
    elseif ($t['estado'] === 'EN ESPERA') $totalEsp++;
    elseif ($t['estado'] === 'ATENDIDO') $totalAtend++;
    elseif ($t['estado'] === 'CANCELADO') $totalCanc++;
}

// Bloque de Resumen KPI
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(240, 243, 246);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(259, 8, toIso(" RESUMEN DEL PERÍODO: Total: {$totalT} | Pendientes: {$totalPend} | En Proceso: {$totalProc} | En Espera: {$totalEsp} | Atendidos: {$totalAtend} | Cancelados: {$totalCanc}"), 1, 1, 'L', true);
$pdf->Ln(3);

// Encabezados de la Tabla
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(30, 41, 59);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(24, 7, 'FOLIO', 1, 0, 'C', true);
$pdf->Cell(38, 7, toIso('ÁREA'), 1, 0, 'L', true);
$pdf->Cell(32, 7, 'EQUIPO / IP', 1, 0, 'L', true);
$pdf->Cell(62, 7, toIso('PROBLEMA REPORTADO'), 1, 0, 'L', true);
$pdf->Cell(18, 7, 'PRIORIDAD', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'ESTADO', 1, 0, 'C', true);
$pdf->Cell(25, 7, toIso('FECHA REG.'), 1, 0, 'C', true);
$pdf->Cell(35, 7, toIso('TÉCNICO'), 1, 1, 'L', true);

// Filas de Datos
$pdf->SetFont('helvetica', '', 7.5);
$pdf->SetTextColor(0, 0, 0);

if (empty($tickets)) {
    $pdf->Cell(259, 10, toIso('No se encontraron tickets con los filtros seleccionados.'), 1, 1, 'C');
} else {
    $fill = false;
    foreach ($tickets as $t) {
        $pdf->SetFillColor($fill ? 248 : 255, $fill ? 249 : 255, $fill ? 250 : 255);

        $equipoTxt = !empty($t['hostname']) ? $t['hostname'] : $t['ip'];
        $folioTxt = $t['folio'];
        $areaTxt = substr($t['area'], 0, 24);
        $probTxt = substr($t['problema'], 0, 42);
        $prioridadTxt = $t['prioridad'];
        $estadoTxt = $t['estado'];
        $fechaTxt = date('d/m/y H:i', strtotime($t['fecha_creacion']));
        $tecnicoTxt = substr($t['usuario_soporte'] ?: 'Sin asignar', 0, 22);

        $pdf->Cell(24, 6, $folioTxt, 1, 0, 'C', $fill);
        $pdf->Cell(38, 6, toIso($areaTxt), 1, 0, 'L', $fill);
        $pdf->Cell(32, 6, toIso($equipoTxt), 1, 0, 'L', $fill);
        $pdf->Cell(62, 6, toIso($probTxt), 1, 0, 'L', $fill);
        $pdf->Cell(18, 6, toIso($prioridadTxt), 1, 0, 'C', $fill);
        
        // Color por estado en celda
        if ($t['estado'] === 'PENDIENTE') {
            $pdf->SetTextColor(220, 53, 69);
        } elseif ($t['estado'] === 'EN PROCESO') {
            $pdf->SetTextColor(180, 120, 0);
        } elseif ($t['estado'] === 'ATENDIDO') {
            $pdf->SetTextColor(25, 135, 84);
        } elseif ($t['estado'] === 'EN ESPERA') {
            $pdf->SetTextColor(13, 110, 253);
        } else {
            $pdf->SetTextColor(108, 117, 125);
        }
        $pdf->Cell(25, 6, $estadoTxt, 1, 0, 'C', $fill);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(25, 6, $fechaTxt, 1, 0, 'C', $fill);
        $pdf->Cell(35, 6, toIso($tecnicoTxt), 1, 1, 'L', $fill);

        $fill = !$fill;
    }
}

if (ob_get_length()) {
    ob_end_clean();
}
$pdf->Output('I', 'Reporte_Soporte_' . date('Ymd_His') . '.pdf');
exit;
