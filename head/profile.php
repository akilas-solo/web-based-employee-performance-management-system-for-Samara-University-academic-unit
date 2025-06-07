<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Profile
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
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
    $error_message = "User information not found.";
}

// Get head of department profile information
$head_profile = null;

// Check if head_profiles table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'head_profiles'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if (!$table_exists) {
    // Create head_profiles table if it doesn't exist
    $sql = "CREATE TABLE head_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            academic_rank VARCHAR(100),
            specialization VARCHAR(255),
            years_of_experience INT DEFAULT 0,
            appointment_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
    $conn->query($sql);
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM head_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $head_profile = $result->fetch_assoc();
        }
    }
}

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $position = sanitize_input($_POST['position']);
    $academic_rank = sanitize_input($_POST['academic_rank']);
    $specialization = sanitize_input($_POST['specialization']);
    $years_of_experience = (int)$_POST['years_of_experience'];
    $appointment_date = sanitize_input($_POST['appointment_date']);
    $bio = sanitize_input($_POST['bio']);

    // Validate form data
    if (empty($full_name) || empty($email)) {
        $error_message = "Full name and email are required.";
    } else {
        // Check if email already exists for another user
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $error_message = "Email already exists for another user.";
        } else {
            // Start transaction
            $conn->begin_transaction();

            try {
                // Update user information
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, position = ?, bio = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $full_name, $email, $phone, $position, $bio, $user_id);
                $stmt->execute();

                // Check if head_profiles table exists
                $table_exists = false;
                $result = $conn->query("SHOW TABLES LIKE 'head_profiles'");
                if ($result && $result->num_rows > 0) {
                    $table_exists = true;
                }

                if (!$table_exists) {
                    // Create head_profiles table if it doesn't exist
                    $sql = "CREATE TABLE head_profiles (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            academic_rank VARCHAR(100),
                            specialization VARCHAR(255),
                            years_of_experience INT DEFAULT 0,
                            appointment_date DATE,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                        )";
                    $conn->query($sql);
                    $table_exists = true;
                }

                if ($table_exists) {
                    // Check if head profile exists
                    if ($head_profile) {
                        // Update head profile
                        $sql = "UPDATE head_profiles SET academic_rank = ?, specialization = ?, years_of_experience = ?, appointment_date = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("ssisi", $academic_rank, $specialization, $years_of_experience, $appointment_date, $user_id);
                            $stmt->execute();
                        }
                    } else {
                        // Insert head profile
                        $sql = "INSERT INTO head_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date) VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("issis", $user_id, $academic_rank, $specialization, $years_of_experience, $appointment_date);
                            $stmt->execute();
                        }
                    }
                }

                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = BASE_PATH . '/uploads/profiles/';

                    // Create uploads directory if it doesn't exist
                    if (!file_exists(BASE_PATH . '/uploads')) {
                        mkdir(BASE_PATH . '/uploads', 0755, true);
                    }

                    // Create profiles directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $temp_file = $_FILES['profile_image']['tmp_name'];
                    $file_name = time() . '_' . $_FILES['profile_image']['name'];
                    $file_path = $upload_dir . $file_name;

                    // Check file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = mime_content_type($temp_file);
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Invalid file type. Only JPEG, PNG, and GIF images are allowed.");
                    }

                    // Check file size (max 2MB)
                    if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                        throw new Exception("File size exceeds the limit of 2MB.");
                    }

                    // Move uploaded file
                    if (move_uploaded_file($temp_file, $file_path)) {
                        // Delete old profile image if exists
                        if (!empty($user['profile_image'])) {
                            $old_file_path = $upload_dir . $user['profile_image'];
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                            }
                        }

                        // Update profile image in database
                        $sql = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("si", $file_name, $user_id);
                        $stmt->execute();

                        // Update session variable
                        $_SESSION['profile_image'] = $file_name;
                    } else {
                        throw new Exception("Failed to upload profile image.");
                    }
                }

                // Commit transaction
                $conn->commit();

                // Update session variables
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;

                // Set success message
                $success_message = "Profile updated successfully.";

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

                // Refresh head profile data
                if ($table_exists) {
                    $sql = "SELECT * FROM head_profiles WHERE user_id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows === 1) {
                            $head_profile = $result->fetch_assoc();
                        }
                    }
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $error_message = "Error updating profile: " . $e->getMessage();
            }
        }
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
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Profile Information</h6>
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
                            <h4 class="mt-3 text-theme"><?php echo $user['full_name']; ?></h4>
                            <p class="text-muted"><?php echo $user['position'] ?? 'Head of Department'; ?></p>
                            <p>
                                <span class="badge badge-<?php echo ($user['status'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>

                        <hr>

                        <div class="profile-details">
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-envelope mr-2"></i> Email:</div>
                                <div class="detail-value"><?php echo $user['email']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-phone mr-2"></i> Phone:</div>
                                <div class="detail-value"><?php echo $user['phone'] ?? 'N/A'; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-building mr-2"></i> Department:</div>
                                <div class="detail-value"><?php echo $user['department_name']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-university mr-2"></i> College:</div>
                                <div class="detail-value"><?php echo $user['college_name']; ?></div>
                            </div>
                            <?php if ($head_profile): ?>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-graduation-cap mr-2"></i> Academic Rank:</div>
                                    <div class="detail-value"><?php echo $head_profile['academic_rank'] ?? 'N/A'; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-book mr-2"></i> Specialization:</div>
                                    <div class="detail-value"><?php echo $head_profile['specialization'] ?? 'N/A'; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-briefcase mr-2"></i> Experience:</div>
                                    <div class="detail-value"><?php echo $head_profile['years_of_experience'] ?? 'N/A'; ?> years</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-calendar-alt mr-2"></i> Appointed:</div>
                                    <div class="detail-value"><?php echo isset($head_profile['appointment_date']) ? date('M d, Y', strtotime($head_profile['appointment_date'])) : 'N/A'; ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-calendar-plus mr-2"></i> Joined:</div>
                                <div class="detail-value"><?php echo isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></div>
                            </div>
                        </div>

                        <?php if (!empty($user['bio'])): ?>
                            <hr>
                            <div class="bio-section">
                                <h6 class="font-weight-bold">About Me</h6>
                                <p><?php echo nl2br($user['bio']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Edit Profile</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" value="<?php echo $user['position'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="academic_rank">Academic Rank</label>
                                        <select class="form-control" id="academic_rank" name="academic_rank">
                                            <option value="">Select Academic Rank</option>
                                            <option value="Professor" <?php echo (isset($head_profile['academic_rank']) && $head_profile['academic_rank'] === 'Professor') ? 'selected' : ''; ?>>Professor</option>
                                            <option value="Associate Professor" <?php echo (isset($head_profile['academic_rank']) && $head_profile['academic_rank'] === 'Associate Professor') ? 'selected' : ''; ?>>Associate Professor</option>
                                            <option value="Assistant Professor" <?php echo (isset($head_profile['academic_rank']) && $head_profile['academic_rank'] === 'Assistant Professor') ? 'selected' : ''; ?>>Assistant Professor</option>
                                            <option value="Lecturer" <?php echo (isset($head_profile['academic_rank']) && $head_profile['academic_rank'] === 'Lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                                            <option value="Assistant Lecturer" <?php echo (isset($head_profile['academic_rank']) && $head_profile['academic_rank'] === 'Assistant Lecturer') ? 'selected' : ''; ?>>Assistant Lecturer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="specialization">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo $head_profile['specialization'] ?? ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="years_of_experience">Years of Experience</label>
                                        <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" value="<?php echo $head_profile['years_of_experience'] ?? ''; ?>" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appointment_date">Appointment Date</label>
                                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="<?php echo $head_profile['appointment_date'] ?? ''; ?>">
                                    </div>
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

                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $user['bio'] ?? ''; ?></textarea>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-theme">
                                <i class="fas fa-save mr-1"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-details {
        font-size: 0.9rem;
    }

    .detail-item {
        display: flex;
        margin-bottom: 10px;
    }

    .detail-label {
        font-weight: bold;
        width: 120px;
    }

    .detail-value {
        flex: 1;
    }
</style>

<script>
    // Show filename in custom file input
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.custom-file-input').addEventListener('change', function(e) {
            var fileName = e.target.files[0].name;
            var nextSibling = e.target.nextElementSibling;
            nextSibling.innerText = fileName;
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
