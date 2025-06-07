<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Staff Management
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
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$department_name = '';
$college_name = '';

// Get department name if department_id is provided
if ($department_id > 0) {
    $sql = "SELECT d.*, c.name as college_name FROM departments d JOIN colleges c ON d.college_id = c.college_id WHERE d.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $department_name = $row['name'];
        $college_name = $row['college_name'];
        $college_id = $row['college_id'];
    }
}

// Get college name if college_id is provided
if ($college_id > 0 && empty($college_name)) {
    $sql = "SELECT name FROM colleges WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $college_name = $row['name'];
    }
}

// Get staff members based on filters
$staff = [];
$sql = "SELECT u.*, d.name as department_name, c.name as college_name, 
        (SELECT COUNT(*) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as evaluation_count,
        (SELECT AVG(e.total_score) FROM evaluations e WHERE e.evaluatee_id = u.user_id) as avg_score
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.department_id 
        LEFT JOIN colleges c ON u.college_id = c.college_id 
        WHERE u.role = 'staff'";

// Add filters
if ($department_id > 0) {
    $sql .= " AND u.department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
} elseif ($college_id > 0) {
    $sql .= " AND u.college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
} else {
    $stmt = $conn->prepare($sql);
}

$sql .= " ORDER BY c.name ASC, d.name ASC, u.full_name ASC";
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">
                <?php if ($department_id > 0): ?>
                    Staff in <?php echo $department_name; ?> Department
                <?php elseif ($college_id > 0): ?>
                    Staff in <?php echo $college_name; ?> College
                <?php else: ?>
                    All Staff Members
                <?php endif; ?>
            </h1>
            <?php if ($department_id > 0): ?>
                <a href="<?php echo $base_url; ?>hrm/departments.php?college_id=<?php echo $college_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Departments
                </a>
            <?php elseif ($college_id > 0): ?>
                <a href="<?php echo $base_url; ?>hrm/colleges.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Colleges
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Staff Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-theme">Staff Members</h6>
            </div>
            <div class="card-body">
                <?php if (count($staff) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="staffTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>College</th>
                                    <th>Evaluations</th>
                                    <th>Avg. Score</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td><?php echo $member['full_name']; ?></td>
                                        <td><?php echo $member['email']; ?></td>
                                        <td><?php echo $member['position'] ?? 'N/A'; ?></td>
                                        <td><?php echo $member['department_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $member['college_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $member['evaluation_count']; ?></td>
                                        <td>
                                            <?php if ($member['avg_score']): ?>
                                                <?php echo number_format($member['avg_score'], 2); ?>/5.00
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-info" title="View Report">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <a href="<?php echo $base_url; ?>hrm/evaluations.php?evaluatee_id=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-primary" title="View Evaluations">
                                                <i class="fas fa-clipboard-list"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No staff members found.</p>
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
        $('#staffTable').DataTable();
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
