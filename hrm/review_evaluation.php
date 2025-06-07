<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - Review Evaluation
 */

// Include configuration file
require_once dirname(__DIR__) . '/includes/config.php';

// Check if user is logged in and has hrm role
if (!is_logged_in() || !has_role('hrm')) {
    redirect($base_url . 'login.php');
}

// Initialize variables
$success_message = '';
$error_message = '';
$user_id = $_SESSION['user_id'];
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$evaluator = null;
$period = null;
$responses_by_category = [];

// Check if evaluation_id is provided
if ($evaluation_id <= 0) {
    redirect($base_url . 'hrm/evaluations.php');
}

// Get evaluation information
$sql = "SELECT e.*,
        p.title as period_title, p.academic_year, p.semester, p.start_date, p.end_date,
        ee.full_name as evaluatee_name, ee.email as evaluatee_email, ee.role as evaluatee_role,
        ee.profile_image as evaluatee_image, ee.position as evaluatee_position,
        er.full_name as evaluator_name, er.email as evaluator_email, er.role as evaluator_role,
        er.profile_image as evaluator_image, er.position as evaluator_position,
        d.name as department_name, d.code as department_code,
        c.name as college_name, c.code as college_code
        FROM evaluations e
        JOIN evaluation_periods p ON e.period_id = p.period_id
        JOIN users ee ON e.evaluatee_id = ee.user_id
        JOIN users er ON e.evaluator_id = er.user_id
        LEFT JOIN departments d ON ee.department_id = d.department_id
        LEFT JOIN colleges c ON ee.college_id = c.college_id
        WHERE e.evaluation_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $evaluation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $evaluation = $result->fetch_assoc();

    // Extract individual objects for easier access
    $evaluatee = [
        'user_id' => $evaluation['evaluatee_id'],
        'full_name' => $evaluation['evaluatee_name'],
        'email' => $evaluation['evaluatee_email'],
        'role' => $evaluation['evaluatee_role'],
        'profile_image' => $evaluation['evaluatee_image'],
        'position' => $evaluation['evaluatee_position']
    ];

    $evaluator = [
        'user_id' => $evaluation['evaluator_id'],
        'full_name' => $evaluation['evaluator_name'],
        'email' => $evaluation['evaluator_email'],
        'role' => $evaluation['evaluator_role'],
        'profile_image' => $evaluation['evaluator_image'],
        'position' => $evaluation['evaluator_position']
    ];

    $period = [
        'period_id' => $evaluation['period_id'],
        'title' => $evaluation['period_title'],
        'academic_year' => $evaluation['academic_year'],
        'semester' => $evaluation['semester'],
        'start_date' => $evaluation['start_date'],
        'end_date' => $evaluation['end_date']
    ];
} else {
    redirect($base_url . 'hrm/evaluations.php');
}

// Process form submission for review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_evaluation'])) {
        $hr_comments = sanitize_input($_POST['hr_comments']);

        // Update evaluation status to approved
        $sql = "UPDATE evaluations SET status = 'approved', hr_comments = ?, hr_reviewed_by = ?, hr_reviewed_at = NOW(), updated_at = NOW() WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $hr_comments, $user_id, $evaluation_id);

        if ($stmt->execute()) {
            $success_message = "Evaluation approved successfully.";
            // Update the evaluation status in our local variable
            $evaluation['status'] = 'approved';
        } else {
            $error_message = "Failed to approve evaluation. Please try again.";
        }
    } elseif (isset($_POST['reject_evaluation'])) {
        $hr_comments = sanitize_input($_POST['hr_comments']);
        $rejection_reason = sanitize_input($_POST['rejection_reason']);

        // Update evaluation status to rejected
        $sql = "UPDATE evaluations SET status = 'rejected', hr_comments = ?, rejection_reason = ?, hr_reviewed_by = ?, hr_reviewed_at = NOW(), updated_at = NOW() WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $hr_comments, $rejection_reason, $user_id, $evaluation_id);

        if ($stmt->execute()) {
            $success_message = "Evaluation rejected. Feedback has been sent to the evaluator.";
            // Update the evaluation status in our local variable
            $evaluation['status'] = 'rejected';
        } else {
            $error_message = "Failed to reject evaluation. Please try again.";
        }
    } elseif (isset($_POST['request_revision'])) {
        $hr_comments = sanitize_input($_POST['hr_comments']);
        $revision_notes = sanitize_input($_POST['revision_notes']);

        // Update evaluation status to needs revision
        $sql = "UPDATE evaluations SET status = 'needs_revision', hr_comments = ?, revision_notes = ?, hr_reviewed_by = ?, hr_reviewed_at = NOW(), updated_at = NOW() WHERE evaluation_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $hr_comments, $revision_notes, $user_id, $evaluation_id);

        if ($stmt->execute()) {
            $success_message = "Revision requested. The evaluator has been notified.";
            // Update the evaluation status in our local variable
            $evaluation['status'] = 'needs_revision';
        } else {
            $error_message = "Failed to request revision. Please try again.";
        }
    }
}

