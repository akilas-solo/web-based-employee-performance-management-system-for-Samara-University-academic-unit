<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Profile
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$errors = [];
$user_id = $_SESSION['user_id'];

// Get user data
$user = get_user_by_id($user_id);
if (!$user) {
    redirect($base_url . 'login.php');
}

// Get HRM profile data
$hrm_profile = null;
$sql = "SELECT * FROM hrm_profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $hrm_profile = $result->fetch_assoc();
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $position = sanitize_input($_POST['position']);
    $department = sanitize_input($_POST['department']);
    $years_of_experience = sanitize_input($_POST['years_of_experience']);

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
        $sql = "SELECT * FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists.";
        }
    }

    // Process profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
        $upload_dir = BASE_PATH . '/uploads/profile_images/';
        $file_name = time() . '_' . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a valid image
        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_type, $valid_extensions)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } elseif ($_FILES['profile_image']['size'] > 2000000) { // 2MB max
            $errors[] = "File size is too large. Maximum size is 2MB.";
        } else {
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete old profile image if exists
                if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $file_name;
            } else {
                $errors[] = "Failed to upload profile image.";
            }
        }
    }

    // Update user data if no errors
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Update user table
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, position = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $position, $profile_image, $user_id);
            $stmt->execute();

            // Update or insert HRM profile
            if ($hrm_profile) {
                $sql = "UPDATE hrm_profiles SET department = ?, position = ?, years_of_experience = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssii", $department, $position, $years_of_experience, $user_id);
                $stmt->execute();
            } else {
                $sql = "INSERT INTO hrm_profiles (user_id, department, position, years_of_experience) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $user_id, $department, $position, $years_of_experience);
                $stmt->execute();
            }

            // Commit transaction
            $conn->commit();

            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_image'] = $profile_image;

            $success_message = "Profile updated successfully.";

            // Refresh user data
            $user = get_user_by_id($user_id);

            // Refresh HRM profile data
            $sql = "SELECT * FROM hrm_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $hrm_profile = $result->fetch_assoc();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Failed to update profile: " . $e->getMessage();
        }
    } else {
        $error_message = "Please correct the following errors:";
    }
}

// Process password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate form data
    if (empty($current_password)) {
        $errors[] = "Current password is required.";
    }

    if (empty($new_password)) {
        $errors[] = "New password is required.";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters long.";
    }

    if (empty($confirm_password)) {
        $errors[] = "Confirm password is required.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New password and confirm password do not match.";
    }

    // Verify current password
    if (empty($errors)) {
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
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

    // Update password if no errors
    if (empty($errors)) {
        $hashed_password = password_hash_custom($new_password);

        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            $success_message = "Password updated successfully.";
        } else {
            $error_message = "Failed to update password.";
        }
    } else {
        $error_message = "Please correct the following errors:";
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
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
                <?php if (!empty($errors)): ?>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Profile Information</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo $user['position']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="department">Department</label>
                                <input type="text" class="form-control" id="department" name="department" value="<?php echo $hrm_profile ? $hrm_profile['department'] : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="years_of_experience">Years of Experience</label>
                                <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" value="<?php echo $hrm_profile ? $hrm_profile['years_of_experience'] : ''; ?>" min="0">
                            </div>

                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="profile_image" name="profile_image">
                                    <label class="custom-file-label" for="profile_image">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPG, JPEG, PNG, GIF.</small>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-theme">
                                <i class="fas fa-save mr-1"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Change Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" name="update_password" class="btn btn-theme">
                                <i class="fas fa-key mr-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Summary -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Profile Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profile_images/<?php echo $user['profile_image']; ?>" alt="Profile Image" style="width: 150px; height: 150px;">
                            <?php else: ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>assets/images/default-profile.png" alt="Default Profile" style="width: 150px; height: 150px;">
                            <?php endif; ?>
                            <h4 class="text-theme"><?php echo $user['full_name']; ?></h4>
                            <p class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Contact Information</h6>
                            <p>
                                <i class="fas fa-envelope mr-2 text-theme"></i> <?php echo $user['email']; ?><br>
                                <i class="fas fa-phone mr-2 text-theme"></i> <?php echo !empty($user['phone']) ? $user['phone'] : 'Not provided'; ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Professional Information</h6>
                            <p>
                                <i class="fas fa-briefcase mr-2 text-theme"></i> <?php echo !empty($user['position']) ? $user['position'] : 'Not provided'; ?><br>
                                <i class="fas fa-building mr-2 text-theme"></i> <?php echo $hrm_profile && !empty($hrm_profile['department']) ? $hrm_profile['department'] : 'Not provided'; ?><br>
                                <i class="fas fa-clock mr-2 text-theme"></i> <?php echo $hrm_profile && !empty($hrm_profile['years_of_experience']) ? $hrm_profile['years_of_experience'] . ' years of experience' : 'Not provided'; ?>
                            </p>
                        </div>

                        <div>
                            <h6 class="font-weight-bold">Account Information</h6>
                            <p>
                                <i class="fas fa-user-shield mr-2 text-theme"></i> Role: <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?><br>
                                <i class="fas fa-calendar-alt mr-2 text-theme"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?><br>
                                <i class="fas fa-clock mr-2 text-theme"></i> Last Updated: <?php echo date('M d, Y', strtotime($user['updated_at'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    $base_url . 'assets/js/bs-custom-file-input.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize custom file input
        bsCustomFileInput.init();
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
