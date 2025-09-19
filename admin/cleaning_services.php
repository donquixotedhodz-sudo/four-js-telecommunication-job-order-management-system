<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Use PDO for database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch admin info
    $admin = null;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle Aircon HP form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_aircon_hp'])) {
        $hp = trim($_POST['hp'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if ($hp && $price && is_numeric($hp) && is_numeric($price)) {
            $stmt = $pdo->prepare("INSERT INTO aircon_hp (hp, price) VALUES (?, ?)");
            $stmt->execute([$hp, $price]);
            $_SESSION['success_message'] = "Aircon HP added successfully!";
            header("Location: cleaning_services.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Please fill in all fields correctly.";
            header("Location: cleaning_services.php");
            exit;
        }
    }

    // Handle Edit Aircon HP
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_aircon_hp'])) {
        $hp_id = intval($_POST['hp_id']);
        $hp = trim($_POST['hp'] ?? '');
        $price = trim($_POST['price'] ?? '');

        if ($hp && $price && is_numeric($hp) && is_numeric($price)) {
            $stmt = $pdo->prepare("UPDATE aircon_hp SET hp = ?, price = ? WHERE id = ?");
            $stmt->execute([$hp, $price, $hp_id]);
            $_SESSION['success_message'] = "Aircon HP updated successfully!";
            header("Location: cleaning_services.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Please fill in all fields correctly.";
            header("Location: cleaning_services.php");
            exit;
        }
    }

    // Handle Delete Aircon HP
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_aircon_hp'])) {
        $hp_id = intval($_POST['hp_id']);
        $stmt = $pdo->prepare("DELETE FROM aircon_hp WHERE id = ?");
        $stmt->execute([$hp_id]);
        $_SESSION['success_message'] = "Aircon HP deleted successfully!";
        header("Location: cleaning_services.php");
        exit;
    }

    // Handle Cleaning Service form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_cleaning_service'])) {
        $service_name = trim($_POST['service_name'] ?? '');
        $service_description = trim($_POST['service_description'] ?? '');

        if ($service_name && $service_description) {
            $stmt = $pdo->prepare("INSERT INTO cleaning_services (service_name, service_description) VALUES (?, ?)");
            $stmt->execute([$service_name, $service_description]);
            $_SESSION['success_message'] = "Cleaning service added successfully!";
            header("Location: cleaning_services.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Please fill in all fields correctly.";
            header("Location: cleaning_services.php");
            exit;
        }
    }

    // Handle Edit Cleaning Service
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_cleaning_service'])) {
        $service_id = intval($_POST['service_id']);
        $service_name = trim($_POST['service_name'] ?? '');
        $service_description = trim($_POST['service_description'] ?? '');

        if ($service_name && $service_description) {
            $stmt = $pdo->prepare("UPDATE cleaning_services SET service_name = ?, service_description = ? WHERE id = ?");
            $stmt->execute([$service_name, $service_description, $service_id]);
            $_SESSION['success_message'] = "Cleaning service updated successfully!";
            header("Location: cleaning_services.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Please fill in all fields correctly.";
            header("Location: cleaning_services.php");
            exit;
        }
    }

    // Handle Delete Cleaning Service
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_cleaning_service'])) {
        $service_id = intval($_POST['service_id']);
        $stmt = $pdo->prepare("DELETE FROM cleaning_services WHERE id = ?");
        $stmt->execute([$service_id]);
        $_SESSION['success_message'] = "Cleaning service deleted successfully!";
        header("Location: cleaning_services.php");
        exit;
    }

    // Fetch all cleaning services
    $stmt = $pdo->query("SELECT * FROM cleaning_services ORDER BY id DESC");
    $cleaningServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all aircon HP
    $stmt = $pdo->query("SELECT * FROM aircon_hp ORDER BY hp ASC");
    $airconHPs = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <h3>Cleaning Services Management</h3>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Add Cleaning Service</h5>
            <form method="post" class="row g-3">
                <input type="hidden" name="add_cleaning_service" value="1">
                <div class="col-md-4">
                    <input type="text" name="service_name" class="form-control" placeholder="Service Name" required>
                </div>
                <div class="col-md-6">
                    <textarea name="service_description" class="form-control" placeholder="Service Description" rows="1" required></textarea>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Add</button>
                </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3">Existing Cleaning Services</h6>
            <div class="table-wrapper" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cleaningServices)): ?>
                            <?php foreach ($cleaningServices as $service): ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['service_name']) ?></td>
                                    <td><?= htmlspecialchars($service['service_description']) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal_<?= $service['id'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $service['id'] ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No cleaning services found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
            <!-- Delete form (hidden, JS will submit this) -->
            <form id="deleteForm" method="post" style="display:none;">
                <input type="hidden" name="delete_cleaning_service" value="1">
                <input type="hidden" name="service_id" id="deleteServiceId">
            </form>
        </div>
    </div>

    <!-- Aircon HP Management Section -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Add Aircon HP</h5>
            <form method="post" class="row g-3">
                <input type="hidden" name="add_aircon_hp" value="1">
                <div class="col-md-4">
                    <input type="number" name="hp" class="form-control" placeholder="HP (e.g., 1.0, 1.5, 2.0)" step="0.1" min="0" required>
                </div>
                <div class="col-md-6">
                    <input type="number" name="price" class="form-control" placeholder="Price" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Add HP</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Aircon HP Pricing</h6>
            <div class="table-wrapper" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>HP</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($airconHPs)): ?>
                            <?php foreach ($airconHPs as $hp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($hp['hp']) ?></td>
                                    <td>₱<?= number_format($hp['price'], 2) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#editHpModal_<?= $hp['id'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteHp(<?= $hp['id'] ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">No aircon HP found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
            <!-- Delete HP form (hidden, JS will submit this) -->
            <form id="deleteHpForm" method="post" style="display:none;">
                <input type="hidden" name="delete_aircon_hp" value="1">
                <input type="hidden" name="hp_id" id="deleteHpId">
            </form>
        </div>
    </div>
</div>

<!-- All Edit Modals (move outside of table for Bootstrap compatibility) -->
<?php if (count($cleaningServices)): ?>
    <?php foreach ($cleaningServices as $service): ?>
        <div class="modal fade" id="editModal_<?= $service['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel_<?= $service['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content">
                    <input type="hidden" name="edit_cleaning_service" value="1">
                    <input type="hidden" name="service_id" value="<?= $service['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel_<?= $service['id'] ?>">Edit Cleaning Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="service_name" class="form-control" value="<?= htmlspecialchars($service['service_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Description</label>
                            <textarea name="service_description" class="form-control" rows="3" required><?= htmlspecialchars($service['service_description']) ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- All Edit HP Modals -->
<?php if (count($airconHPs)): ?>
    <?php foreach ($airconHPs as $hp): ?>
        <div class="modal fade" id="editHpModal_<?= $hp['id'] ?>" tabindex="-1" aria-labelledby="editHpModalLabel_<?= $hp['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post" class="modal-content">
                    <input type="hidden" name="edit_aircon_hp" value="1">
                    <input type="hidden" name="hp_id" value="<?= $hp['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHpModalLabel_<?= $hp['id'] ?>">Edit Aircon HP</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">HP</label>
                            <input type="number" name="hp" class="form-control" value="<?= htmlspecialchars($hp['hp']) ?>" step="0.1" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($hp['price']) ?>" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

  <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <!-- <script src="../js/dashboard.js"></script> -->
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this service?')) {
                document.getElementById('deleteServiceId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function confirmDeleteHp(id) {
            if (confirm('Are you sure you want to delete this HP entry?')) {
                // Create a temporary form to submit the delete request
                const form = document.createElement('form');
                form.method = 'post';
                form.style.display = 'none';
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_aircon_hp';
                deleteInput.value = '1';
                
                const hpIdInput = document.createElement('input');
                hpIdInput.type = 'hidden';
                hpIdInput.name = 'hp_id';
                hpIdInput.value = id;
                
                form.appendChild(deleteInput);
                form.appendChild(hpIdInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <style>
        @media print {
            /* Hide screen elements */
            .navbar, .sidebar, .btn, .card-header, .form-control, .form-select, 
            .dropdown, .btn-group, .d-flex.gap-2 {
                display: none !important;
            }
            
            /* Show print header */
            .print-header {
                display: block !important;
                margin-bottom: 30px !important;
                padding-bottom: 20px !important;
                border-bottom: 2px solid #34495e !important;
                page-break-after: avoid !important;
            }
            
            .print-header img {
                display: block !important;
                max-height: 60px !important;
                width: auto !important;
            }
            
            .print-header .text-end {
                text-align: right !important;
            }
            
            /* Reset page layout */
            body {
                margin: 0 !important;
                padding: 20px !important;
                font-family: 'Arial', sans-serif !important;
                font-size: 12px !important;
                line-height: 1.4 !important;
                color: #000 !important;
                background: white !important;
            }
            
            /* Header styling */
            .container-fluid {
                max-width: none !important;
                padding: 0 !important;
            }
            
            /* Table styling for print */
            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 20px !important;
                font-size: 11px !important;
            }
            
            .table th {
                background: #34495e !important;
                color: white !important;
                font-weight: bold !important;
                text-align: left !important;
                padding: 12px 8px !important;
                border: none !important;
                font-size: 12px !important;
            }
            
            .table td {
                padding: 10px 8px !important;
                border: none !important;
                vertical-align: top !important;
            }
            
            .table tbody tr:nth-child(even) {
                background: #f8f9fa !important;
            }
            
            /* Status badges for print */
            .badge {
                display: inline-block !important;
                padding: 4px 8px !important;
                font-size: 10px !important;
                font-weight: bold !important;
                border-radius: 3px !important;
                border: 1px solid !important;
            }
            
            .badge.bg-success {
                background: #d4edda !important;
                color: #155724 !important;
                border-color: #c3e6cb !important;
            }
            
            .badge.bg-danger {
                background: #f8d7da !important;
                color: #721c24 !important;
                border-color: #f5c6cb !important;
            }
            
            .badge.bg-info {
                background: #d1ecf1 !important;
                color: #0c5460 !important;
                border-color: #bee5eb !important;
            }
            
            /* Hide profile images in print */
            .table img {
                display: none !important;
            }
            
            /* Hide action column in print */
            .table th:last-child,
            .table td:last-child {
                display: none !important;
            }
            
            /* Customer information styling */
            .fw-medium {
                font-weight: bold !important;
                color: #2c3e50 !important;
            }
            
            .text-muted {
                color: #7f8c8d !important;
            }
            
            .fw-semibold {
                font-weight: bold !important;
                color: #2c3e50 !important;
            }
            
            /* Page breaks */
            .table-responsive {
                page-break-inside: auto !important;
            }
            
            .table tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
        }
    </style>

    <script>
        function confirmDelete(serviceId) {
            if (confirm('Are you sure you want to delete this cleaning service?')) {
                document.getElementById('deleteServiceId').value = serviceId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>