<?php
// Handles complex, role-based inventory retrieval with advanced JOINs
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

// Ensure active session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$userRole = $_SESSION['rol'];
$locationId = (int) $_SESSION['ubicacion_id'];

try {
    // Base query utilizing JOINs to aggregate data from multiple normalized tables
    $baseQuery = "
        SELECT 
            i.id, i.lote, i.fecha_vencimiento, i.cantidad,
            p.id AS producto_id, p.codigo, p.nombre AS producto_nombre, p.categoria, p.stock_minimo,
            u.id AS ubicacion_id, u.nombre AS ubicacion_nombre,
            CASE 
                WHEN i.fecha_vencimiento IS NOT NULL AND i.fecha_vencimiento < CURDATE() THEN 'VENCIDO'
                WHEN i.cantidad <= p.stock_minimo THEN 'BAJO'
                WHEN i.fecha_vencimiento IS NOT NULL AND i.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'POR_VENCER'
                ELSE 'OK' 
            END AS alerta
        FROM inventory i
        JOIN products p ON p.id = i.producto_id
        JOIN locations u ON u.id = i.ubicacion_id
        WHERE p.activo = 1
    ";

    // Dynamic query construction based on Role-Based Access Control (RBAC)
    if (in_array($userRole, ['ADMIN_GLOBAL', 'ADMIN_MASTER'])) {
        $stmt = $pdo->query($baseQuery . " ORDER BY u.nombre, p.nombre");
    } elseif ($userRole === 'MANAGER_MAIN_HUB') {
        $stmt = $pdo->prepare($baseQuery . " AND i.ubicacion_id IN (2, 3, 4) ORDER BY u.nombre, p.nombre");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare($baseQuery . " AND i.ubicacion_id = :ubicacion_id ORDER BY p.nombre");
        $stmt->execute(['ubicacion_id' => $locationId]);
    }

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query failed.']);
}
