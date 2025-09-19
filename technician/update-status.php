<?php
session_start();
require_once '../config/database.php';
require_once '../config/EmailService.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

// Check if order ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Invalid request.";
    header('Location: orders.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // First check if the order belongs to this technician (primary or secondary) and get customer info
    $stmt = $pdo->prepare("
        SELECT jo.id, jo.status, jo.job_order_number, jo.service_type, jo.price,
               c.name as customer_name, c.email as customer_email,
               cs.service_description as cleaning_description,
               am.model_name, am.brand,
               ap.part_name, ap.part_code,
               CASE 
                   WHEN jo.assigned_technician_id = ? THEN 'primary'
                   WHEN jo.secondary_technician_id = ? THEN 'secondary'
                   ELSE 'none'
               END as technician_role
        FROM job_orders jo
        LEFT JOIN customers c ON jo.customer_id = c.id
        LEFT JOIN cleaning_services cs ON jo.cleaning_service_id = cs.id AND jo.service_type = 'cleaning'
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id AND jo.service_type IN ('installation', 'cleaning')
        LEFT JOIN ac_parts ap ON jo.part_id = ap.id AND jo.service_type = 'repair'
        WHERE jo.id = ? AND (jo.assigned_technician_id = ? OR jo.secondary_technician_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order || $order['technician_role'] === 'none') {
        $_SESSION['error'] = "Order not found or unauthorized.";
        header('Location: orders.php');
        exit();
    }

    // Validate status
    $allowed_statuses = ['in_progress', 'completed', 'cancelled'];
    if (!in_array($_GET['status'], $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status.";
        header('Location: orders.php');
        exit();
    }

    // Update the order status
    $stmt = $pdo->prepare("
        UPDATE job_orders 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP,
            completed_at = CASE 
                WHEN ? IN ('completed', 'cancelled') THEN CURRENT_TIMESTAMP 
                ELSE completed_at 
            END
        WHERE id = ? AND (assigned_technician_id = ? OR secondary_technician_id = ?)
    ");
    $stmt->execute([$_GET['status'], $_GET['status'], $_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);

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

    // Set success message based on status
    $status_messages = [
        'in_progress' => "Order has been started.",
        'completed' => "Order has been marked as completed.",
        'cancelled' => "Order has been cancelled and moved to archive."
    ];
    $_SESSION['success'] = $status_messages[$_GET['status']];

    // Redirect back to orders page
    header('Location: orders.php');
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header('Location: orders.php');
    exit();
}