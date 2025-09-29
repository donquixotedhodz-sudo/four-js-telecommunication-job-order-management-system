<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get filter parameters from the request
$search_customer = $_GET['search_customer'] ?? '';
// Remove other filters

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get admin details
    $stmt = $pdo->prepare("SELECT name, profile_picture FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all ongoing job orders (pending and in_progress)
    $sql = "
        SELECT 
            jo.*,
            CASE 
                WHEN jo.service_type = 'repair' THEN COALESCE(ap.part_name, 'Not Specified')
                ELSE COALESCE(am.model_name, 'Not Specified')
            END as model_name,
            COALESCE(jo.service_type, 'Not Specified') as service_type,
            t.name as technician_name,
            t.profile_picture as technician_profile,
            t2.name as secondary_technician_name,
            t2.profile_picture as secondary_technician_profile
        FROM job_orders jo 
        LEFT JOIN aircon_models am ON jo.aircon_model_id = am.id 
        LEFT JOIN ac_parts ap ON jo.part_id = ap.id
        LEFT JOIN technicians t ON jo.assigned_technician_id = t.id
        LEFT JOIN technicians t2 ON jo.secondary_technician_id = t2.id
        WHERE jo.status IN ('pending', 'in_progress')
    ";

    $params = [];

    if (!empty($search_customer)) {
        $sql .= " AND jo.customer_name LIKE ?";
        $params[] = '%' . $search_customer . '%';
    }

    $sql .= "
        ORDER BY 
            CASE 
                WHEN jo.status = 'pending' THEN 1
                WHEN jo.status = 'in_progress' THEN 2
            END,
            jo.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get technicians for dropdown
    $stmt = $pdo->query("SELECT id, name FROM technicians");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get aircon models for dropdown
    $stmt = $pdo->query("SELECT id, model_name, brand, hp, price FROM aircon_models");
    $airconModels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get AC parts for dropdown (for repair orders)
    $stmt = $pdo->query("SELECT id, part_name, part_code, part_category, unit_price FROM ac_parts ORDER BY part_name");
    $acParts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get cleaning services for dropdown
    $stmt = $pdo->query("SELECT id, service_name, service_description, base_price FROM cleaning_services ORDER BY service_name");
    $cleaningServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get aircon HP for dropdown
    $stmt = $pdo->query("SELECT id, hp, price FROM aircon_hp ORDER BY hp ASC");
    $airconHPs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all customers for dropdown
    $stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name ASC");
    $allCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all customers with their active order count (excluding completed and cancelled)
    $sql = "
        SELECT 
            c.id as customer_id,
            c.name as customer_name,
            c.phone as customer_phone,
            c.address as customer_address,
            COUNT(CASE WHEN jo.status NOT IN ('completed', 'cancelled') THEN jo.id END) as order_count
        FROM customers c
        LEFT JOIN job_orders jo ON jo.customer_id = c.id
    ";
    $params = [];
    if (!empty($search_customer)) {
        $sql .= " WHERE c.id = ? ";
        $params[] = $search_customer;
    }
    $sql .= " GROUP BY c.id, c.name, c.phone, c.address
        ORDER BY c.name ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customerOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
require_once 'includes/header.php';
?>
<body></body>
 <div class="wrapper">
        <?php
        // Include sidebar
        require_once 'includes/sidebar.php';
        ?>


        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= !empty($admin['profile_picture']) ? '../' . htmlspecialchars($admin['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($admin['name'] ?: 'Admin') . '&background=1a237e&color=fff' ?>" 
                                     alt="Admin" 
                                     class="rounded-circle me-2" 
                                     width="32" 
                                     height="32"
                                     style="object-fit: cover; border: 2px solid #4A90E2;">
                                <span class="me-3">Welcome, <?= htmlspecialchars($admin['name'] ?: 'Admin') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="min-width: 200px;">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2" href="profile.php">
                                        <i class="fas fa-user me-2 text-primary"></i>
                                        <span>Profile</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

<div class="container mt-4">
    <h3>Orders</h3>
    
    <div class="mb-4">
        <p class="text-muted mb-0">Manage and track all job orders</p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Job Orders</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderTypeModal">
                     <i class="fas fa-plus me-2"></i>Add Job Order
                 </button>
            </div>

            <!-- Search and Filter Form -->
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search_customer" class="form-label">Select Customer</label>
                    <select class="form-select" id="search_customer" name="search_customer">
                        <option value="">All Customers</option>
                        <?php foreach ($allCustomers as $customer): ?>
                        <option value="<?= $customer['id'] ?>" <?= $search_customer == $customer['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($customer['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="orders.php" class="btn btn-outline-secondary w-100">Clear Filter</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="table-wrapper" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Order Count</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerOrders as $customer): ?>
                        <tr>
                            <td>
                                <span class="fw-medium">
                                    <?= htmlspecialchars($customer['customer_name']) ?>
                                </span>
                            </td>
                            <td><i class="fas fa-phone text-primary me-1"></i><?= htmlspecialchars($customer['customer_phone']) ?></td>
                            <td><i class="fas fa-map-marker-alt text-danger me-1"></i><?= htmlspecialchars($customer['customer_address']) ?></td>
                            <td><?= (int)$customer['order_count'] ?></td>
                            <td class="text-center">
                                <a href="customer_orders.php?customer_id=<?= (int)$customer['customer_id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-list"></i> View Orders
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Service Type Selection Modal -->
<div class="modal fade" id="orderTypeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i>
                    Select Service Type
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 order-type-card border-0 shadow-sm" data-service-type="survey">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-search fa-3x text-primary"></i>
                                </div>
                                <h5 class="card-title mb-3">Survey</h5>
                                <p class="card-text text-muted">Site inspection and assessment for aircon installation or repair</p>
                                <div class="mt-auto">
                                    <span class="badge bg-primary-subtle text-primary">Assessment</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 order-type-card border-0 shadow-sm" data-service-type="cleaning">
                            <div class="card-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-spray-can fa-3x text-info"></i>
                                </div>
                                <h5 class="card-title mb-3">Cleaning</h5>
                                <p class="card-text text-muted">Professional aircon cleaning and maintenance services</p>
                                <div class="mt-auto">
                                    <span class="badge bg-info-subtle text-info">Maintenance</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Cleaning Order Modal -->
<div class="modal fade" id="cleaningOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Multiple Cleaning Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cleaningOrderForm" action="controller/process_bulk_cleaning.php" method="POST">
                <div class="modal-body">
                    <div class="alert alert-info bulk-order-alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bulk Cleaning Orders:</strong> Create multiple cleaning service orders for the same customer.
                    </div>
                    <div class="row g-3">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" name="customer_name" id="cleaning_customer_name_autocomplete" autocomplete="off" required>
                            <div id="cleaning_customer_suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="customer_phone" id="cleaning_customer_phone" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="customer_email" id="cleaning_customer_email" placeholder="customer@example.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="customer_address" id="cleaning_customer_address" rows="2" required></textarea>
                        </div>

                        <!-- Order Details -->
                        <div class="col-md-6">
                            <label class="form-label">Assign Technician <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_technician_id" required>
                                <option value="">Select Technician</option>
                                <?php foreach ($technicians as $tech): ?>
                                <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secondary Technician <small class="text-muted">(Optional)</small></label>
                            <select class="form-select" name="secondary_technician_id">
                                <option value="">Select Secondary Technician</option>
                                <?php foreach ($technicians as $tech): ?>
                                <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cleaning Orders Container -->
                        <div class="col-12">
                            <label class="form-label">Cleaning Orders</label>
                            <div id="cleaning-orders-container">
                                <!-- Cleaning Order 1 -->
                                <div class="cleaning-order-item border rounded p-3 mb-3" data-order="1">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Aircon HP <span class="text-danger">*</span></label>
                                            <select class="form-select cleaning-aircon-hp-select" name="aircon_hp_id[]" required>
                                                <option value="">Select HP</option>
                                                <?php foreach ($airconHPs as $hp): ?>
                                                <option value="<?= $hp['id'] ?>" data-price="<?= $hp['price'] ?>"><?= htmlspecialchars($hp['hp']) ?> HP (₱<?= number_format($hp['price'], 2) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Aircon Model <small class="text-muted">(Optional)</small></label>
                                            <select class="form-select cleaning-aircon-model-select" name="aircon_model_id[]">
                                                <option value="">Select Model</option>
                                                <?php foreach ($airconModels as $model): ?>
                                                <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name'] . ' (' . $model['hp'] . ' HP)') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Base Price (₱)</label>
                                            <input type="number" class="form-control cleaning-base-price-input" name="base_price[]" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-info btn-sm" id="addCleaningOrderBtn">
                                <i class="fas fa-plus me-2"></i>Add Another Cleaning Order
                            </button>
                        </div>

                        <!-- Pricing Summary -->
                        <div class="col-md-4">
                            <label class="form-label">Total Additional Fee (₱)</label>
                            <input type="number" class="form-control" name="total_additional_fee" id="cleaning_bulk_additional_fee" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Discount (₱)</label>
                            <input type="number" class="form-control" name="discount" id="cleaning_bulk_discount" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Price (₱)</label>
                            <input type="number" class="form-control" name="price" id="cleaning_bulk_total_price" readonly>
                        </div>
                    </div>
                    
                    <!-- Price Summary -->
                    <div class="price-summary">
                        <h6><i class="fas fa-calculator me-2"></i>Price Summary</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-muted">Total Base Price:</small>
                                <div class="fw-bold" id="cleaning_summary_base_price">₱0.00</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Total Additional Fees:</small>
                                <div class="fw-bold" id="cleaning_summary_additional_fee">₱0.00</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted">Total Discount:</small>
                                <div class="fw-bold text-danger" id="cleaning_summary_discount">₱0.00</div>
                            </div>
                            <div class="col-md-12 mt-2">
                                <small class="text-muted">Total Price:</small>
                                <div class="fw-bold text-success fs-5" id="cleaning_summary_total">₱0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Create Cleaning Orders</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Add Job Order Modal -->
    <div class="modal fade" id="addJobOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Survey Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="surveyOrderForm" action="controller/process_order.php" method="POST">
                    <input type="hidden" name="service_type" id="selected_service_type" required>
                    <div class="modal-body">
                        <div class="alert alert-info bulk-order-alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Survey Order:</strong> Create a survey order to assess customer requirements and provide estimates.
                        </div>
                        <div class="row g-3">
                            <!-- Customer Information -->
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" id="customer_name_autocomplete" autocomplete="off" required>
                                <div id="customer_suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" id="customer_phone" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="customer_email" id="customer_email" placeholder="customer@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="customer_address" id="customer_address" rows="2" required></textarea>
                            </div>

                            <!-- Service Information -->
                            <div class="col-md-6">
                                <label class="form-label">Service Type</label>
                                <input type="text" class="form-control" id="display_service_type" readonly>
                            </div>
                            <div class="col-md-6" id="aircon_model_section">
                                <label class="form-label" id="aircon_model_label">Aircon Model <small class="text-muted">(Optional for Survey)</small></label>
                                <select class="form-select" name="aircon_model_id" id="aircon_model_select">
                                    <option value="">Select Model</option>
                                    <?php foreach ($airconModels as $model): ?>
                                    <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name'] . ' (' . $model['hp'] . ' HP)') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Assignment Information -->
                            <div class="col-md-6">
                                <label class="form-label">Assign Technician <span class="text-danger">*</span></label>
                                <select class="form-select" name="assigned_technician_id" required>
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary Technician <small class="text-muted">(Optional)</small></label>
                                <select class="form-select" name="secondary_technician_id">
                                    <option value="">Select Secondary Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                                <!-- Removed Due Date field here -->

                            <!-- Price Section -->
                            <div class="col-md-4">
                                <label class="form-label">Base Price (₱)</label>
                                <input type="number" class="form-control" name="base_price" id="base_price" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Additional Fee (₱)</label>
                                <input type="number" class="form-control" name="additional_fee" id="additional_fee" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Discount (₱)</label>
                                <input type="number" class="form-control" name="discount" id="discount" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Total Price (₱)</label>
                                <input type="number" class="form-control" name="price" id="total_price" readonly>
                            </div>
                        </div>
                        
                        <!-- Price Summary -->
                        <div class="price-summary">
                            <h6><i class="fas fa-calculator me-2"></i>Price Summary</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <small class="text-muted">Base Price:</small>
                                    <div class="fw-bold" id="survey_summary_base_price">₱0.00</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Additional Fees:</small>
                                    <div class="fw-bold" id="survey_summary_additional_fee">₱0.00</div>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Discount:</small>
                                    <div class="fw-bold text-danger" id="survey_summary_discount">₱0.00</div>
                                </div>
                                <div class="col-md-12 mt-2">
                                    <small class="text-muted">Total Price:</small>
                                    <div class="fw-bold text-success fs-5" id="survey_summary_total">₱0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Create Survey Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editOrderForm" action="controller/update_order.php" method="POST">
                <div class="modal-header">
                        <h5 class="modal-title">Edit Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="edit_order_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" id="edit_customer_name" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="customer_phone" id="edit_customer_phone" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="customer_address" id="edit_customer_address" rows="2" readonly></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Service Type</label>
                                <input type="text" class="form-control" name="service_type" id="edit_service_type" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" id="edit_aircon_model_label">Aircon Model</label>
                                <select class="form-select" name="aircon_model_id" id="edit_aircon_model_id">
                                    <option value="">Select Model</option>
                                    <?php foreach ($airconModels as $model): ?>
                                    <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name'] . ' (' . $model['hp'] . ' HP)') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assign Technician <span class="text-danger">*</span></label>
                                <select class="form-select" name="assigned_technician_id" id="edit_assigned_technician_id" required>
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Secondary Technician <small class="text-muted">(Optional)</small></label>
                                <select class="form-select" name="secondary_technician_id" id="edit_secondary_technician_id">
                                    <option value="">Select Secondary Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                    <option value="<?= $tech['id'] ?>"><?= htmlspecialchars($tech['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Base Price (₱)</label>
                                <input type="number" class="form-control" name="base_price" id="edit_base_price" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Additional Fee (₱)</label>
                                <input type="number" class="form-control" name="additional_fee" id="edit_additional_fee" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Discount (₱)</label>
                                <input type="number" class="form-control" name="discount" id="edit_discount" min="0" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Total Price (₱)</label>
                                <input type="number" class="form-control" name="price" id="edit_total_price" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="edit_status">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Order Modal -->
    <div class="modal fade" id="completeOrderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Job Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="controller/complete_order.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="complete_order_id">
                        <p>Are you sure you want to mark this job order as completed?</p>
                        <div class="alert alert-info">
                            <strong>Order #:</strong> <span id="complete_order_number"></span><br>
                            <strong>Customer:</strong> <span id="complete_customer_name"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Mark as Completed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <!-- <script src="../js/dashboard.js"></script> -->
    <style>
        
    </style>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Service type selection and modal handling
        document.addEventListener('DOMContentLoaded', function() {
            const orderTypeCards = document.querySelectorAll('.order-type-card');
            const orderTypeModal = document.getElementById('orderTypeModal');
            const addJobOrderModal = document.getElementById('addJobOrderModal');
            const cleaningOrderModal = document.getElementById('cleaningOrderModal');

            // Debug: Check if elements are found
            console.log('Order type cards found:', orderTypeCards.length);
            console.log('Order type modal:', orderTypeModal);
            console.log('Add job order modal:', addJobOrderModal);
            console.log('Cleaning order modal:', cleaningOrderModal);

            orderTypeCards.forEach(card => {
                card.addEventListener('click', function() {
                    const serviceType = this.getAttribute('data-service-type');
                    console.log('Service type clicked:', serviceType);
                    
                    // Close the order type modal
                    const orderTypeModalInstance = bootstrap.Modal.getInstance(orderTypeModal);
                    if (orderTypeModalInstance) {
                        orderTypeModalInstance.hide();
                    }

                    // Add delay to ensure the first modal is fully closed before opening the next one
                    setTimeout(() => {
                        if (serviceType === 'survey') {
                            console.log('Opening survey modal...');
                            // Set service type for survey
                            const selectedServiceType = document.getElementById('selected_service_type');
                            const displayServiceType = document.getElementById('display_service_type');
                            
                            if (selectedServiceType) selectedServiceType.value = 'survey';
                            if (displayServiceType) displayServiceType.value = 'Survey';
                            
                            // Set default pricing for survey
                            const basePriceInput = document.getElementById('base_price');
                            const totalPriceInput = document.getElementById('total_price');
                            if (basePriceInput) basePriceInput.value = '500.00'; // Default survey fee
                            if (totalPriceInput) totalPriceInput.value = '500.00';
                            
                            // Show survey order modal
                            if (addJobOrderModal) {
                                const addJobOrderModalInstance = new bootstrap.Modal(addJobOrderModal);
                                addJobOrderModalInstance.show();
                            } else {
                                console.error('addJobOrderModal not found');
                            }
                        } else if (serviceType === 'cleaning') {
                            console.log('Opening cleaning modal...');
                            // Show cleaning order modal
                            if (cleaningOrderModal) {
                                const cleaningOrderModalInstance = new bootstrap.Modal(cleaningOrderModal);
                                cleaningOrderModalInstance.show();
                            } else {
                                console.error('cleaningOrderModal not found');
                            }
                        }
                    }, 300); // 300ms delay to ensure smooth transition
                });
            });

            // Add hover effect to order type cards
            orderTypeCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.cursor = 'pointer';
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'transform 0.3s ease';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                });
            });

            // Bulk cleaning order functionality
            let cleaningOrderCounter = 1;
            document.getElementById('addCleaningOrderBtn').addEventListener('click', function() {
                cleaningOrderCounter++;
                const cleaningOrdersContainer = document.getElementById('cleaning-orders-container');
                const newCleaningOrder = document.createElement('div');
                newCleaningOrder.className = 'cleaning-order-item border rounded p-3 mb-3';
                newCleaningOrder.setAttribute('data-order', cleaningOrderCounter);
                
                newCleaningOrder.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Aircon HP <span class="text-danger">*</span></label>
                            <select class="form-select cleaning-aircon-hp-select" name="aircon_hp_id[]" required>
                                <option value="">Select HP</option>
                                <?php foreach ($airconHPs as $hp): ?>
                                <option value="<?= $hp['id'] ?>" data-price="<?= $hp['price'] ?>"><?= htmlspecialchars($hp['hp']) ?> HP (₱<?= number_format($hp['price'], 2) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Aircon Model <small class="text-muted">(Optional)</small></label>
                            <select class="form-select cleaning-aircon-model-select" name="aircon_model_id[]">
                                <option value="">Select Model</option>
                                <?php foreach ($airconModels as $model): ?>
                                <option value="<?= $model['id'] ?>"><?= htmlspecialchars($model['brand'] . ' - ' . $model['model_name'] . ' (' . $model['hp'] . ' HP)') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Base Price (₱)</label>
                            <input type="number" class="form-control cleaning-base-price-input" name="base_price[]" readonly>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-cleaning-order-btn">
                        <i class="fas fa-trash me-1"></i>Remove Order
                    </button>
                `;
                
                cleaningOrdersContainer.appendChild(newCleaningOrder);
                
                // Add event listeners to new cleaning order
                const newCleaningHPSelect = newCleaningOrder.querySelector('.cleaning-aircon-hp-select');
                const removeCleaningBtn = newCleaningOrder.querySelector('.remove-cleaning-order-btn');
                
                newCleaningHPSelect.addEventListener('change', function() {
                    updateCleaningOrderPrice(newCleaningOrder);
                });
                
                removeCleaningBtn.addEventListener('click', function() {
                    newCleaningOrder.remove();
                    calculateCleaningBulkTotal();
                });
            });

            // Bulk cleaning order price calculation
            function calculateCleaningBulkTotal() {
                const cleaningBasePriceInputs = document.querySelectorAll('.cleaning-base-price-input');
                const cleaningAdditionalFeeInput = document.getElementById('cleaning_bulk_additional_fee');
                const cleaningDiscountInput = document.getElementById('cleaning_bulk_discount');
                const cleaningTotalPriceInput = document.getElementById('cleaning_bulk_total_price');
                
                let totalBasePrice = 0;
                
                cleaningBasePriceInputs.forEach(input => {
                    totalBasePrice += parseFloat(input.value) || 0;
                });
                
                const totalAdditionalFee = parseFloat(cleaningAdditionalFeeInput.value) || 0;
                const discount = parseFloat(cleaningDiscountInput.value) || 0;
                const total = totalBasePrice + totalAdditionalFee - discount;
                
                cleaningTotalPriceInput.value = total.toFixed(2);
                
                // Update summary
                document.getElementById('cleaning_summary_base_price').textContent = '₱' + totalBasePrice.toFixed(2);
                document.getElementById('cleaning_summary_additional_fee').textContent = '₱' + totalAdditionalFee.toFixed(2);
                document.getElementById('cleaning_summary_discount').textContent = '₱' + discount.toFixed(2);
                document.getElementById('cleaning_summary_total').textContent = '₱' + total.toFixed(2);
            }

            // Add event listeners for bulk cleaning order price calculation
            // Add event listeners for aircon HP selection
            document.querySelectorAll('.cleaning-aircon-hp-select').forEach(select => {
                select.addEventListener('change', function() {
                    updateCleaningOrderPrice(this.closest('.cleaning-order-item'));
                });
            });

            // Function to update cleaning order price based on service and HP selection
            function updateCleaningOrderPrice(orderItem) {
                const hpSelect = orderItem.querySelector('.cleaning-aircon-hp-select');
                const basePriceInput = orderItem.querySelector('.cleaning-base-price-input');
                
                const selectedHP = hpSelect.options[hpSelect.selectedIndex];
                
                let finalPrice = 0;
                
                // Get price directly from selected HP
                if (selectedHP && selectedHP.value) {
                    const hpPrice = selectedHP.getAttribute('data-price');
                    finalPrice = parseFloat(hpPrice) || 0;
                }
                
                basePriceInput.value = finalPrice.toFixed(2);
                calculateCleaningBulkTotal();
            }

            document.getElementById('cleaning_bulk_additional_fee').addEventListener('input', calculateCleaningBulkTotal);
            document.getElementById('cleaning_bulk_discount').addEventListener('input', calculateCleaningBulkTotal);

            // When the add survey order modal opens, automatically set service type to survey
            addJobOrderModal.addEventListener('show.bs.modal', function() {
                // Set the service type to survey
                document.getElementById('selected_service_type').value = 'survey';
                document.getElementById('display_service_type').value = 'Survey';
                
                // Handle form field behavior for survey
                handleServiceTypeFields('survey');
            });



            // Function to handle form fields based on service type
            function handleServiceTypeFields(serviceType) {
                const airconModelSelect = document.querySelector('select[name="aircon_model_id"]');
                const basePriceInput = document.getElementById('base_price');
                const totalPriceInput = document.getElementById('total_price');

                // For survey, aircon model is optional and price is set to default survey fee
                if (airconModelSelect) {
                    airconModelSelect.required = false;
                }
                if (basePriceInput) {
                    basePriceInput.value = '500.00'; // Default survey fee
                }
                if (totalPriceInput) {
                    totalPriceInput.value = '500.00';
                }
                calculateSingleTotalPrice();
            }

            // Store aircon model prices
            const airconPrices = <?php 
                $prices = [];
                foreach ($airconModels as $model) {
                    $prices[$model['id']] = $model['price'];
                }
                echo json_encode($prices);
            ?>;

            // Handle price calculations for single order
            const airconModelSelect = document.querySelector('select[name="aircon_model_id"]');
            const basePriceInput = document.getElementById('base_price');
            const additionalFeeInput = document.getElementById('additional_fee');
            const discountInput = document.getElementById('discount');
            const totalPriceInput = document.getElementById('total_price');

            // Function to calculate total price for single order
            function calculateSingleTotalPrice() {
                const basePrice = parseFloat(basePriceInput ? basePriceInput.value : 0) || 0;
                const additionalFee = parseFloat(additionalFeeInput ? additionalFeeInput.value : 0) || 0;
                const discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
                
                const total = basePrice + additionalFee - discount;
                if (totalPriceInput) {
                    totalPriceInput.value = total.toFixed(2);
                }
                
                // Update price summary fields
                updateSurveyPriceSummary(basePrice, additionalFee, discount, total);
            }
            
            // Function to update survey price summary
            function updateSurveyPriceSummary(basePrice, additionalFee, discount, total) {
                const summaryBasePrice = document.getElementById('survey_summary_base_price');
                const summaryAdditionalFee = document.getElementById('survey_summary_additional_fee');
                const summaryDiscount = document.getElementById('survey_summary_discount');
                const summaryTotal = document.getElementById('survey_summary_total');
                
                if (summaryBasePrice) summaryBasePrice.textContent = '₱' + basePrice.toFixed(2);
                if (summaryAdditionalFee) summaryAdditionalFee.textContent = '₱' + additionalFee.toFixed(2);
                if (summaryDiscount) summaryDiscount.textContent = '₱' + discount.toFixed(2);
                if (summaryTotal) summaryTotal.textContent = '₱' + total.toFixed(2);
            }

            // Make calculateSingleTotalPrice available globally
            window.calculateSingleTotalPrice = calculateSingleTotalPrice;

            // Update base price when aircon model is selected
            if (airconModelSelect) {
                airconModelSelect.addEventListener('change', function() {
                    const selectedModelId = this.value;
                    const serviceType = document.getElementById('selected_service_type').value;

                    // For survey, keep the default survey fee regardless of aircon model selection
                    if (serviceType === 'survey') {
                        basePriceInput.value = '500.00'; // Keep survey fee
                    }
                    calculateSingleTotalPrice();
                });
            }

            // Update total price when additional fee or discount changes
            if (additionalFeeInput) {
                additionalFeeInput.addEventListener('input', calculateSingleTotalPrice);
            }
            if (discountInput) {
                discountInput.addEventListener('input', calculateSingleTotalPrice);
            }


        });

        // CUSTOMER AUTOCOMPLETE FOR CLEANING MODAL
        document.addEventListener('DOMContentLoaded', function() {
            const cleaningNameInput = document.getElementById('cleaning_customer_name_autocomplete');
            const cleaningPhoneInput = document.getElementById('cleaning_customer_phone');
            const cleaningAddressInput = document.getElementById('cleaning_customer_address');
            const cleaningSuggestionsBox = document.getElementById('cleaning_customer_suggestions');
            let cleaningSelectedCustomerId = null;

            if (cleaningNameInput) {
                cleaningNameInput.addEventListener('input', function() {
                    const term = this.value.trim();
                    cleaningSelectedCustomerId = null;
                    if (term.length < 2) {
                        cleaningSuggestionsBox.innerHTML = '';
                        cleaningSuggestionsBox.style.display = 'none';
                        cleaningPhoneInput.value = '';
                        cleaningAddressInput.value = '';
                        cleaningPhoneInput.readOnly = false;
                        cleaningAddressInput.readOnly = false;
                        return;
                    }
                    fetch('controller/search_customers.php?term=' + encodeURIComponent(term))
                        .then(res => res.json())
                        .then(data => {
                            cleaningSuggestionsBox.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(customer => {
                                    const item = document.createElement('button');
                                    item.type = 'button';
                                    item.className = 'list-group-item list-group-item-action';
                                    item.textContent = customer.name + (customer.phone ? ' (' + customer.phone + ')' : '');
                                    item.addEventListener('click', function() {
                                        cleaningNameInput.value = customer.name;
                                        cleaningPhoneInput.value = customer.phone || '';
                                        cleaningAddressInput.value = customer.address || '';
                                        cleaningPhoneInput.readOnly = !!customer.phone;
                                        cleaningAddressInput.readOnly = !!customer.address;
                                        cleaningSelectedCustomerId = customer.id;
                                        cleaningSuggestionsBox.innerHTML = '';
                                        cleaningSuggestionsBox.style.display = 'none';
                                    });
                                    cleaningSuggestionsBox.appendChild(item);
                                });
                                cleaningSuggestionsBox.style.display = 'block';
                            } else {
                                cleaningSuggestionsBox.style.display = 'none';
                            }
                        });
                });
                
                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!cleaningSuggestionsBox.contains(e.target) && e.target !== cleaningNameInput) {
                        cleaningSuggestionsBox.innerHTML = '';
                        cleaningSuggestionsBox.style.display = 'none';
                    }
                });
                
                // Allow manual entry for new customers
                cleaningNameInput.addEventListener('blur', function() {
                    setTimeout(() => {
                        if (!cleaningSelectedCustomerId) {
                            cleaningPhoneInput.value = '';
                            cleaningAddressInput.value = '';
                            cleaningPhoneInput.readOnly = false;
                            cleaningAddressInput.readOnly = false;
                        }
                    }, 200);
                });
            }
        });

        // CUSTOMER AUTOCOMPLETE FOR MAIN MODAL
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('customer_name_autocomplete');
            const phoneInput = document.getElementById('customer_phone');
            const addressInput = document.getElementById('customer_address');
            const suggestionsBox = document.getElementById('customer_suggestions');
            let selectedCustomerId = null;

            if (nameInput) {
                nameInput.addEventListener('input', function() {
                    const term = this.value.trim();
                    selectedCustomerId = null;
                    if (term.length < 2) {
                        suggestionsBox.innerHTML = '';
                        suggestionsBox.style.display = 'none';
                        phoneInput.value = '';
                        addressInput.value = '';
                        phoneInput.readOnly = false;
                        addressInput.readOnly = false;
                        return;
                    }
                    fetch('controller/search_customers.php?term=' + encodeURIComponent(term))
                        .then(res => res.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(customer => {
                                    const item = document.createElement('button');
                                    item.type = 'button';
                                    item.className = 'list-group-item list-group-item-action';
                                    item.textContent = customer.name + (customer.phone ? ' (' + customer.phone + ')' : '');
                                    item.addEventListener('click', function() {
                                        nameInput.value = customer.name;
                                        phoneInput.value = customer.phone || '';
                                        addressInput.value = customer.address || '';
                                        phoneInput.readOnly = !!customer.phone;
                                        addressInput.readOnly = !!customer.address;
                                        selectedCustomerId = customer.id;
                                        suggestionsBox.innerHTML = '';
                                        suggestionsBox.style.display = 'none';
                                    });
                                    suggestionsBox.appendChild(item);
                                });
                                suggestionsBox.style.display = 'block';
                            } else {
                                suggestionsBox.style.display = 'none';
                            }
                        });
                });
            }
            if (nameInput) {
                // Hide suggestions when clicking outside
                document.addEventListener('click', function(e) {
                    if (!suggestionsBox.contains(e.target) && e.target !== nameInput) {
                        suggestionsBox.innerHTML = '';
                        suggestionsBox.style.display = 'none';
                    }
                });
                // Allow manual entry for new customers
                nameInput.addEventListener('blur', function() {
                    setTimeout(() => {
                        if (!selectedCustomerId) {
                            phoneInput.value = '';
                            addressInput.value = '';
                            phoneInput.readOnly = false;
                            addressInput.readOnly = false;
                        }
                    }, 200);
                });
            }
        });

        // CREATE ANOTHER ORDER BUTTON
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.create-another-order-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const nameInput = document.getElementById('customer_name_autocomplete');
                    const phoneInput = document.getElementById('customer_phone');
                    const addressInput = document.getElementById('customer_address');
                    
                    if (nameInput) nameInput.value = btn.getAttribute('data-customer-name');
                    if (phoneInput) phoneInput.value = btn.getAttribute('data-customer-phone');
                    if (addressInput) addressInput.value = btn.getAttribute('data-customer-address');
                    if (phoneInput) phoneInput.readOnly = !!btn.getAttribute('data-customer-phone');
                    if (addressInput) addressInput.readOnly = !!btn.getAttribute('data-customer-address');
                });
            });
        });

        // Handle cleaning order form submission with animation
        document.getElementById('cleaningOrderForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Orders...';
            
            // Allow the form to submit normally
            // The button will be re-enabled when the page reloads or redirects
        });

        // Handle survey order form submission with animation
        document.getElementById('surveyOrderForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Order...';
            
            // Allow the form to submit normally
            // The button will be re-enabled when the page reloads or redirects
        });

        // DYNAMIC LABEL CHANGE FOR AIRCON MODEL/AC PARTS
        function updateAirconModelLabel(serviceType, labelId, selectId) {
            const label = document.getElementById(labelId);
            const select = document.getElementById(selectId);
            
            // Clear existing options except the first one
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            if (serviceType === 'repair') {
                if (labelId === 'aircon_model_label') {
                    label.innerHTML = 'AC Parts <small class="text-muted">(Optional for Survey)</small>';
                } else {
                    label.textContent = 'AC Parts';
                }
                select.querySelector('option[value=""]').textContent = 'Select Parts';
                
                // Populate with AC parts
                const acParts = <?= json_encode($acParts) ?>;
                acParts.forEach(part => {
                    const option = document.createElement('option');
                    option.value = part.id;
                    option.textContent = `${part.part_name} - ${part.part_code || 'N/A'} (₱${parseFloat(part.unit_price).toFixed(2)})`;
                    option.setAttribute('data-price', part.unit_price);
                    select.appendChild(option);
                });
            } else {
                if (labelId === 'aircon_model_label') {
                    label.innerHTML = 'Aircon Model <small class="text-muted">(Optional for Survey)</small>';
                } else {
                    label.textContent = 'Aircon Model';
                }
                select.querySelector('option[value=""]').textContent = 'Select Model';
                
                // Populate with aircon models
                const airconModels = <?= json_encode($airconModels) ?>;
                airconModels.forEach(model => {
                    const option = document.createElement('option');
                    option.value = model.id;
                    option.textContent = `${model.brand} - ${model.model_name}`;
                    option.setAttribute('data-price', model.price);
                    select.appendChild(option);
                });
            }
        }

        // Listen for service type changes in add modal
        document.addEventListener('DOMContentLoaded', function() {
            // For the main add order form - listen to display_service_type changes
            const displayServiceType = document.getElementById('display_service_type');
            if (displayServiceType) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                            updateAirconModelLabel(displayServiceType.value, 'aircon_model_label', 'aircon_model_select');
                        }
                    });
                });
                observer.observe(displayServiceType, { attributes: true, attributeFilter: ['value'] });
                
                // Also listen for input events
                displayServiceType.addEventListener('input', function() {
                    updateAirconModelLabel(this.value, 'aircon_model_label', 'aircon_model_select');
                });
            }

            // For the edit modal - listen to edit_service_type changes
            const editServiceType = document.getElementById('edit_service_type');
            if (editServiceType) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                            updateAirconModelLabel(editServiceType.value, 'edit_aircon_model_label', 'edit_aircon_model_id');
                        }
                    });
                });
                observer.observe(editServiceType, { attributes: true, attributeFilter: ['value'] });
                
                // Also listen for input events
                editServiceType.addEventListener('input', function() {
                    updateAirconModelLabel(this.value, 'edit_aircon_model_label', 'edit_aircon_model_id');
                });
            }
        });
    </script>

    <style>
        .order-type-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .order-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .bulk-order-alert {
            border-left: 4px solid #17a2b8;
            background-color: #f8f9fa;
        }
        
        .bulk-order-alert i {
            color: #17a2b8;
        }
        
        #cleaningOrderModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        #addJobOrderModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .price-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .price-summary h6 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .bg-primary-subtle {
            background-color: rgba(13, 110, 253, 0.1) !important;
        }
        
        .text-primary {
            color: #0d6efd !important;
        }
        
        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        
        .text-success {
            color: #198754 !important;
        }
        
        .bg-info-subtle {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }
        
        .text-info {
            color: #0dcaf0 !important;
        }
    </style>
</body>
</html>