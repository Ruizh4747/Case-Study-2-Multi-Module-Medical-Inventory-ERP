<?php
// Manages atomic inventory transactions to ensure data consistency
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

// (Authentication and Role verification omitted for brevity)
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$productId = (int)($body['producto_id'] ?? 0);
$lotNumber = trim($body['lote'] ?? '');
$quantity = (int)($body['cantidad'] ?? 0);
$destinationId = (int)($body['destino_id'] ?? 0);

try {
    // 1. Validate Stock Availability
    $stmtCheck = $pdo->prepare("SELECT id, cantidad, fecha_vencimiento FROM inventory_balances WHERE ubicacion_id = :ubi AND producto_id = :prod AND lote = :lote LIMIT 1");
    $stmtCheck->execute(['ubi' => $LOCATION_SOURCE, 'prod' => $productId, 'lote' => $lotNumber]);
    $lotInfo = $stmtCheck->fetch();

    if (!$lotInfo || $lotInfo['cantidad'] < $quantity) {
        throw new Exception("Insufficient stock.");
    }

    // 2. Begin Atomic Transaction
    $pdo->beginTransaction();

    // 2a. Deduct from Source
    $pdo->prepare("UPDATE inventory_balances SET cantidad = cantidad - :cant WHERE id = :id")
        ->execute(['cant' => $quantity, 'id' => $lotInfo['id']]);

    // 2b. Add to Destination (Handling potential duplicates gracefully)
    $pdo->prepare("
        INSERT INTO inventory_balances (ubicacion_id, producto_id, lote, fecha_vencimiento, cantidad)
        VALUES (:ubi, :prod, :lote, :vence, :cant)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)
    ")->execute([
        'ubi' => $destinationId,
        'prod' => $productId,
        'lote' => $lotInfo['lote'],
        'vence' => $lotInfo['fecha_vencimiento'],
        'cant' => $quantity
    ]);

    // 2c. Log Transaction History
    $pdo->prepare("INSERT INTO movement_logs (type, source_id, destination_id, product_id, quantity) VALUES ('TRANSFER', :orig, :dest, :prod, :cant)")
        ->execute(['orig' => $LOCATION_SOURCE, 'dest' => $destinationId, 'prod' => $productId, 'cant' => $quantity]);

    // 3. Commit Transaction
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Transfer completed."]);

} catch (Exception $e) {
    // Ensure no partial data is saved if an error occurs
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
