<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Profile
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has dean role
if (!is_logged_in() || !has_role('dean')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$errors = [];
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];

// Get user information
$user = null;
$sql = "SELECT u.*, c.name as college_name
        FROM users u
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

// Get dean profile information
$dean_profile = null;
$table_exists = false;

// Check if dean_profiles table exists
$result = $conn->query("SHOW TABLES LIKE 'dean_profiles'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT * FROM dean_profiles WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $dean_profile = $result->fetch_assoc();
    }
}

// If dean_profiles table doesn't exist, create it
if (!$table_exists) {
    $sql = "CREATE TABLE dean_profiles (
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
    $conn->query($sql);
    $table_exists = true;
}

// Process form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $position = sanitize_input($_POST['position']);
    $bio = sanitize_input($_POST['bio']);
    $academic_rank = sanitize_input($_POST['academic_rank']);
    $specialization = sanitize_input($_POST['specialization']);
    $years_of_experience = (int)$_POST['years_of_experience'];
    $appointment_date = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
    
    // Validate form data
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $GLOBALS['BASE_PATH'] . '/uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPEG, PNG, and GIF images are allowed.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $errors[] = "File size exceeds the maximum limit of 2MB.";
        } else {
            // Generate unique filename
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'dean_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Delete old profile image if exists
                if (!empty($profile_image) && file_exists($upload_dir . $profile_image)) {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $new_filename;
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
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, position = ?, bio = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssi", $full_name, $email, $phone, $position, $bio, $profile_image, $user_id);
            $stmt->execute();
            
            // Update or insert dean profile
            if ($dean_profile) {
                $sql = "UPDATE dean_profiles SET academic_rank = ?, specialization = ?, years_of_experience = ?, appointment_date = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisi", $academic_rank, $specialization, $years_of_experience, $appointment_date, $user_id);
                $stmt->execute();
            } else {
                $sql = "INSERT INTO dean_profiles (user_id, academic_rank, specialization, years_of_experience, appointment_date) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issis", $user_id, $academic_rank, $specialization, $years_of_experience, $appointment_date);
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
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
            }
            
            // Refresh dean profile data
            $sql = "SELECT * FROM dean_profiles WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $dean_profile = $result->fetch_assoc();
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Failed to update profile: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}

// Include header
include_once $GLOBALS['BASE_PATH'] . '/includes/header_management.php';

// Include sidebar
include_once $GLOBALS['BASE_PATH'] . '/includes/sidebar.php';
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
            <!-- Profile Summary -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Profile Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($user['profile_image'])): ?>
                                <img class="img-profile rounded-circle mb-3" src="<?php echo $base_url; ?>uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile Image" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <h4 class="text-theme"><?php echo $user['full_name']; ?></h4>
                            <p class="text-muted"><?php echo $user['position'] ?? 'Dean'; ?></p>
                            <p><span class="badge badge-primary">Dean</span></p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">Contact Information</h6>
                            <p>
                                <i class="fas fa-envelope mr-2 text-theme"></i> <?php echo $user['email']; ?><br>
                                <?php if (!empty($user['phone'])): ?>
                                    <i class="fas fa-phone mr-2 text-theme"></i> <?php echo $user['phone']; ?><br>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="mb-3">
                            <h6 class="font-weight-bold">College</h6>
                            <p>
                                <i class="fas fa-university mr-2 text-theme"></i> <?php echo $user['college_name'] ?? 'Not assigned'; ?>
                            </p>
                        </div>

                        <?php if ($dean_profile): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Academic Information</h6>
                                <p>
                                    <?php if (!empty($dean_profile['academic_rank'])): ?>
                                        <i class="fas fa-graduation-cap mr-2 text-theme"></i> <?php echo $dean_profile['academic_rank']; ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['specialization'])): ?>
                                        <i class="fas fa-book mr-2 text-theme"></i> <?php echo $dean_profile['specialization']; ?><br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['years_of_experience'])): ?>
                                        <i class="fas fa-history mr-2 text-theme"></i> <?php echo $dean_profile['years_of_experience']; ?> years of experience<br>
                                    <?php endif; ?>
                                    <?php if (!empty($dean_profile['appointment_date'])): ?>
                                        <i class="fas fa-calendar-check mr-2 text-theme"></i> Appointed: <?php echo date('M d, Y', strtotime($dean_profile['appointment_date'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['bio'])): ?>
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Bio</h6>
                                <p><?php echo $user['bio']; ?></p>
                            </div>
                        <?php endif; ?>

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

            <!-- Edit Profile Form -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-theme text-white">
                        <h6 class="m-0 font-weight-bold">Edit Profile</h6>
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
                                        <label for="email">Email <span class="text-danger">*</span></label>
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
                                        <label for="position">Position</label>
                                        <input type="text" class="form-control" id="position" name="position" value="<?php echo $user['position']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="academic_rank">Academic Rank</label>
                                        <select class="form-control" id="academic_rank" name="academic_rank">
                                            <option value="">Select Academic Rank</option>
                                            <option value="Professor" <?php echo ($dean_profile && $dean_profile['academic_rank'] === 'Professor') ? 'selected' : ''; ?>>Professor</option>
                                            <option value="Associate Professor" <?php echo ($dean_profile && $dean_profile['academic_rank'] === 'Associate Professor') ? 'selected' : ''; ?>>Associate Professor</option>
                                            <option value="Assistant Professor" <?php echo ($dean_profile && $dean_profile['academic_rank'] === 'Assistant Professor') ? 'selected' : ''; ?>>Assistant Professor</option>
                                            <option value="Lecturer" <?php echo ($dean_profile && $dean_profile['academic_rank'] === 'Lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                                            <option value="Assistant Lecturer" <?php echo ($dean_profile && $dean_profile['academic_rank'] === 'Assistant Lecturer') ? 'selected' : ''; ?>>Assistant Lecturer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="specialization">Specialization</label>
                                        <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo $dean_profile ? $dean_profile['specialization'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="years_of_experience">Years of Experience</label>
                                        <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" min="0" value="<?php echo $dean_profile ? $dean_profile['years_of_experience'] : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appointment_date">Appointment Date</label>
                                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" value="<?php echo $dean_profile ? $dean_profile['appointment_date'] : ''; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="profile_image" name="profile_image">
                                    <label class="custom-file-label" for="profile_image">Choose file</label>
                                </div>
                                <small class="form-text text-muted">Max file size: 2MB. Allowed formats: JPEG, PNG, GIF.</small>
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
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>
