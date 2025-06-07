<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Staff Evaluations
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$staff_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$staff = null;
$evaluations = [];
$periods = [];
$period_details = null;
$evaluation_history = [];

// Get staff details
if ($staff_id > 0) {
    $sql = "SELECT * FROM users WHERE user_id = ? AND department_id = ? AND role = 'staff'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $staff_id, $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $staff = $result->fetch_assoc();
    } else {
        $error_message = "Staff member not found or you don't have permission to view their evaluations.";
    }
}

// Get all evaluation periods
$sql = "SELECT * FROM evaluation_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row;
    }
}

// Get period details if selected
if ($period_id > 0) {
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period_details = $result->fetch_assoc();
    }
}

// Get evaluations for the staff member
if ($staff) {
    $sql = "SELECT e.*,
            u.full_name as evaluator_name,
            u.role as evaluator_role,
            p.title as period_title,
            p.academic_year,
            p.semester
            FROM evaluations e
            JOIN users u ON e.evaluator_id = u.user_id
            JOIN evaluation_periods p ON e.period_id = p.period_id
            WHERE e.evaluatee_id = ?";

    // Add period filter if selected
    if ($period_id > 0) {
        $sql .= " AND e.period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $staff_id, $period_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $staff_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evaluations[] = $row;
        }
    }

    // Get evaluation history (scores by period)
    $sql = "SELECT p.period_id, p.title, p.academic_year, p.semester,
            AVG(e.total_score) as avg_score, COUNT(e.evaluation_id) as evaluation_count
            FROM evaluation_periods p
            LEFT JOIN evaluations e ON p.period_id = e.period_id AND e.evaluatee_id = ?
            GROUP BY p.period_id
            ORDER BY p.start_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $evaluation_history[] = $row;
        }
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
            <h1 class="h3 mb-0 text-gray-800">Staff Evaluations</h1>
            <div>
                <a href="<?php echo $base_url; ?>head/staff.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Staff
                </a>
                <?php if ($staff && count($evaluations) > 0): ?>
                    <a href="<?php echo $base_url; ?>head/print_staff_report.php?id=<?php echo $staff_id; ?>&period_id=<?php echo $period_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-theme shadow-sm" target="_blank">
                        <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print Report
                    </a>
                <?php endif; ?>
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

        <?php if ($staff): ?>
            <!-- Staff Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Staff Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <?php if (!empty($staff['profile_image'])): ?>
                                <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $staff['profile_image']; ?>" alt="<?php echo $staff['full_name']; ?>" class="img-profile rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem; margin: 0 auto;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <h4 class="text-theme"><?php echo $staff['full_name']; ?></h4>
                            <p><strong>Email:</strong> <?php echo $staff['email']; ?></p>
                            <p><strong>Position:</strong> <?php echo $staff['position'] ?? 'N/A'; ?></p>
                            <p><strong>Status:</strong>
                                <span class="badge badge-<?php echo ($staff['status'] == 1) ? 'success' : 'danger'; ?>">
                                    <?php echo ($staff['status'] == 1) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-5">
                            <h5 class="text-theme">Evaluation Summary</h5>
                            <?php
                            $total_evaluations = 0;
                            $avg_score = 0;
                            $total_scores = 0;

                            foreach ($evaluations as $evaluation) {
                                if ($evaluation['total_score'] > 0) {
                                    $total_scores += $evaluation['total_score'];
                                    $total_evaluations++;
                                }
                            }

                            if ($total_evaluations > 0) {
                                $avg_score = $total_scores / $total_evaluations;
                            }
                            ?>

                            <p><strong>Total Evaluations:</strong> <?php echo count($evaluations); ?></p>

                            <?php if ($avg_score > 0): ?>
                                <p><strong>Average Score:</strong> <?php echo number_format($avg_score, 2); ?>/5.00</p>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-<?php
                                        $score_percent = ($avg_score / 5) * 100;
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
                                        aria-valuenow="<?php echo $avg_score; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                                <p><strong>Rating:</strong>
                                    <span class="badge badge-<?php
                                        if ($score_percent >= 80) {
                                            echo 'success';
                                        } elseif ($score_percent >= 60) {
                                            echo 'info';
                                        } elseif ($score_percent >= 40) {
                                            echo 'warning';
                                        } else {
                                            echo 'danger';
                                        }
                                    ?>">
                                        <?php
                                        if ($score_percent >= 90) {
                                            echo 'Excellent';
                                        } elseif ($score_percent >= 80) {
                                            echo 'Very Good';
                                        } elseif ($score_percent >= 70) {
                                            echo 'Good';
                                        } elseif ($score_percent >= 60) {
                                            echo 'Satisfactory';
                                        } elseif ($score_percent >= 50) {
                                            echo 'Fair';
                                        } else {
                                            echo 'Needs Improvement';
                                        }
                                        ?>
                                    </span>
                                </p>
                            <?php else: ?>
                                <p><strong>Average Score:</strong> N/A</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Filter Evaluations</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline">
                        <input type="hidden" name="id" value="<?php echo $staff_id; ?>">
                        <div class="form-group mb-2 mr-2">
                            <label for="period_id" class="mr-2">Evaluation Period:</label>
                            <select class="form-control" id="period_id" name="period_id">
                                <option value="0">All Periods</option>
                                <?php foreach ($periods as $period): ?>
                                    <option value="<?php echo $period['period_id']; ?>" <?php echo ($period_id == $period['period_id']) ? 'selected' : ''; ?>>
                                        <?php echo $period['title']; ?> (<?php echo $period['academic_year']; ?>, Semester <?php echo $period['semester']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-theme mb-2">
                            <i class="fas fa-filter mr-1"></i> Filter
                        </button>
                        <?php if ($period_id > 0): ?>
                            <a href="<?php echo $base_url; ?>head/staff_evaluations.php?id=<?php echo $staff_id; ?>" class="btn btn-secondary mb-2 ml-2">
                                <i class="fas fa-times mr-1"></i> Clear Filter
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Evaluations Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">
                        <?php
                        if ($period_id > 0 && $period_details) {
                            echo 'Evaluations for ' . $period_details['title'] . ' (' . $period_details['academic_year'] . ', Semester ' . $period_details['semester'] . ')';
                        } else {
                            echo 'All Evaluations';
                        }
                        ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (count($evaluations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="evaluationsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Evaluator</th>
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
                                                        <div class="icon-circle bg-<?php
                                                            switch ($evaluation['evaluator_role']) {
                                                                case 'head_of_department':
                                                                    echo 'info';
                                                                    break;
                                                                case 'dean':
                                                                    echo 'primary';
                                                                    break;
                                                                case 'college':
                                                                    echo 'success';
                                                                    break;
                                                                case 'hrm':
                                                                    echo 'warning';
                                                                    break;
                                                                default:
                                                                    echo 'secondary';
                                                            }
                                                        ?>">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="font-weight-bold"><?php echo $evaluation['evaluator_name']; ?></div>
                                                        <div class="small text-gray-600"><?php echo ucwords(str_replace('_', ' ', $evaluation['evaluator_role'])); ?></div>
                                                    </div>
                                                </div>
                                            </td>
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
                                                <?php if ($evaluation['evaluator_id'] == $user_id): ?>
                                                    <a href="<?php echo $base_url; ?>head/view_evaluation.php?id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-info" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($evaluation['status'] === 'draft'): ?>
                                                        <a href="<?php echo $base_url; ?>head/evaluation_form.php?evaluation_id=<?php echo $evaluation['evaluation_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No access</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No evaluations found for the selected period.</p>

                        <?php if (count($periods) > 0): ?>
                            <div class="text-center mt-3">
                                <a href="<?php echo $base_url; ?>head/evaluation_form.php?evaluatee_id=<?php echo $staff_id; ?>&period_id=<?php echo $periods[0]['period_id']; ?>" class="btn btn-theme">
                                    <i class="fas fa-clipboard-check mr-1"></i> Create New Evaluation
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Performance History -->
            <div class="row">
                <!-- Performance Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">Performance History</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="performanceHistoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-theme">Performance Summary</h6>
                        </div>
                        <div class="card-body">
                            <?php if (count($evaluation_history) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Period</th>
                                                <th>Score</th>
                                                <th>Rating</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($evaluation_history as $history): ?>
                                                <tr>
                                                    <td>
                                                        <?php echo $history['title']; ?><br>
                                                        <span class="small text-gray-600">
                                                            <?php echo $history['academic_year']; ?>, Semester <?php echo $history['semester']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($history['avg_score']): ?>
                                                            <?php echo number_format($history['avg_score'], 2); ?>/5.00
                                                        <?php else: ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($history['avg_score']): ?>
                                                            <span class="badge badge-<?php
                                                                $score = $history['avg_score'];
                                                                if ($score >= 4.5) {
                                                                    echo 'success';
                                                                } elseif ($score >= 3.5) {
                                                                    echo 'primary';
                                                                } elseif ($score >= 2.5) {
                                                                    echo 'info';
                                                                } elseif ($score >= 1.5) {
                                                                    echo 'warning';
                                                                } else {
                                                                    echo 'danger';
                                                                }
                                                            ?>">
                                                                <?php
                                                                $score = $history['avg_score'];
                                                                if ($score >= 4.5) {
                                                                    echo 'Excellent';
                                                                } elseif ($score >= 3.5) {
                                                                    echo 'Very Good';
                                                                } elseif ($score >= 2.5) {
                                                                    echo 'Good';
                                                                } elseif ($score >= 1.5) {
                                                                    echo 'Fair';
                                                                } else {
                                                                    echo 'Poor';
                                                                }
                                                                ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Not Evaluated</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No performance history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Staff member not found or you don't have permission to view their evaluations.</p>
                <a href="<?php echo $base_url; ?>head/staff.php" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Staff
                </a>
            </div>
        <?php endif; ?>
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
        $('#evaluationsTable').DataTable();

        // Chart.js - Performance History
        var performanceHistoryCtx = document.getElementById('performanceHistoryChart');

        <?php if (count($evaluation_history) > 0): ?>
            // Prepare data for chart
            var periodLabels = [];
            var scores = [];

            <?php
            // Reverse the array to show oldest to newest
            $reversed_history = array_reverse($evaluation_history);
            foreach ($reversed_history as $history):
            ?>
                periodLabels.push('<?php echo $history['title']; ?> (<?php echo $history['academic_year']; ?>, Sem <?php echo $history['semester']; ?>)');
                scores.push(<?php echo $history['avg_score'] ? $history['avg_score'] : 'null'; ?>);
            <?php endforeach; ?>

            // Create Performance History Chart
            if (performanceHistoryCtx) {
                new Chart(performanceHistoryCtx, {
                    type: 'line',
                    data: {
                        labels: periodLabels,
                        datasets: [{
                            label: 'Performance Score',
                            lineTension: 0.3,
                            backgroundColor: "rgba(78, 115, 223, 0.05)",
                            borderColor: "rgba(78, 115, 223, 1)",
                            pointRadius: 3,
                            pointBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointBorderColor: "rgba(78, 115, 223, 1)",
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                            pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            data: scores,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true,
                                    max: 5,
                                    stepSize: 1
                                },
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Score (out of 5)'
                                }
                            }]
                        },
                        tooltips: {
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    var score = tooltipItem.yLabel;
                                    var rating = '';

                                    if (score >= 4.5) {
                                        rating = 'Excellent';
                                    } else if (score >= 3.5) {
                                        rating = 'Very Good';
                                    } else if (score >= 2.5) {
                                        rating = 'Good';
                                    } else if (score >= 1.5) {
                                        rating = 'Fair';
                                    } else if (score > 0) {
                                        rating = 'Poor';
                                    } else {
                                        return 'Not Evaluated';
                                    }

                                    return 'Score: ' + score.toFixed(2) + ' (' + rating + ')';
                                }
                            }
                        }
                    }
                });
            }
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
