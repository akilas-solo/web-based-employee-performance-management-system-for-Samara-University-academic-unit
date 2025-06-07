<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - Evaluation Form
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has head_of_department role
if (!is_logged_in() || !has_role('head_of_department')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$evaluator_id = $_SESSION['user_id'];
$evaluatee_id = isset($_GET['evaluatee_id']) ? (int)$_GET['evaluatee_id'] : 0;
$evaluation_id = isset($_GET['evaluation_id']) ? (int)$_GET['evaluation_id'] : 0;
$period_id = isset($_GET['period_id']) ? (int)$_GET['period_id'] : 0;
$success_message = '';
$error_message = '';
$evaluatee = null;
$evaluation = null;
$period = null;
$criteria_by_category = [];

// Check if evaluatee exists
if ($evaluatee_id > 0) {
    $sql = "SELECT * FROM users WHERE user_id = ? AND role = 'staff'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluatee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $evaluatee = $result->fetch_assoc();
    } else {
        $error_message = "Staff member not found.";
    }
}

// Check if period exists
if ($period_id > 0) {
    $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $period = $result->fetch_assoc();
    } else {
        $error_message = "Evaluation period not found.";
    }
}

// Check if evaluation exists or create new one
if ($evaluation_id > 0) {
    $sql = "SELECT * FROM evaluations WHERE evaluation_id = ? AND evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluation_id, $evaluator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();
        $evaluatee_id = $evaluation['evaluatee_id'];
        $period_id = $evaluation['period_id'];

        // Get evaluatee info
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluatee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $evaluatee = $result->fetch_assoc();
        }

        // Get period info
        $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $period_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $period = $result->fetch_assoc();
        }
    } else {
        $error_message = "Evaluation not found.";
    }
} elseif ($evaluatee_id > 0 && $period_id > 0) {
    // Check if evaluation already exists
    $sql = "SELECT * FROM evaluations WHERE evaluator_id = ? AND evaluatee_id = ? AND period_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $evaluator_id, $evaluatee_id, $period_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();
        $evaluation_id = $evaluation['evaluation_id'];
    } else {
        // Create new evaluation
        $sql = "INSERT INTO evaluations (evaluator_id, evaluatee_id, period_id, status) VALUES (?, ?, ?, 'draft')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $evaluator_id, $evaluatee_id, $period_id);
        if ($stmt->execute()) {
            $evaluation_id = $conn->insert_id;

            // Get the new evaluation
            $sql = "SELECT * FROM evaluations WHERE evaluation_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $evaluation_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $evaluation = $result->fetch_assoc();
            }
        } else {
            $error_message = "Error creating evaluation: " . $conn->error;
        }
    }
}

