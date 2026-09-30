# Multi-Module Medical Inventory ERP

**Client/Context:** Clínica La Floresta
**Role:** Full-Stack Developer & Database Architect
**Tech Stack:** PHP, MySQL, Vanilla JavaScript, Relational Database Design

## 1. The Problem
The clinic required a robust, scalable system to manage a complex web of medical inventory across multiple departments. The operation lacked a unified architecture, leading to data silos, slow query times, and difficulties in tracking the lifecycle of critical medical supplies. They needed a secure, multi-module web application to centralize their entire inventory operation.

## 2. The Solution
I architected and developed a custom, modular web application from the ground up, focusing on backend stability, data integrity, and fast execution.

* **Database Architecture:** Designed a highly normalized MySQL database schema to handle multiple interconnected modules (master inventory, departmental stock, user roles, and transaction logs), ensuring absolute data integrity and zero redundancy.
* **Backend Development (PHP):** Built a secure PHP backend utilizing PDO to handle complex CRUD operations, session management, and relational data joining. I focused heavily on refactoring queries to optimize server response times.
* **Frontend Integration:** Developed a clean, functional interface utilizing JavaScript and asynchronous requests (Fetch/AJAX) to interact with the PHP backend, providing a seamless, fast experience for the clinical staff without unnecessary page reloads.

## 3. The Impact
* Centralized multiple departmental inventories into a single, reliable source of truth.
* Optimized database queries, drastically reducing load times when generating large inventory and consumption reports.
* Delivered a scalable, modular foundation that allows the clinic to easily add new features as their operational needs grow.

---

## 🖥️ System Interface

![Dashboard Preview](./assets/dashboard-preview.png)
![Inventory Module](./assets/inventory-module.png)

---

## 🧠 Core Engineering Highlight: Atomic Transactions

To guarantee data integrity during complex inventory transfers across departments, I implemented strict SQL transactions using PHP Data Objects (PDO). This ensures that if any part of the transfer process fails (e.g., deducting stock but failing to log the movement), the entire operation is rolled back, preventing orphaned data or stock discrepancies.

```php
// api/almacen/emergencia.php (Sanitized Snippet)
try {
    // 1. Validate Stock Availability
    $stmtCheck =$pdo->prepare("SELECT id, cantidad, fecha_vencimiento FROM inventario_saldos WHERE ubicacion_id = :ubi AND producto_id = :prod AND lote = :lote LIMIT 1");
    $stmtCheck->execute(['ubi' =>$ALMACEN_GENERAL, 'prod' => $producto_id, 'lote' =>$lote]);
    $lotInfo =$stmtCheck->fetch();

    if (!$lotInfo || $lotInfo['cantidad'] <$cantidad) {
        throw new Exception("Insufficient stock.");
    }

    // 2. Begin Atomic Transaction
    $pdo->beginTransaction();

    // 2a. Deduct from Source
    $pdo->prepare("UPDATE inventario_saldos SET cantidad = cantidad - :cant WHERE id = :id")
        ->execute(['cant' => $cantidad, 'id' =>$lotInfo['id']]);

    // 2b. Add to Destination (Handling potential duplicates gracefully)
    $pdo->prepare("
        INSERT INTO inventario_saldos (ubicacion_id, producto_id, lote, fecha_vencimiento, cantidad)
        VALUES (:ubi, :prod, :lote, :vence, :cant)
        ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)
    ")->execute([
        'ubi'   => $destino_id,
        'prod'  => $producto_id,
        'lote'  => $lotInfo['lote'],
        'vence' => $lotInfo['fecha_vencimiento'],
        'cant'  => $cantidad,
    ]);

    // 2c. Log Transaction History
    $pdo->prepare("INSERT INTO movimientos_historial (tipo_movimiento, origen_id, destino_id, producto_id, cantidad) VALUES ('DESPACHO_EMG', :orig, :dest, :prod, :cant)")
        ->execute(['orig' => $ALMACEN_GENERAL, 'dest' =>$destino_id, 'prod' => $producto_id, 'cant' =>$cantidad]);

    // 3. Commit Transaction
    $pdo->commit();
    
} catch (Exception $e) {
    // Ensure no partial data is saved if an error occurs
    if ($pdo->inTransaction()) {$pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
