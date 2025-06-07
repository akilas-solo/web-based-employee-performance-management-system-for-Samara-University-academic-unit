<?php
/**
 * Samara University Academic Performance Evaluation System
 * College - Evaluation Form
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
$errors = [];
$user_id = $_SESSION['user_id'];
$college_id = $_SESSION['college_id'];
$evaluation_id = isset($_GET['evaluation_id']) ? (int)$_GET['evaluation_id'] : 0;
$evaluatee_id = isset($_GET['evaluatee_id']) ? (int)$_GET['evaluatee_id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$evaluation = null;
$evaluatee = null;
$period = null;
$criteria_by_category = [];

// Check if we're editing an existing evaluation or creating a new one
if ($evaluation_id > 0) {
    // Get evaluation data
    $sql = "SELECT e.*, u.full_name as evaluatee_name, u.email as evaluatee_email, u.position as evaluatee_position,
            d.name as department_name, p.title as period_title, p.academic_year, p.semester
            FROM evaluations e
            JOIN users u ON e.evaluatee_id = u.user_id
            LEFT JOIN departments d ON u.department_id = d.department_id
            JOIN evaluation_periods p ON e.period_id = p.period_id
            WHERE e.evaluation_id = ? AND e.evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();
        $evaluatee_id = $evaluation['evaluatee_id'];
        $period_id = $evaluation['period_id'];
    } else {
        $error_message = "Evaluation not found or you don't have permission to edit it.";
    }
} else if ($evaluatee_id > 0 && $period_id > 0) {
    // Check if evaluation already exists
    $sql = "SELECT evaluation_id FROM evaluations
            WHERE evaluator_id = ? AND evaluatee_id = ? AND period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $evaluatee_id, $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        redirect($base_url . 'college/evaluation_form.php?evaluation_id=' . $row['evaluation_id']);
    }

    // Get evaluatee data
    $sql = "SELECT u.*, d.name as department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            WHERE u.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluatee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $evaluatee = $result->fetch_assoc();
    } else {
        $error_message = "Evaluatee not found.";
    }

    // Get period data
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period = $result->fetch_assoc();
    } else {
        $error_message = "Evaluation period not found.";
    }

    // Create new evaluation
    if ($evaluatee && $period && empty($error_message)) {
        $sql = "INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, status, created_at, updated_at)
                VALUES (?, ?, ?, 'draft', NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $evaluatee_id, $period_id);
        if ($stmt->execute()) {
            $evaluation_id = $conn->insert_id;
            redirect($base_url . 'college/evaluation_form.php?evaluation_id=' . $evaluation_id);
        } else {
            $error_message = "Failed to create evaluation.";
        }
    }
} else {
    $error_message = "Invalid parameters. Please select an evaluatee and evaluation period.";
}

// Get evaluation criteria by category
if ($evaluation) {
    // Get evaluatee role
    $evaluatee_role = '';
    $sql_role = "SELECT role FROM users WHERE user_id = ?";
    $stmt_role = $conn->prepare($sql_role);
    $stmt_role->bind_param("i", $evaluation['evaluatee_id']);
    $stmt_role->execute();
    $result_role = $stmt_role->get_result();
    if ($result_role && $result_role->num_rows === 1) {
        $row_role = $result_role->fetch_assoc();
        $evaluatee_role = $row_role['role'];
    }

    // Get criteria based on evaluatee role
    $sql = "SELECT ec.*, cat.name as category_name, cat.category_id, er.response_id, er.rating, er.comment
            FROM evaluation_criteria ec
            JOIN evaluation_categories cat ON ec.category_id = cat.category_id
            LEFT JOIN evaluation_responses er ON ec.criteria_id = er.criteria_id AND er.evaluation_id = ?
            WHERE FIND_IN_SET('college', ec.evaluator_roles) > 0
            AND (FIND_IN_SET(?, ec.evaluatee_roles) > 0 OR FIND_IN_SET('all', ec.evaluatee_roles) > 0)
            ORDER BY cat.name ASC, ec.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $evaluation_id, $evaluatee_role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_id = $row['category_id'];

            if (!isset($criteria_by_category[$category_id])) {
                $criteria_by_category[$category_id] = [
                    'name' => $row['category_name'],
                    'criteria' => []
                ];
            }

            $criteria_by_category[$category_id]['criteria'][] = $row;
        }
    } else {
        $error_message = "No evaluation criteria found for college evaluator.";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_evaluation'])) {
    $total_score = 0;
    $total_weight = 0;
    $ratings = $_POST['rating'] ?? [];
    $comments = $_POST['comment'] ?? [];
    $status = isset($_POST['submit']) ? 'submitted' : 'draft';

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete existing responses
        $sql = "DELETE FROM evaluation_responses WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();

        // Insert new responses and calculate total score
        foreach ($ratings as $criteria_id => $rating) {
            $comment = $comments[$criteria_id] ?? '';

            // Get criteria weight
            $sql = "SELECT weight, max_rating FROM evaluation_criteria WHERE criteria_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $criteria_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $criteria = $result->fetch_assoc();

            // Calculate weighted score
            $weight = $criteria['weight'];
            $max_rating = $criteria['max_rating'];
            $weighted_score = ($rating / $max_rating) * $weight;
            $total_score += $weighted_score;
            $total_weight += $weight;

            // Insert response
            $sql = "INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment, created_at, updated_at)
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $evaluation_id, $criteria_id, $rating, $comment);
            $stmt->execute();
        }

        // Calculate final score (normalized to 5)
        $final_score = ($total_weight > 0) ? ($total_score / $total_weight) * 5 : 0;

        // Update evaluation
        $sql = "UPDATE evaluations SET
                total_score = ?,
                comments = ?,
                status = ?,
                submission_date = " . ($status === 'submitted' ? 'NOW()' : 'NULL') . ",
                updated_at = NOW()
                WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $comments = $_POST['evaluation_comments'] ?? '';
        $stmt->bind_param("dssi", $final_score, $comments, $status, $evaluation_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $success_message = "Evaluation " . ($status === 'submitted' ? 'submitted' : 'saved') . " successfully.";

        // Redirect to evaluations page if submitted
        if ($status === 'submitted') {
            set_flash_message($success_message, 'success');
            redirect($base_url . 'college/evaluations.php');
        }

        // Refresh evaluation data
        $sql = "SELECT e.*, u.full_name as evaluatee_name, u.email as evaluatee_email, u.position as evaluatee_position,
                d.name as department_name, p.title as period_title, p.academic_year, p.semester
                FROM evaluations e
                JOIN users u ON e.evaluatee_id = u.user_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $evaluation = $result->fetch_assoc();
        }

        // Refresh criteria data
        $criteria_by_category = [];

        // Get evaluatee role again to ensure it's current
        $evaluatee_role = '';
        $sql_role = "SELECT role FROM users WHERE user_id = ?";
        $stmt_role = $conn->prepare($sql_role);
        $stmt_role->bind_param("i", $evaluation['evaluatee_id']);
        $stmt_role->execute();
        $result_role = $stmt_role->get_result();
        if ($result_role && $result_role->num_rows === 1) {
            $row_role = $result_role->fetch_assoc();
            $evaluatee_role = $row_role['role'];
        }

        $sql = "SELECT ec.*, cat.name as category_name, cat.category_id, er.response_id, er.rating, er.comment
                FROM evaluation_criteria ec
                JOIN evaluation_categories cat ON ec.category_id = cat.category_id
                LEFT JOIN evaluation_responses er ON ec.criteria_id = er.criteria_id AND er.evaluation_id = ?
                WHERE FIND_IN_SET('college', ec.evaluator_roles) > 0
                AND (FIND_IN_SET(?, ec.evaluatee_roles) > 0 OR FIND_IN_SET('all', ec.evaluatee_roles) > 0)
                ORDER BY cat.name ASC, ec.name ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $evaluation_id, $evaluatee_role);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $category_id = $row['category_id'];

                if (!isset($criteria_by_category[$category_id])) {
                    $criteria_by_category[$category_id] = [
                        'name' => $row['category_name'],
                        'criteria' => []
                    ];
                }

                $criteria_by_category[$category_id]['criteria'][] = $row;
            }
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Failed to save evaluation: " . $e->getMessage();
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
                <?php echo $evaluation ? 'Edit Evaluation' : 'New Evaluation'; ?>
            </h1>
            <?php if ($evaluation): ?>
                <div>
                    <a href="<?php echo $base_url; ?>college/print_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm" target="_blank">
                        <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print
                    </a>
                    <a href="<?php echo $base_url; ?>college/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                    </a>
                </div>
            <?php endif; ?>
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
                </div>
            </div>

            <!-- Evaluation Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?evaluation_id=' . $evaluation_id); ?>" id="evaluationForm">
                <?php if (count($criteria_by_category) > 0): ?>
                    <?php foreach ($criteria_by_category as $category_id => $category): ?>
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
                                                <th width="15%">Rating (1-5)</th>
                                                <th width="45%">Comments</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($category['criteria'] as $criteria): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo $criteria['name']; ?></strong>
                                                        <p class="small text-muted mb-0"><?php echo $criteria['description']; ?></p>
                                                        <p class="small text-info mb-0">Weight: <?php echo $criteria['weight']; ?></p>
                                                    </td>
                                                    <td>
                                                        <select class="form-control" name="rating[<?php echo $criteria['criteria_id']; ?>]" required <?php echo ($evaluation['status'] !== 'draft') ? 'disabled' : ''; ?>>
                                                            <option value="">Select</option>
                                                            <?php for ($i = $criteria['min_rating']; $i <= $criteria['max_rating']; $i++): ?>
                                                                <option value="<?php echo $i; ?>" <?php echo (isset($criteria['rating']) && $criteria['rating'] == $i) ? 'selected' : ''; ?>>
                                                                    <?php echo $i; ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control" name="comment[<?php echo $criteria['criteria_id']; ?>]" rows="2" <?php echo ($evaluation['status'] !== 'draft') ? 'disabled' : ''; ?>><?php echo $criteria['comment'] ?? ''; ?></textarea>
                                                    </td>
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
                            <div class="form-group">
                                <textarea class="form-control" name="evaluation_comments" rows="4" <?php echo ($evaluation['status'] !== 'draft') ? 'disabled' : ''; ?>><?php echo $evaluation['comments']; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <?php if ($evaluation['status'] === 'draft'): ?>
                        <div class="text-center mb-4">
                            <button type="submit" name="save_evaluation" class="btn btn-primary mr-2">
                                <i class="fas fa-save mr-1"></i> Save as Draft
                            </button>
                            <button type="submit" name="save_evaluation" class="btn btn-success" onclick="return confirm('Are you sure you want to submit this evaluation? You will not be able to make changes after submission.');">
                                <i class="fas fa-check-circle mr-1"></i> Submit Evaluation
                            </button>
                            <input type="hidden" name="submit" id="submitFlag" value="0">
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <p>No evaluation criteria found. Please contact the administrator.</p>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set submit flag when submitting evaluation
        document.querySelector('button[name="save_evaluation"].btn-success').addEventListener('click', function() {
            document.getElementById('submitFlag').value = '1';
        });
    });
</script>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
