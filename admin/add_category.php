<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Add Evaluation Category
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
$description = '';
$weight = '1.00';
$success_message = '';
$error_message = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $weight = isset($_POST['weight']) ? (float)$_POST['weight'] : 1.00;
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Category name is required.";
    } else {
        // Check if category name already exists
        $sql = "SELECT * FROM evaluation_categories WHERE name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Category name already exists.";
        }
    }
    
    if ($weight <= 0) {
        $errors[] = "Weight must be greater than 0.";
    }
    
    // If no errors, insert category
    if (empty($errors)) {
        // Check if the weight column exists in the table
        $column_exists = false;
        $result = $conn->query("SHOW COLUMNS FROM evaluation_categories LIKE 'weight'");
        if ($result && $result->num_rows > 0) {
            $column_exists = true;
        }
        
        if (!$column_exists) {
            // If weight column doesn't exist, add it
            $conn->query("ALTER TABLE evaluation_categories ADD COLUMN weight DECIMAL(5,2) DEFAULT 1.00 AFTER description");
        }
        
        $sql = "INSERT INTO evaluation_categories (name, description, weight) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssd", $name, $description, $weight);
        
        if ($stmt->execute()) {
            $success_message = "Evaluation category added successfully.";
            
            // Reset form fields
            $name = '';
            $description = '';
            $weight = '1.00';
        } else {
            $error_message = "Error adding evaluation category: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Add New Evaluation Category</h1>
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

        <!-- Add Category Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Category Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                    <div class="form-group">
                        <label for="name">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                        <div class="invalid-feedback">
                            Please provide a category name.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                        <small class="form-text text-muted">A brief description of the evaluation category.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="weight" name="weight" value="<?php echo $weight; ?>" step="0.01" min="0.01" required>
                        <div class="invalid-feedback">
                            Please provide a valid weight (greater than 0).
                        </div>
                        <small class="form-text text-muted">The weight of this category in the overall evaluation (e.g., 1.00, 1.50, 2.00).</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save Category
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
