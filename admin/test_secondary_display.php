<?php
require_once '../config/database.php';

// Test script to verify secondary technician display
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Same query as in reports.php
    $query = "SELECT 
        jo.id,
        jo.order_number,
        jo.customer_name,
        jo.device_type,
        jo.issue_description,
        jo.status,
        jo.total_price,
        jo.created_at,
        jo.technician_id,
        jo.secondary_technician_id,
        t1.name as technician_name,
        t1.profile_picture as technician_profile,
        t2.name as secondary_technician_name,
        t2.profile_picture as secondary_technician_profile
    FROM job_orders jo
    LEFT JOIN technicians t1 ON jo.technician_id = t1.id
    LEFT JOIN technicians t2 ON jo.secondary_technician_id = t2.id
    WHERE jo.secondary_technician_id IS NOT NULL AND jo.secondary_technician_id != ''
    ORDER BY jo.created_at DESC
    LIMIT 5";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Orders with Secondary Technicians (Test)</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Order #</th><th>Customer</th><th>Primary Tech</th><th>Secondary Tech</th><th>Status</th></tr>";
    
    foreach ($orders as $order) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($order['order_number']) . "</td>";
        echo "<td>" . htmlspecialchars($order['customer_name']) . "</td>";
        echo "<td>" . htmlspecialchars($order['technician_name'] ?? 'None') . "</td>";
        echo "<td>";
        
        // Test the same logic as in reports.php
        if (!empty($order['secondary_technician_name'])) {
            echo htmlspecialchars($order['secondary_technician_name']);
        } else {
            echo "None";
        }
        
        echo "</td>";
        echo "<td>" . htmlspecialchars($order['status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Raw Data Debug:</h3>";
    echo "<pre>";
    print_r($orders);
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>