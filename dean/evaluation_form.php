<?php
/**
 * Samara University Academic Performance Evaluation System
 * Dean - Evaluation Form
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
$evaluation_id = isset($_GET['evaluation_id']) ? (int)$_GET['evaluation_id'] : 0;
$evaluatee_id = isset($_GET['evaluatee_id']) ? (int)$_GET['evaluatee_id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$evaluation = null;
$evaluatee = null;
$period = null;
$criteria_by_category = [];
$responses = [];

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

        // Get evaluation responses
        $sql = "SELECT er.*, ec.name as criteria_name
                FROM evaluation_responses er
                JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
                WHERE er.evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $responses[$row['criteria_id']] = $row;
            }
        }
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
        redirect($base_url . 'dean/evaluation_form.php?evaluation_id=' . $row['evaluation_id']);
    }

    // Get evaluatee information
    $sql = "SELECT u.*, d.name as department_name
            FROM users u
            LEFT JOIN departments d ON u.department_id = d.department_id
            WHERE u.user_id = ? AND u.role = 'head_of_department' AND d.college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluatee_id, $college_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $evaluatee = $result->fetch_assoc();
    } else {
        $error_message = "Invalid evaluatee or you don't have permission to evaluate this person.";
    }

    // Get period information
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ? AND status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $period = $result->fetch_assoc();
    } else {
        $error_message = "Invalid evaluation period or the period is not active.";
    }

    // Create new evaluation
    if ($evaluatee && $period && empty($error_message)) {
        $sql = "INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, status, created_at, updated_at)
                VALUES (?, ?, ?, 'draft', NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $evaluatee_id, $period_id);
        if ($stmt->execute()) {
            $evaluation_id = $conn->insert_id;
            redirect($base_url . 'dean/evaluation_form.php?evaluation_id=' . $evaluation_id);
        } else {
            $error_message = "Failed to create evaluation.";
        }
    }
} else {
    $error_message = "Invalid parameters. Please select an evaluatee and evaluation period.";
}

// Get evaluation criteria by category
if ($evaluation_id > 0 && empty($error_message)) {
    // Check if the table has evaluator_role or evaluator_roles column
    $result = $conn->query("SHOW COLUMNS FROM evaluation_criteria LIKE 'evaluator_role'");
    if ($result && $result->num_rows > 0) {
        // Using evaluator_role and target_role columns
        $sql = "SELECT ec.*, cat.name as category_name
                FROM evaluation_criteria ec
                JOIN evaluation_categories cat ON ec.category_id = cat.category_id
                WHERE (ec.evaluator_role = 'dean' OR ec.evaluator_role = 'all')
                AND (ec.target_role = 'head_of_department' OR ec.target_role = 'all')
                ORDER BY cat.name ASC, ec.name ASC";
    } else {
        // Using evaluator_roles and evaluatee_roles columns
        $sql = "SELECT ec.*, cat.name as category_name
                FROM evaluation_criteria ec
                JOIN evaluation_categories cat ON ec.category_id = cat.category_id
                WHERE (ec.evaluator_roles LIKE '%dean%' OR ec.evaluator_roles = 'all')
                AND (ec.evaluatee_roles LIKE '%head_of_department%' OR ec.evaluatee_roles = 'all')
                ORDER BY cat.name ASC, ec.name ASC";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error_message = "Database error: " . $conn->error;
    } else {
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
            $error_message = "No evaluation criteria found for dean evaluator.";
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $total_score = 0;
    $total_weight = 0;
    $ratings = $_POST['rating'] ?? [];
    $comments = $_POST['comment'] ?? [];
    $overall_comment = $_POST['overall_comment'] ?? '';
    $status = ($action === 'submit') ? 'submitted' : 'draft';
    $submission_date = ($action === 'submit') ? date('Y-m-d H:i:s') : null;

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Delete existing responses
        $sql = "DELETE FROM evaluation_responses WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();

        // Insert new responses
        $sql = "INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        foreach ($ratings as $criteria_id => $rating) {
            $comment = $comments[$criteria_id] ?? '';
            $stmt->bind_param("iiss", $evaluation_id, $criteria_id, $rating, $comment);
            $stmt->execute();

            // Get criteria weight and max_rating for score calculation
            $weight = 1; // Default weight
            $max_rating = 5; // Default max rating
            foreach ($criteria_by_category as $category) {
                foreach ($category['criteria'] as $criteria) {
                    if ($criteria['criteria_id'] == $criteria_id) {
                        $weight = $criteria['weight'];
                        $max_rating = $criteria['max_rating'];
                        break 2;
                    }
                }
            }

            // Calculate weighted score (normalize rating by max_rating first)
            $weighted_score = ($rating / $max_rating) * $weight;
            $total_score += $weighted_score;
            $total_weight += $weight;
        }

        // Calculate final score (normalized to 5)
        $final_score = ($total_weight > 0) ? ($total_score / $total_weight) * 5 : 0;

        // Update evaluation
        $sql = "UPDATE evaluations SET
                total_score = ?,
                comments = ?,
                status = ?,
                submission_date = ?,
                updated_at = NOW()
                WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsssi", $final_score, $overall_comment, $status, $submission_date, $evaluation_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $success_message = ($action === 'save_draft') ? "Evaluation saved as draft." : "Evaluation submitted successfully.";

        // Redirect to evaluations list if submitted
        if ($action === 'submit') {
            redirect($base_url . 'dean/evaluations.php?success=' . urlencode($success_message));
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error saving evaluation: " . $e->getMessage();
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
            <h1 class="h3 mb-0 text-gray-800">
                <?php echo $evaluation ? 'Edit Evaluation' : 'New Evaluation'; ?>
            </h1>
            <?php if ($evaluation): ?>
                <div>
                    <a href="<?php echo $base_url; ?>dean/print_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm" target="_blank">
                        <i class="fas fa-print fa-sm text-white-50 mr-1"></i> Print
                    </a>
                    <a href="<?php echo $base_url; ?>dean/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                    </a>
                </div>
            <?php endif; ?>
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

        <?php if ($evaluation): ?>
            <!-- Evaluation Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Evaluation Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-theme">Evaluatee Information</h5>
                            <p>
                                <strong>Name:</strong> <?php echo $evaluation['evaluatee_name']; ?><br>
                                <strong>Position:</strong> <?php echo $evaluation['evaluatee_position'] ?? 'Head of Department'; ?><br>
                                <strong>Department:</strong> <?php echo $evaluation['department_name']; ?><br>
                                <strong>Email:</strong> <?php echo $evaluation['evaluatee_email']; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-theme">Evaluation Details</h5>
                            <p>
                                <strong>Period:</strong> <?php echo $evaluation['period_title']; ?><br>
                                <strong>Academic Year:</strong> <?php echo $evaluation['academic_year']; ?><br>
                                <strong>Semester:</strong> <?php echo $evaluation['semester']; ?><br>
                                <strong>Status:</strong>
                                <span class="badge badge-<?php echo ($evaluation['status'] === 'draft') ? 'secondary' : 'info'; ?>">
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
                                                        <?php if (!empty($criteria['description'])): ?>
                                                            <p class="small text-muted mb-0"><?php echo $criteria['description']; ?></p>
                                                        <?php endif; ?>
                                                        <p class="small text-info mb-0">Weight: <?php echo $criteria['weight']; ?></p>
                                                    </td>
                                                    <td>
                                                        <select class="form-control" name="rating[<?php echo $criteria['criteria_id']; ?>]" required>
                                                            <option value="">Select</option>
                                                            <?php for ($i = $criteria['min_rating']; $i <= $criteria['max_rating']; $i++): ?>
                                                                <option value="<?php echo $i; ?>" <?php echo (isset($responses[$criteria['criteria_id']]) && $responses[$criteria['criteria_id']]['rating'] == $i) ? 'selected' : ''; ?>>
                                                                    <?php echo $i; ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control" name="comment[<?php echo $criteria['criteria_id']; ?>]" rows="2"><?php echo isset($responses[$criteria['criteria_id']]) ? $responses[$criteria['criteria_id']]['comment'] : ''; ?></textarea>
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
                                <textarea class="form-control" name="overall_comment" rows="4" placeholder="Enter your overall comments about the evaluatee's performance..."><?php echo $evaluation['comments'] ?? ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="action" value="save_draft" class="btn btn-secondary">
                                    <i class="fas fa-save mr-1"></i> Save as Draft
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to submit this evaluation? You will not be able to make changes after submission.')">
                                    <i class="fas fa-paper-plane mr-1"></i> Submit Evaluation
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>No evaluation criteria found for this evaluation. Please contact the administrator.</p>
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <p><?php echo $error_message; ?></p>
                <a href="<?php echo $base_url; ?>dean/department_heads.php" class="btn btn-secondary mt-3">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Department Heads
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once $GLOBALS['BASE_PATH'] . '/includes/footer_management.php';
?>
