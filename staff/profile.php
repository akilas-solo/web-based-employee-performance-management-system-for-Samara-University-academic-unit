<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff - Profile
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$college_id = $_SESSION['college_id'];

// Get user information
$user = null;
$sql = "SELECT u.*, d.name as department_name, c.name as college_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN colleges c ON u.college_id = c.college_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    redirect($base_url . 'staff/dashboard.php');
}

// Get staff profile information
$profile = null;

// First check if the staff_profiles table exists
$table_exists = false;
$check_table = $conn->query("SHOW TABLES LIKE 'staff_profiles'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// Create the table if it doesn't exist
if (!$table_exists) {
    $create_table = "CREATE TABLE IF NOT EXISTS staff_profiles (
        profile_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        academic_rank VARCHAR(50) DEFAULT NULL,
        specialization VARCHAR(100) DEFAULT NULL,
        years_of_experience INT DEFAULT NULL,
        appointment_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
    )";
    $conn->query($create_table);
}

// Get staff profile
$sql = "SELECT * FROM staff_profiles WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $profile = $result->fetch_assoc();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $bio = sanitize_input($_POST['bio']);
    $address = sanitize_input($_POST['address']);
    $academic_rank = isset($_POST['academic_rank']) ? sanitize_input($_POST['academic_rank']) : '';
    $specialization = isset($_POST['specialization']) ? sanitize_input($_POST['specialization']) : '';
    $years_of_experience = isset($_POST['years_of_experience']) ? (int)$_POST['years_of_experience'] : 0;
    $appointment_date = isset($_POST['appointment_date']) ? sanitize_input($_POST['appointment_date']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate form data
    $errors = [];

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    } elseif ($email !== $user['email']) {
        // Check if email already exists
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $errors[] = 'Email already exists.';
        }
    }

    // Validate password change if requested
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify_custom($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (empty($new_password)) {
            $errors[] = 'New password is required.';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirm password do not match.';
        }
    }

    // If no errors, update user
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Update user information
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash_custom($new_password);
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, bio = ?, address = ?, password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $full_name, $email, $phone, $bio, $address, $hashed_password, $user_id);
            } else {
                // Update without changing password
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, bio = ?, address = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $bio, $address, $user_id);
            }

            $stmt->execute();

            // Update or insert staff profile
            if ($profile) {
                // Update existing profile
                $sql = "UPDATE staff_profiles SET academic_rank = ?, specialization = ?, years_of_experience = ?, appointment_date = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisi", $academic_rank, $specialization, $years_of_experience, $appointment_date, $user_id);
            } else {
                // Insert new profile
                $sql = "INSERT INTO staff_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issis", $user_id, $academic_rank, $specialization, $years_of_experience, $appointment_date);
            }

            $stmt->execute();

            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = BASE_PATH . '/uploads/profiles/';
                $temp_file = $_FILES['profile_image']['tmp_name'];
                $file_name = time() . '_' . $_FILES['profile_image']['name'];
                $file_path = $upload_dir . $file_name;

                // Check if file is an image
                $check = getimagesize($temp_file);
                if ($check !== false) {
                    // Move uploaded file
                    if (move_uploaded_file($temp_file, $file_path)) {
                        // Update user profile image
                        $sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $file_name, $user_id);
                        $stmt->execute();

                        // Update session variable
                        $_SESSION['profile_image'] = $file_name;
                    } else {
                        $errors[] = 'Failed to upload profile image.';
                    }
                } else {
                    $errors[] = 'File is not an image.';
                }
            }

            // Commit transaction
            $conn->commit();

            // Update session variables
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;

            // Set success message
            $success_message = 'Profile updated successfully.';

            // Refresh user data
            $sql = "SELECT u.*, d.name as department_name, c.name as college_name
                    FROM users u
                    LEFT JOIN departments d ON u.department_id = d.department_id
                    LEFT JOIN colleges c ON u.college_id = c.college_id
                    WHERE u.user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
            }

            // Refresh profile data
            $sql = "SELECT * FROM staff_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $profile = $result->fetch_assoc();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = 'Error updating profile: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Please fix the following errors:<br>' . implode('<br>', $errors);
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
            <div class="col-lg-4">
                <!-- Profile Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Profile Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $user['profile_image']; ?>" alt="<?php echo $user['full_name']; ?>" class="img-profile rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h5 class="text-center mb-3"><?php echo $user['full_name']; ?></h5>
                        <p class="text-center text-muted mb-4"><?php echo $user['position'] ?: 'Staff Member'; ?></p>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Department:</h6>
                            <p><?php echo $user['department_name'] ?: 'N/A'; ?></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">College:</h6>
                            <p><?php echo $user['college_name'] ?: 'N/A'; ?></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Email:</h6>
                            <p><?php echo $user['email']; ?></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Phone:</h6>
                            <p><?php echo $user['phone'] ?: 'N/A'; ?></p>
                        </div>

                        <?php if ($profile): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Academic Rank:</h6>
                                <p><?php echo $profile['academic_rank'] ?: 'N/A'; ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="font-weight-bold">Specialization:</h6>
                                <p><?php echo $profile['specialization'] ?: 'N/A'; ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="font-weight-bold">Years of Experience:</h6>
                                <p><?php echo $profile['years_of_experience'] ?: 'N/A'; ?></p>
                            </div>

                            <div class="mb-3">
                                <h6 class="font-weight-bold">Appointment Date:</h6>
                                <p><?php echo $profile['appointment_date'] ? date('F d, Y', strtotime($profile['appointment_date'])) : 'N/A'; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Edit Profile Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Edit Profile</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo $base_url; ?>staff/profile.php" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_image">Profile Image</label>
                                        <input type="file" class="form-control-file" id="profile_image" name="profile_image">
                                        <small class="form-text text-muted">Upload a new profile image (optional).</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo $user['bio']; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo $user['address']; ?></textarea>
                            </div>

                            <h5 class="mt-4 mb-3">Academic Information</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="academic_rank">Academic Rank</label>
                                        <select class="form-control" id="academic_rank" name="academic_rank">
                                            <option value="">Select Rank</option>
                                            <option value="Professor" <?php echo ($profile && $profile['academic_rank'] === 'Professor') ? 'selected' : ''; ?>>Professor</option>
                                            <option value="Associate Professor" <?php echo ($profile && $profile['academic_rank'] === 'Associate Professor') ? 'selected' : ''; ?>>Associate Professor</option>
                                            <option value="Assistant Professor" <?php echo ($profile && $profile['academic_rank'] === 'Assistant Professor') ? 'selected' : ''; ?>>Assistant Professor</option>
                                            <option value="Lecturer" <?php echo ($profile && $profile['academic_rank'] === 'Lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                                            <option value="Assistant Lecturer" <?php echo ($profile && $profile['academic_rank'] === 'Assistant Lecturer') ? 'selected' : ''; ?>>Assistant Lecturer</option>
                                            <option value="Graduate Assistant" <?php echo ($profile && $profile['academic_rank'] === 'Graduate Assistant') ? 'selected' : ''; ?>>Graduate Assistant</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="specialization">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo $profile ? $profile['specialization'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="years_of_experience">Years of Experience</label>
                                        <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" value="<?php echo $profile ? $profile['years_of_experience'] : ''; ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appointment_date">Appointment Date</label>
                                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="<?php echo $profile ? $profile['appointment_date'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4 mb-3">Change Password</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>
                            </div>

                            <div class="text-right mt-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
