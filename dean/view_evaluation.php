<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - View Evaluation
 */

// Include configuration file - use direct path to avoid memory issues
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has dean role
if (!is_logged_in() || !has_role('dean')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$period = null;
$responses_by_category = [];

// Check if evaluation_id is provided
if ($evaluation_id <= 0) {
    redirect($base_url . 'dean/evaluations.php');
}

// Get evaluation data
$sql = "SELECT e.*,
        u1.full_name as evaluator_name,
        u1.email as evaluator_email,
        u1.role as evaluator_role,
        u2.full_name as evaluatee_name,
        u2.email as evaluatee_email,
        u2.role as evaluatee_role,
        u2.position as evaluatee_position,
        d.name as department_name,
        d.code as department_code,
        p.title as period_title,
        p.academic_year,
        p.semester
        FROM evaluations e
        JOIN users u1 ON e.evaluator_id = u1.user_id
        JOIN users u2 ON e.evaluatee_id = u2.user_id
        LEFT JOIN departments d ON u2.department_id = d.department_id
        JOIN evaluation_periods p ON e.period_id = p.period_id
        WHERE e.evaluation_id = ? AND (e.evaluator_id = ? OR d.college_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $evaluation_id, $user_id, $college_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows === 1) {
    $evaluation = $result->fetch_assoc();
} else {
    redirect($base_url . 'dean/evaluations.php');
}

// Get evaluation responses by category
$sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description, ec.weight, ec.max_rating,
        cat.category_id, cat.name as category_name
        FROM evaluation_responses er
        JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
        JOIN evaluation_categories cat ON ec.category_id = cat.category_id
        WHERE er.evaluation_id = ?
        ORDER BY cat.name ASC, ec.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluation_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category_id = $row['category_id'];
        if (!isset($responses_by_category[$category_id])) {
            $responses_by_category[$category_id] = [
                'name' => $row['category_name'],
                'responses' => [],
                'avg_score' => 0,
                'total_weight' => 0
            ];
        }
        $responses_by_category[$category_id]['responses'][] = $row;
        $responses_by_category[$category_id]['total_weight'] += $row['weight'];
    }

    // Calculate average score for each category
    foreach ($responses_by_category as $category_id => $category) {
        $total_weighted_score = 0;
        foreach ($category['responses'] as $response) {
            $normalized_score = ($response['rating'] / $response['max_rating']) * $response['weight'];
            $total_weighted_score += $normalized_score;
        }
        if ($category['total_weight'] > 0) {
            $responses_by_category[$category_id]['avg_score'] = ($total_weighted_score / $category['total_weight']) * 5;
        }
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
            <h1 class="h3 mb-0 text-gray-800">View Evaluation</h1>
            <div>
                <?php if ($evaluation['evaluatee_role'] === 'head_of_department'): ?>
                    <a href="<?php echo $base_url; ?>dean/head_evaluations.php?id=<?php echo $evaluation['evaluatee_id']; ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Head Evaluations
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>dean/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                    </a>
                <?php endif; ?>
                <a href="<?php echo $base_url; ?>dean/print_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" target="_blank">
                    <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print Evaluation
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

        <!-- Evaluation Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-theme text-white">
                <h6 class="m-0 font-weight-bold">Evaluation Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-theme">Evaluation Details</h5>
                        <p>
                            <strong>Period:</strong> <?php echo $evaluation['period_title']; ?> (<?php echo $evaluation['academic_year']; ?>, Semester <?php echo $evaluation['semester']; ?>)<br>
                            <strong>Status:</strong>
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
                            </span><br>
                            <strong>Created:</strong> <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?><br>
                            <?php if (!empty($evaluation['submission_date'])): ?>
                                <strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?><br>
                            <?php endif; ?>
                            <strong>Total Score:</strong> <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-theme">Evaluatee Information</h5>
                        <p>
                            <strong>Name:</strong> <?php echo $evaluation['evaluatee_name']; ?><br>
                            <strong>Position:</strong> <?php echo $evaluation['evaluatee_position'] ?? ucwords(str_replace('_', ' ', $evaluation['evaluatee_role'])); ?><br>
                            <strong>Department:</strong> <?php echo $evaluation['department_name']; ?> (<?php echo $evaluation['department_code']; ?>)<br>
                            <strong>Email:</strong> <?php echo $evaluation['evaluatee_email']; ?>
                        </p>
                    </div>
                </div>
                <div class="progress mb-4" style="height: 25px;">
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
                        aria-valuenow="<?php echo $evaluation['total_score']; ?>" aria-valuemin="0" aria-valuemax="5">
                        <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                    </div>
                </div>
                <?php if (!empty($evaluation['comments'])): ?>
                    <div class="mt-3">
                        <h5 class="text-theme">Overall Comments</h5>
                        <div class="card bg-light">
                            <div class="card-body">
                                <?php echo nl2br($evaluation['comments']); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Evaluation Categories -->
        <?php if (count($responses_by_category) > 0): ?>
            <?php foreach ($responses_by_category as $category_id => $category): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme"><?php echo $category['name']; ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h5 class="small font-weight-bold">
                                Category Score:
                                <span class="float-right"><?php echo number_format($category['avg_score'], 2); ?>/5.00</span>
                            </h5>
                            <div class="progress mb-4">
                                <div class="progress-bar bg-<?php
                                    $score_percent = ($category['avg_score'] / 5) * 100;
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
                                    aria-valuenow="<?php echo $category['avg_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Criteria</th>
                                        <th width="100">Weight</th>
                                        <th width="100">Rating</th>
                                        <th width="200">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category['responses'] as $response): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $response['criteria_name']; ?></strong>
                                                <?php if (!empty($response['criteria_description'])): ?>
                                                    <p class="small text-muted mb-0"><?php echo $response['criteria_description']; ?></p>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $response['weight']; ?></td>
                                            <td class="text-center">
                                                <span class="badge badge-<?php
                                                    if ($response['rating'] >= 4.5) {
                                                        echo 'success';
                                                    } elseif ($response['rating'] >= 3.5) {
                                                        echo 'primary';
                                                    } elseif ($response['rating'] >= 2.5) {
                                                        echo 'info';
                                                    } elseif ($response['rating'] >= 1.5) {
                                                        echo 'warning';
                                                    } else {
                                                        echo 'danger';
                                                    }
                                                ?> p-2">
                                                    <?php echo $response['rating']; ?>/5
                                                </span>
                                            </td>
                                            <td><?php echo !empty($response['comment']) ? nl2br($response['comment']) : '<em class="text-muted">No comments</em>'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <p>No evaluation responses found for this evaluation.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>
