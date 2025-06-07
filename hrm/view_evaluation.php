<?php
/**
 * Samara University Academic Performance Evaluation System
 * HRM - View Evaluation
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
        ee.full_name as evaluatee_name, ee.email as evaluatee_email, ee.role as evaluatee_role, ee.profile_image as evaluatee_image,
        er.full_name as evaluator_name, er.email as evaluator_email, er.role as evaluator_role, er.profile_image as evaluator_image,
        d.name as department_name, c.name as college_name
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
    $evaluatee = [
        'user_id' => $evaluation['evaluatee_id'],
        'full_name' => $evaluation['evaluatee_name'],
        'email' => $evaluation['evaluatee_email'],
        'role' => $evaluation['evaluatee_role'],
        'profile_image' => $evaluation['evaluatee_image'],
        'department_name' => $evaluation['department_name'],
        'college_name' => $evaluation['college_name']
    ];
    $evaluator = [
        'user_id' => $evaluation['evaluator_id'],
        'full_name' => $evaluation['evaluator_name'],
        'email' => $evaluation['evaluator_email'],
        'role' => $evaluation['evaluator_role'],
        'profile_image' => $evaluation['evaluator_image']
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

    if ($stmt) {
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $category_name = $row['category_name'];
                if (!isset($responses_by_category[$category_name])) {
                    $responses_by_category[$category_name] = [
                        'description' => $row['category_description'],
                        'criteria' => []
                    ];
                }
                $responses_by_category[$category_name]['criteria'][] = $row;
            }
        }
    }
}

// Process form submission for adding comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = sanitize_input($_POST['comment']);

    if (empty($comment)) {
        $error_message = "Comment cannot be empty.";
    } else {
        // Check if evaluation_comments table exists
        $table_exists = false;
        $result = $conn->query("SHOW TABLES LIKE 'evaluation_comments'");
        if ($result && $result->num_rows > 0) {
            $table_exists = true;
        }

        if (!$table_exists) {
            // Create evaluation_comments table if it doesn't exist
            $sql = "CREATE TABLE evaluation_comments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    evaluation_id INT NOT NULL,
                    user_id INT NOT NULL,
                    comment TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (evaluation_id) REFERENCES evaluations(evaluation_id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                )";
            $conn->query($sql);
            $table_exists = true;
        }

        if ($table_exists) {
            $sql = "INSERT INTO evaluation_comments (evaluation_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iis", $evaluation_id, $user_id, $comment);
                if ($stmt->execute()) {
                    $success_message = "Comment added successfully.";
                } else {
                    $error_message = "Failed to add comment. Please try again.";
                }
            } else {
                $error_message = "Failed to prepare statement for adding comment.";
            }
        } else {
            $error_message = "Failed to create comments table.";
        }
    }
}

// Get evaluation comments
$comments = [];

// Check if evaluation_comments table exists
$table_exists = false;
$result = $conn->query("SHOW TABLES LIKE 'evaluation_comments'");
if ($result && $result->num_rows > 0) {
    $table_exists = true;
}

if ($table_exists) {
    $sql = "SELECT c.*, u.full_name, u.role, u.profile_image
            FROM evaluation_comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.evaluation_id = ?
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $evaluation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $comments[] = $row;
            }
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
            <h1 class="h3 mb-0 text-gray-800">View Evaluation</h1>
            <div>
                <a href="<?php echo $base_url; ?>hrm/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                </a>
                <a href="<?php echo $base_url; ?>hrm/staff_report.php?user_id=<?php echo $evaluatee['user_id']; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm ml-2">
                    <i class="fas fa-chart-bar fa-sm text-white-50 mr-1"></i> View Staff Report
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

        <div class="row">
            <!-- Evaluation Information -->
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Information</h6>
                        <div>
                            <span class="badge badge-<?php
                                if ($evaluation['status'] === 'completed') {
                                    echo 'success';
                                } elseif ($evaluation['status'] === 'submitted') {
                                    echo 'warning';
                                } elseif ($evaluation['status'] === 'in_progress') {
                                    echo 'info';
                                } else {
                                    echo 'secondary';
                                }
                            ?> badge-lg">
                                <?php echo ucfirst(str_replace('_', ' ', $evaluation['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="evaluation-info-section">
                                    <h5 class="mb-3">Evaluatee</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if (!empty($evaluatee['profile_image'])): ?>
                                            <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $evaluatee['profile_image']; ?>" alt="<?php echo $evaluatee['full_name']; ?>" class="img-profile rounded-circle mr-3" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo $evaluatee['full_name']; ?></h6>
                                            <p class="mb-0 text-muted"><?php echo $evaluatee['email']; ?></p>
                                            <span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $evaluatee['role'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Department:</span>
                                        <span class="info-value"><?php echo $evaluatee['department_name'] ?? 'N/A'; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">College:</span>
                                        <span class="info-value"><?php echo $evaluatee['college_name'] ?? 'N/A'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="evaluation-info-section">
                                    <h5 class="mb-3">Evaluator</h5>
                                    <div class="d-flex align-items-center mb-3">
                                        <?php if (!empty($evaluator['profile_image'])): ?>
                                            <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $evaluator['profile_image']; ?>" alt="<?php echo $evaluator['full_name']; ?>" class="img-profile rounded-circle mr-3" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?php echo $evaluator['full_name']; ?></h6>
                                            <p class="mb-0 text-muted"><?php echo $evaluator['email']; ?></p>
                                            <span class="badge badge-secondary"><?php echo ucfirst(str_replace('_', ' ', $evaluator['role'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="evaluation-info-section mt-4">
                                    <h5 class="mb-3">Evaluation Period</h5>
                                    <div class="info-item">
                                        <span class="info-label">Title:</span>
                                        <span class="info-value"><?php echo $period['title']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Academic Year:</span>
                                        <span class="info-value"><?php echo $period['academic_year']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Semester:</span>
                                        <span class="info-value"><?php echo $period['semester']; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Period:</span>
                                        <span class="info-value"><?php echo date('M d, Y', strtotime($period['start_date'])); ?> - <?php echo date('M d, Y', strtotime($period['end_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="evaluation-info-section">
                                    <h5 class="mb-3">Evaluation Details</h5>
                                    <div class="info-item">
                                        <span class="info-label">Date Submitted:</span>
                                        <span class="info-value"><?php echo date('M d, Y h:i A', strtotime($evaluation['created_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Last Updated:</span>
                                        <span class="info-value"><?php echo date('M d, Y h:i A', strtotime($evaluation['updated_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Total Score:</span>
                                        <span class="info-value font-weight-bold"><?php echo number_format($evaluation['total_score'], 2); ?>/5.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="evaluation-info-section">
                                    <h5 class="mb-3">Overall Comments</h5>
                                    <div class="overall-comments p-3 bg-light rounded">
                                        <?php if (!empty($evaluation['comments'])): ?>
                                            <?php echo nl2br($evaluation['comments']); ?>
                                        <?php else: ?>
                                            <em>No overall comments provided.</em>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Evaluation Responses -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Evaluation Responses</h6>
                    </div>
                    <div class="card-body">
                        <?php if (count($responses_by_category) > 0): ?>
                            <div class="accordion" id="evaluationAccordion">
                                <?php $category_index = 0; foreach ($responses_by_category as $category_name => $category_data): $category_index++; ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-light" id="heading<?php echo $category_index; ?>">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link btn-block text-left d-flex justify-content-between" type="button" data-toggle="collapse" data-target="#collapse<?php echo $category_index; ?>" aria-expanded="<?php echo ($category_index === 1) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $category_index; ?>">
                                                    <span><?php echo $category_name; ?></span>
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapse<?php echo $category_index; ?>" class="collapse <?php echo ($category_index === 1) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $category_index; ?>" data-parent="#evaluationAccordion">
                                            <div class="card-body">
                                                <?php if (!empty($category_data['description'])): ?>
                                                    <p class="mb-3"><?php echo $category_data['description']; ?></p>
                                                <?php endif; ?>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th width="60%">Criteria</th>
                                                                <th width="15%">Score</th>
                                                                <th width="25%">Comments</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($category_data['criteria'] as $criteria): ?>
                                                                <tr>
                                                                    <td>
                                                                        <strong><?php echo $criteria['criteria_name']; ?></strong>
                                                                        <?php if (!empty($criteria['criteria_description'])): ?>
                                                                            <p class="mb-0 text-muted small"><?php echo $criteria['criteria_description']; ?></p>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="score-badge badge badge-<?php
                                                                                if ($criteria['score'] >= 4.5) {
                                                                                    echo 'success';
                                                                                } elseif ($criteria['score'] >= 3.5) {
                                                                                    echo 'info';
                                                                                } elseif ($criteria['score'] >= 2.5) {
                                                                                    echo 'warning';
                                                                                } else {
                                                                                    echo 'danger';
                                                                                }
                                                                            ?> mr-2">
                                                                                <?php echo $criteria['score']; ?>
                                                                            </div>
                                                                            <div class="score-stars">
                                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                    <?php if ($i <= $criteria['score']): ?>
                                                                                        <i class="fas fa-star text-warning"></i>
                                                                                    <?php elseif ($i - 0.5 <= $criteria['score']): ?>
                                                                                        <i class="fas fa-star-half-alt text-warning"></i>
                                                                                    <?php else: ?>
                                                                                        <i class="far fa-star text-warning"></i>
                                                                                    <?php endif; ?>
                                                                                <?php endfor; ?>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (!empty($criteria['comments'])): ?>
                                                                            <?php echo nl2br($criteria['comments']); ?>
                                                                        <?php else: ?>
                                                                            <em class="text-muted">No comments</em>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No evaluation responses found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comments Section -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-theme">Comments & Feedback</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $evaluation_id); ?>">
                            <div class="form-group">
                                <label for="comment">Add Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-theme">
                                <i class="fas fa-comment mr-1"></i> Submit Comment
                            </button>
                        </form>

                        <hr>

                        <div class="comments-section mt-4">
                            <h6 class="mb-3">Previous Comments</h6>
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item mb-3 p-3 border rounded">
                                        <div class="d-flex">
                                            <div class="comment-avatar mr-3">
                                                <?php if (!empty($comment['profile_image'])): ?>
                                                    <img src="<?php echo $base_url; ?>uploads/profiles/<?php echo $comment['profile_image']; ?>" alt="<?php echo $comment['full_name']; ?>" class="img-profile rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="img-profile rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1rem;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="comment-content">
                                                <div class="comment-header d-flex justify-content-between">
                                                    <div>
                                                        <span class="font-weight-bold"><?php echo $comment['full_name']; ?></span>
                                                        <span class="badge badge-secondary ml-2"><?php echo ucfirst(str_replace('_', ' ', $comment['role'])); ?></span>
                                                    </div>
                                                    <div class="comment-date text-muted small">
                                                        <?php echo date('M d, Y h:i A', strtotime($comment['created_at'])); ?>
                                                    </div>
                                                </div>
                                                <div class="comment-text mt-2">
                                                    <?php echo nl2br($comment['comment']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No comments yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .info-item {
        margin-bottom: 10px;
    }

    .info-label {
        font-weight: bold;
        margin-right: 5px;
    }

    .score-badge {
        font-size: 1rem;
        padding: 5px 10px;
    }

    .accordion .card-header {
        cursor: pointer;
    }

    .accordion .btn-link {
        color: #333;
        text-decoration: none;
    }

    .accordion .btn-link:hover {
        text-decoration: none;
    }

    .comment-item:nth-child(odd) {
        background-color: #f8f9fc;
    }
</style>

<?php
// Include footer
include_once BASE_PATH . '/includes/footer_management.php';
?>
