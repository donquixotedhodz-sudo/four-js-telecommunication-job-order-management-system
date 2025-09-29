<?php
session_start();
require_once('../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php'); // Redirect to login if not logged in or not admin
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get admin details
    $stmt = $pdo->prepare("SELECT name, profile_picture FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get all admins
    $stmt = $pdo->query("SELECT * FROM admins ORDER BY id DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // ... existing code ...
}
require_once 'includes/header.php';
?>
<body>
 <div class="wrapper">
        <?php
        // Include sidebar
        require_once 'includes/sidebar.php';
        ?>
        <!-- Page Content -->
        <div id="content">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
    <h3>Admin Management</h3>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Add New Admin</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
                <i class="fas fa-user-plus me-2"></i>Create New Admin
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="mb-3">Existing Admins</h6>
            <div class="table-wrapper" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($admins)): ?>
                            <?php foreach ($admins as $adminUser): ?>
                                <tr>
                                    <td><?= htmlspecialchars($adminUser['id'] ?? '') ?></td>
                                    <td>
                                        <img src="<?= !empty($adminUser['profile_picture']) ? '../' . htmlspecialchars($adminUser['profile_picture'] ?? '') : 'https://ui-avatars.com/api/?name=' . urlencode($adminUser['name'] ?: 'Admin') . '&background=1a237e&color=fff' ?>" 
                                             alt="Admin" 
                                             class="rounded-circle" 
                                             width="32" 
                                             height="32"
                                             style="object-fit: cover; border: 2px solid #4A90E2;">
                                    </td>
                                    <td><?= htmlspecialchars($adminUser['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($adminUser['username'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($adminUser['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($adminUser['phone'] ?? '') ?></td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal_<?= $adminUser['id'] ?>" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-danger" title="Delete" onclick="confirmDelete(<?= $adminUser['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center">No admins found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    </table>
                </div>
            </div>
            <!-- Delete form (hidden, JS will submit this) -->
            <form id="deleteForm" method="post" action="controller/delete_admin.php" style="display:none;">
                <input type="hidden" name="delete_admin" value="1">
                <input type="hidden" name="admin_id" id="deleteAdminId">
            </form>
        </div>
    </div>
</div>
        </div>
    </div>



    <!-- Create Additional Admin Modal -->
    <div class="modal fade" id="createAdminModal" tabindex="-1" aria-labelledby="createAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAdminModalLabel">Create Additional Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Create Admin Form -->
                    <form action="controller/create_admin.php" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="adminName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="adminName" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="adminUsername" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="adminUsername" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="adminEmail" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="adminEmail" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="adminPhone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="adminPhone" name="phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="adminPassword" class="form-label">Password</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="adminPassword" name="password" required style="padding-right: 40px;">
                                        <button class="btn position-absolute top-50 end-0 translate-middle-y me-2" type="button" id="togglePassword" style="border: none; background: none; color: #6c757d; z-index: 10; display: none;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmAdminPassword" class="form-label">Confirm Password</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control" id="confirmAdminPassword" name="confirm_password" required style="padding-right: 40px;">
                                        <button class="btn position-absolute top-50 end-0 translate-middle-y me-2" type="button" id="toggleConfirmPassword" style="border: none; background: none; color: #6c757d; z-index: 10; display: none;">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="adminProfilePicture" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="adminProfilePicture" name="profile_picture" accept="image/*">
                                    <small class="text-muted">Max file size: 2MB. Allowed formats: JPG, PNG, GIF</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Create Admin</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- All Edit Modals -->
<?php if (count($admins)): ?>
    <?php foreach ($admins as $adminUser): ?>
        <div class="modal fade" id="editModal_<?= $adminUser['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel_<?= $adminUser['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="post" action="controller/edit_admin.php" class="modal-content" enctype="multipart/form-data">
                    <input type="hidden" name="edit_admin" value="1">
                    <input type="hidden" name="admin_id" value="<?= $adminUser['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel_<?= $adminUser['id'] ?>">Edit Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($adminUser['name'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($adminUser['username'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($adminUser['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($adminUser['phone'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Profile Picture</label>
                                    <input type="file" name="profile_picture" class="form-control" accept="image/*">
                                    <small class="text-muted">Max file size: 2MB. Leave blank to keep current picture.</small>
                                </div>
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
    <?php endforeach; ?>
<?php endif; ?>

   <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- <script src="../../js/dashboard.js"></script> -->

    <style>
        /* Delete notification modal styles */
        .delete-notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .delete-notification-overlay.show {
            opacity: 1;
        }

        .delete-notification-modal {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
            transform: scale(0.7);
            transition: transform 0.3s ease;
        }

        .delete-notification-modal.show {
            transform: scale(1);
        }

        .delete-notification-header {
            padding: 20px 20px 0 20px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 0;
        }

        .delete-notification-header h5 {
            margin: 0;
            color: #dc3545;
            font-weight: 600;
            padding-bottom: 15px;
        }

        .delete-notification-body {
            padding: 20px;
        }

        .delete-notification-body p {
            margin: 0;
            color: #6c757d;
            line-height: 1.5;
        }

        .delete-notification-footer {
            padding: 0 20px 20px 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Animation keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                transform: scale(0.7);
                opacity: 0;
            }
            to { 
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>

    <script>
        // Password toggle functionality for create admin modal
        const adminPasswordInput = document.getElementById('adminPassword');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const confirmAdminPasswordInput = document.getElementById('confirmAdminPassword');
        const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');

        // Show/hide toggle button based on input content for password field
        adminPasswordInput.addEventListener('input', function() {
            if (adminPasswordInput.value.length > 0) {
                togglePasswordBtn.style.display = 'block';
            } else {
                togglePasswordBtn.style.display = 'none';
            }
        });

        // Show/hide toggle button based on input content for confirm password field
        confirmAdminPasswordInput.addEventListener('input', function() {
            if (confirmAdminPasswordInput.value.length > 0) {
                toggleConfirmPasswordBtn.style.display = 'block';
            } else {
                toggleConfirmPasswordBtn.style.display = 'none';
            }
        });

        togglePasswordBtn.addEventListener('click', function() {
            const type = adminPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            adminPasswordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        toggleConfirmPasswordBtn.addEventListener('click', function() {
            const type = confirmAdminPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmAdminPasswordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Custom delete notification function
        function showDeleteNotification(id) {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'delete-notification-overlay';
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'delete-notification-modal';
            
            // Create header
            const header = document.createElement('div');
            header.className = 'delete-notification-header';
            header.innerHTML = '<h5>Confirm Deletion</h5>';
            
            // Create body
            const body = document.createElement('div');
            body.className = 'delete-notification-body';
            body.innerHTML = '<p>Are you sure you want to delete this admin?</p>';
            
            // Create footer with buttons
            const footer = document.createElement('div');
            footer.className = 'delete-notification-footer';
            
            const cancelBtn = document.createElement('button');
            cancelBtn.textContent = 'Cancel';
            cancelBtn.className = 'btn btn-secondary me-2';
            cancelBtn.onclick = () => document.body.removeChild(overlay);
            
            const confirmBtn = document.createElement('button');
            confirmBtn.textContent = 'Delete';
            confirmBtn.className = 'btn btn-danger';
            confirmBtn.onclick = () => {
                document.getElementById('deleteAdminId').value = id;
                document.getElementById('deleteForm').submit();
                document.body.removeChild(overlay);
            };
            
            footer.appendChild(cancelBtn);
            footer.appendChild(confirmBtn);
            
            // Assemble modal
            modal.appendChild(header);
            modal.appendChild(body);
            modal.appendChild(footer);
            overlay.appendChild(modal);
            
            // Add to page
            document.body.appendChild(overlay);
            
            // Trigger animations
            setTimeout(() => {
                overlay.classList.add('show');
                modal.classList.add('show');
            }, 10);
        }

        // Confirm delete function
        function confirmDelete(id) {
            showDeleteNotification(id);
        }

        // Make confirmDelete function global
        window.confirmDelete = confirmDelete;

        // File size validation
        document.getElementById('adminProfilePicture').addEventListener('change', function() {
            const file = this.files[0];
            const maxSize = 2 * 1024 * 1024; // 2MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if (file) {
                if (file.size > maxSize) {
                    alert('File size exceeds 2MB limit');
                    this.value = '';
                }
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPG, PNG, or GIF)');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>