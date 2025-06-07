<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - View Evaluation
 */

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include configuration file
require_once BASE_PATH . '/includes/config.php';

// Check if user is logged in and has college role
if (!is_logged_in() || !has_role('college')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$period = null;
$responses_by_category = [];

// Get evaluation data
if ($evaluation_id > 0) {
    $sql = "SELECT e.*, 
            u1.full_name as evaluator_name, 
            u2.full_name as evaluatee_name, 
            u2.email as evaluatee_email,
            u2.position as evaluatee_position,
            d.name as department_name,
            p.title as period_title,
            p.academic_year,
            p.semester
            FROM evaluations e 
            JOIN users u1 ON e.evaluator_id = u1.user_id 
            JOIN users u2 ON e.evaluatee_id = u2.user_id 
            LEFT JOIN departments d ON u2.department_id = d.department_id
            JOIN evaluation_periods p ON e.period_id = p.period_id 
            WHERE e.evaluation_id = ? AND (e.evaluator_id = ? OR u2.college_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $evaluation_id, $user_id, $_SESSION['college_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();
        
        // Get evaluation responses by category
        $sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description, 
                ec.weight, ec.min_rating, ec.max_rating, cat.name as category_name, cat.category_id 
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
                        'responses' => []
                    ];
                }
                
                $responses_by_category[$category_id]['responses'][] = $row;
            }
        }
    } else {
        $error_message = "Evaluation not found or you don't have permission to view it.";
    }
} else {
    $error_message = "Invalid evaluation ID.";
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
            <h1 class="h3 mb-0 text-gray-800">View Evaluation</h1>
            <div>
                <a href="<?php echo $base_url; ?>college/print_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm" target="_blank">
                    <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print
                </a>
                <a href="<?php echo $base_url; ?>college/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                </a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if ($evaluation): ?>
            <!-- Evaluation Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Evaluation Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-theme"><?php echo $evaluation['evaluatee_name']; ?></h5>
                            <p>
                                <strong>Position:</strong> <?php echo $evaluation['evaluatee_position']; ?><br>
                                <strong>Department:</strong> <?php echo $evaluation['department_name']; ?><br>
                                <strong>Email:</strong> <?php echo $evaluation['evaluatee_email']; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-theme"><?php echo $evaluation['period_title']; ?></h5>
                            <p>
                                <strong>Academic Year:</strong> <?php echo $evaluation['academic_year']; ?><br>
                                <strong>Semester:</strong> <?php echo $evaluation['semester']; ?><br>
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
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p>
                                <strong>Evaluator:</strong> <?php echo $evaluation['evaluator_name']; ?><br>
                                <strong>Created:</strong> <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?><br>
                                <?php if (!empty($evaluation['submission_date'])): ?>
                                    <strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?><br>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['review_date'])): ?>
                                    <strong>Reviewed:</strong> <?php echo date('M d, Y', strtotime($evaluation['review_date'])); ?><br>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <div class="text-center">
                                <h4 class="font-weight-bold text-theme">Overall Score</h4>
                                <div class="h1 mb-0 font-weight-bold text-theme">
                                    <?php echo number_format($evaluation['total_score'], 2); ?>/5
                                </div>
                                <div class="progress progress-lg mt-2">
                                    <div class="progress-bar bg-theme" role="progressbar" style="width: <?php echo ($evaluation['total_score'] / 5) * 100; ?>%" 
                                        aria-valuenow="<?php echo $evaluation['total_score']; ?>" aria-valuemin="0" aria-valuemax="5">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Evaluation Responses -->
            <?php foreach ($responses_by_category as $category_id => $category): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme"><?php echo $category['name']; ?></h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="40%">Criteria</th>
                                        <th width="15%">Rating</th>
                                        <th width="45%">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category['responses'] as $response): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $response['criteria_name']; ?></strong>
                                                <p class="small text-muted mb-0"><?php echo $response['criteria_description']; ?></p>
                                                <p class="small text-info mb-0">Weight: <?php echo $response['weight']; ?></p>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    $rating_percentage = ($response['rating'] / $response['max_rating']) * 100;
                                                    if ($rating_percentage >= 80) {
                                                        echo 'success';
                                                    } elseif ($rating_percentage >= 60) {
                                                        echo 'info';
                                                    } elseif ($rating_percentage >= 40) {
                                                        echo 'warning';
                                                    } else {
                                                        echo 'danger';
                                                    }
                                                ?>">
                                                    <?php echo $response['rating']; ?>/<?php echo $response['max_rating']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo nl2br(htmlspecialchars($response['comment'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Overall Comments -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Overall Comments</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($evaluation['comments'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($evaluation['comments'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No overall comments provided.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($evaluation['status'] === 'rejected' && !empty($evaluation['rejection_reason'])): ?>
                <!-- Rejection Reason -->
                <div class="card shadow mb-4 border-left-danger">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-danger">Rejection Reason</h6>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($evaluation['rejection_reason'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
