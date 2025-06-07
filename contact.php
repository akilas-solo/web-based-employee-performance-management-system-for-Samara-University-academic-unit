<?php
/**
 * Samara University Academic Performance Evaluation System
 * Contact Page
 */

// Include configuration file
require_once 'includes/config.php';

// Initialize variables
$name = '';
$email = '';
$subject = '';
$message = '';
$success_message = '';
$error_message = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }
    
    if (empty($message)) {
        $errors[] = "Message is required.";
    }
    
    // If no errors, process the form
    if (empty($errors)) {
        // In a real application, you would send an email or save to database
        // For now, we'll just show a success message
        $success_message = "Thank you for your message! We will get back to you soon.";
        
        // Reset form fields
        $name = '';
        $email = '';
        $subject = '';
        $message = '';
    } else {
        $error_message = "Please fix the following errors:<br>" . implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Samara University Academic Performance Evaluation System</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <style>
        .contact-header {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('<?php echo $base_url; ?>assets/images/university-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .contact-section {
            padding: 80px 0;
        }
        
        .contact-info-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .contact-info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .contact-info-card .card-body {
            padding: 2rem;
        }
        
        .contact-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .contact-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
        }
        
        .map-container {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">
                <img src="<?php echo $base_url; ?>assets/images/logo.png" alt="Samara University" height="40" class="mr-2">
                Samara University
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>about.php">About</a>
                    </li>
                    <li class="nav-item active">
                        <a class="nav-link" href="<?php echo $base_url; ?>contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light ml-2" href="<?php echo $base_url; ?>login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contact Header -->
    <header class="contact-header">
        <div class="container">
            <h1 class="display-4">Contact Us</h1>
            <p class="lead">Get in touch with the Samara University Academic Performance Evaluation System team</p>
        </div>
    </header>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center mb-5">
                    <h2 class="section-heading">Get In Touch</h2>
                    <hr class="my-4">
                    <p class="lead">Have questions about the Academic Performance Evaluation System? We're here to help!</p>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="row">
                    <div class="col-lg-8 mx-auto">
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="contact-info-card card h-100">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h4 class="card-title">Visit Us</h4>
                            <p class="card-text">Samara University<br>Main Campus<br>Samara, Ethiopia</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="contact-info-card card h-100">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h4 class="card-title">Email Us</h4>
                            <p class="card-text">info@samara.edu.et<br>support@samara.edu.et</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="contact-info-card card h-100">
                        <div class="card-body text-center">
                            <div class="contact-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h4 class="card-title">Call Us</h4>
                            <p class="card-text">+251 123 456 789<br>+251 987 654 321</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-section bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="contact-form">
                        <h3 class="mb-4">Send Us a Message</h3>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="needs-validation" novalidate>
                            <div class="form-group">
                                <label for="name">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide your name.
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Your Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo $subject; ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a subject.
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="message">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo $message; ?></textarea>
                                <div class="invalid-feedback">
                                    Please provide a message.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane mr-2"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="map-container">
                        <!-- Replace with actual Google Maps embed code for Samara University -->
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.802456351869!2d38.97559661485946!3d11.794534991671444!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1644d2c3e5c2d2ff%3A0xb7878a82c0e26e7a!2sSamara%20University!5e0!3m2!1sen!2sus!4v1623456789012!5m2!1sen!2sus" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                    <div class="mt-4">
                        <h4>Office Hours</h4>
                        <ul class="list-unstyled">
                            <li><strong>Monday - Friday:</strong> 8:00 AM - 5:00 PM</li>
                            <li><strong>Saturday:</strong> 9:00 AM - 1:00 PM</li>
                            <li><strong>Sunday:</strong> Closed</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Samara University</h5>
                    <p>Academic Performance Evaluation System</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo $base_url; ?>">Home</a></li>
                        <li><a href="<?php echo $base_url; ?>about.php">About</a></li>
                        <li><a href="<?php echo $base_url; ?>contact.php">Contact</a></li>
                        <li><a href="<?php echo $base_url; ?>login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt mr-2"></i> Samara University, Ethiopia</p>
                        <p><i class="fas fa-phone mr-2"></i> +251 123 456 789</p>
                        <p><i class="fas fa-envelope mr-2"></i> info@samara.edu.et</p>
                    </address>
                </div>
            </div>
            <hr class="bg-secondary">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Samara University. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Form Validation -->
    <script>
        // Example starter JavaScript for disabling form submissions if there are invalid fields
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                // Fetch all the forms we want to apply custom Bootstrap validation styles to
                var forms = document.getElementsByClassName('needs-validation');
                // Loop over them and prevent submission
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
