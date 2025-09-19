<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/EmailService.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if order ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
    header('Location: ' . $redirect);
    exit();
}

$allowed_statuses = ['in_progress', 'completed', 'cancelled'];
if (!in_array($_GET['status'], $allowed_statuses)) {
    $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
    header('Location: ' . $redirect);
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the order exists and belongs to the technician (if technician)
    $stmt = $pdo->prepare("
        SELECT jo.id, jo.status, jo.job_order_number, jo.service_type, jo.price,
               c.name as customer_name, c.email as customer_email,
               cs.service_description as cleaning_description,
               am.model_name, am.brand,
               ap.part_name, ap.part_code
        FROM job_orders jo
        LEFT JOIN customers c ON jo.customer_id = c.id
        LEFT JOIN cleaning_services cs ON jo.cleaning_service_id = cs.id AND jo.service_type = 'cleaning'
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id AND jo.service_type IN ('installation', 'cleaning')
        LEFT JOIN ac_parts ap ON jo.part_id = ap.id AND jo.service_type = 'repair'
        WHERE jo.id = ? 
        " . ($_SESSION['role'] === 'technician' ? "AND (jo.assigned_technician_id = ? OR jo.secondary_technician_id = ?)" : "")
    );
    
    if ($_SESSION['role'] === 'technician') {
        $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    } else {
        $stmt->execute([$_GET['id']]);
    }
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
        header('Location: ' . $redirect);
        exit();
    }

    // Validate status transition
    if ($_GET['status'] === 'completed' && $order['status'] !== 'in_progress') {
        $_SESSION['error'] = "Cannot mark order as completed. It must be in progress first.";
        $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
        header('Location: ' . $redirect);
        exit();
    }

    if ($_GET['status'] === 'in_progress' && $order['status'] !== 'pending') {
        $_SESSION['error'] = "Cannot start work. Order must be pending first.";
        $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
        header('Location: ' . $redirect);
        exit();
    }

    // Update the order status
    $stmt = $pdo->prepare("
        UPDATE job_orders 
        SET status = ?, 
            " . ($_GET['status'] === 'completed' ? "completed_at = NOW()," : "") . "
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$_GET['status'], $_GET['id']]);

    // Send email notification if customer has email
    if (!empty($order['customer_email'])) {
        try {
            $emailService = new EmailService();
            
            // Create status-specific message
            $status_messages = [
                'in_progress' => 'Your service request is now being processed by our technician.',
                'completed' => 'Your service request has been completed successfully. Thank you for choosing our services!',
                'cancelled' => 'Your service request has been cancelled. If you have any questions, please contact us.'
            ];
            
            $message = $status_messages[$_GET['status']] ?? 'Your service request status has been updated.';
            
            // Build description based on service type
            $description = '';
            switch($order['service_type']) {
                case 'cleaning':
                    $description = $order['cleaning_description'] ?? 'Cleaning service';
                    break;
                case 'installation':
                case 'repair':
                    if ($order['service_type'] === 'installation') {
                        $description = "Installation of " . ($order['brand'] ? $order['brand'] . ' ' : '') . ($order['model_name'] ?? 'Air Conditioning Unit');
                    } else {
                        $description = "Repair service" . ($order['part_name'] ? " - " . $order['part_name'] . " (" . $order['part_code'] . ")" : '');
                    }
                    break;
                case 'survey':
                    $description = 'Site survey for air conditioning service';
                    break;
                default:
                    $description = ucfirst($order['service_type']) . ' service';
            }
            
            $emailService->sendTicketStatusUpdate(
                $order['customer_email'],
                $order['customer_name'],
                $order['job_order_number'],
                $_GET['status'],
                $message,
                $description
            );
        } catch (Exception $e) {
            // Log email error but don't fail the status update
            error_log("Email notification failed for order " . $order['job_order_number'] . ": " . $e->getMessage());
        }
    }

    $_SESSION['success'] = "Order status has been updated successfully.";
    $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
    header('Location: ' . $redirect);
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    $redirect = (isset($_GET['customer_id'])) ? '../customer_orders.php?customer_id=' . (int)$_GET['customer_id'] : (($_SESSION['role'] === 'admin') ? '../orders.php' : '../technician/orders.php');
    header('Location: ' . $redirect);
    exit();
}