<?php
/**
 * Email Notification Sender
 * This script can be called by other parts of the system to send email notifications
 */

require_once __DIR__ . '/../config/EmailService.php';
require_once __DIR__ . '/../config/database.php';

// Function to send ticket status notification
function sendTicketNotification($ticketId, $type = 'status_update', $additionalData = []) {
    try {
        // Get database connection
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get ticket and customer information
        $stmt = $pdo->prepare("
            SELECT 
                jo.*,
                c.customer_name,
                c.email,
                c.phone,
                c.address
            FROM job_orders jo
            LEFT JOIN customers c ON jo.customer_id = c.customer_id
            WHERE jo.job_order_id = ?
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        if (!$ticket['email']) {
            return ['success' => false, 'message' => 'Customer email not found'];
        }
        
        $emailService = new EmailService();
        
        switch ($type) {
            case 'confirmation':
                return $emailService->sendTicketConfirmation(
                    $ticket['email'],
                    $ticket['customer_name'],
                    $ticket['job_order_id'],
                    $ticket['service_type'] ?? 'Service Request',
                    $ticket['problem_description'] ?? 'Service request submitted'
                );
                
            case 'status_update':
                $message = $additionalData['message'] ?? '';
                return $emailService->sendTicketStatusUpdate(
                    $ticket['email'],
                    $ticket['customer_name'],
                    $ticket['job_order_id'],
                    $ticket['status'],
                    $message
                );
                
            case 'completion':
                $services = $additionalData['services'] ?? [];
                $totalAmount = $additionalData['total_amount'] ?? $ticket['total_amount'] ?? 0;
                return $emailService->sendCompletionNotification(
                    $ticket['email'],
                    $ticket['customer_name'],
                    $ticket['job_order_id'],
                    $totalAmount,
                    $services
                );
                
            default:
                return ['success' => false, 'message' => 'Invalid notification type'];
        }
        
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send notification: ' . $e->getMessage()];
    }
}

// If called directly via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $ticketId = $_POST['ticket_id'] ?? null;
    $type = $_POST['type'] ?? 'status_update';
    $additionalData = [];
    
    // Parse additional data
    if (isset($_POST['message'])) {
        $additionalData['message'] = $_POST['message'];
    }
    if (isset($_POST['services'])) {
        $additionalData['services'] = json_decode($_POST['services'], true);
    }
    if (isset($_POST['total_amount'])) {
        $additionalData['total_amount'] = floatval($_POST['total_amount']);
    }
    
    if (!$ticketId) {
        echo json_encode(['success' => false, 'message' => 'Ticket ID is required']);
        exit;
    }
    
    $result = sendTicketNotification($ticketId, $type, $additionalData);
    echo json_encode($result);
    exit;
}

// If called via GET (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
    echo "<h2>Email Notification Test</h2>";
    
    $ticketId = $_GET['ticket_id'] ?? null;
    $type = $_GET['type'] ?? 'status_update';
    
    if (!$ticketId) {
        echo "<p style='color: red;'>Please provide a ticket_id parameter</p>";
        echo "<p>Example: send_notification.php?test=1&ticket_id=1&type=status_update</p>";
        exit;
    }
    
    $result = sendTicketNotification($ticketId, $type);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to send email: " . $result['message'] . "</p>";
    }
    
    echo "<p><a href='../test_email.php'>← Back to Email Tests</a></p>";
}
?>