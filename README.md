# Samara University Academic Performance Evaluation System

## Overview

The Samara University Academic Performance Evaluation System is a comprehensive web-based application designed to streamline and standardize the academic performance evaluation process for staff members across different colleges and departments. The system provides role-based access control, allowing different stakeholders (administrators, deans, college representatives, and department heads) to participate in the evaluation process according to their responsibilities.

## System Features

### User Management
- Role-based access control with specific permissions for each role
- User profiles with personal and professional information
- Profile management for all user roles (Admin, Dean, College, HRM, Head of Department)
- Specialized profile fields for academic staff (academic rank, specialization, years of experience)
- Secure authentication and authorization

### Profile Management
- Customizable user profiles for each role
- Academic information tracking (specialization, years of experience, appointment date)
- Professional development tracking
- Profile settings and preferences
- Profile image upload and management

### Evaluation Management
- Creation and management of evaluation periods
- Customizable evaluation criteria and categories
- Multi-level evaluation workflow
- Performance tracking across evaluation periods

### Reporting and Analytics
- Comprehensive performance reports at individual, department, and college levels
- Statistical analysis of evaluation data
- Performance trends visualization
- Exportable reports in various formats

### Administrative Functions
- College and department management
- User role assignment
- System configuration and settings
- Evaluation criteria management

## User Roles

### Administrator
- System-wide access and control
- User management (create, edit, delete)
- College and department management
- Evaluation period management
- Evaluation criteria management
- System configuration
- Profile management

### Dean
- College-level oversight
- Evaluation of department heads
- Review of department performance
- College-wide reports and analytics
- Performance trend analysis
- Profile management with academic information (specialization, years of experience, appointment date)

### College
- College-level evaluation of staff
- Department performance monitoring
- Staff evaluation reports
- College-specific analytics
- Profile management with academic information (specialization, years of experience, appointment date)

### Head of Department
- Department-level staff evaluation
- Staff performance monitoring
- Department-specific reports
- Performance improvement planning
- Profile management with academic information (specialization, years of experience, appointment date)

### HRM (Human Resource Management)
- Staff records management
- Performance evaluation oversight
- Department performance monitoring
- Profile management with professional information

## Technical Architecture

### Frontend
- HTML5, CSS3, JavaScript
- Bootstrap 4 for responsive design
- jQuery for DOM manipulation
- Chart.js for data visualization

### Backend
- PHP 7.4+
- MySQL/MariaDB database
- Object-oriented programming approach
- MVC-inspired architecture

### Security Features
- Password hashing and salting
- Input sanitization
- CSRF protection
- Session management
- Role-based access control

## Database Schema

The system uses a relational database with the following key tables:

### Users
- user_id (PK)
- username
- password (hashed)
- email
- full_name
- role
- department_id (FK)
- college_id (FK)
- status
- created_at
- updated_at

### Colleges
- college_id (PK)
- name
- code
- description
- vision
- mission
- created_at
- updated_at

### Departments
- department_id (PK)
- name
- code
- college_id (FK)
- description
- created_at
- updated_at

### College Profiles
- profile_id (PK)
- user_id (FK)
- academic_rank
- specialization
- years_of_experience
- appointment_date
- created_at
- updated_at

### Dean Profiles
- profile_id (PK)
- user_id (FK)
- academic_rank
- specialization
- years_of_experience
- appointment_date
- created_at
- updated_at

### Head Profiles
- profile_id (PK)
- user_id (FK)
- academic_rank
- specialization
- years_of_experience
- appointment_date
- created_at
- updated_at

### HRM Profiles
- profile_id (PK)
- user_id (FK)
- department
- position
- years_of_experience
- created_at
- updated_at

### Evaluation Periods
- period_id (PK)
- title
- academic_year
- semester
- start_date
- end_date
- description
- status
- created_at
- updated_at

### Evaluation Categories
- category_id (PK)
- name
- description
- created_at
- updated_at

### Evaluation Criteria
- criteria_id (PK)
- category_id (FK)
- name
- description
- weight
- min_rating
- max_rating
- created_at
- updated_at

### Evaluations
- evaluation_id (PK)
- evaluator_id (FK)
- evaluatee_id (FK)
- period_id (FK)
- total_score
- comments
- status
- submission_date
- created_at
- updated_at

### Evaluation Responses
- response_id (PK)
- evaluation_id (FK)
- criteria_id (FK)
- rating
- comment
- created_at
- updated_at

## Installation

### Requirements
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL/MariaDB 5.7 or higher
- mod_rewrite enabled (for Apache)

### Installation Steps
1. Clone the repository to your web server directory
2. Create a new MySQL database
3. Import the database schema from `database/schema.sql`
4. Configure database connection in `includes/config.php`
5. Set appropriate file permissions
6. Access the system through your web browser

## Configuration

The system can be configured through the `includes/config.php` file, which includes:

- Database connection settings
- Base URL configuration
- Session settings
- Email configuration
- System-wide settings

## Usage

### Administrator
1. Log in with administrator credentials
2. Set up colleges and departments
3. Create user accounts and assign roles
4. Define evaluation periods
5. Configure evaluation criteria
6. Monitor system usage and generate reports

### Dean
1. Log in with dean credentials
2. View college dashboard
3. Evaluate department heads
4. Review department performance
5. Generate college-level reports

### College
1. Log in with college credentials
2. View college dashboard
3. Evaluate staff members
4. Monitor department performance
5. Generate evaluation reports

### Head of Department
1. Log in with head of department credentials
2. View department dashboard
3. Evaluate staff members
4. Monitor staff performance
5. Generate department-level reports

### HRM
1. Log in with HRM credentials
2. View HRM dashboard
3. Manage staff records
4. Monitor department performance
5. Update profile information

## Customization

The system can be customized in several ways:

- Modify the evaluation criteria and categories
- Adjust the evaluation workflow
- Customize the user interface through CSS
- Add new report types
- Extend functionality through additional modules

## License

This system is proprietary software developed for Samara University. Unauthorized use, reproduction, or distribution is prohibited.

## Contact

For support or inquiries, please contact:
- Email: support@samarauniversity.edu.et
- Phone: +251-XX-XXX-XXXX

---

© 2023 Samara University. All rights reserved.