// Get evaluation criteria by category
if ($evaluation) {
    $sql = "SELECT ec.*, cat.name as category_name, cat.category_id, er.response_id, er.rating, er.comment
            FROM evaluation_criteria ec
            JOIN evaluation_categories cat ON ec.category_id = cat.category_id
            LEFT JOIN evaluation_responses er ON ec.criteria_id = er.criteria_id AND er.evaluation_id = ?
            WHERE FIND_IN_SET('head_of_department', ec.evaluator_roles) > 0
            AND FIND_IN_SET('staff', ec.evaluatee_roles) > 0
            ORDER BY cat.name ASC, ec.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_id = $row['category_id'];
            $category_name = $row['category_name'];

            if (!isset($criteria_by_category[$category_id])) {
                $criteria_by_category[$category_id] = [
                    'name' => $category_name,
                    'criteria' => []
                ];
            }

            $criteria_by_category[$category_id]['criteria'][] = $row;
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $evaluation) {
    $ratings = isset($_POST['rating']) ? $_POST['rating'] : [];
    $comments = isset($_POST['comment']) ? $_POST['comment'] : [];
    $overall_comment = sanitize_input($_POST['overall_comment']);
    $action = sanitize_input($_POST['action']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Save responses
        foreach ($ratings as $criteria_id => $rating) {
            $comment = isset($comments[$criteria_id]) ? sanitize_input($comments[$criteria_id]) : '';
            $response_id = 0;

            // Check if response exists
            $sql = "SELECT response_id FROM evaluation_responses WHERE evaluation_id = ? AND criteria_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $evaluation_id, $criteria_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Update existing response
                $row = $result->fetch_assoc();
                $response_id = $row['response_id'];

                $sql = "UPDATE evaluation_responses SET rating = ?, comment = ? WHERE response_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isi", $rating, $comment, $response_id);
                $stmt->execute();
            } else {
                // Insert new response
                $sql = "INSERT INTO evaluation_responses (evaluation_id, criteria_id, rating, comment) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiis", $evaluation_id, $criteria_id, $rating, $comment);
                $stmt->execute();
            }
        }

        // Calculate total score (normalized)
        $sql = "SELECT SUM((er.rating / ec.max_rating) * ec.weight) / SUM(ec.weight) * 5 as total_score
                FROM evaluation_responses er
                JOIN evaluation_criteria ec ON er.criteria_id = ec.criteria_id
                WHERE er.evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_score = $row['total_score'];

        // Update evaluation
        $status = ($action === 'save_draft') ? 'draft' : 'submitted';
        $submission_date = ($action === 'submit') ? date('Y-m-d H:i:s') : null;

        $sql = "UPDATE evaluations SET status = ?, comments = ?, total_score = ?, submission_date = ? WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $status, $overall_comment, $total_score, $submission_date, $evaluation_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        $success_message = ($action === 'save_draft') ? "Evaluation saved as draft." : "Evaluation submitted successfully.";

        // Redirect to evaluations list if submitted
        if ($action === 'submit') {
            redirect($base_url . 'head/evaluations.php?success=' . urlencode($success_message));
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error saving evaluation: " . $e->getMessage();
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
            <h1 class="h3 mb-0 text-gray-800">Staff Evaluation Form</h1>
            <a href="<?php echo $base_url; ?>head/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
            </a>
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

        <?php if ($evaluatee && $period && $evaluation): ?>
            <!-- Evaluation Info Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-theme text-white">
                    <h6 class="m-0 font-weight-bold">Evaluation Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-theme">Staff Information</h5>
                            <p><strong>Name:</strong> <?php echo $evaluatee['full_name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $evaluatee['email']; ?></p>
                            <p><strong>Position:</strong> <?php echo $evaluatee['position'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-theme">Evaluation Period</h5>
                            <p><strong>Title:</strong> <?php echo $period['title']; ?></p>
                            <p><strong>Academic Year:</strong> <?php echo $period['academic_year']; ?></p>
                            <p><strong>Semester:</strong> <?php echo $period['semester']; ?></p>
                            <p><strong>Period:</strong> <?php echo date('M d, Y', strtotime($period['start_date'])); ?> - <?php echo date('M d, Y', strtotime($period['end_date'])); ?></p>
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
                                                    </td>
                                                    <td>
                                                        <select class="form-control" name="rating[<?php echo $criteria['criteria_id']; ?>]" required>
                                                            <option value="">Select</option>
                                                            <?php for ($i = $criteria['min_rating']; $i <= $criteria['max_rating']; $i++): ?>
                                                                <option value="<?php echo $i; ?>" <?php echo (isset($criteria['rating']) && $criteria['rating'] == $i) ? 'selected' : ''; ?>>
                                                                    <?php echo $i; ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <textarea class="form-control" name="comment[<?php echo $criteria['criteria_id']; ?>]" rows="2"><?php echo isset($criteria['comment']) ? $criteria['comment'] : ''; ?></textarea>
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
                                <label for="overall_comment">Additional Comments</label>
                                <textarea class="form-control" id="overall_comment" name="overall_comment" rows="4"><?php echo $evaluation['comments'] ?? ''; ?></textarea>
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
                <p>Invalid evaluation parameters. Please select a valid staff member and evaluation period.</p>
                <a href="<?php echo $base_url; ?>head/evaluations.php" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Evaluations
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
