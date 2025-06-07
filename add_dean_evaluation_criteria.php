<?php
/**
 * Samara University Academic Performance Evaluation System
 * Add Dean Evaluation Criteria
 * 
 * This script adds evaluation criteria for college representatives to evaluate deans
 */

// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect($base_url . 'login.php');
}

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get existing categories
$categories = [];
$sql = "SELECT * FROM evaluation_categories";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[$row['category_id']] = $row['name'];
    }
}

// Define the criteria to add
$criteria = [
    // Leadership category
    [
        'category_name' => 'Administrative Duties',
        'name' => 'Strategic Leadership',
        'description' => 'Ability to develop and implement strategic plans for the college',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    [
        'category_name' => 'Administrative Duties',
        'name' => 'Resource Management',
        'description' => 'Effective allocation and management of college resources',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    [
        'category_name' => 'Administrative Duties',
        'name' => 'Decision Making',
        'description' => 'Quality and timeliness of decisions affecting the college',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    
    // Academic Leadership
    [
        'category_name' => 'Teaching Performance',
        'name' => 'Academic Program Development',
        'description' => 'Initiatives to improve academic programs and curriculum',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    [
        'category_name' => 'Teaching Performance',
        'name' => 'Faculty Development',
        'description' => 'Support for faculty professional development and growth',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    
    // Research and Innovation
    [
        'category_name' => 'Research Output',
        'name' => 'Research Promotion',
        'description' => 'Efforts to promote and support research activities in the college',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    [
        'category_name' => 'Research Output',
        'name' => 'Innovation Initiatives',
        'description' => 'Support for innovative projects and initiatives within the college',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    
    // Community Engagement
    [
        'category_name' => 'Community Service',
        'name' => 'Community Engagement',
        'description' => 'Initiatives to engage with the wider community and stakeholders',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ],
    [
        'category_name' => 'Community Service',
        'name' => 'Industry Partnerships',
        'description' => 'Development of partnerships with industry and external organizations',
        'weight' => 1.00,
        'min_rating' => 1,
        'max_rating' => 5,
        'evaluator_roles' => 'college',
        'evaluatee_roles' => 'dean'
    ]
];

// Add criteria to the database
$added_count = 0;
$skipped_count = 0;

foreach ($criteria as $criterion) {
    // Find category ID
    $category_id = array_search($criterion['category_name'], $categories);
    
    if (!$category_id) {
        // Create category if it doesn't exist
        $sql = "INSERT INTO evaluation_categories (name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $description = "Evaluation of " . strtolower($criterion['category_name']);
        $stmt->bind_param("ss", $criterion['category_name'], $description);
        $stmt->execute();
        $category_id = $conn->insert_id;
        $categories[$category_id] = $criterion['category_name'];
    }
    
    // Check if criterion already exists
    $sql = "SELECT criteria_id FROM evaluation_criteria 
            WHERE category_id = ? AND name = ? AND evaluator_roles = ? AND evaluatee_roles = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $category_id, $criterion['name'], $criterion['evaluator_roles'], $criterion['evaluatee_roles']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Add criterion
        $sql = "INSERT INTO evaluation_criteria 
                (category_id, name, description, weight, min_rating, max_rating, evaluator_roles, evaluatee_roles) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issddiss", 
            $category_id, 
            $criterion['name'], 
            $criterion['description'], 
            $criterion['weight'], 
            $criterion['min_rating'], 
            $criterion['max_rating'], 
            $criterion['evaluator_roles'], 
            $criterion['evaluatee_roles']
        );
        $stmt->execute();
        $added_count++;
    } else {
        $skipped_count++;
    }
}

// Close connection
$conn->close();

// Output results
echo "<h2>Dean Evaluation Criteria Setup</h2>";
echo "<p>Added $added_count new evaluation criteria for dean evaluations.</p>";
echo "<p>Skipped $skipped_count criteria that already existed.</p>";
echo "<p><a href='{$base_url}admin/evaluation_criteria.php'>View All Criteria</a></p>";
echo "<p><a href='{$base_url}college/deans.php'>Go to Deans Page</a></p>";
?>
