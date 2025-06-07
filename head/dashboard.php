<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department Dashboard
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
    redirect($base_url . 'login.php');
}

// Get head information
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$college_id = $_SESSION['college_id'];
$department_name = '';
$college_name = '';
$total_staff = 0;
$total_evaluations = 0;
$pending_evaluations = 0;
$completed_evaluations = 0;

// Get department name
if ($department_id) {
    $sql = "SELECT name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $department_name = $row['name'];
    }
}

// Get college name
if ($college_id) {
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

// Get total staff
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_staff = $row['count'];
}

// Get total evaluations
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluator_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_evaluations = $row['count'];
}

// Get pending evaluations
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluator_id = ? AND status = 'draft'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $pending_evaluations = $row['count'];
}

// Get completed evaluations
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluator_id = ? AND status IN ('submitted', 'reviewed', 'approved')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $completed_evaluations = $row['count'];
}

// Get staff members
$staff_members = [];
$sql = "SELECT * FROM users WHERE role = 'staff' AND department_id = ? ORDER BY full_name ASC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $staff_members[] = $row;
    }
}

// Get recent evaluations
$recent_evaluations = [];
$sql = "SELECT e.*, u.full_name as evaluatee_name
        FROM evaluations e
        JOIN users u ON e.evaluatee_id = u.user_id
        WHERE e.evaluator_id = ?
        ORDER BY e.created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_evaluations[] = $row;
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
            <h1 class="h3 mb-0 text-gray-800">Head of Department Dashboard</h1>
            <a href="<?php echo $base_url; ?>head/reports.php" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
                <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Generate Report
            </a>
        </div>

        <!-- Department Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Department Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme"><?php echo $department_name; ?></h4>
                <p>College: <?php echo $college_name; ?></p>
                <p class="mb-0">Welcome to the Head of Department Dashboard. Here you can manage staff, conduct evaluations, and view reports.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <!-- Staff Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Staff Members</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_staff; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Evaluations Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Total Evaluations</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_evaluations; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Evaluations Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Pending Evaluations</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_evaluations; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Evaluations Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Completed Evaluations</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_evaluations; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Staff Members -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Staff Members</h6>
                        <a href="<?php echo $base_url; ?>head/staff.php" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($staff_members) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staff_members as $staff): ?>
                                            <tr>
                                                <td><?php echo $staff['full_name']; ?></td>
                                                <td><?php echo $staff['email']; ?></td>
                                                <td><?php echo $staff['position'] ?? 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($staff['status'] == 1) ? 'success' : 'danger'; ?>">
                                                        <?php echo ($staff['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                                    </span>
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

            <!-- Recent Evaluations -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>head/evaluations.php" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Evaluatee</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td><?php echo $evaluation['evaluatee_name']; ?></td>
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
                                                <td><?php echo $evaluation['total_score']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No evaluations found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
