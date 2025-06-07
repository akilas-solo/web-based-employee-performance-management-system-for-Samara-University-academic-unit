<?php
/**
 * Samara University Academic Performance Evaluation System
 * Admin - Evaluations Management
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has admin role
if (!is_logged_in() || !has_role('admin')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$college_id = isset($_GET['college_id']) ? (int)$_GET['college_id'] : 0;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$evaluator_role = isset($_GET['evaluator_role']) ? sanitize_input($_GET['evaluator_role']) : '';

// Check for success message in URL
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
}

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

// Get departments based on selected college
$departments = [];
if ($college_id > 0) {
    $sql = "SELECT * FROM departments WHERE college_id = ? ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
}

// Get all evaluations with filters
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

if (!empty($status)) {
    $sql .= " AND e.status = ?";
    $params[] = $status;
    $types .= "s";
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

if (!empty($evaluator_role)) {
    $sql .= " AND u1.role = ?";
    $params[] = $evaluator_role;
    $types .= "s";
}

$sql .= " ORDER BY e.created_at DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

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
            <h1 class="h3 mb-0 text-gray-800">Evaluations Management</h1>
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

        <!-- Filter Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Evaluations</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="period_id">Period:</label>
                        <select class="form-control" id="period_id" name="period_id">
                            <option value="0">All Periods</option>
                            <?php foreach ($periods as $period): ?>
                                <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                    <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="status">Status:</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo ($status === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="submitted" <?php echo ($status === 'submitted') ? 'selected' : ''; ?>>Submitted</option>
                            <option value="reviewed" <?php echo ($status === 'reviewed') ? 'selected' : ''; ?>>Reviewed</option>
                            <option value="approved" <?php echo ($status === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo ($status === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="college_id">College:</label>
                        <select class="form-control" id="college_id" name="college_id" onchange="this.form.submit()">
                            <option value="0">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?php echo $college['college_id']; ?>" <?php echo ($college_id == $college['college_id']) ? 'selected' : ''; ?>>
                                    <?php echo $college['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="department_id">Department:</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="0">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>" <?php echo ($department_id == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo $department['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="evaluator_role">Evaluator Role:</label>
                        <select class="form-control" id="evaluator_role" name="evaluator_role">
                            <option value="">All Roles</option>
                            <option value="head_of_department" <?php echo ($evaluator_role === 'head_of_department') ? 'selected' : ''; ?>>Head of Department</option>
                            <option value="dean" <?php echo ($evaluator_role === 'dean') ? 'selected' : ''; ?>>Dean</option>
                            <option value="college" <?php echo ($evaluator_role === 'college') ? 'selected' : ''; ?>>College</option>
                            <option value="hrm" <?php echo ($evaluator_role === 'hrm') ? 'selected' : ''; ?>>HRM</option>
                        </select>
                    </div>
                    <div class="col-md-1 mb-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
                <?php if ($period_id > 0 || !empty($status) || $college_id > 0 || $department_id > 0 || !empty($evaluator_role)): ?>
                    <div class="mt-2">
                        <a href="<?php echo $base_url; ?>admin/evaluations.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times mr-1"></i> Clear Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluations Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    All Evaluations (<?php echo count($evaluations); ?> found)
                </h6>
            </div>
            <div class="card-body">
                <?php if (count($evaluations) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Evaluator</th>
                                    <th>Evaluatee</th>
                                    <th>Department</th>
                                    <th>College</th>
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
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <div class="icon-circle bg-info">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold"><?php echo $evaluation['evaluator_name']; ?></div>
                                                    <div class="small text-gray-600"><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    <div class="icon-circle bg-success">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold"><?php echo $evaluation['evaluatee_name']; ?></div>
                                                    <div class="small text-gray-600"><?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $evaluation['department_name'] ?? 'N/A'; ?></td>
                                        <td><?php echo $evaluation['college_name'] ?? 'N/A'; ?></td>
                                        <td>
                                            <?php echo $evaluation['period_title']; ?><br>
                                            <span class="small text-gray-600">
                                                <?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($evaluation['total_score'] > 0): ?>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                                                </div>
                                                <div class="progress progress-sm mr-2 mt-1">
                                                    <div class="progress-bar bg-<?php
                                                        $score_percent = ($evaluation['total_score'] / 5) * 100;
                                                        if ($score_percent >= 80) {
                                                            echo 'success';
                                                        } elseif ($score_percent >= 60) {
                                                            echo 'info';
                                                        } elseif ($score_percent >= 40) {
                                                            echo 'warning';
                                                        } else {
                                                            echo 'danger';
                                                        }
                                                    ?>" role="progressbar" style="width: <?php echo $score_percent; ?>%"
                                                        aria-valuenow="<?php echo $evaluation['total_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-500">Not scored</span>
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
                                            <?php if (!empty($evaluation['submission_date'])): ?>
                                                <br>
                                                <span class="small text-gray-600">
                                                    Submitted: <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo $base_url; ?>admin/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No evaluations found for the selected filters.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Statistics -->
        <div class="row">
            <!-- Evaluation Status -->
            <div class="col-xl-4 col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Evaluation Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie">
                            <canvas id="evaluationStatusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center small">
                            <span class="mr-2">
                                <i class="fas fa-circle text-secondary"></i> Draft
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-info"></i> Submitted
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-warning"></i> Reviewed
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-success"></i> Approved
                            </span>
                            <span class="mr-2">
                                <i class="fas fa-circle text-danger"></i> Rejected
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluations by Role -->
            <div class="col-xl-4 col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Evaluations by Role</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="evaluationsByRoleChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Scores -->
            <div class="col-xl-4 col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Average Scores by Period</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-bar">
                            <canvas id="averageScoresChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Set page-specific scripts
$page_scripts = [
    'assets/js/datatables/jquery.dataTables.min.js',
    'assets/js/datatables/dataTables.bootstrap4.min.js',
    'assets/js/chart.js/Chart.min.js'
];
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        $('#evaluationsTable').DataTable({
            "pageLength": 25,
            "order": [[ 7, "desc" ]]
        });

        // Chart.js - Evaluation Status
        var statusCtx = document.getElementById('evaluationStatusChart');
        var roleCtx = document.getElementById('evaluationsByRoleChart');
        var averageScoresCtx = document.getElementById('averageScoresChart');

        // Count evaluations by status
        var draftCount = 0;
        var submittedCount = 0;
        var reviewedCount = 0;
        var approvedCount = 0;
        var rejectedCount = 0;

        <?php foreach ($evaluations as $evaluation): ?>
            <?php if ($evaluation['status'] === 'draft'): ?>
                draftCount++;
            <?php elseif ($evaluation['status'] === 'submitted'): ?>
                submittedCount++;
            <?php elseif ($evaluation['status'] === 'reviewed'): ?>
                reviewedCount++;
            <?php elseif ($evaluation['status'] === 'approved'): ?>
                approvedCount++;
            <?php elseif ($evaluation['status'] === 'rejected'): ?>
                rejectedCount++;
            <?php endif; ?>
        <?php endforeach; ?>

        // Create Evaluation Status Chart
        if (statusCtx) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Draft', 'Submitted', 'Reviewed', 'Approved', 'Rejected'],
                    datasets: [{
                        data: [draftCount, submittedCount, reviewedCount, approvedCount, rejectedCount],
                        backgroundColor: ['#858796', '#36b9cc', '#f6c23e', '#1cc88a', '#e74a3b'],
                        hoverBackgroundColor: ['#717384', '#2c9faf', '#dda20a', '#17a673', '#be2617'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: false
                    },
                    cutoutPercentage: 80,
                },
            });
        }

        // Count evaluations by role
        var roleCounts = {};
        <?php foreach ($evaluations as $evaluation): ?>
            var role = '<?php echo ucfirst(str_replace('_', ' ', $evaluation['evaluator_role'])); ?>';
            if (roleCounts[role]) {
                roleCounts[role]++;
            } else {
                roleCounts[role] = 1;
            }
        <?php endforeach; ?>

        var roleLabels = Object.keys(roleCounts);
        var roleData = Object.values(roleCounts);

        // Create Evaluations by Role Chart
        if (roleCtx) {
            new Chart(roleCtx, {
                type: 'bar',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Number of Evaluations',
                        data: roleData,
                        backgroundColor: '#4e73df',
                        borderColor: '#4e73df',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        }

        // Prepare data for average scores chart
        var periodLabels = [];
        var averageScores = [];

        <?php
        // Group evaluations by period and calculate average scores
        $period_scores = [];
        foreach ($evaluations as $evaluation) {
            if ($evaluation['total_score'] > 0) {
                $period_id = $evaluation['period_id'];
                $period_title = $evaluation['period_title'] . ' (' . $evaluation['academic_year'] . ', S' . $evaluation['semester'] . ')';

                if (!isset($period_scores[$period_id])) {
                    $period_scores[$period_id] = [
                        'title' => $period_title,
                        'scores' => [],
                    ];
                }

                $period_scores[$period_id]['scores'][] = $evaluation['total_score'];
            }
        }

        // Calculate averages
        foreach ($period_scores as $period_id => $data) {
            $avg_score = array_sum($data['scores']) / count($data['scores']);
            echo "periodLabels.push('" . addslashes($data['title']) . "');\n";
            echo "averageScores.push(" . number_format($avg_score, 2) . ");\n";
        }
        ?>

        // Create Average Scores Chart
        if (averageScoresCtx) {
            new Chart(averageScoresCtx, {
                type: 'bar',
                data: {
                    labels: periodLabels,
                    datasets: [{
                        label: 'Average Score',
                        data: averageScores,
                        backgroundColor: '#1cc88a',
                        borderColor: '#1cc88a',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                max: 5
                            }
                        }]
                    }
                }
            });
        }
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>