<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Edit Department
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
$department_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if department_id is provided
if ($department_id <= 0) {
    redirect($base_url . 'admin/departments.php');
}

// Get department information
$department = null;
$sql = "SELECT * FROM departments WHERE department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $department = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/departments.php');
}

// Get all colleges
$colleges = [];
$sql = "SELECT * FROM colleges ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Initialize form variables
$name = $department['name'];
$code = $department['code'];
$college_id = $department['college_id'];
$description = $department['description'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $code = sanitize_input($_POST['code']);
    $college_id = sanitize_input($_POST['college_id']);
    $description = sanitize_input($_POST['description']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Department name is required.";
    }
    
    if (empty($code)) {
        $errors[] = "Department code is required.";
    } else {
        // Check if code already exists (excluding current department)
        $sql = "SELECT * FROM departments WHERE code = ? AND department_id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $code, $department_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Department code already exists.";
        }
    }
    
    if (empty($college_id)) {
        $errors[] = "College is required.";
    }
    
    // If no errors, update department
    if (empty($errors)) {
        $sql = "UPDATE departments SET name = ?, code = ?, college_id = ?, description = ? WHERE department_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisi", $name, $code, $college_id, $description, $department_id);
        
        if ($stmt->execute()) {
            $success_message = "Department updated successfully.";
            
            // Refresh department data
            $sql = "SELECT * FROM departments WHERE department_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $department = $result->fetch_assoc();
                
                // Update form variables
                $name = $department['name'];
                $code = $department['code'];
                $college_id = $department['college_id'];
                $description = $department['description'];
            }
        } else {
            $error_message = "Error updating department: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Edit Department</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/view_department.php?id=<?php echo $department_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                    <i class="fas fa-eye fa-sm text-white-50 mr-1"></i> View Department
                </a>
                <a href="<?php echo $base_url; ?>admin/departments.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Departments
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

        <!-- Edit Department Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Department Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $department_id); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Department Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a department name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="code">Department Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" value="<?php echo $code; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a department code.
                                </div>
                                <small class="form-text text-muted">A unique code for the department (e.g., CSE, EEE, ME).</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="college_id">College <span class="text-danger">*</span></label>
                        <select class="form-control" id="college_id" name="college_id" required>
                            <option value="">Select College</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo $college['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a college.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                        <small class="form-text text-muted">A brief description of the department.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Department
                        </button>
                        <a href="<?php echo $base_url; ?>admin/departments.php" class="btn btn-secondary">
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
