<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Evaluations
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Get all evaluation periods
$periods = [];
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
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

// Get departments based on college selection
$departments = [];
$sql = "SELECT * FROM departments";
if ($college_id > 0) {
    $sql .= " WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get evaluations with filters
$evaluations = [];
$sql = "SELECT e.*,
        u1.full_name as evaluator_name,
        u2.full_name as evaluatee_name,
        u1.role as evaluator_role,
        u2.role as evaluatee_role,
        d.name as department_name,
        c.name as college_name,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        LEFT JOIN departments d ON u2.department_id = d.department_id
        LEFT JOIN colleges c ON u2.college_id = c.college_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE 1=1";

// Add filters
$params = [];
$types = "";

if ($period_id > 0) {
    $sql .= " AND e.period_id = ?";
    $params[] = $period_id;
    $types .= "i";
}

if ($college_id > 0) {
    $sql .= " AND u2.college_id = ?";
    $params[] = $college_id;
    $types .= "i";
}

if ($department_id > 0) {
    $sql .= " AND u2.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
}

if (!empty($status)) {
    $sql .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY e.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Evaluations</h1>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Filter Evaluations</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                    <div class="form-group mb-2 mr-2">
                        <label for="period_id" class="mr-2">Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title'] . ' (' . $period['academic_year'] . ', Semester ' . $period['semester'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="college_id" class="mr-2">College:</label>
                        <select class="form-control" id="college_id" name="college_id">
                            <option value="0">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo $college['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="department_id" class="mr-2">Department:</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-2 mr-2">
                        <label for="status" class="mr-2">Status:</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo ($status == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="submitted" <?php echo ($status == 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                            <option value="reviewed" <?php echo ($status == 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-theme mb-2">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <a href="<?php echo $base_url; ?>hrm/evaluations.php" class="btn btn-secondary mb-2 ml-2">
                        <i class="fas fa-sync-alt mr-1"></i> Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Evaluations</h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department/College</th>
                                    <th>Period</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($evaluations as $evaluation): ?>
                                    <tr>
                                        <td>
                                            <?php echo $evaluation['evaluator_name']; ?>
                                            <br>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $evaluation['evaluatee_name']; ?>
                                            <br>
                                            <span class="badge badge-secondary">
                                                <?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $evaluation['department_name']; ?>
                                            <br>
                                            <small><?php echo $evaluation['college_name']; ?></small>
                                        </td>
                                        <td>
                                            <?php echo $evaluation['period_title']; ?>
                                            <br>
                                            <small><?php echo $evaluation['academic_year'] . ', Semester ' . $evaluation['semester']; ?></small>
                                        </td>
                                        <td><?php echo number_format($evaluation['total_score'], 2); ?>/5</td>
                                        <td>
                                            <span class="badge badge-<?php
                                                switch ($evaluation['status']) {
                                                    case 'draft':
                                                        echo 'secondary';
                                                        break;
                                                    case 'submitted':
                                                        echo 'info';
                                                        break;
                                                    case 'reviewed':
                                                        echo 'warning';
                                                        break;
                                                    case 'approved':
                                                        echo 'success';
                                                        break;
                                                    case 'rejected':
                                                        echo 'danger';
                                                        break;
                                                    default:
                                                        echo 'secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($evaluation['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?>
                                            <?php if (!empty($evaluation['submission_date'])): ?>
                                                <br>
                                                <small>Submitted: <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($evaluation['status'] === 'submitted'): ?>
                                                <a href="<?php echo $base_url; ?>hrm/review_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-warning" title="Review">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluations found matching the selected criteria.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    $base_url . 'assets/js/datatables.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#evaluationsTable').DataTable();

        // Handle college change to update departments
        $('#college_id').change(function() {
            var collegeId = $(this).val();
            if (collegeId > 0) {
                // AJAX call to get departments for selected college
                $.ajax({
                    url: '<?php echo $base_url; ?>includes/ajax_handlers.php',
                    type: 'POST',
                    data: {
                        action: 'get_departments',
                        college_id: collegeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        var departmentSelect = $('#department_id');
                        departmentSelect.empty();
                        departmentSelect.append('<option value="0">All Departments</option>');

                        if (response.success && response.departments.length > 0) {
                            $.each(response.departments, function(index, department) {
                                departmentSelect.append('<option value="' + department.department_id + '">' + department.name + '</option>');
                            });
                        }
                    }
                });
            } else {
                // Reset departments dropdown
                $('#department_id').html('<option value="0">All Departments</option>');
            }
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
