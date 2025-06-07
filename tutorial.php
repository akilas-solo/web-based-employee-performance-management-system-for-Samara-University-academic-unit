<?php
/**
 * Samara University Academic Performance Evaluation System
 * Unified Tutorial & Workflow Guide - Role Selection
 */

// Include configuration file
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect($base_url . 'login.php');
}

// Get user's current role for default selection
$user_role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutorial & Workflow Guide - Samara University</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Inter', sans-serif; background-color: #f8f9fa;"><?antml:parameter>
<parameter name="old_str_start_line_number_1">1

<style>
.tutorial-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    margin-bottom: 40px;
}

.role-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 3px solid transparent;
    height: 100%;
}

.role-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.role-card.admin {
    border-left: 5px solid #22AE9A;
}

.role-card.admin:hover {
    border-color: #22AE9A;
    background: linear-gradient(135deg, #f8fdfc 0%, #e7f7f5 100%);
}

.role-card.college {
    border-left: 5px solid #2ECC71;
}

.role-card.college:hover {
    border-color: #2ECC71;
    background: linear-gradient(135deg, #f8fdf9 0%, #E9F7EF 100%);
}

.role-card.dean {
    border-left: 5px solid #9B59B6;
}

.role-card.dean:hover {
    border-color: #9B59B6;
    background: linear-gradient(135deg, #fdfafd 0%, #F4ECF7 100%);
}

.role-card.head {
    border-left: 5px solid #3498DB;
}

.role-card.head:hover {
    border-color: #3498DB;
    background: linear-gradient(135deg, #f9fcfe 0%, #EBF3FD 100%);
}

.role-card.hrm {
    border-left: 5px solid #E67E22;
}

.role-card.hrm:hover {
    border-color: #E67E22;
    background: linear-gradient(135deg, #fefcf9 0%, #FDF2E9 100%);
}

.role-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 35px;
    margin: 0 auto 20px;
    transition: all 0.3s ease;
}

.role-icon.admin {
    background: #e7f7f5;
    color: #22AE9A;
}

.role-icon.college {
    background: #E9F7EF;
    color: #2ECC71;
}

.role-icon.dean {
    background: #F4ECF7;
    color: #9B59B6;
}

.role-icon.head {
    background: #EBF3FD;
    color: #3498DB;
}

.role-icon.hrm {
    background: #FDF2E9;
    color: #E67E22;
}

.role-card:hover .role-icon {
    transform: scale(1.1);
}

.role-title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 15px;
    color: #2c3e50;
}

.role-description {
    color: #7f8c8d;
    margin-bottom: 20px;
    line-height: 1.6;
}

.role-features {
    list-style: none;
    padding: 0;
}

.role-features li {
    padding: 5px 0;
    color: #34495e;
}

.role-features li i {
    margin-right: 10px;
    width: 16px;
}

.current-role-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #27ae60;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.tutorial-content {
    display: none;
    margin-top: 40px;
}

.tutorial-content.active {
    display: block;
}

.workflow-step {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-left: 5px solid #667eea;
}

.step-number {
    background: #667eea;
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 20px;
}

.feature-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    height: 100%;
}

.feature-icon-small {
    background: #f8f9fa;
    color: #667eea;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 15px;
}

.btn-back {
    background: #6c757d;
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-back:hover {
    background: #5a6268;
    color: white;
    transform: translateY(-2px);
}

.nav-pills .nav-link.active {
    background-color: #667eea;
    border-color: #667eea;
}

.nav-pills .nav-link {
    color: #667eea;
    border: 2px solid #667eea;
    margin-right: 10px;
    margin-bottom: 10px;
}

.nav-pills .nav-link:hover {
    background-color: #f8f9fa;
    color: #5a67d8;
}

.alert-tutorial {
    background: #f8f9fa;
    border: 1px solid #667eea;
    color: #5a67d8;
    border-radius: 10px;
}
</style>

<!-- Tutorial Content -->
<div class="container-fluid p-0">
    <!-- Hero Section -->
    <div class="tutorial-hero">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 font-weight-bold mb-3">
                        <i class="fas fa-graduation-cap mr-3"></i>
                        System Tutorial & Workflow Guide
                    </h1>
                    <p class="lead mb-4">
                        Master the Academic Performance Evaluation System with comprehensive tutorials
                        tailored for your role. Learn workflows, explore features, and discover best practices.
                    </p>
                    <div class="d-flex align-items-center">
                        <div class="mr-4">
                            <h5 class="mb-1">Your Current Role:</h5>
                            <span class="badge badge-light badge-lg"><?php echo ucfirst(str_replace('_', ' ', $user_role)); ?></span>
                        </div>
                        <div>
                            <h5 class="mb-1">Select Any Role:</h5>
                            <span class="badge badge-light badge-lg">Choose Below</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-users-cog" style="font-size: 120px; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Role Selection Section -->
        <div id="roleSelection">
            <h2 class="text-center mb-5">Select a Role to View Tutorial</h2>

            <div class="row">
                <!-- Admin Role -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card admin" onclick="showTutorial('admin')" style="position: relative;">
                        <?php if ($user_role === 'admin'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="role-title text-center">Administrator</h3>
                        <p class="role-description text-center">
                            System-wide access and control of the Samara University Academic Performance
                            Evaluation System. Manage all users, organizational structure, and evaluation settings.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>Users management (create, edit, delete)</li>
                            <li><i class="fas fa-check text-success"></i>Colleges & departments setup</li>
                            <li><i class="fas fa-check text-success"></i>Evaluation periods & criteria</li>
                            <li><i class="fas fa-check text-success"></i>System reports & analytics</li>
                        </ul>
                    </div>
                </div>

                <!-- College Role -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card college" onclick="showTutorial('college')" style="position: relative;">
                        <?php if ($user_role === 'college'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon college">
                            <i class="fas fa-university"></i>
                        </div>
                        <h3 class="role-title text-center">College Administrator</h3>
                        <p class="role-description text-center">
                            Manage college-level operations in the Samara University system. Oversee departments,
                            coordinate with deans, and conduct staff evaluations.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>Departments management</li>
                            <li><i class="fas fa-check text-success"></i>Deans coordination</li>
                            <li><i class="fas fa-check text-success"></i>Staff evaluations</li>
                            <li><i class="fas fa-check text-success"></i>College-wide reports</li>
                        </ul>
                    </div>
                </div>

                <!-- Dean Role -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card dean" onclick="showTutorial('dean')" style="position: relative;">
                        <?php if ($user_role === 'dean'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon dean">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="role-title text-center">Dean</h3>
                        <p class="role-description text-center">
                            Provide strategic leadership for your college in the Samara University system.
                            Evaluate department heads and ensure academic excellence.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>College-level oversight</li>
                            <li><i class="fas fa-check text-success"></i>Department heads evaluation</li>
                            <li><i class="fas fa-check text-success"></i>Academic quality oversight</li>
                            <li><i class="fas fa-check text-success"></i>College-wide reports & analytics</li>
                        </ul>
                    </div>
                </div>

                <!-- Head of Department Role -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card head" onclick="showTutorial('head')" style="position: relative;">
                        <?php if ($user_role === 'head_of_department'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon head">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="role-title text-center">Head of Department</h3>
                        <p class="role-description text-center">
                            Lead your academic department in the Samara University system. Evaluate instructors,
                            manage department staff, and ensure quality education delivery.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>Instructors management & evaluation</li>
                            <li><i class="fas fa-check text-success"></i>Department dashboard & analytics</li>
                            <li><i class="fas fa-check text-success"></i>Performance reports generation</li>
                            <li><i class="fas fa-check text-success"></i>Dean coordination & reporting</li>
                        </ul>
                    </div>
                </div>

                <!-- HRM Role -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card hrm" onclick="showTutorial('hrm')" style="position: relative;">
                        <?php if ($user_role === 'hrm'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon hrm">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h3 class="role-title text-center">Human Resource Manager</h3>
                        <p class="role-description text-center">
                            Manage university-wide HR operations in the Samara University system. Analyze staff
                            performance, review evaluations, and generate comprehensive HR reports.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>University-wide staff analytics</li>
                            <li><i class="fas fa-check text-success"></i>Evaluation review & approval</li>
                            <li><i class="fas fa-check text-success"></i>Colleges & departments oversight</li>
                            <li><i class="fas fa-check text-success"></i>Comprehensive HR reporting</li>
                        </ul>
                    </div>
                </div>

                <!-- Instructor Role (if needed) -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="role-card" onclick="showTutorial('instructor')" style="position: relative; border-left: 5px solid #95a5a6;">
                        <?php if ($user_role === 'instructor'): ?>
                            <div class="current-role-badge">Your Role</div>
                        <?php endif; ?>
                        <div class="role-icon" style="background: #ecf0f1; color: #95a5a6;">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <h3 class="role-title text-center">Instructor</h3>
                        <p class="role-description text-center">
                            Focus on teaching excellence in the Samara University system. Participate in
                            performance evaluations and contribute to academic quality improvement.
                        </p>
                        <ul class="role-features">
                            <li><i class="fas fa-check text-success"></i>Teaching performance tracking</li>
                            <li><i class="fas fa-check text-success"></i>Evaluation participation</li>
                            <li><i class="fas fa-check text-success"></i>Professional development</li>
                            <li><i class="fas fa-check text-success"></i>Performance improvement</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tutorial Content Sections -->
        <!-- Admin Tutorial Content -->
        <div id="adminTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="fas fa-user-shield mr-2" style="color: #22AE9A;"></i>
                    Administrator Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="admin-overview-tab" data-toggle="pill" href="#admin-overview" role="tab">
                        <i class="fas fa-eye mr-2"></i>Overview
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="admin-workflow-tab" data-toggle="pill" href="#admin-workflow" role="tab">
                        <i class="fas fa-tasks mr-2"></i>Workflow
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="admin-features-tab" data-toggle="pill" href="#admin-features" role="tab">
                        <i class="fas fa-star mr-2"></i>Features
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="admin-tips-tab" data-toggle="pill" href="#admin-tips" role="tab">
                        <i class="fas fa-lightbulb mr-2"></i>Best Practices
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="adminTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="admin-overview" role="tabpanel">
                    <div class="workflow-step">
                        <h3 class="mb-4" style="color: #22AE9A;">
                            <i class="fas fa-info-circle mr-2"></i>
                            Administrator Role Overview
                        </h3>
                        <p class="lead">
                            As a System Administrator in the Samara University Academic Performance Evaluation System,
                            you have complete control over all system components. You manage users, organizational
                            structure, evaluation settings, and system-wide operations.
                        </p>

                        <h5 class="mt-4 mb-3">Key Responsibilities:</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        User account management
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        College & department setup
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        Evaluation period management
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        Evaluation criteria configuration
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        System monitoring & reports
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        Security & access control
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-tutorial mt-4">
                            <h5><i class="fas fa-graduation-cap mr-2"></i>Quick Start Guide</h5>
                            <p class="mb-3">New to the Samara University system? Follow these steps:</p>
                            <ol class="mb-0">
                                <li>Access the <strong>Dashboard</strong> for system overview</li>
                                <li>Set up <strong>Colleges</strong> and <strong>Departments</strong></li>
                                <li>Create <strong>User accounts</strong> for all roles</li>
                                <li>Configure <strong>Evaluation Periods</strong></li>
                                <li>Define <strong>Evaluation Criteria</strong> and categories</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Workflow Tab -->
                <div class="tab-pane fade" id="admin-workflow" role="tabpanel">
                    <h3 class="mb-4" style="color: #22AE9A;">
                        <i class="fas fa-route mr-2"></i>
                        Complete Administrator Workflow
                    </h3>

                    <div class="workflow-step">
                        <div class="step-number" style="background: #22AE9A;"> </div>
                        <h4>Initial System Setup</h4>
                        <p>Start by configuring the basic Samara University organizational structure and hierarchy.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-university mr-2"></i>Colleges Management</h6>
                                <ul>
                                    <li>Navigate to <strong>Admin → Colleges</strong></li>
                                    <li>Add new colleges with details</li>
                                    <li>Set college codes and descriptions</li>
                                    <li>Assign college administrators</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-building mr-2"></i>Departments Setup</h6>
                                <ul>
                                    <li>Go to <strong>Admin → Departments</strong></li>
                                    <li>Create departments under colleges</li>
                                    <li>Assign department codes</li>
                                    <li>Set department heads</li>
                                </ul>
                            </div>
                        </div>
                        <a href="<?php echo $base_url; ?>admin/colleges.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px;">
                            <i class="fas fa-arrow-right mr-2"></i>Start with Colleges
                        </a>
                    </div>

                    <div class="workflow-step">
                        <div class="step-number" style="background: #22AE9A;">2</div>
                        <h4>User Account Management</h4>
                        <p>Create and manage user accounts for all Samara University system participants.</p>
                        <div class="row">
                            <div class="col-md-4">
                                <h6><i class="fas fa-user-tie mr-2"></i>Deans</h6>
                                <ul>
                                    <li>Navigate to <strong>Admin → Users</strong></li>
                                    <li>Create dean accounts</li>
                                    <li>Assign to specific colleges</li>
                                    <li>Set dean role permissions</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-user-graduate mr-2"></i>Department Heads</h6>
                                <ul>
                                    <li>Create head_of_department accounts</li>
                                    <li>Assign to specific departments</li>
                                    <li>Configure department access</li>
                                    <li>Set evaluation permissions</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-users mr-2"></i>Staff & Others</h6>
                                <ul>
                                    <li>Create instructor/staff accounts</li>
                                    <li>Assign roles (instructor, hrm, college)</li>
                                    <li>Set department assignments</li>
                                    <li>Manage user status</li>
                                </ul>
                            </div>
                        </div>
                        <a href="<?php echo $base_url; ?>admin/users.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px;">
                            <i class="fas fa-arrow-right mr-2"></i>Manage Users
                        </a>
                    </div>
                </div>
                <!-- Features Tab -->
                <div class="tab-pane fade" id="admin-features" role="tabpanel">
                    <h3 class="mb-4" style="color: #22AE9A;">
                        <i class="fas fa-star mr-2"></i>
                        Administrator Features & Capabilities
                    </h3>

                    <div class="row">
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="feature-card">
                                <div class="feature-icon-small" style="background: #e7f7f5; color: #22AE9A;">
                                    <i class="fas fa-users-cog"></i>
                                </div>
                                <h5>User Management</h5>
                                <p>Complete control over user accounts, roles, and permissions across the entire system.</p>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="feature-card">
                                <div class="feature-icon-small" style="background: #e7f7f5; color: #22AE9A;">
                                    <i class="fas fa-university"></i>
                                </div>
                                <h5>Organizational Structure</h5>
                                <p>Set up and manage the complete organizational hierarchy of the university.</p>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="feature-card">
                                <div class="feature-icon-small" style="background: #e7f7f5; color: #22AE9A;">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <h5>Evaluation Framework</h5>
                                <p>Configure the complete evaluation system including periods, criteria, and categories.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Tab -->
                <div class="tab-pane fade" id="admin-tips" role="tabpanel">
                    <h3 class="mb-4" style="color: #22AE9A;">
                        <i class="fas fa-lightbulb mr-2"></i>
                        Best Practices & Tips
                    </h3>

                    <div class="workflow-step">
                        <h4><i class="fas fa-rocket mr-2"></i>Getting Started Tips</h4>
                        <ul>
                            <li><strong>Plan First:</strong> Before creating users, set up your organizational structure (colleges and departments)</li>
                            <li><strong>Test Environment:</strong> Create test accounts to familiarize yourself with different role perspectives</li>
                            <li><strong>Backup Strategy:</strong> Establish regular backup procedures before going live</li>
                            <li><strong>User Training:</strong> Prepare training materials for different user roles</li>
                        </ul>
                    </div>

                    <div class="alert alert-tutorial mt-4">
                        <h5><i class="fas fa-info-circle mr-2"></i>Need Help?</h5>
                        <p class="mb-2">If you encounter any issues or need assistance:</p>
                        <ul class="mb-0">
                            <li>Check the system documentation in the <strong>docs</strong> folder</li>
                            <li>Review user guides for specific role instructions</li>
                            <li>Contact technical support for system-related issues</li>
                            <li>Use the built-in help features throughout the system</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <h5 class="mb-4">Quick Actions</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo $base_url; ?>admin/dashboard.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px; width: 100%;">
                                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo $base_url; ?>admin/users.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px; width: 100%;">
                                        <i class="fas fa-users mr-2"></i>Manage Users
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo $base_url; ?>admin/colleges.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px; width: 100%;">
                                        <i class="fas fa-university mr-2"></i>Manage Colleges
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?php echo $base_url; ?>admin/evaluation_periods.php" class="btn" style="background: #22AE9A; color: white; border-radius: 25px; padding: 12px 30px; width: 100%;">
                                        <i class="fas fa-calendar-alt mr-2"></i>Evaluation Periods
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placeholder for other role tutorials (will be loaded dynamically) -->
        <div id="collegeTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #2ECC71;">
                    <i class="fas fa-university mr-2"></i>
                    College Administrator Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>
            <div class="workflow-step">
                <h3 style="color: #2ECC71;">College Management Overview</h3>
                <p class="lead">As a College Administrator, you oversee the academic and administrative operations of your college. You manage departments, evaluate staff, and ensure quality academic performance across all college units.</p>
                <div class="alert alert-tutorial">
                    <h5><i class="fas fa-info-circle mr-2"></i>Key Features</h5>
                    <ul class="mb-0">
                        <li>Department oversight and management</li>
                        <li>Staff performance evaluation</li>
                        <li>Dean coordination and support</li>
                        <li>Performance reporting and analytics</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="deanTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #9B59B6;">
                    <i class="fas fa-user-tie mr-2"></i>
                    Dean Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>
            <div class="workflow-step">
                <h3 style="color: #9B59B6;">Strategic Leadership Overview</h3>
                <p class="lead">As a Dean, you provide strategic leadership for your college. You evaluate department heads, oversee college-wide performance, and ensure academic excellence across all departments.</p>
                <div class="alert alert-tutorial">
                    <h5><i class="fas fa-info-circle mr-2"></i>Key Features</h5>
                    <ul class="mb-0">
                        <li>Strategic college leadership</li>
                        <li>Department head evaluation</li>
                        <li>Academic quality oversight</li>
                        <li>Performance analytics and reporting</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="headTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #3498DB;">
                    <i class="fas fa-user-graduate mr-2"></i>
                    Head of Department Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>
            <div class="workflow-step">
                <h3 style="color: #3498DB;">Departmental Leadership Overview</h3>
                <p class="lead">As a Head of Department, you lead your academic department, manage faculty and staff, evaluate instructors, and ensure the delivery of quality education and research within your department.</p>
                <div class="alert alert-tutorial">
                    <h5><i class="fas fa-info-circle mr-2"></i>Key Features</h5>
                    <ul class="mb-0">
                        <li>Departmental leadership and management</li>
                        <li>Instructor evaluation and development</li>
                        <li>Academic program oversight</li>
                        <li>Performance monitoring and reporting</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="hrmTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #E67E22;">
                    <i class="fas fa-users-cog mr-2"></i>
                    HRM Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>
            <div class="workflow-step">
                <h3 style="color: #E67E22;">HR Management Overview</h3>
                <p class="lead">As a Human Resource Manager, you oversee staff performance across the entire university. You analyze HR metrics, support professional development, and ensure optimal human resource utilization for organizational success.</p>
                <div class="alert alert-tutorial">
                    <h5><i class="fas fa-info-circle mr-2"></i>Key Features</h5>
                    <ul class="mb-0">
                        <li>University-wide staff analysis</li>
                        <li>Performance trend monitoring</li>
                        <li>Professional development planning</li>
                        <li>HR analytics and reporting</li>
                    </ul>
                </div>
            </div>
        </div>

        <div id="instructorTutorial" class="tutorial-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: #95a5a6;">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    Instructor Tutorial
                </h2>
                <button class="btn btn-back" onclick="showRoleSelection()">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Role Selection
                </button>
            </div>
            <div class="workflow-step">
                <h3 style="color: #95a5a6;">Teaching Excellence Overview</h3>
                <p class="lead">As an Instructor, you focus on teaching excellence, participate in evaluations, and contribute to academic quality improvement within your department and college.</p>
                <div class="alert alert-tutorial">
                    <h5><i class="fas fa-info-circle mr-2"></i>Key Features</h5>
                    <ul class="mb-0">
                        <li>Teaching excellence and innovation</li>
                        <li>Self-evaluation and reflection</li>
                        <li>Professional development participation</li>
                        <li>Performance tracking and improvement</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTutorial(role) {
    // Hide role selection
    document.getElementById('roleSelection').style.display = 'none';

    // Hide all tutorial content
    const tutorials = document.querySelectorAll('.tutorial-content');
    tutorials.forEach(tutorial => {
        tutorial.classList.remove('active');
    });

    // Show selected tutorial
    const selectedTutorial = document.getElementById(role + 'Tutorial');
    if (selectedTutorial) {
        selectedTutorial.classList.add('active');
    }

    // Scroll to top
    window.scrollTo(0, 0);
}

function showRoleSelection() {
    // Hide all tutorial content
    const tutorials = document.querySelectorAll('.tutorial-content');
    tutorials.forEach(tutorial => {
        tutorial.classList.remove('active');
    });

    // Show role selection
    document.getElementById('roleSelection').style.display = 'block';

    // Scroll to top
    window.scrollTo(0, 0);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Show role selection by default
    showRoleSelection();
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
