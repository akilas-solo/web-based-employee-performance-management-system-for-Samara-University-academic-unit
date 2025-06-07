<?php
/**
 * Samara University Academic Performance Evaluation System
 * Staff - My Evaluations
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has staff role
if (!is_logged_in() || !has_role('staff')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$evaluations = [];
$periods = [];
$period_details = null;

// Get all evaluation periods
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;

        // Set default period_id to the most recent period if not specified
        if ($period_id === 0 && $row['status'] === 'active') {
            $period_id = $row['period_id'];
        }
    }

    // If no active period, use the most recent one
    if ($period_id === 0 && count($periods) > 0) {
        $period_id = $periods[0]['period_id'];
    }
}

// Get period details if period_id is set
if ($period_id > 0) {
    foreach ($periods as $period) {
        if ($period['period_id'] === $period_id) {
            $period_details = $period;
            break;
        }
    }
}

// Get evaluations for the selected period
if ($period_id > 0) {
    $sql = "SELECT e.*, ep.title as period_name, u.full_name as evaluator_name, u.role as evaluator_role
            FROM evaluations e
            JOIN evaluation_periods ep ON e.period_id = ep.period_id
            JOIN users u ON e.evaluator_id = u.user_id
            WHERE e.evaluatee_id = ? AND e.period_id = ?
            ORDER BY e.updated_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $evaluations[] = $row;
            }
        }
    } else {
        // Handle error - query preparation failed
        $error_message = "Error preparing query: " . $conn->error;
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
            <h1 class="h3 mb-0 text-gray-800">My Evaluations</h1>
            <div>
                <form action="<?php echo $base_url; ?>staff/evaluations.php" method="get" class="form-inline">
                    <div class="form-group mr-2">
                        <select class="form-control" name="period_id" onchange="this.form.submit()">
                            <option value="">Select Evaluation Period</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id === $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?>
                                    <?php if ($period['status'] === 'active'): ?>
                                        (Active)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
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

        <?php if ($period_details): ?>
            <!-- Period Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Evaluation Period: <?php echo $period_details['title']; ?></h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Start Date:</h6>
                                <p><?php echo date('F d, Y', strtotime($period_details['start_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="font-weight-bold">End Date:</h6>
                                <p><?php echo date('F d, Y', strtotime($period_details['end_date'])); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <h6 class="font-weight-bold">Status:</h6>
                                <p>
                                    <span class="badge badge-<?php echo ($period_details['status'] === 'active') ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($period_details['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6 class="font-weight-bold">Description:</h6>
                        <p><?php echo $period_details['description']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Evaluations Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Evaluations</h6>
                </div>
                <div class="card-body">
                    <?php if (count($evaluations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Evaluator</th>
                                        <th>Role</th>
                                        <th>Category</th>
                                        <th>Score</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($evaluations as $evaluation): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($evaluation['updated_at'])); ?></td>
                                            <td><?php echo $evaluation['evaluator_name']; ?></td>
                                            <td><?php echo ucfirst($evaluation['evaluator_role']); ?></td>
                                            <td>Performance Evaluation</td>
                                            <td><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</td>
                                            <td>
                                                <span class="badge badge-<?php echo get_status_color($evaluation['status']); ?>">
                                                    <?php echo ucfirst($evaluation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>staff/evaluation_details.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-clipboard-list fa-4x text-gray-300"></i>
                            </div>
                            <p class="text-muted">No evaluations found for this period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle mr-1"></i> Please select an evaluation period to view your evaluations.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#evaluationsTable').DataTable();
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';

/**
 * Get status color for badges
 *
 * @param string $status Status string
 * @return string Bootstrap color class
 */
function get_status_color($status) {
    switch ($status) {
        case 'draft':
            return 'secondary';
        case 'submitted':
            return 'primary';
        case 'reviewed':
            return 'info';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
