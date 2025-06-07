<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Edit College
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
$success_message = '';
$error_message = '';
$errors = [];
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if college_id is provided
if ($college_id <= 0) {
    redirect($base_url . 'admin/colleges.php');
}

// Get college information
$college = null;
$sql = "SELECT * FROM colleges WHERE college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $college = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/colleges.php');
}

// Initialize form variables
$name = $college['name'];
$code = $college['code'];
$description = $college['description'];
$vision = $college['vision'];
$mission = $college['mission'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $code = sanitize_input($_POST['code']);
    $description = sanitize_input($_POST['description']);
    $vision = sanitize_input($_POST['vision']);
    $mission = sanitize_input($_POST['mission']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "College name is required.";
    }
    
    if (empty($code)) {
        $errors[] = "College code is required.";
    } else {
        // Check if code already exists (excluding current college)
        $sql = "SELECT * FROM colleges WHERE code = ? AND college_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $code, $college_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "College code already exists.";
        }
    }
    
    // If no errors, update college
    if (empty($errors)) {
        $sql = "UPDATE colleges SET name = ?, code = ?, description = ?, vision = ?, mission = ? WHERE college_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $name, $code, $description, $vision, $mission, $college_id);
        
        if ($stmt->execute()) {
            $success_message = "College updated successfully.";
            
            // Refresh college data
            $sql = "SELECT * FROM colleges WHERE college_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $college_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $college = $result->fetch_assoc();
                
                // Update form variables
                $name = $college['name'];
                $code = $college['code'];
                $description = $college['description'];
                $vision = $college['vision'];
                $mission = $college['mission'];
            }
        } else {
            $error_message = "Error updating college: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Edit College</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/view_college.php?id=<?php echo $college_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                    <i class="fas fa-eye fa-sm text-white-50 mr-1"></i> View College
                </a>
                <a href="<?php echo $base_url; ?>admin/colleges.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Colleges
                </a>
            </div>
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

        <!-- Edit College Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">College Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $college_id); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">College Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a college name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">College Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" value="<?php echo $code; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a college code.
                                </div>
                                <small class="form-text text-muted">A unique code for the college (e.g., COE, COCS, COHS).</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                        <small class="form-text text-muted">A brief description of the college.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="vision">Vision</label>
                        <textarea class="form-control" id="vision" name="vision" rows="3"><?php echo $vision; ?></textarea>
                        <small class="form-text text-muted">The vision statement of the college.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="mission">Mission</label>
                        <textarea class="form-control" id="mission" name="mission" rows="3"><?php echo $mission; ?></textarea>
                        <small class="form-text text-muted">The mission statement of the college.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update College
                        </button>
                        <a href="<?php echo $base_url; ?>admin/colleges.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/form-validation.js'
];

// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
