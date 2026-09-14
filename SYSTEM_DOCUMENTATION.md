# BlendEd LMS: Blended Learning System with Syllabus Mapping
**Institutional Setting:** I-Tech College Inc. Bago City  
**Academic Program:** Bachelor of Science in Information Systems (BSIS)  
**Document Version:** 1.0  
**Generated:** September 14, 2026  

---

## 1. Executive Summary & System Overview

**BlendEd LMS** is a specialized Learning Management System developed for **I-Tech College Inc. Bago City**. The platform bridges traditional face-to-face instruction and modern online e-learning modalities by placing **Syllabus Mapping** at the core of curriculum delivery and student tracking.

Unlike generic LMS platforms where files and assignments are loosely organized into unlinked folders, BlendEd LMS directly binds topics, learning materials, delivery modes (face-to-face, online, or hybrid), assessments, and student progress records to official syllabus learning competencies and weekly milestones.

---

## 2. Research Context & Academic Alignment

Based on the research study documentation for this system:

### 2.1 Objectives of the Study
The general aim of the study is to develop a blended learning system with syllabus mapping that supports effective lesson tracking and alignment of learning activities.

Specifically, the study aims to:
1. **Curriculum Alignment:** Develop a system that maps lessons, activities, and assessments directly to syllabus objectives.
2. **Timely Communication:** Provide students with timely announcements about assigned learning activities, upcoming assessments, and important course requirements.
3. **Progress Tracking:** Enable students to track their learning activities and understand their progress in the course.
4. **Lesson Organization & Deduplication:** Improve lesson organization and reduce missed or duplicated topics in blended learning.
5. **Instructional Support:** Support effective teaching and learning through a centralized and organized learning system.
6. **Software Quality Evaluation:** Evaluate the quality of the developed system using **McCall’s Software Quality Model**, specifically in terms of:
   - Correctness
   - Reliability
   - Efficiency
   - Integrity
   - Usability
   - Maintainability
   - Flexibility
   - Testability
   - Portability
   - Reusability
   - Interoperability
7. **Usability & User Satisfaction Evaluation:** Determine usability and user satisfaction using the **Computer System Usability Questionnaire (CSUQ)** in terms of:
   - System Usefulness (SYSUSE)
   - Information Quality (INFOQUAL)
   - Interface Quality (INTERQUAL)
   - Overall User Satisfaction (OVERALL)

### 2.2 Scope of the Study
- **Domain:** Implementation of a Blended Learning System with Syllabus Mapping in the teaching and learning process, examining the combination of face-to-face classes and online learning.
- **Setting:** Selected college-level classes within **I-Tech College Inc. Bago City**. Limited strictly to this institutional setting.
- **Duration:** Conducted over one academic semester (Academic Year 2025–2026, 1st Semester).
- **Target Participants:** College faculty teachers and enrolled students who regularly utilize the blended learning system.
- **Methodology:** Descriptive research design utilizing surveys, interviews, and system evaluation tools to assess usability, instructional effectiveness, and operational challenges.

### 2.3 Limitations of the Study
1. **Participant Generalizability:** The number of participants is limited to a specific group of teachers and students at I-Tech College Inc. Bago City, which may not represent other institutions or educational levels.
2. **Temporal Constraint:** The study spans a single academic semester; therefore, it does not assess multi-year longitudinal effects on cumulative academic performance.
3. **Response Authenticity:** Data collection relies on participant availability and willingness to provide complete and honest feedback during surveys and interviews.
4. **Infrastructure & Digital Literacy:** Technological variables such as internet bandwidth, student device availability (smartphones vs. PCs), and prior familiarity with digital learning platforms influence adoption.
5. **Self-Reported Bias & External Factors:** Evaluation responses are subject to self-reporting bias. Institutional schedule shifts or sudden changes in learning modalities remain external variables beyond researcher control.

---

## 3. Database Configuration Verification

### 3.1 Local Database Configuration Status: ✅ VERIFIED & WORKING

The core database configuration file is located at `includes/config.php`.

