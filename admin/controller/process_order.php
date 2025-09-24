<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Generate unique job order number (format: JO-YYYYMMDD-XXXX)
        do {
            $job_order_number = 'JO-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Check if this job order number already exists
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM job_orders WHERE job_order_number = ?");
            $check_stmt->execute([$job_order_number]);
            $exists = $check_stmt->fetchColumn() > 0;
        } while ($exists);

        // CUSTOMER HANDLING
        $customer_name = trim($_POST['customer_name']);
        $customer_phone = trim($_POST['customer_phone']);
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_address = trim($_POST['customer_address']);
        
        // VALIDATION - Check if assigned technician is provided
        if (empty($_POST['assigned_technician_id'])) {
            $_SESSION['error'] = "Assigned technician is required for creating job orders.";
            
            // For survey orders, always redirect to customer_orders.php for better interface
            if (!empty($_POST['service_type']) && $_POST['service_type'] === 'survey') {
                // Need to get or create customer_id first for survey orders
                $customer_name = trim($_POST['customer_name']);
                $customer_phone = trim($_POST['customer_phone']);
                $customer_email = trim($_POST['customer_email'] ?? '');
                $customer_address = trim($_POST['customer_address']);
                
                // Try to find existing customer by name and phone
                $stmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND phone = ? LIMIT 1");
                $stmt->execute([$customer_name, $customer_phone]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($customer) {
                    $customer_id = $customer['id'];
                } else {
                    // Insert new customer
                    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$customer_name, $customer_phone, $customer_email, $customer_address]);
                    $customer_id = $pdo->lastInsertId();
                }
                
                header('Location: ../customer_orders.php?customer_id=' . $customer_id);
                exit();
            }
            
            // For other service types, use existing logic
            if (!empty($_POST['customer_id'])) {
                header('Location: ../customer_orders.php?customer_id=' . (int)$_POST['customer_id']);
                exit();
            }
            header('Location: ../orders.php');
            exit();
        }
        
        // If customer_id is provided (from customer_orders.php), use it
        if (!empty($_POST['customer_id'])) {
            $customer_id = (int)$_POST['customer_id'];
            
            // Update customer info
            $stmt = $pdo->prepare("UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?");
            $stmt->execute([$customer_name, $customer_phone, $customer_email, $customer_address, $customer_id]);
        } else {
            // Try to find existing customer by name and phone
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE name = ? AND phone = ? LIMIT 1");
            $stmt->execute([$customer_name, $customer_phone]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                $customer_id = $customer['id'];
                // Update customer address and email if they have changed
                $stmt = $pdo->prepare("UPDATE customers SET address = ?, email = ? WHERE id = ?");
                $stmt->execute([$customer_address, $customer_email, $customer_id]);
            } else {
                // Insert new customer
                $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$customer_name, $customer_phone, $customer_email, $customer_address]);
                $customer_id = $pdo->lastInsertId();
            }
        }

        // Prepare the SQL statement
        $stmt = $pdo->prepare("
            INSERT INTO job_orders (
                job_order_number,
                customer_name,
                customer_address,
                customer_phone,
                customer_email,
                service_type,
                aircon_model_id,
                part_id,
                assigned_technician_id,
                secondary_technician_id,
                status,
                price,
                base_price,
                additional_fee,
                discount,
                created_by,
                customer_id
            ) VALUES (
                :job_order_number,
                :customer_name,
                :customer_address,
                :customer_phone,
                :customer_email,
                :service_type,
                :aircon_model_id,
                :part_id,
                :assigned_technician_id,
                :secondary_technician_id,
                :status,
                :price,
                :base_price,
                :additional_fee,
                :discount,
                :created_by,
                :customer_id
            )
        ");

        // Handle aircon_model_id vs part_id based on service_type
        $service_type = $_POST['service_type'];
        $aircon_model_id = null;
        $part_id = null;
        
        if ($service_type === 'repair') {
            // For repair orders, the aircon_model_id field contains the part ID
            $part_id = $_POST['aircon_model_id'] ?: null;
        } else {
            // For installation/survey orders, use aircon_model_id normally
            $aircon_model_id = $_POST['aircon_model_id'] ?: null;
        }

        // Bind parameters using bindValue instead of bindParam
        $stmt->bindValue(':job_order_number', $job_order_number);
        $stmt->bindValue(':customer_name', $customer_name);
        $stmt->bindValue(':customer_address', $customer_address);
        $stmt->bindValue(':customer_phone', $customer_phone);
        $stmt->bindValue(':customer_email', $customer_email);
        $stmt->bindValue(':service_type', $service_type);
        $stmt->bindValue(':aircon_model_id', $aircon_model_id);
        $stmt->bindValue(':part_id', $part_id);
        $stmt->bindValue(':assigned_technician_id', (int)$_POST['assigned_technician_id']);
        $stmt->bindValue(':secondary_technician_id', !empty($_POST['secondary_technician_id']) ? (int)$_POST['secondary_technician_id'] : null);
        $stmt->bindValue(':status', 'pending');
        $stmt->bindValue(':price', $_POST['price']);
        $stmt->bindValue(':base_price', $_POST['base_price'] ?? 0);
        $stmt->bindValue(':additional_fee', $_POST['additional_fee'] ?? 0);
        $stmt->bindValue(':discount', $_POST['discount'] ?? 0);
        $stmt->bindValue(':created_by', $_SESSION['user_id']);
        $stmt->bindValue(':customer_id', $customer_id);

        // Execute the statement
        $stmt->execute();

        // Send email notification if customer has email
        if (!empty($customer_email)) {
            try {
                require_once '../../config/EmailService.php';
                $emailService = new EmailService();
                
                // Prepare order details for email
                $orderDetails = [
                    'job_order_number' => $job_order_number,
                    'service_type' => $service_type,
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone,
                    'customer_address' => $customer_address,
                    'price' => $_POST['price']
                ];
                
                // Send confirmation email
                $emailResult = $emailService->sendTicketConfirmation(
                    $customer_email,
                    $customer_name,
                    $job_order_number,
                    ucfirst($service_type),
                    "Service: " . ucfirst($service_type) . " - Price: ₱" . number_format($_POST['price'], 2)
                );
                
                if ($emailResult['success']) {
                    $_SESSION['success'] = "Job order #$job_order_number has been created successfully. Confirmation email sent to customer.";
                } else {
                    $_SESSION['success'] = "Job order #$job_order_number has been created successfully. Note: Email notification failed to send.";
                }
            } catch (Exception $e) {
                $_SESSION['success'] = "Job order #$job_order_number has been created successfully. Note: Email notification failed to send.";
            }
        } else {
            $_SESSION['success'] = "Job order #$job_order_number has been created successfully.";
        }
        
        // For survey orders, always redirect to customer_orders.php for better interface
        if ($service_type === 'survey') {
            header('Location: ../customer_orders.php?customer_id=' . $customer_id);
            exit();
        }
        
        // For other service types, use existing logic
        if (!empty($_POST['customer_id'])) {
            header('Location: ../customer_orders.php?customer_id=' . (int)$_POST['customer_id']);
            exit();
        }
        header('Location: ../orders.php');
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating job order: " . $e->getMessage();
        
        // For survey orders, always redirect to customer_orders.php for better interface
        if (!empty($_POST['service_type']) && $_POST['service_type'] === 'survey') {
            header('Location: ../customer_orders.php?customer_id=' . $customer_id);
            exit();
        }
        
        // For other service types, use existing logic
        if (!empty($_POST['customer_id'])) {
            header('Location: ../customer_orders.php?customer_id=' . (int)$_POST['customer_id']);
            exit();
        }
        header('Location: ../orders.php');
        exit();
    }
} else {
    // If not POST request, redirect to orders page
    header('Location: ../orders.php');
    exit();
}