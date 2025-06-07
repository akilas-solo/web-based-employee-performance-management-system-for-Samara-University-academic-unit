<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Edit Evaluation Period
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
$period_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if period_id is provided
if ($period_id <= 0) {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Get period information
$period = null;
$sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $period_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $period = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/evaluation_periods.php');
}

// Initialize form variables
$title = $period['title'];
$academic_year = $period['academic_year'];
$semester = $period['semester'];
$start_date = $period['start_date'];
$end_date = $period['end_date'];
$description = $period['description'];
$status = $period['status'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize_input($_POST['title']);
    $academic_year = sanitize_input($_POST['academic_year']);
    $semester = sanitize_input($_POST['semester']);
    $start_date = sanitize_input($_POST['start_date']);
    $end_date = sanitize_input($_POST['end_date']);
    $description = sanitize_input($_POST['description']);
    $status = isset($_POST['status']) ? 'active' : 'inactive';
    
    // Validate form data
    if (empty($title)) {
        $errors[] = "Title is required.";
    }
    
    if (empty($academic_year)) {
        $errors[] = "Academic year is required.";
    }
    
    if (empty($semester)) {
        $errors[] = "Semester is required.";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required.";
    }
    
    if (empty($end_date)) {
        $errors[] = "End date is required.";
    } elseif ($end_date < $start_date) {
        $errors[] = "End date must be after start date.";
    }
    
    // If no errors, update evaluation period
    if (empty($errors)) {
        $sql = "UPDATE evaluation_periods SET 
                title = ?, 
                academic_year = ?, 
                semester = ?, 
                start_date = ?, 
                end_date = ?, 
                description = ?, 
                status = ? 
                WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $title, $academic_year, $semester, $start_date, $end_date, $description, $status, $period_id);
        
        if ($stmt->execute()) {
            $success_message = "Evaluation period updated successfully.";
            
            // Refresh period data
            $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $period_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows === 1) {
                $period = $result->fetch_assoc();
                
                // Update form variables
                $title = $period['title'];
                $academic_year = $period['academic_year'];
                $semester = $period['semester'];
                $start_date = $period['start_date'];
                $end_date = $period['end_date'];
                $description = $period['description'];
                $status = $period['status'];
            }
        } else {
            $error_message = "Error updating evaluation period: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">Edit Evaluation Period</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/view_period.php?id=<?php echo $period_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                    <i class="fas fa-eye fa-sm text-white-50 mr-1"></i> View Period
                </a>
                <a href="<?php echo $base_url; ?>admin/evaluation_periods.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Periods
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

        <!-- Edit Evaluation Period Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Evaluation Period Information</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $period_id); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="title">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a title.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="academic_year">Academic Year <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo $academic_year; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide an academic year.
                                </div>
                                <small class="form-text text-muted">Format: YYYY-YYYY (e.g., 2022-2023).</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="semester">Semester <span class="text-danger">*</span></label>
                                <select class="form-control" id="semester" name="semester" required>
                                    <option value="">Select Semester</option>
                                    <option value="1" <?php echo ($semester == '1') ? 'selected' : ''; ?>>Semester 1</option>
                                    <option value="2" <?php echo ($semester == '2') ? 'selected' : ''; ?>>Semester 2</option>
                                    <option value="Summer" <?php echo ($semester == 'Summer') ? 'selected' : ''; ?>>Summer</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a semester.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a start date.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide an end date.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $description; ?></textarea>
                        <small class="form-text text-muted">A brief description of the evaluation period.</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="status" name="status" <?php echo ($status == 'active') ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="status">Active</label>
                        </div>
                        <small class="form-text text-muted">If active, users can conduct evaluations during this period.</small>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Update Evaluation Period
                        </button>
                        <a href="<?php echo $base_url; ?>admin/evaluation_periods.php" class="btn btn-secondary">
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
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set min date for end date based on start date
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            
            // If end date is before start date, reset it
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
