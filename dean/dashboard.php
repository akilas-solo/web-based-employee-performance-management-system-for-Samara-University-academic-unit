<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean Dashboard
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has dean role
if (!is_logged_in() || !has_role('dean')) {
    redirect($base_url . 'login.php');
}

// Get dean information
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$college_name = '';
$total_departments = 0;
$total_heads = 0;
$total_evaluations = 0;
$pending_evaluations = 0;

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

// Get total departments
$sql = "SELECT COUNT(*) as count FROM departments WHERE college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_departments = $row['count'];
}

// Get total department heads
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'head_of_department' AND college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $total_heads = $row['count'];
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

// Get department heads
$department_heads = [];
$sql = "SELECT u.*, d.name as department_name
        FROM users u
        JOIN departments d ON u.department_id = d.department_id
        WHERE u.role = 'head_of_department' AND u.college_id = ?
        ORDER BY u.full_name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $department_heads[] = $row;
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
include_once $GLOBALS['BASE_PATH'] . '/includes/header_management.php';

// Include sidebar
include_once $GLOBALS['BASE_PATH'] . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dean Dashboard</h1>
            <a href="<?php echo $base_url; ?>dean/reports.php" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm">
                <i class="fas fa-download fa-sm text-white-50 mr-1"></i> Generate Report
            </a>
        </div>

        <!-- Dean Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Dean Information</h6>
            </div>
            <div class="card-body">
                <h4 class="text-theme">Dean of <?php echo $college_name; ?></h4>
                <p class="mb-0">Welcome to the Dean Dashboard. Here you can manage department heads, conduct evaluations, and view reports.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <!-- Departments Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Departments</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_departments; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Heads Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-theme shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-theme text-uppercase mb-1">
                                    Department Heads</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_heads; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-tie fa-2x text-gray-300"></i>
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
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Department Heads -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Department Heads</h6>
                        <a href="<?php echo $base_url; ?>dean/department_heads.php" class="btn btn-sm btn-theme">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (count($department_heads) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Department</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($department_heads as $head): ?>
                                            <tr>
                                                <td><?php echo $head['full_name']; ?></td>
                                                <td><?php echo $head['department_name']; ?></td>
                                                <td><?php echo $head['email']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($head['status'] == 1) ? 'success' : 'danger'; ?>">
                                                        <?php echo ($head['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No department heads found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Evaluations -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Recent Evaluations</h6>
                        <a href="<?php echo $base_url; ?>dean/evaluations.php" class="btn btn-sm btn-theme">
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
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>
