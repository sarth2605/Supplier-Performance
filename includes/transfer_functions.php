<?php
/**
 * Supplier Performance Analysis and Management System (SPAS)
 * Product Transfer & Inventory Management Helper Functions
 * Workflow: Manufacturer -> Supplier -> Shopkeeper
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Gets stock quantity for a user and product
 */
function get_user_stock(int $user_id, int $product_id): int {
    $db = get_db();
    $stmt = $db->prepare("SELECT quantity FROM user_inventory WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$user_id, $product_id]);
    $res = $stmt->fetch();
    return $res ? (int)$res['quantity'] : 0;
}

/**
 * Adjusts user stock atomically in user_inventory
 */
function adjust_user_stock(int $user_id, string $role, int $product_id, int $delta_qty, ?string $batch_number = null): bool {
    $db = get_db();
    
    // Check if record exists
    $stmt = $db->prepare("SELECT id, quantity FROM user_inventory WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $new_qty = (int)$existing['quantity'] + $delta_qty;
        if ($new_qty < 0) {
            return false; // Cannot have negative inventory
        }
        $upd = $db->prepare("UPDATE user_inventory SET quantity = ?, batch_number = COALESCE(?, batch_number), last_updated = CURRENT_TIMESTAMP WHERE id = ?");
        return $upd->execute([$new_qty, $batch_number, $existing['id']]);
    } else {
        if ($delta_qty < 0) {
            return false; // Cannot subtract from non-existent stock
        }
        $ins = $db->prepare("INSERT INTO user_inventory (user_id, role, product_id, quantity, batch_number) VALUES (?, ?, ?, ?, ?)");
        return $ins->execute([$user_id, $role, $product_id, $delta_qty, $batch_number]);
    }
}

/**
 * Dispatches a new transfer in the supply chain
 */
function create_product_transfer(
    int $sender_id,
    string $sender_role,
    int $receiver_id,
    string $receiver_role,
    int $product_id,
    int $quantity,
    float $unit_price,
    ?string $batch_number = null,
    ?string $remarks = null,
    ?int $parent_transfer_id = null
): array {
    $db = get_db();

    // 1. Validation: Quantity must be positive
    if ($quantity <= 0) {
        return ['success' => false, 'message' => 'Transfer quantity must be greater than zero.'];
    }

    // 2. Validation: Role Stage Workflow
    $stage = '';
    if ($sender_role === 'manufacturer' && $receiver_role === 'supplier') {
        $stage = 'manufacturer_to_supplier';
    } elseif ($sender_role === 'supplier' && $receiver_role === 'shopkeeper') {
        $stage = 'supplier_to_shopkeeper';
    } else {
        return ['success' => false, 'message' => 'Invalid transfer route. Products can only move: Manufacturer → Supplier → Shopkeeper.'];
    }

    // 3. Validation: Verify sender has enough stock
    $current_stock = get_user_stock($sender_id, $product_id);
    if ($current_stock < $quantity) {
        return ['success' => false, 'message' => "Insufficient stock. Available: $current_stock units, Requested: $quantity units."];
    }

    // 4. Generate Unique Transfer Reference
    $date_prefix = date('Ymd');
    $random_suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    $transfer_ref = "TRF-{$date_prefix}-{$random_suffix}";

    $total_amount = $quantity * $unit_price;

    try {
        $db->beginTransaction();

        // 5. Decrement Sender Stock
        $stock_decremented = adjust_user_stock($sender_id, $sender_role, $product_id, -$quantity, $batch_number);
        if (!$stock_decremented) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to reserve sender inventory.'];
        }

        // 6. Insert Transfer Record
        $stmt = $db->prepare("
            INSERT INTO product_transfers (
                transfer_ref, product_id, sender_id, sender_role, 
                receiver_id, receiver_role, stage, quantity, 
                batch_number, unit_price, total_amount, status, 
                transfer_date, parent_transfer_id, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'In Transit', NOW(), ?, ?)
        ");
        $stmt->execute([
            $transfer_ref, $product_id, $sender_id, $sender_role,
            $receiver_id, $receiver_role, $stage, $quantity,
            $batch_number, $unit_price, $total_amount, $parent_transfer_id, $remarks
        ]);
        $transfer_id = $db->lastInsertId();

        // 7. Log Activity
        log_activity(
            $sender_id, 
            'Product Transfer Dispatched', 
            'Transfers', 
            $transfer_id, 
            "Dispatched $quantity units of product #$product_id (Ref: $transfer_ref) to recipient user #$receiver_id."
        );

        $db->commit();
        return [
            'success' => true, 
            'transfer_id' => $transfer_id, 
            'transfer_ref' => $transfer_ref, 
            'message' => "Transfer {$transfer_ref} successfully dispatched!"
        ];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Database error during transfer: ' . $e->getMessage()];
    }
}

/**
 * Receives an incoming product transfer into receiver's inventory
 */
function receive_product_transfer(int $transfer_id, int $receiver_id): array {
    $db = get_db();

    $stmt = $db->prepare("SELECT * FROM product_transfers WHERE id = ? LIMIT 1");
    $stmt->execute([$transfer_id]);
    $transfer = $stmt->fetch();

    if (!$transfer) {
        return ['success' => false, 'message' => 'Transfer record not found.'];
    }

    if ((int)$transfer['receiver_id'] !== $receiver_id) {
        return ['success' => false, 'message' => 'Unauthorized: You are not the designated recipient of this transfer.'];
    }

    if ($transfer['status'] === 'Received') {
        return ['success' => false, 'message' => 'This transfer has already been received.'];
    }

    if ($transfer['status'] === 'Cancelled' || $transfer['status'] === 'Rejected') {
        return ['success' => false, 'message' => "Cannot receive a transfer that is {$transfer['status']}."];
    }

    try {
        $db->beginTransaction();

        // 1. Update status to Received
        $upd = $db->prepare("UPDATE product_transfers SET status = 'Received', received_date = NOW() WHERE id = ?");
        $upd->execute([$transfer_id]);

        // 2. Increment receiver inventory
        $stock_incremented = adjust_user_stock(
            $receiver_id, 
            $transfer['receiver_role'], 
            (int)$transfer['product_id'], 
            (int)$transfer['quantity'], 
            $transfer['batch_number']
        );

        if (!$stock_incremented) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Failed to update receiver inventory.'];
        }

        // 3. Log Activity
        log_activity(
            $receiver_id, 
            'Product Transfer Received', 
            'Transfers', 
            $transfer_id, 
            "Received {$transfer['quantity']} units for Transfer {$transfer['transfer_ref']} into inventory."
        );

        $db->commit();
        return ['success' => true, 'message' => "Transfer {$transfer['transfer_ref']} successfully received into your inventory!"];
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        return ['success' => false, 'message' => 'Error receiving transfer: ' . $e->getMessage()];
    }
}

