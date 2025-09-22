<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'technician') {
    header('Location: ../index.php');
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get technician details
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set default values for missing fields
    $technician['email'] = $technician['email'] ?? '';
    $technician['phone'] = $technician['phone'] ?? '';
    $technician['name'] = $technician['name'] ?? '';
    $technician['username'] = $technician['username'] ?? '';
    $technician['profile_picture'] = $technician['profile_picture'] ?? '';
    $technician['created_at'] = $technician['created_at'] ?? date('Y-m-d H:i:s');
    $technician['updated_at'] = $technician['updated_at'] ?? date('Y-m-d H:i:s');

    // Get technician's performance statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            AVG(CASE 
                WHEN status = 'completed' 
                THEN TIMESTAMPDIFF(HOUR, created_at, completed_at)
                ELSE NULL 
            END) as avg_completion_time
        FROM job_orders 
        WHERE assigned_technician_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle password change form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate input
        $password_errors = [];
        
        if (empty($current_password)) {
            $password_errors[] = "Current password is required";
        }
        
        if (empty($new_password)) {
            $password_errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $password_errors[] = "New password must be at least 8 characters long";
        }
        
        if ($new_password !== $confirm_password) {
            $password_errors[] = "New passwords do not match";
        }

        // Verify current password
        if (empty($password_errors)) {
            if (!password_verify($current_password, $technician['password'])) {
                $password_errors[] = "Current password is incorrect";
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE technicians SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $_SESSION['success_message'] = "Password updated successfully";
                    header('Location: profile.php');
                    exit();
                } else {
                    $password_errors[] = "Failed to update password";
                }
            }
        }
        
        // Store errors in session to display as alert
        if (!empty($password_errors)) {
            $_SESSION['error_message'] = implode('<br>', $password_errors);
        }
    }
    }
 catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
// Include header
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
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                     alt="Technician" 
                                     class="rounded-circle me-2" 
                                     width="32" 
                                     height="32"
                                     style="object-fit: cover; border: 2px solid #4A90E2;">
                                <span class="me-3">Welcome, <?= htmlspecialchars($technician['name'] ?: 'Technician') ?></span>
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
                                    <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="../admin/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">Technician Profile</h4>
                        <p class="text-muted mb-0">View and manage your account details</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); endif; ?>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-5">
                                <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                     alt="Profile" 
                                     class="rounded-circle mb-4" 
                                     style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #4A90E2; box-shadow: 0 0 10px rgba(74, 144, 226, 0.3);">
                                <h5 class="card-title mb-2"><?= htmlspecialchars($technician['name'] ?: 'Technician') ?></h5>
                                <p class="text-muted mb-0">Technician</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Account Information</h5>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Full Name</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['name'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Username</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['username'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Email</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['email'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Phone</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= htmlspecialchars($technician['phone'] ?: 'N/A') ?></p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row mb-3">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-muted">Member Since</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="mb-0"><?= date('F d, Y', strtotime($technician['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?= !empty($technician['profile_picture']) ? '../' . htmlspecialchars($technician['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($technician['name'] ?: 'Technician') . '&background=1a237e&color=fff' ?>" 
                                 alt="Profile" 
                                 class="rounded-circle mb-3" 
                                 style="width: 150px; height: 150px; object-fit: cover; border: 4px solid #4A90E2; box-shadow: 0 0 10px rgba(74, 144, 226, 0.3);"
                                 id="profilePreview">
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" class="form-control" name="profile_picture" accept="image/jpeg,image/png,image/gif">
                                <small class="text-muted">Max file size: 5MB. Allowed formats: JPG, PNG, GIF</small>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($technician['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($technician['username'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($technician['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($technician['phone'] ?? '') ?>" required>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="profile.php" method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" name="current_password" id="current_password" required>
                                <button class="btn position-absolute top-50 end-0 translate-middle-y me-2 password-toggle-btn" type="button" onclick="togglePassword('current_password')" style="border: none; background: none; color: #6c757d; z-index: 10; display: none;">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" name="new_password" id="new_password" required minlength="8">
                                <button class="btn position-absolute top-50 end-0 translate-middle-y me-2 password-toggle-btn" type="button" onclick="togglePassword('new_password')" style="border: none; background: none; color: #6c757d; z-index: 10; display: none;">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Password must be at least 8 characters long</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control pe-5" name="confirm_password" id="confirm_password" required minlength="8">
                                <button class="btn position-absolute top-50 end-0 translate-middle-y me-2 password-toggle-btn" type="button" onclick="togglePassword('confirm_password')" style="border: none; background: none; color: #6c757d; z-index: 10; display: none;">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer px-0 pb-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/dashboard.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Show/hide password toggle icons based on input content
        document.addEventListener('DOMContentLoaded', function() {
            const passwordFields = ['current_password', 'new_password', 'confirm_password'];
            
            passwordFields.forEach(function(fieldId) {
                const passwordInput = document.getElementById(fieldId);
                const toggleButton = passwordInput.nextElementSibling;
                
                // Show/hide toggle button based on input content
                passwordInput.addEventListener('input', function() {
                    if (passwordInput.value.length > 0) {
                        toggleButton.style.display = 'block';
                    } else {
                        toggleButton.style.display = 'none';
                        // Reset to password type when input is cleared
                        passwordInput.type = 'password';
                        const icon = document.getElementById(fieldId + '_icon');
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Show change password modal if there are password errors
            <?php if (isset($_SESSION['show_password_modal'])): ?>
            var changePasswordModal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            changePasswordModal.show();
            <?php 
            unset($_SESSION['show_password_modal']); 
            endif; ?>
        });

        // Profile picture preview
        document.querySelector('input[name="profile_picture"]').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>