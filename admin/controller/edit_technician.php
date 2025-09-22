<?php
session_start();
require_once('../../config/database.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_message'] = 'Access denied.';
    header('Location: ../../index.php');
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $technician_id = $_POST['technician_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Basic validation
    if (empty($technician_id) || empty($name) || empty($username) || empty($phone)) {
        $_SESSION['error_message'] = 'All fields are required to edit a technician.';
    } else {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Get current technician data for profile picture handling
            $current_stmt = $pdo->prepare("SELECT profile_picture FROM technicians WHERE id = ?");
            $current_stmt->execute([$technician_id]);
            $current_technician = $current_stmt->fetch(PDO::FETCH_ASSOC);

            // Check if the username already exists for another technician
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM technicians WHERE username = ? AND id != ?");
            $stmt->execute([$username, $technician_id]);
            if ($stmt->fetchColumn() > 0) {
                $_SESSION['error_message'] = 'Username already exists for another technician.';
            } else {
                $profile_picture = $current_technician['profile_picture']; // Keep current picture by default
                
                // Handle profile picture upload
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB
                    
                    if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                        $_SESSION['error_message'] = 'Invalid file type. Please upload JPG, PNG, or GIF images only.';
                    } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                        $_SESSION['error_message'] = 'File size too large. Maximum size is 5MB.';
                    } else {
                        // Create upload directory if it doesn't exist
                        $upload_dir = '../../uploads/profile_pictures/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }

                        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                        $new_filename = 'technician_' . $technician_id . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                            // Delete old profile picture if exists
                            if (!empty($current_technician['profile_picture']) && file_exists('../../' . $current_technician['profile_picture'])) {
                                unlink('../../' . $current_technician['profile_picture']);
                            }
                            $profile_picture = 'uploads/profile_pictures/' . $new_filename;
                        } else {
                            $_SESSION['error_message'] = 'Failed to upload profile picture.';
                        }
                    }
                }

                // Only proceed with update if no errors occurred
                if (!isset($_SESSION['error_message'])) {
                    // Update technician details including profile picture
                    $update_stmt = $pdo->prepare("UPDATE technicians SET name = ?, username = ?, phone = ?, profile_picture = ?, updated_at = NOW() WHERE id = ?");
                    $update_stmt->execute([$name, $username, $phone, $profile_picture, $technician_id]);

                    $_SESSION['success_message'] = 'Technician updated successfully.';
                }
            }

        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Redirect back to the technicians page
header('Location: ../technicians.php');
exit();
?>