// Get evaluation responses
$responses_by_category = [];

// Check if required tables exist
$tables_exist = true;
$required_tables = ['evaluation_responses', 'evaluation_criteria', 'evaluation_categories'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if (!$result || $result->num_rows === 0) {
        $tables_exist = false;
        break;
    }
}

if ($tables_exist) {
    $sql = "SELECT r.*, c.name as criteria_name, c.description as criteria_description,
            cat.name as category_name, cat.description as category_description
            FROM evaluation_responses r
            JOIN evaluation_criteria c ON r.criteria_id = c.criteria_id
            JOIN evaluation_categories cat ON c.category_id = cat.category_id
            WHERE r.evaluation_id = ?
            ORDER BY cat.category_id, c.criteria_id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $evaluation_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $category_name = $row['category_name'];
            if (!isset($responses_by_category[$category_name])) {
                $responses_by_category[$category_name] = [
                    'description' => $row['category_description'],
                    'responses' => []
                ];
            }
            $responses_by_category[$category_name]['responses'][] = $row;
        }
    }
}

// Include header
include_once dirname(__DIR__) . '/includes/header_management.php';

// Include sidebar
include_once dirname(__DIR__) . '/includes/sidebar.php';
?>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Review Evaluation</h1>
            <div>
                <a href="<?php echo $base_url; ?>hrm/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                </a>
                <a href="<?php echo $base_url; ?>hrm/view_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm">
                    <i class="fas fa-eye fa-sm text-white-50 mr-1"></i> View Details
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

        <!-- Evaluation Overview -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Overview</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-theme">Evaluatee Information</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo $base_url; ?>assets/images/profiles/<?php echo !empty($evaluatee['profile_image']) ? $evaluatee['profile_image'] : 'default-profile.svg'; ?>"
                                         alt="Profile" class="rounded-circle mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo $evaluatee['full_name']; ?></h6>
                                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $evaluatee['role'])); ?></small>
                                    </div>
                                </div>
                                <p><strong>Email:</strong> <?php echo $evaluatee['email']; ?></p>
                                <?php if (!empty($evaluatee['position'])): ?>
                                    <p><strong>Position:</strong> <?php echo $evaluatee['position']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['department_name'])): ?>
                                    <p><strong>Department:</strong> <?php echo $evaluation['department_name']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($evaluation['college_name'])): ?>
                                    <p><strong>College:</strong> <?php echo $evaluation['college_name']; ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold text-theme">Evaluator Information</h5>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo $base_url; ?>assets/images/profiles/<?php echo !empty($evaluator['profile_image']) ? $evaluator['profile_image'] : 'default-profile.svg'; ?>"
                                         alt="Profile" class="rounded-circle mr-3" style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo $evaluator['full_name']; ?></h6>
                                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $evaluator['role'])); ?></small>
                                    </div>
                                </div>
                                <p><strong>Email:</strong> <?php echo $evaluator['email']; ?></p>
                                <?php if (!empty($evaluator['position'])): ?>
                                    <p><strong>Position:</strong> <?php echo $evaluator['position']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Details</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Period:</strong> <?php echo $period['title']; ?></p>
                        <p><strong>Academic Year:</strong> <?php echo $period['academic_year']; ?></p>
                        <p><strong>Semester:</strong> <?php echo $period['semester']; ?></p>
                        <p><strong>Status:</strong>
                            <span class="badge badge-<?php
                                echo ($evaluation['status'] === 'approved') ? 'success' :
                                    (($evaluation['status'] === 'rejected') ? 'danger' :
                                    (($evaluation['status'] === 'submitted') ? 'warning' : 'secondary'));
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $evaluation['status'])); ?>
                            </span>
                        </p>
                        <?php if (!empty($evaluation['total_score'])): ?>
                            <p><strong>Total Score:</strong>
                                <span class="badge badge-primary"><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</span>
                            </p>
                        <?php endif; ?>
                        <p><strong>Submitted:</strong> <?php echo date('M d, Y H:i', strtotime($evaluation['submission_date'])); ?></p>
                        <?php if (!empty($evaluation['hr_reviewed_at'])): ?>
                            <p><strong>Reviewed:</strong> <?php echo date('M d, Y H:i', strtotime($evaluation['hr_reviewed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Responses -->
        <?php if (!empty($responses_by_category)): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Evaluation Responses</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($responses_by_category as $category_name => $category_data): ?>
                        <div class="mb-4">
                            <h5 class="text-theme border-bottom pb-2"><?php echo $category_name; ?></h5>
                            <?php if (!empty($category_data['description'])): ?>
                                <p class="text-muted mb-3"><?php echo $category_data['description']; ?></p>
                            <?php endif; ?>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 50%;">Criteria</th>
                                            <th style="width: 15%;">Rating</th>
                                            <th style="width: 35%;">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category_data['responses'] as $response): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $response['criteria_name']; ?></strong>
                                                    <?php if (!empty($response['criteria_description'])): ?>
                                                        <br><small class="text-muted"><?php echo $response['criteria_description']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-<?php
                                                        if ($response['rating'] >= 4) echo 'success';
                                                        elseif ($response['rating'] >= 3) echo 'warning';
                                                        else echo 'danger';
                                                    ?> badge-lg">
                                                        <?php echo $response['rating']; ?>/5
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo !empty($response['comments']) ? $response['comments'] : '<em class="text-muted">No comments</em>'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Overall Comments -->
        <?php if (!empty($evaluation['comments'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Overall Comments</h6>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br($evaluation['comments']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- HR Review Section -->
        <?php if ($evaluation['status'] === 'submitted' || $evaluation['status'] === 'needs_revision'): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">HR Review Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Approve Evaluation -->
                        <div class="col-lg-4 mb-3">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-check-circle mr-2"></i>Approve Evaluation</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $evaluation_id); ?>">
                                        <div class="form-group">
                                            <label for="approve_comments">HR Comments</label>
                                            <textarea class="form-control" id="approve_comments" name="hr_comments" rows="3" placeholder="Add your approval comments..."></textarea>
                                        </div>
                                        <button type="submit" name="approve_evaluation" class="btn btn-success btn-block">
                                            <i class="fas fa-check mr-1"></i> Approve
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Request Revision -->
                        <div class="col-lg-4 mb-3">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0"><i class="fas fa-edit mr-2"></i>Request Revision</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $evaluation_id); ?>">
                                        <div class="form-group">
                                            <label for="revision_comments">HR Comments</label>
                                            <textarea class="form-control" id="revision_comments" name="hr_comments" rows="2" placeholder="General comments..."></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="revision_notes">Revision Notes</label>
                                            <textarea class="form-control" id="revision_notes" name="revision_notes" rows="2" placeholder="Specific areas that need revision..." required></textarea>
                                        </div>
                                        <button type="submit" name="request_revision" class="btn btn-warning btn-block">
                                            <i class="fas fa-edit mr-1"></i> Request Revision
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Evaluation -->
                        <div class="col-lg-4 mb-3">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0"><i class="fas fa-times-circle mr-2"></i>Reject Evaluation</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $evaluation_id); ?>" onsubmit="return confirm('Are you sure you want to reject this evaluation? This action cannot be undone.');">
                                        <div class="form-group">
                                            <label for="reject_comments">HR Comments</label>
                                            <textarea class="form-control" id="reject_comments" name="hr_comments" rows="2" placeholder="General comments..."></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label for="rejection_reason">Rejection Reason</label>
                                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="2" placeholder="Specific reason for rejection..." required></textarea>
                                        </div>
                                        <button type="submit" name="reject_evaluation" class="btn btn-danger btn-block">
                                            <i class="fas fa-times mr-1"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Previous HR Review (if exists) -->
        <?php if (!empty($evaluation['hr_comments']) || !empty($evaluation['rejection_reason']) || !empty($evaluation['revision_notes'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-theme">Previous HR Review</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($evaluation['hr_comments'])): ?>
                        <div class="mb-3">
                            <h6 class="font-weight-bold">HR Comments:</h6>
                            <p><?php echo nl2br($evaluation['hr_comments']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($evaluation['rejection_reason'])): ?>
                        <div class="mb-3">
                            <h6 class="font-weight-bold text-danger">Rejection Reason:</h6>
                            <p class="text-danger"><?php echo nl2br($evaluation['rejection_reason']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($evaluation['revision_notes'])): ?>
                        <div class="mb-3">
                            <h6 class="font-weight-bold text-warning">Revision Notes:</h6>
                            <p class="text-warning"><?php echo nl2br($evaluation['revision_notes']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($evaluation['hr_reviewed_at'])): ?>
                        <small class="text-muted">
                            Reviewed on: <?php echo date('M d, Y H:i', strtotime($evaluation['hr_reviewed_at'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h5 class="mb-4">Additional Actions</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="<?php echo $base_url; ?>hrm/view_evaluation.php?id=<?php echo $evaluation_id; ?>" class="btn btn-info btn-block">
                                    <i class="fas fa-eye mr-2"></i>View Full Details
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $evaluatee['user_id']; ?>" class="btn btn-primary btn-block">
                                    <i class="fas fa-user mr-2"></i>Staff Report
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="<?php echo $base_url; ?>hrm/evaluations.php?evaluatee_id=<?php echo $evaluatee['user_id']; ?>" class="btn btn-secondary btn-block">
                                    <i class="fas fa-history mr-2"></i>Evaluation History
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="<?php echo $base_url; ?>hrm/evaluations.php" class="btn btn-outline-secondary btn-block">
                                    <i class="fas fa-list mr-2"></i>All Evaluations
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once dirname(__DIR__) . '/includes/footer_management.php';
?>