/**
 * Gets complete supply chain provenance chain for a transfer
 */
function get_transfer_chain_details(int $transfer_id): array {
    $db = get_db();
    
    // Fetch target transfer
    $stmt = $db->prepare("
        SELECT t.*, 
               p.product_name, p.product_code, p.category, p.brand,
               s.name as sender_name, s.company_name as sender_company, s.city as sender_city,
               r.name as receiver_name, r.company_name as receiver_company, r.shop_name as receiver_shop, r.city as receiver_city
        FROM product_transfers t
        JOIN products p ON t.product_id = p.id
        JOIN users s ON t.sender_id = s.id
        JOIN users r ON t.receiver_id = r.id
        WHERE t.id = ? LIMIT 1
    ");
    $stmt->execute([$transfer_id]);
    $current = $stmt->fetch();
    if (!$current) return [];

    $chain = ['current' => $current, 'parent' => null, 'children' => []];

    // If there is a parent transfer (Stage 1: Manufacturer -> Supplier)
    if (!empty($current['parent_transfer_id'])) {
        $pstmt = $db->prepare("
            SELECT t.*, 
                   s.name as sender_name, s.company_name as sender_company, s.city as sender_city,
                   r.name as receiver_name, r.company_name as receiver_company, r.city as receiver_city
            FROM product_transfers t
            JOIN users s ON t.sender_id = s.id
            JOIN users r ON t.receiver_id = r.id
            WHERE t.id = ? LIMIT 1
        ");
        $pstmt->execute([$current['parent_transfer_id']]);
        $chain['parent'] = $pstmt->fetch();
    }

    // If there are downstream child transfers (Stage 2: Supplier -> Shopkeeper)
    $cstmt = $db->prepare("
        SELECT t.*, 
               s.name as sender_name, s.company_name as sender_company, s.city as sender_city,
               r.name as receiver_name, r.shop_name as receiver_shop, r.city as receiver_city
        FROM product_transfers t
        JOIN users s ON t.sender_id = s.id
        JOIN users r ON t.receiver_id = r.id
        WHERE t.parent_transfer_id = ?
    ");
    $cstmt->execute([$transfer_id]);
    $chain['children'] = $cstmt->fetchAll();

    return $chain;
}
