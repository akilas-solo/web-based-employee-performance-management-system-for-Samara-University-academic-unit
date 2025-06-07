<?php
/**
 * Samara University Academic Performance Evaluation System
 * Head of Department - View Evaluation
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
$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$evaluation = null;
$evaluatee = null;
$period = null;
$responses_by_category = [];

// Get evaluation details
if ($evaluation_id > 0) {
    $sql = "SELECT e.* FROM evaluations e WHERE e.evaluation_id = ? AND e.evaluator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $evaluation_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $evaluation = $result->fetch_assoc();

        // Get evaluatee details
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation['evaluatee_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $evaluatee = $result->fetch_assoc();
        }

        // Get period details
        $sql = "SELECT * FROM evaluation_periods WHERE period_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $evaluation['period_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $period = $result->fetch_assoc();
        }

        // Get evaluation responses by category
        $sql = "SELECT er.*, ec.name as criteria_name, ec.description as criteria_description,
                ec.weight, ec.min_rating, ec.max_rating, cat.category_id, cat.name as category_name
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
                $category_name = $row['category_name'];

                if (!isset($responses_by_category[$category_id])) {
                    $responses_by_category[$category_id] = [
                        'name' => $category_name,
                        'responses' => [],
                        'average_score' => 0,
                        'total_weight' => 0
                    ];
                }

                $responses_by_category[$category_id]['responses'][] = $row;
                $responses_by_category[$category_id]['total_weight'] += $row['weight'];
            }

            // Calculate average score for each category
            foreach ($responses_by_category as $category_id => &$category) {
                $weighted_sum = 0;
                foreach ($category['responses'] as $response) {
                    $normalized_score = ($response['rating'] / $response['max_rating']) * $response['weight'];
                    $weighted_sum += $normalized_score;
                }
                $category['average_score'] = ($weighted_sum / $category['total_weight']) * 5;
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
                <a href="<?php echo $base_url; ?>head/evaluations.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm mr-2">
                    <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> Back to Evaluations
                </a>
                <a href="<?php echo $base_url; ?>head/print_evaluation.php?id=<?php echo $evaluation_id; ?>" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" target="_blank">
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

        <?php if ($evaluation && $evaluatee && $period): ?>
            <!-- Evaluation Info Card -->
            <div class="row">
                <div class="col-lg-8">
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
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <h5 class="text-theme">Evaluation Details</h5>
                                    <p><strong>Status:</strong>
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
                                    <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($evaluation['created_at'])); ?></p>
                                    <?php if (!empty($evaluation['submission_date'])): ?>
                                        <p><strong>Submitted:</strong> <?php echo date('M d, Y', strtotime($evaluation['submission_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="text-theme">Overall Score</h5>
                                    <div class="h1 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($evaluation['total_score'], 2); ?>/5.00
                                    </div>
                                    <div class="progress progress-lg mt-2 mb-2">
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
                                    <p class="text-<?php
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
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-theme text-white">
                            <h6 class="m-0 font-weight-bold">Category Scores</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($responses_by_category as $category): ?>
                                <h5 class="small font-weight-bold">
                                    <?php echo $category['name']; ?>
                                    <span class="float-right"><?php echo number_format($category['average_score'], 2); ?>/5.00</span>
                                </h5>
                                <div class="progress mb-4">
                                    <div class="progress-bar bg-<?php
                                        $cat_score_percent = ($category['average_score'] / 5) * 100;
                                        if ($cat_score_percent >= 80) {
                                            echo 'success';
                                        } elseif ($cat_score_percent >= 60) {
                                            echo 'info';
                                        } elseif ($cat_score_percent >= 40) {
                                            echo 'warning';
                                        } else {
                                            echo 'danger';
                                        }
                                    ?>" role="progressbar" style="width: <?php echo $cat_score_percent; ?>%"
                                        aria-valuenow="<?php echo $category['average_score']; ?>" aria-valuemin="0" aria-valuemax="5"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($evaluation['comments'])): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-theme text-white">
                                <h6 class="m-0 font-weight-bold">Overall Comments</h6>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($evaluation['comments'])); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Evaluation Responses -->
            <?php foreach ($responses_by_category as $category): ?>
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
                                        <th width="10%">Weight</th>
                                        <th width="10%">Rating</th>
                                        <th width="40%">Comments</th>
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
                                            <td><?php echo $response['weight']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php
                                                    $rating_percent = ($response['rating'] / $response['max_rating']) * 100;
                                                    if ($rating_percent >= 80) {
                                                        echo 'success';
                                                    } elseif ($rating_percent >= 60) {
                                                        echo 'info';
                                                    } elseif ($rating_percent >= 40) {
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
        <?php else: ?>
            <div class="alert alert-danger">
                <p>Invalid evaluation parameters. Please select a valid evaluation.</p>
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
