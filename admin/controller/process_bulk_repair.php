<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/EmailService.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: ../customer_orders.php');
    exit();
}

// Validate required fields
$required_fields = ['customer_name', 'customer_phone', 'customer_address', 'assigned_technician_id'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        header('Location: ../customer_orders.php');
        exit();
    }
}

// Validate arrays
if (empty($_POST['part_id']) || !is_array($_POST['part_id'])) {
    $_SESSION['error'] = 'At least one AC part must be selected';
    header('Location: ../customer_orders.php');
    exit();
}

if (empty($_POST['base_price']) || !is_array($_POST['base_price'])) {
    $_SESSION['error'] = 'Base prices are required for all repair orders';
    header('Location: ../customer_orders.php');
    exit();
}

if (empty($_POST['aircon_model_id']) || !is_array($_POST['aircon_model_id'])) {
    $_SESSION['error'] = 'Aircon models are required for all repair orders';
    header('Location: ../customer_orders.php');
    exit();
}

// Validate assigned technician
if (empty($_POST['assigned_technician_id'])) {
    $_SESSION['error'] = 'Assigned technician is required';
    header('Location: ../customer_orders.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get form data
    $customer_id = (int)$_POST['customer_id'];
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_address = trim($_POST['customer_address']);
    $assigned_technician_id = (int)$_POST['assigned_technician_id'];
    $discount = floatval($_POST['discount'] ?? 0);
    $additional_fee = floatval($_POST['additional_fee'] ?? 0);
    $service_type = 'repair';
    
    // Get customer email for notifications
    $stmt = $pdo->prepare("SELECT email FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer_data = $stmt->fetch();
    $customer_email = $customer_data ? $customer_data['email'] : null;
    
    $part_ids = $_POST['part_id'];
    $base_prices = $_POST['base_price'];
    $aircon_model_ids = $_POST['aircon_model_id'];
    
    // Validate arrays have same length
    if (count($part_ids) !== count($base_prices) || count($part_ids) !== count($aircon_model_ids)) {
        throw new Exception('Mismatch in repair order data');
    }
    
    $order_count = count($part_ids);
    $total_final_price = 0;
    $created_orders = []; // Track created orders for email notifications
    
    // Prepare the insert statement
    $stmt = $pdo->prepare("
        INSERT INTO job_orders (
            job_order_number, customer_id, customer_name, customer_phone, customer_address, 
            service_type, aircon_model_id, part_id, assigned_technician_id, secondary_technician_id,
            base_price, additional_fee, discount, price, status
        ) VALUES (
            :job_order_number, :customer_id, :customer_name, :customer_phone, :customer_address,
            :service_type, :aircon_model_id, :part_id, :assigned_technician_id, :secondary_technician_id,
            :base_price, :additional_fee, :discount, :price, 'pending'
        )
    ");
    
    // Insert each repair order
    for ($i = 0; $i < $order_count; $i++) {
        $part_id = (int)$part_ids[$i];
        $base_price = floatval($base_prices[$i]);
        $aircon_model_id = (int)$aircon_model_ids[$i];
        
        // Generate unique job order number for each repair order
        $job_order_number = 'JO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Calculate individual order total (discount and additional fee are applied proportionally)
        $order_subtotal = $base_price;
        $total_base_price = array_sum(array_map('floatval', $base_prices));
        $proportional_discount = ($order_subtotal / $total_base_price) * $discount;
        $proportional_additional_fee = ($order_subtotal / $total_base_price) * $additional_fee;
        
        $order_total = $order_subtotal + $proportional_additional_fee - $proportional_discount;
        $total_final_price += $order_total;
        
        // Validate part exists
        $part_check = $pdo->prepare("SELECT id FROM ac_parts WHERE id = ?");
        $part_check->execute([$part_id]);
        if (!$part_check->fetch()) {
            throw new Exception("Invalid AC part selected for order " . ($i + 1));
        }
        
        // Insert the order
        $stmt->execute([
            ':job_order_number' => $job_order_number,
            ':customer_id' => $customer_id,
            ':customer_name' => $customer_name,
            ':customer_phone' => $customer_phone,
            ':customer_address' => $customer_address,
            ':service_type' => $service_type,
            ':aircon_model_id' => $aircon_model_id,
            ':part_id' => $part_id,
            ':assigned_technician_id' => $assigned_technician_id,
            ':secondary_technician_id' => !empty($_POST['secondary_technician_id']) ? (int)$_POST['secondary_technician_id'] : null,
            ':base_price' => $base_price,
            ':additional_fee' => $proportional_additional_fee,
            ':discount' => $proportional_discount,
            ':price' => $order_total
        ]);
        
        // Track created order for email notifications
        $created_orders[] = [
            'job_order_number' => $job_order_number,
            'price' => $order_total
        ];
    }
    
    $pdo->commit();
    
    // Send email notification if customer has email
    if (!empty($customer_email)) {
        try {
            $emailService = new EmailService();
            
            // Send confirmation email for each repair order
            foreach ($created_orders as $order) {
                $emailService->sendTicketConfirmation(
                    $customer_email,
                    $customer_name,
                    $order['job_order_number'],
                    $service_type,
                    'Your repair service has been scheduled and will be processed soon.',
                    $order['price']
                );
            }
        } catch (Exception $e) {
            // Log email error but don't fail the order creation
            error_log("Email notification failed: " . $e->getMessage());
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Successfully created $order_count repair order(s) for $customer_name. Total: ₱" . number_format($total_final_price, 2),
        'order_count' => $order_count,
        'total_price' => $total_final_price
    ]);
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error creating bulk repair orders: ' . $e->getMessage()
    ]);
    exit();
}
?>