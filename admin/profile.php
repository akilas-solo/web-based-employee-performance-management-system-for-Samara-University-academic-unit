<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Profile
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$admin_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$email = $_SESSION['email'];
$profile_image = $_SESSION['profile_image'];
$success_message = '';
$error_message = '';
$errors = [];

// Get admin details
$sql = "SELECT * FROM admin WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $full_name = $admin['full_name'];
    $email = $admin['email'];
    $profile_image = $admin['profile_image'];
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    
    // Validate form data
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists for other users
        $sql = "SELECT * FROM admin WHERE email = ? AND admin_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
    }
    
    // Handle profile image upload
    $new_profile_image = $profile_image;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and GIF images are allowed.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = "File size exceeds the maximum limit of 2MB.";
        } else {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_profile_image = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
            $upload_path = BASE_PATH . '/uploads/profiles/' . $new_profile_image;
            
            // Create directory if it doesn't exist
            if (!file_exists(BASE_PATH . '/uploads/profiles/')) {
                mkdir(BASE_PATH . '/uploads/profiles/', 0777, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Delete old profile image if exists
                if (!empty($profile_image) && file_exists(BASE_PATH . '/uploads/profiles/' . $profile_image)) {
                    unlink(BASE_PATH . '/uploads/profiles/' . $profile_image);
                }
            } else {
                $errors[] = "Failed to upload profile image.";
                $new_profile_image = $profile_image;
            }
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $sql = "UPDATE admin SET full_name = ?, email = ?, profile_image = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $full_name, $email, $new_profile_image, $admin_id);
        
        if ($stmt->execute()) {
            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_image'] = $new_profile_image;
            
            $success_message = "Profile updated successfully.";
            $profile_image = $new_profile_image;
        } else {
            $error_message = "Error updating profile: " . $conn->error;
        }
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password']; // Don't sanitize password
    $new_password = $_POST['new_password']; // Don't sanitize password
    $confirm_password = $_POST['confirm_password']; // Don't sanitize password
    
    // Validate form data
    if (empty($current_password)) {
        $errors[] = "Current password is required.";
    }
    
    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters.";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    
    // Verify current password
    if (empty($errors)) {
        $sql = "SELECT password FROM admin WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $stored_password = $row['password'];
            
            if (!password_verify_custom($current_password, $stored_password)) {
                $errors[] = "Current password is incorrect.";
            }
        } else {
            $errors[] = "User not found.";
        }
    }
    
    // If no errors, update password
    if (empty($errors)) {
        // Hash new password
        $hashed_password = password_hash_custom($new_password);
        
        $sql = "UPDATE admin SET password = ? WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $admin_id);
        
        if ($stmt->execute()) {
            $success_message = "Password changed successfully.";
        } else {
            $error_message = "Error changing password: " . $conn->error;
        }
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}

// Include header
include_once BASE_PATH . '/includes/header_management.php';

// Include sidebar
include_once BASE_PATH . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">My Profile</h1>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="profile-image-container mb-3">
                            <?php if (!empty($profile_image) && file_exists(BASE_PATH . '/uploads/profiles/' . $profile_image)): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $profile_image; ?>" alt="<?php echo $full_name; ?>" class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; font-size: 3rem; margin: 0 auto;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h4 class="font-weight-bold"><?php echo $full_name; ?></h4>
                        <p class="text-muted"><?php echo $email; ?></p>
                        <p><span class="badge badge-primary">Administrator</span></p>
                    </div>
                </div>
            </div>

            <!-- Update Profile -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Update Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide your full name.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="profile_image" name="profile_image" accept="image/*">
                                    <label class="custom-file-label" for="profile_image">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF.</small>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="current_password">Current Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <div class="invalid-feedback">
                                    Please provide your current password.
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="invalid-feedback">
                                    Please provide a new password.
                                </div>
                                <small class="form-text text-muted">Password must be at least 6 characters.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">
                                    Please confirm your new password.
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key mr-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/form-validation.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update custom file input label with selected filename
        $('.custom-file-input').on('change', function() {
            var fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').html(fileName);
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
