<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - View User Details
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect($base_url . 'login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    redirect($base_url . 'admin/users.php');
}

// Get user data with department and college information
$user = null;
$sql = "SELECT u.*, d.name as department_name, c.name as college_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN colleges c ON u.college_id = c.college_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    redirect($base_url . 'admin/users.php');
}

// Get user's evaluation statistics
$evaluation_stats = [
    'total_evaluations' => 0,
    'as_evaluator' => 0,
    'as_evaluatee' => 0,
    'completed_evaluations' => 0,
    'pending_evaluations' => 0,
    'average_score' => 0
];

// Total evaluations as evaluator
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluator_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $evaluation_stats['as_evaluator'] = $row['count'];
}

// Total evaluations as evaluatee
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluatee_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $evaluation_stats['as_evaluatee'] = $row['count'];
}

// Completed evaluations (as evaluatee)
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluatee_id = ? AND status IN ('submitted', 'approved')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $evaluation_stats['completed_evaluations'] = $row['count'];
}

// Pending evaluations (as evaluatee)
$sql = "SELECT COUNT(*) as count FROM evaluations WHERE evaluatee_id = ? AND status = 'draft'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $evaluation_stats['pending_evaluations'] = $row['count'];
}

// Average score (as evaluatee)
$sql = "SELECT AVG(total_score) as avg_score FROM evaluations WHERE evaluatee_id = ? AND total_score > 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $evaluation_stats['average_score'] = $row['avg_score'] ? round($row['avg_score'], 2) : 0;
}

$evaluation_stats['total_evaluations'] = $evaluation_stats['as_evaluator'] + $evaluation_stats['as_evaluatee'];

// Get recent evaluations
$recent_evaluations = [];
$sql = "SELECT e.*,
        u1.full_name as evaluator_name,
        u2.full_name as evaluatee_name,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE e.evaluator_id = ? OR e.evaluatee_id = ?
        ORDER BY e.created_at DESC
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
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
            <h1 class="h3 mb-0 text-gray-800">User Details</h1>
            <div>
                <a href="<?php echo $base_url; ?>admin/edit_user.php?id=<?php echo $user['user_id']; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-edit fa-sm text-white-50 mr-1"></i> Edit User
                </a>
                <a href="<?php echo $base_url; ?>admin/users.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Users
                </a>
            </div>
        </div>

        <div class="row">
            <!-- User Information Card -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="icon-circle bg-primary mx-auto mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                            <h5 class="font-weight-bold"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>

                        <div class="row text-center">
                            <div class="col-12 mb-3">
                                <span class="badge badge-<?php
                                    switch ($user['role']) {
                                        case 'head_of_department':
                                            echo 'info';
                                            break;
                                        case 'dean':
                                            echo 'purple';
                                            break;
                                        case 'college':
                                            echo 'success';
                                            break;
                                        case 'hrm':
                                            echo 'warning';
                                            break;
                                        case 'admin':
                                            echo 'dark';
                                            break;
                                        default:
                                            echo 'secondary';
                                    }
                                ?> p-2">
                                    <?php echo ucwords(str_replace('_', ' ', $user['role'])); ?>
                                </span>
                            </div>
                        </div>

                        <div class="text-left">
                            <hr>
                            <div class="row mb-2">
                                <div class="col-5"><strong>Position:</strong></div>
                                <div class="col-7"><?php echo !empty($user['position']) ? htmlspecialchars($user['position']) : 'N/A'; ?></div>
                            </div>

                            <?php if (!empty($user['department_name'])): ?>
                            <div class="row mb-2">
                                <div class="col-5"><strong>Department:</strong></div>
                                <div class="col-7"><?php echo htmlspecialchars($user['department_name']); ?></div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($user['college_name'])): ?>
                            <div class="row mb-2">
                                <div class="col-5"><strong>College:</strong></div>
                                <div class="col-7"><?php echo htmlspecialchars($user['college_name']); ?></div>
                            </div>
                            <?php endif; ?>

                            <div class="row mb-2">
                                <div class="col-5"><strong>Phone:</strong></div>
                                <div class="col-7"><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A'; ?></div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-5"><strong>Status:</strong></div>
                                <div class="col-7">
                                    <span class="badge badge-<?php echo ($user['status'] == 1) ? 'success' : 'danger'; ?>">
                                        <?php echo ($user['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-5"><strong>Created:</strong></div>
                                <div class="col-7">
                                    <?php if (!empty($user['created_at'])): ?>
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluation Statistics -->
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Evaluations</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $evaluation_stats['total_evaluations']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">As Evaluator</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $evaluation_stats['as_evaluator']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">As Evaluatee</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $evaluation_stats['as_evaluatee']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average Score</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $evaluation_stats['average_score']; ?>/5.00
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Evaluations -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Evaluations</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_evaluations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Role</th>
                                            <th>Evaluator/Evaluatee</th>
                                            <th>Score</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_evaluations as $evaluation): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($evaluation['period_title']); ?><br>
                                                    <small class="text-muted">
                                                        <?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($evaluation['evaluator_id'] == $user_id): ?>
                                                        <span class="badge badge-info">Evaluator</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">Evaluatee</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($evaluation['evaluator_id'] == $user_id): ?>
                                                        <?php echo htmlspecialchars($evaluation['evaluatee_name']); ?>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($evaluation['evaluator_name']); ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($evaluation['total_score'] > 0): ?>
                                                        <span class="font-weight-bold"><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not scored</span>
                                                    <?php endif; ?>
                                                </td>
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
                                                </td>
                                                <td>
                                                    <a href="<?php echo $base_url; ?>admin/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>"
                                                       class="btn btn-sm btn-info" title="View Evaluation">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No evaluations found for this user.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .icon-circle {
        height: 2rem;
        width: 2rem;
        border-radius: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>