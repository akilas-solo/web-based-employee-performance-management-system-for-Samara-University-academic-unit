<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Add Evaluation Criteria
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
$name = '';
$category_id = '';
$description = '';
$weight = '1.00';
$min_rating = '1';
$max_rating = '5';
$evaluator_role = '';
$target_role = '';
$success_message = '';
$error_message = '';
$errors = [];

// Get all categories
$categories = [];
$sql = "SELECT * FROM evaluation_categories ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

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

    // If no errors, insert criteria
    if (empty($errors)) {
        // Check which columns exist in the table
        $columns_exist = [];
        $result = $conn->query("SHOW COLUMNS FROM evaluation_criteria");
        while ($row = $result->fetch_assoc()) {
            $columns_exist[] = $row['Field'];
        }

        // Determine which column names to use
        if (in_array('evaluator_role', $columns_exist) && in_array('target_role', $columns_exist)) {
            // Use singular column names
            $sql = "INSERT INTO evaluation_criteria (category_id, name, description, weight, min_rating, max_rating, evaluator_role, target_role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdddss", $category_id, $name, $description, $weight, $min_rating, $max_rating, $evaluator_role, $target_role);
        } elseif (in_array('evaluator_roles', $columns_exist) && in_array('evaluatee_roles', $columns_exist)) {
            // Use plural column names
            $sql = "INSERT INTO evaluation_criteria (category_id, name, description, weight, min_rating, max_rating, evaluator_roles, evaluatee_roles)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdddss", $category_id, $name, $description, $weight, $min_rating, $max_rating, $evaluator_role, $target_role);
        } else {
            // Fallback: insert without role columns
            $sql = "INSERT INTO evaluation_criteria (category_id, name, description, weight, min_rating, max_rating)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issddd", $category_id, $name, $description, $weight, $min_rating, $max_rating);
        }

        if ($stmt->execute()) {
            $success_message = "Evaluation criteria added successfully.";
            // Reset form fields
            $name = '';
            $category_id = '';
            $description = '';
            $weight = '1.00';
            $min_rating = '1';
            $max_rating = '5';
            $evaluator_role = '';
            $target_role = '';
        } else {
            $error_message = "Error adding evaluation criteria: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Add Evaluation Criteria</h1>
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

        <!-- Add Criteria Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Add New Evaluation Criteria</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
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
                                </select>
                                <div class="invalid-feedback">
                                    Please select a target role.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Criteria
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
