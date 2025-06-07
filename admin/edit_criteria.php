<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Edit Evaluation Criteria
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
$criteria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if criteria_id is provided
if ($criteria_id <= 0) {
    redirect($base_url . 'admin/evaluation_criteria.php');
}

// Get criteria information
$criteria = null;
$sql = "SELECT * FROM evaluation_criteria WHERE criteria_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $criteria_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $criteria = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/evaluation_criteria.php');
}

// Get all categories
$categories = [];
$sql = "SELECT * FROM evaluation_categories ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Initialize form variables
$name = $criteria['name'];
$category_id = $criteria['category_id'];
$description = $criteria['description'];
$weight = $criteria['weight'];
$min_rating = $criteria['min_rating'];
$max_rating = $criteria['max_rating'];
$evaluator_role = isset($criteria['evaluator_role']) ? $criteria['evaluator_role'] : '';
$target_role = isset($criteria['target_role']) ? $criteria['target_role'] : '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $category_id = sanitize_input($_POST['category_id']);
    $description = sanitize_input($_POST['description']);
    $weight = sanitize_input($_POST['weight']);
    $min_rating = sanitize_input($_POST['min_rating']);
    $max_rating = sanitize_input($_POST['max_rating']);
    $evaluator_role = sanitize_input($_POST['evaluator_role']);
    $target_role = sanitize_input($_POST['target_role']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required.";
    }
    
    if (empty($weight) || !is_numeric($weight) || $weight <= 0) {
        $errors[] = "Weight must be a positive number.";
    }
    
    if (empty($min_rating) || !is_numeric($min_rating) || $min_rating < 0) {
        $errors[] = "Minimum rating must be a non-negative number.";
    }
    
    if (empty($max_rating) || !is_numeric($max_rating) || $max_rating <= $min_rating) {
        $errors[] = "Maximum rating must be greater than minimum rating.";
    }
    
    if (empty($evaluator_role)) {
        $errors[] = "Evaluator role is required.";
    }
    
    if (empty($target_role)) {
        $errors[] = "Target role is required.";
    }
    
    // If no errors, update criteria
    if (empty($errors)) {
        // Check if evaluator_role and target_role columns exist
        $columns_exist = true;
        $result = $conn->query("SHOW COLUMNS FROM evaluation_criteria LIKE 'evaluator_role'");
        if (!$result || $result->num_rows === 0) {
            $columns_exist = false;
            // Add evaluator_role column
            $conn->query("ALTER TABLE evaluation_criteria ADD COLUMN evaluator_role VARCHAR(50) AFTER max_rating");
        }
        
        $result = $conn->query("SHOW COLUMNS FROM evaluation_criteria LIKE 'target_role'");
        if (!$result || $result->num_rows === 0) {
            $columns_exist = false;
            // Add target_role column
            $conn->query("ALTER TABLE evaluation_criteria ADD COLUMN target_role VARCHAR(50) AFTER evaluator_role");
        }
        
        $sql = "UPDATE evaluation_criteria SET 
                category_id = ?, 
                name = ?, 
                description = ?, 
                weight = ?, 
                min_rating = ?, 
                max_rating = ?, 
                evaluator_role = ?, 
                target_role = ? 
                WHERE criteria_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issdddssi", $category_id, $name, $description, $weight, $min_rating, $max_rating, $evaluator_role, $target_role, $criteria_id);
        
        if ($stmt->execute()) {
            $success_message = "Evaluation criteria updated successfully.";
            
            // Refresh criteria data
            $sql = "SELECT * FROM evaluation_criteria WHERE criteria_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $criteria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $criteria = $result->fetch_assoc();
                
                // Update form variables
                $name = $criteria['name'];
                $category_id = $criteria['category_id'];
                $description = $criteria['description'];
                $weight = $criteria['weight'];
                $min_rating = $criteria['min_rating'];
                $max_rating = $criteria['max_rating'];
                $evaluator_role = isset($criteria['evaluator_role']) ? $criteria['evaluator_role'] : '';
                $target_role = isset($criteria['target_role']) ? $criteria['target_role'] : '';
            }
        } else {
            $error_message = "Error updating evaluation criteria: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Edit Evaluation Criteria</h1>
            <a href="<?php echo $base_url; ?>admin/evaluation_criteria.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Criteria
            </a>
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

        <!-- Edit Criteria Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Evaluation Criteria</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $criteria_id); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Criteria Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a criteria name.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Category <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo ($category_id == $category['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo $category['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a category.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="weight">Weight <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="weight" name="weight" value="<?php echo $weight; ?>" step="0.01" min="0.01" required>
                                <div class="invalid-feedback">
                                    Please provide a positive weight value.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="min_rating">Minimum Rating <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="min_rating" name="min_rating" value="<?php echo $min_rating; ?>" min="0" required>
                                <div class="invalid-feedback">
                                    Please provide a non-negative minimum rating.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_rating">Maximum Rating <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="max_rating" name="max_rating" value="<?php echo $max_rating; ?>" min="1" required>
                                <div class="invalid-feedback">
                                    Please provide a maximum rating greater than minimum rating.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="evaluator_role">Evaluator Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="evaluator_role" name="evaluator_role" required>
                                    <option value="">Select Evaluator Role</option>
                                    <option value="head_of_department" <?php echo ($evaluator_role == 'head_of_department') ? 'selected' : ''; ?>>Head of Department</option>
                                    <option value="dean" <?php echo ($evaluator_role == 'dean') ? 'selected' : ''; ?>>Dean</option>
                                    <option value="college" <?php echo ($evaluator_role == 'college') ? 'selected' : ''; ?>>College</option>
                                    <option value="hrm" <?php echo ($evaluator_role == 'hrm') ? 'selected' : ''; ?>>HRM</option>
                                    <option value="all" <?php echo ($evaluator_role == 'all' || empty($evaluator_role)) ? 'selected' : ''; ?>>All</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select an evaluator role.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="target_role">Target Role <span class="text-danger">*</span></label>
                                <select class="form-control" id="target_role" name="target_role" required>
                                    <option value="">Select Target Role</option>
                                    <option value="head_of_department" <?php echo ($target_role == 'head_of_department') ? 'selected' : ''; ?>>Head of Department</option>
                                    <option value="dean" <?php echo ($target_role == 'dean') ? 'selected' : ''; ?>>Dean</option>
                                    <option value="college" <?php echo ($target_role == 'college') ? 'selected' : ''; ?>>College</option>
                                    <option value="hrm" <?php echo ($target_role == 'hrm') ? 'selected' : ''; ?>>HRM</option>
                                    <option value="all" <?php echo ($target_role == 'all' || empty($target_role)) ? 'selected' : ''; ?>>All</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a target role.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Criteria
                        </button>
                        <a href="<?php echo $base_url; ?>admin/evaluation_criteria.php" class="btn btn-secondary">
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