#### Current Settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'learnlms');
define('SITE_NAME', 'BlendEd LMS');
```

#### Diagnostic Results:
- **MySQL Host (`localhost` / `127.0.0.1`):** Verified reachable on port `3306`.
- **Database Name (`learnlms`):** Verified existing with 20 populated tables.
- **User Authentication (`root` / no password):** Successfully authenticated (`DB Connection: SUCCESSFUL`).
- **Initial Configuration Issue Identified & Corrected:**
  - *Previous State:* `DB_USER` was initially configured as `'user'`, which caused MySQL to fail immediately with:  
    `Uncaught mysqli_sql_exception: Access denied for user 'user'@'localhost'`.
  - *Resolution:* Setting `DB_USER` to `'root'` resolved the error and enabled complete local connectivity.
- **SQL Data Dump:** The database schema and records match the dump file `if0_42325974_learnlms.sql` (generated Sept 14, 2026).
- **Note on `blendedlearning/` subfolder:** The subfolder `blendedlearning/includes/config.php` still contains remote InfinityFree hosting credentials (`sql305.infinityfree.com` / `if0_42325974`). If you plan to serve files from that subfolder, update its configuration to match the root `includes/config.php`.

---

## 4. Database Schema & Data Summary

The `learnlms` database currently contains **20 tables** with live institutional data:

| Table Name | Record Count | Description |
| :--- | :---: | :--- |
| `users` | **53** | System administrators, teachers, and enrolled students |
| `departments` | **1** | Bachelor of Science and Information System (BSIS) |
| `courses` | **6** | Academic course subjects under BSIS |
| `syllabi` | **6** | Published course syllabi linking courses to instructors |
| `syllabus_topics` | **1+** | Weekly topic breakdowns and competencies |
| `learning_materials` | **5** | Uploaded instructional resources and syllabus PDF files |
| `assessments` | **4** | Quizzes, assignments, exams, and projects |
| `submissions` | **4** | Student assessment submissions and work |
| `enrollments` | **4** | Student-to-syllabus enrollment mappings |
| `announcements` | **1** | System and course-level broadcast notifications |
| `activity_logs` | **598** | Audit trail tracking logins, logouts, and system events |
| `system_settings` | **1** | Global parameters (e.g., Maintenance Mode toggle) |
| `topic_done_status` | **5** | Topic completion flags for tracking progress |
| `query_logs` | **3** | Database interaction logs |
| `syllabus_templates` | **0** | Reusable syllabus blueprint templates |
| `syllabus_template_topics`| **0** | Topics associated with syllabus templates |
| `syllabus_assignments` | **0** | Assignment-syllabus cross-reference mappings |
| `topic_progress` | **0** | Granular topic step completion trackers |
| `topic_week_done` | **0** | Weekly milestone completion trackers |

### 4.1 Users Breakdown (Total: 53)
- **Administrators (2):**
  - `admin` (System Administrator - `jeff.lim111@gmail.com`)
  - `admin2` (Rafael Claveria - `Rafael.Claveria@gmail.com`)
- **Teachers / Faculty (6):**
  - Albert Buenafe (`albertbuenafe@gmail.com`)
  - Eazylle Conception (`eazylleconception@gmail.com`)
  - Famie Rose Bilbao (`famierose@gmail.com`)
  - Redgie Pomario (`redgiepomario@gmail.com`)
  - Jeffred Lim (`jeffredlim@gmail.com`)
  - Kaye Jacildo (`kayejacildo@gmail.com`)
- **Students (45):**
  - 4th Year BSIS students including `Abrasdo`, `Agata`, `Aguillion`, `Aguirre`, `Alvior`, `Balmera`, `Bellanio`, `Billiones`, `Campos`, `Camposa`, `Caseres`, `Clamor`, `Niel`, etc.

### 4.2 Academic Department
- **Department ID 5:** Bachelor of Science and Information System (`BSIS`)

### 4.3 Academic Courses (BSIS 4th Year - AY 2025–2026, 1st Semester)
1. **ISSMA413:** IS Strategy, Management and Acquisition (3 Units, Year 4, 1st Sem) — *Assigned Teacher: Famie Rose Bilbao*
2. **ADET413:** Application Development and Emerging Technologies (3 Units, Year 4, 1st Sem) — *Assigned Teacher: Redgie Pomario*
3. **HCI413:** Human Computer Interaction (3 Units, Year 4, 1st Sem) — *Assigned Teacher: Eazylle Conception*
4. **PROMAN413:** IS Project Management 2 (3 Units, Year 4, 1st Sem) — *Assigned Teacher: Albert Buenafe*
5. **CAP413:** Capstone 2 (2 Units, Year 4, 1st Sem) — *Assigned Teacher: Kaye Jacildo*
6. **ADV08:** Data Mining (3 Units, Year 4, 1st Sem) — *Assigned Teacher: Jeffred Lim*

---

## 5. System Roles & Test Credentials

All user passwords utilize PHP's `password_hash()` (Bcrypt). The accounts have been verified locally:

| Role | Username | Default Password | Dashboard URL | Notes |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | `/admin/dashboard.php` | Full management privileges, audit logs, maintenance mode |
| **Teacher** | `Albert Buenafe` | `123456` | `/teacher/dashboard.php` | Manages PROMAN413, syllabus topics, uploads materials |
| **Teacher** | `Jeffred` | `123456` | `/teacher/dashboard.php` | Manages ADV08, quizzes, materials |
| **Teacher** | `Famie` | `123456` | `/teacher/dashboard.php` | Manages ISSMA413 |
| **Student** | `Abrasdo` | `123456` | `/student/dashboard.php` | Enrolled in BSIS courses, submits assessments |
| **Student** | `Niel` | `123456` | `/student/dashboard.php` | Enrolled in BSIS courses, tracks syllabus progress |

---

## 6. How to Run the Application Locally

You can run BlendEd LMS locally using either the **PHP Built-in Server** or the **XAMPP Apache Server**.

### Option A: Using the PHP Built-in Server (Recommended for Instant Testing)

Open PowerShell or Command Prompt, navigate to the project directory, and run:
```powershell
cd C:\PHP
php -S localhost:8000
```
Then open your web browser and visit:
- **Landing Page:** http://localhost:8000/
- **Login Page:** http://localhost:8000/login.php

### Option B: Using XAMPP Apache Server (Port 80)

A directory junction has been created at `C:\xampp\htdocs\PHP` pointing directly to `C:\PHP`.
Ensure Apache and MySQL are running in your XAMPP Control Panel, then visit:
- **Landing Page:** http://localhost/PHP/
- **Login Page:** http://localhost/PHP/login *(Apache automatically handles clean URLs via `.htaccess`)*

---

## 7. Key System Modules & Features

### 7.1 Administrator Module (`/admin`)
- **Dashboard (`dashboard.php`):** Real-time metric cards (total users, teachers, students, courses, syllabi, published counts), recent syllabi overview, user activity feed, and instant Maintenance Mode toggle.
- **User Management (`users.php`):** Create, update, deactivate, and reset passwords for students, faculty, and administrative staff.
- **Curriculum & Courses (`courses.php`):** Define course codes, titles, unit weights, year levels, and semesters.
- **Department Setup (`departments.php`):** Manage academic college departments (e.g., BSIS).
- **Syllabi Master Oversight (`syllabi.php`, `syllabi_view.php`):** Review teacher-submitted syllabi, track publication status (`draft`, `published`, `archived`), and verify lesson alignment.
- **Audit & Activity Logs (`logs.php`):** Comprehensive chronological log of all authentications, auto-logouts, role transitions, and administrative actions.
- **Reports & Analytics (`reports.php`):** Visual distribution charts of users by role, syllabi status breakdown, and delivery modes (Online vs. Face-to-Face vs. Both).

### 7.2 Teacher Module (`/teacher`)
- **Syllabus Editor & Builder (`syllabus_edit.php`, `syllabi.php`):** Construct week-by-week lesson maps, define intended learning outcomes, upload syllabus documentation, and designate delivery modalities (`online`, `offline`, `both`).
- **Learning Materials Hub (`materials.php`):** Upload documents (PDF, slides, handouts), assign them to specific syllabus topics, and set external reference links.
- **Assessment Management (`assessments.php`):** Create and schedule quizzes, assignments, exams, projects, and activities tied to individual syllabus objectives with custom due dates and score ceilings.
- **Grading & Submissions (`grades.php`):** Evaluate student submissions, record marks, and provide structured qualitative feedback.
- **Class Lists & Student Roster (`students.php`, `classes.php`):** Monitor enrolled students per section/course.
- **Announcements (`announcements.php`):** Post targeted notices to specific classes or entire cohorts.

### 7.3 Student Module (`/student`)
- **Student Dashboard (`dashboard.php`):** View enrolled subjects, upcoming assignment deadlines, recent announcements, and overall completion rate.
- **Interactive Syllabus Explorer (`syllabus.php`):** Browse the course roadmap week-by-week, inspect completed vs. pending topics, and download aligned learning resources.
- **Learning Materials Library (`materials.php`):** Filter and view instructional materials organized by course and lesson topic.
- **Assessments & Submissions (`assessments.php`):** Submit digital files or text answers for quizzes and assignments before the deadline.
- **Progress Tracking (`progress.php`):** Real-time visual progress bars showing student milestone completion according to syllabus mapping.

### 7.4 System Governance & Hardening
- **Emergency Maintenance Mode:** Administrators can lock the platform with a single toggle. Active student and teacher sessions are gracefully auto-logged out with an activity log record, while administrators retain backend access.
- **Security Hardening (`.htaccess`):** Directory indexing disabled (`Options -Indexes`), sensitive backup/database/configuration extensions denied direct HTTP access, security headers applied (`X-Frame-Options`, `X-XSS-Protection`, `X-Content-Type-Options`, `Referrer-Policy`).

---

## 8. Software Quality & Evaluation Frameworks

In accordance with Objectives 6 & 7 of the capstone study:

### 8.1 McCall’s Software Quality Model (Objective 6)
The system is structured for evaluation across McCall’s three product quality perspectives:
1. **Product Operation:**
   - *Correctness:* Alignment of syllabus objectives with delivered materials and assessment scoring.
   - *Reliability:* Stable session persistence, transaction-safe database queries, and role-enforced access checks.
   - *Efficiency:* Fast page load speeds via minimal asset dependencies, indexed database queries, and lightweight Tailwind styling.
   - *Integrity:* Password hashing (Bcrypt), input sanitization, SQL injection prevention via prepared statements, and role-based access control.
   - *Usability:* Intuitive dual-panel responsive interfaces, mobile accessibility, and clear status badges.
2. **Product Revision:**
   - *Maintainability:* Clean modular PHP architecture separating configuration, includes, dashboards, and role directories.
   - *Flexibility:* Configurable academic years, dynamic course and department additions without code changes.
   - *Testability:* Isolated module functions, automated activity logging for diagnostics, and standalone preview endpoints.
3. **Product Transition:**
   - *Portability:* Runs seamlessly across Windows (XAMPP/IIS) and Linux (LAMP) stacks with standard PHP 7.4–8.2+ and MariaDB/MySQL.
   - *Reusability:* Shared UI headers, topbars, sidebars, and authentication helper functions (`requireRole()`, `isLoggedIn()`, `sanitize()`).
   - *Interoperability:* Exportable/importable standard SQL schemas, support for external URL references and standard PDF/document uploads.

### 8.2 Computer System Usability Questionnaire - CSUQ (Objective 7)
Survey instruments evaluate user satisfaction across 4 standard psychometric subscales:
1. **System Usefulness (SYSUSE):** Measures how effectively BlendEd LMS accelerates syllabus navigation, lesson tracking, and academic task completion.
2. **Information Quality (INFOQUAL):** Assesses the clarity of course outlines, assessment guidelines, deadline announcements, and grade reporting.
3. **Interface Quality (INTERQUAL):** Evaluates visual hierarchy, readability, navigation consistency, and device responsiveness.
4. **Overall Satisfaction (OVERALL):** Captures general user satisfaction from both faculty and student perspectives.

---

## 9. File & Directory Structure Reference

```text
c:\PHP\
├── admin/                         # Administrator management controllers & views
│   ├── announcements.php         # Global announcements broadcaster
│   ├── courses.php               # Course catalog management
│   ├── dashboard.php             # Admin metrics & maintenance control
│   ├── departments.php           # Academic department definitions
│   ├── logs.php                  # System audit trails (598 logs)
│   ├── reports.php               # Statistical analytics & charts
│   ├── syllabi.php               # Syllabus approval & list
│   ├── syllabi_view.php          # Detailed syllabus inspector
│   └── users.php                 # User account CRUD & password resets
├── assets/                        # Shared CSS, JS, fonts, and icon assets
├── includes/                      # Shared system core
│   ├── config.php                # Database constants, session handling, auth functions
│   ├── header.php                # HTML head & global styles
│   ├── layout.php                # Master page wrapper
│   ├── sidebar.php               # Role-based sidebar navigation
│   └── topbar.php                # User status bar & notification area
├── student/                       # Student portal
│   ├── announcements.php         # Student view of announcements
│   ├── assessments.php           # Student assignment & quiz submissions
│   ├── courses.php               # Enrolled courses overview
│   ├── dashboard.php             # Student dashboard & quick metrics
│   ├── materials.php             # Topic-linked material downloads
│   ├── progress.php              # Visual completion tracking
│   └── syllabus.php              # Syllabus roadmap & lesson tracker
├── teacher/                       # Faculty / Instructor portal
│   ├── announcements.php         # Class announcement creator
│   ├── assessments.php           # Assessment builder & due dates
│   ├── dashboard.php             # Teacher dashboard & subject metrics
│   ├── grades.php                # Submission grading & marks
│   ├── materials.php             # Learning materials uploader
│   ├── students.php              # Section student roster
│   ├── syllabi.php               # Teacher syllabi list
│   ├── syllabus_edit.php         # Multi-step syllabus mapping editor
│   └── topics.php                # Topic creation & delivery mode setup
├── uploads/                       # Uploaded materials & PDFs
├── .htaccess                      # Security hardening & clean URL rewrites
├── if0_42325974_learnlms.sql      # Latest database backup (Sep 14, 2026)
├── databasecode.sql               # Historical database dump
├── index.php                      # Public homepage & landing portal
├── login.php                      # Role-tabbed authentication gateway
├── logout.php                     # Secure session destruction & audit log
├── maintenance.php                # Maintenance mode lock screen
├── profile.php                    # User profile & password management
└── SYSTEM_DOCUMENTATION.md        # Complete system documentation (This file)
```
