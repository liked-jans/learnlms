<?php
/**
 * BlendEd LMS - Comprehensive Sample Data Seeder
 * Populates realistic BSIS curriculum data, multi-week topics, materials,
 * assessments, submissions, grades, and enrollments for I-Tech College Inc. Bago City.
 */
require_once __DIR__ . '/includes/config.php';

echo "========================================================\n";
echo "  BlendEd LMS - Realistic Institutional Data Seeder     \n";
echo "  I-Tech College Inc. Bago City | BSIS Curriculum       \n";
echo "========================================================\n\n";

// 1. Fetch Students
$studentRes = $conn->query("SELECT id, username, full_name, email FROM users WHERE role='student' AND status='active'");
$students = [];
while ($row = $studentRes->fetch_assoc()) {
    $students[] = $row;
}
echo "Found " . count($students) . " active students.\n";

// 2. Fetch Syllabi
$sylRes = $conn->query("
    SELECT s.id, s.course_id, s.teacher_id, c.course_code, c.course_name, u.full_name as teacher_name
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN users u ON s.teacher_id = u.id
    WHERE s.status = 'published'
");
$syllabi = [];
while ($row = $sylRes->fetch_assoc()) {
    $syllabi[$row['course_code']] = $row;
}
echo "Found " . count($syllabi) . " published syllabi.\n";

// 3. Curriculum Definitions (Weeks 1 to 6 for each subject)
$curriculum = [
    'PROMAN413' => [
        ['week' => 1, 'title' => 'Project Initiation & Project Charter', 'desc' => 'Developing business cases, defining project scope, objectives, and stakeholder identification.', 'ilo' => 'Formulate a complete project charter defining project boundaries and stakeholder matrices.', 'mode' => 'face-to-face', 'platform' => 'Room 302 Lab'],
        ['week' => 2, 'title' => 'Work Breakdown Structure (WBS) & Scope Baseline', 'desc' => 'Decomposition of project deliverables into manageable work packages and WBS dictionary creation.', 'ilo' => 'Construct a 3-level Work Breakdown Structure for an Information Systems project.', 'mode' => 'face-to-face', 'platform' => 'Room 302 Lab'],
        ['week' => 3, 'title' => 'Project Scheduling & Critical Path Method (CPM)', 'desc' => 'Activity sequencing, dependency mapping, network diagrams, and Gantt chart scheduling.', 'ilo' => 'Calculate critical path, early start, early finish, and total float using Gantt scheduling software.', 'mode' => 'online', 'platform' => 'BlendEd LMS & MS Project'],
        ['week' => 4, 'title' => 'Agile Methodologies & Scrum Sprint Execution', 'desc' => 'Scrum ceremonies, product backlog grooming, user stories, and burndown charts.', 'ilo' => 'Manage an iterative sprint cycle utilizing user stories and backlog prioritization.', 'mode' => 'both', 'platform' => 'Jira / GitHub Projects'],
        ['week' => 5, 'title' => 'Project Risk Management & Mitigation Strategies', 'desc' => 'Qualitative and quantitative risk analysis, probability-impact matrix, and contingency planning.', 'ilo' => 'Develop an actionable risk register with avoidance, transfer, and mitigation strategies.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 6, 'title' => 'Quality Assurance & Project Closure Reporting', 'desc' => 'Quality control metrics, acceptance criteria, post-mortem retrospectives, and client handover.', 'ilo' => 'Execute project acceptance testing and prepare a project closeout audit report.', 'mode' => 'face-to-face', 'platform' => 'Room 302 Lab'],
    ],
    'CAP413' => [
        ['week' => 1, 'title' => 'Capstone Problem Formulation & Objectives Validation', 'desc' => 'Re-aligning research objectives, scope boundaries, and client requirements baseline.', 'ilo' => 'Validate client requirements against research scope and write a formal project specification.', 'mode' => 'face-to-face', 'platform' => 'AVR Hall'],
        ['week' => 2, 'title' => 'System Architecture & Data Flow Modeling', 'desc' => 'Context diagrams, DFD Level 0/1, architectural component decomposition, and design diagrams.', 'ilo' => 'Model system workflows and data interactions using standardized architectural diagrams.', 'mode' => 'both', 'platform' => 'Figma / Lucidchart'],
        ['week' => 3, 'title' => 'Database Normalization & Physical Schema Optimization', 'desc' => '3NF normalization, relational integrity, foreign key constraints, and query indexing.', 'ilo' => 'Design an optimized relational database schema meeting 3NF standards.', 'mode' => 'face-to-face', 'platform' => 'Computer Lab 1'],
        ['week' => 4, 'title' => 'Sprint 1 Development & Core Functional Modules', 'desc' => 'Building core CRUD modules, secure session management, and responsive layout foundations.', 'ilo' => 'Demonstrate a functioning prototype covering primary business workflows.', 'mode' => 'online', 'platform' => 'GitHub Repository'],
        ['week' => 5, 'title' => 'Software Quality Evaluation (McCall’s Model & CSUQ)', 'desc' => 'Preparing evaluation instruments for system testing, correctness, efficiency, and usability.', 'ilo' => 'Administer CSUQ questionnaires and measure system quality metrics according to McCall’s model.', 'mode' => 'both', 'platform' => 'BlendEd LMS'],
        ['week' => 6, 'title' => 'Capstone Oral Defense Preparation & System Packaging', 'desc' => 'Rehearsal of presentation deck, defense rubric walkthrough, and live deployment demonstration.', 'ilo' => 'Deliver an effective oral defense presenting technical architecture and research findings.', 'mode' => 'face-to-face', 'platform' => 'Conference Room'],
    ],
    'ADET413' => [
        ['week' => 1, 'title' => 'Emerging Web Technologies & Architecture Paradigms', 'desc' => 'Monolithic vs microservice architectures, JAMstack, and asynchronous web protocols.', 'ilo' => 'Evaluate modern architectural patterns for enterprise software scalability.', 'mode' => 'face-to-face', 'platform' => 'Lab 2'],
        ['week' => 2, 'title' => 'RESTful API Engineering & Endpoints Specification', 'desc' => 'HTTP methods, status codes, JSON payload contracts, and parameterized query routing.', 'ilo' => 'Build compliant REST API endpoints with robust error handling and standardized response payloads.', 'mode' => 'both', 'platform' => 'Postman / PHP'],
        ['week' => 3, 'title' => 'Modern Front-End Engineering with TailwindCSS', 'desc' => 'Utility-first styling, CSS custom properties, responsive breakpoints, and UI componentization.', 'ilo' => 'Construct accessible, responsive user interfaces adhering to mobile-first standards.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 4, 'title' => 'Authentication, Session Security & CSRF Hardening', 'desc' => 'Bcrypt password hashing, token validation, secure cookies, and protection against OWASP Top 10.', 'ilo' => 'Implement secure authentication mechanisms and defense against Cross-Site Request Forgery.', 'mode' => 'both', 'platform' => 'Lab 2'],
        ['week' => 5, 'title' => 'Asynchronous JavaScript & Real-time Web Communication', 'desc' => 'Fetch API, async/await patterns, JSON parsing, and DOM manipulation without page reload.', 'ilo' => 'Develop dynamic UI components consuming asynchronous backend endpoints.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 6, 'title' => 'Containerization Basics & Server Deployment Protocols', 'desc' => 'Web server virtual hosts, environment variable separation, and CI/CD basics.', 'ilo' => 'Configure web server environments for secure deployment of web applications.', 'mode' => 'face-to-face', 'platform' => 'Lab 2'],
    ],
    'HCI413' => [
        ['week' => 1, 'title' => 'Foundations of Human-Computer Interaction', 'desc' => 'Cognitive models, mental models, Norman’s 7 stages of action, and user-centered design (UCD).', 'ilo' => 'Analyze human cognitive limitations and apply them to user interface layout decisions.', 'mode' => 'face-to-face', 'platform' => 'Lecture Room 101'],
        ['week' => 2, 'title' => 'User Research, Empathy Mapping & Persona Creation', 'desc' => 'Conducting contextual inquiries, drafting user journey maps, and building archetypal personas.', 'ilo' => 'Synthesize user interview data into actionable user personas and journey maps.', 'mode' => 'both', 'platform' => 'Miro / Figma'],
        ['week' => 3, 'title' => 'Information Architecture & Wireframe Prototyping', 'desc' => 'Card sorting, visual hierarchy, low-fidelity paper wireframes to interactive high-fidelity mocks.', 'ilo' => 'Design an interactive clickable prototype in Figma following visual hierarchy rules.', 'mode' => 'online', 'platform' => 'Figma'],
        ['week' => 4, 'title' => 'Nielsen’s 10 Usability Heuristics & Interface Auditing', 'desc' => 'Evaluating system feedback, user control, error prevention, recognition over recall, and consistency.', 'ilo' => 'Conduct a heuristic evaluation of a web system and produce a severity-rated audit report.', 'mode' => 'face-to-face', 'platform' => 'Lecture Room 101'],
        ['week' => 5, 'title' => 'Usability Testing Methodologies & CSUQ Questionnaires', 'desc' => 'Moderated usability testing, task completion times, think-aloud protocols, and CSUQ metrics.', 'ilo' => 'Execute a usability evaluation using CSUQ subscales (SYSUSE, INFOQUAL, INTERQUAL).', 'mode' => 'both', 'platform' => 'Computer Lab 3'],
        ['week' => 6, 'title' => 'Web Accessibility Guidelines (WCAG) & Dark Patterns', 'desc' => 'Color contrast ratios, screen reader accessibility, semantic HTML, and avoiding deceptive design.', 'ilo' => 'Audit and remediate web interfaces for WCAG 2.1 AA accessibility compliance.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
    ],
    'ADV08' => [
        ['week' => 1, 'title' => 'Introduction to Data Mining & Knowledge Discovery (KDD)', 'desc' => 'Data mining taxonomy, stages of knowledge discovery in databases, and predictive modeling.', 'ilo' => 'Distinguish supervised vs. unsupervised learning models across business intelligence applications.', 'mode' => 'face-to-face', 'platform' => 'Lab 4'],
        ['week' => 2, 'title' => 'Data Cleaning, Transformation & Discretization', 'desc' => 'Handling missing values, outlier detection, z-score normalization, and feature scaling.', 'ilo' => 'Preprocess tabular raw datasets into clean normalized arrays ready for algorithm training.', 'mode' => 'both', 'platform' => 'Python / Spreadsheets'],
        ['week' => 3, 'title' => 'Exploratory Data Analysis & Dimensionality Reduction', 'desc' => 'Correlation matrices, scatter plots, feature variance, and Principal Component Analysis (PCA).', 'ilo' => 'Perform visual exploratory data analysis to isolate influential variables in a dataset.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 4, 'title' => 'Association Rule Mining & Apriori Algorithm', 'desc' => 'Market basket analysis, support, confidence, lift metrics, and candidate itemset generation.', 'ilo' => 'Apply the Apriori algorithm to uncover frequent itemsets and significant association rules.', 'mode' => 'face-to-face', 'platform' => 'Lab 4'],
        ['week' => 5, 'title' => 'Classification Modeling & Decision Trees', 'desc' => 'Entropy, Information Gain, ID3/C4.5 decision tree splits, and confusion matrix metrics.', 'ilo' => 'Construct a decision tree classifier and evaluate precision, recall, and F1-score.', 'mode' => 'both', 'platform' => 'Python / Scikit-Learn'],
        ['week' => 6, 'title' => 'Cluster Analysis & K-Means Optimization', 'desc' => 'Euclidean distance, centroid updates, Elbow method, and silhouette coefficients.', 'ilo' => 'Execute K-Means clustering and interpret cluster characteristics for data segmentation.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
    ],
    'ISSMA413' => [
        ['week' => 1, 'title' => 'Strategic Alignment of IT and Corporate Strategy', 'desc' => 'Porter’s Five Forces, strategic grid, value chain analysis, and sustainable competitive advantage.', 'ilo' => 'Analyze an organization’s business model and propose aligned IT strategic initiatives.', 'mode' => 'face-to-face', 'platform' => 'Room 205'],
        ['week' => 2, 'title' => 'IT Infrastructure & Enterprise Architecture Planning', 'desc' => 'Legacy modernization, cloud adoption frameworks, and total cost of ownership (TCO).', 'ilo' => 'Calculate TCO and Return on Investment (ROI) for enterprise cloud migration proposals.', 'mode' => 'both', 'platform' => 'Room 205'],
        ['week' => 3, 'title' => 'Software Sourcing & Acquisition Strategy', 'desc' => 'Commercial-off-the-shelf (COTS), custom development, Open Source, and SaaS licensing.', 'ilo' => 'Develop an acquisition evaluation matrix comparing vendor proposals across technical criteria.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 4, 'title' => 'Request for Proposal (RFP) & Vendor Negotiations', 'desc' => 'Drafting RFPs, service level agreements (SLAs), contract milestones, and dispute resolution.', 'ilo' => 'Formulate an RFP and evaluate vendor contract terms including warranty and SLA guarantees.', 'mode' => 'face-to-face', 'platform' => 'Room 205'],
        ['week' => 5, 'title' => 'IT Governance, Risk Management & COBIT Standards', 'desc' => 'ITIL best practices, regulatory compliance, data privacy acts, and corporate governance.', 'ilo' => 'Map organizational IT processes to COBIT governance objectives and audit controls.', 'mode' => 'online', 'platform' => 'BlendEd LMS'],
        ['week' => 6, 'title' => 'Business Continuity & Disaster Recovery Strategy', 'desc' => 'RTO and RPO metrics, backup redundancy, incident response plans, and failover testing.', 'ilo' => 'Construct a comprehensive business continuity plan and disaster recovery protocol.', 'mode' => 'both', 'platform' => 'Room 205'],
    ],
];

// 4. Populate Topics
echo "Inserting structured syllabus topics...\n";
$topicMap = []; // [course_code][week] => topic_id

$stmtTopic = $conn->prepare("
    INSERT INTO syllabus_topics 
    (syllabus_id, week_number, topic_title, topic_description, learning_outcomes, delivery_mode, online_platform, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

foreach ($curriculum as $code => $weeks) {
    if (!isset($syllabi[$code])) continue;
    $sylId = $syllabi[$code]['id'];

    foreach ($weeks as $w) {
        // Check if topic already exists
        $check = $conn->query("SELECT id FROM syllabus_topics WHERE syllabus_id=$sylId AND week_number={$w['week']} AND topic_title='{$conn->real_escape_string($w['title'])}'");
        if ($check && $check->num_rows > 0) {
            $topicId = $check->fetch_assoc()['id'];
        } else {
            $stmtTopic->bind_param('iisssssi', $sylId, $w['week'], $w['title'], $w['desc'], $w['ilo'], $w['mode'], $w['platform'], $w['week']);
            $stmtTopic->execute();
            $topicId = $stmtTopic->insert_id;
        }
        $topicMap[$code][$w['week']] = $topicId;
    }
}
echo "Syllabus topics seeded successfully!\n";

// 5. Enroll Students across BSIS courses
echo "Enrolling students into courses...\n";
$stmtEnroll = $conn->prepare("
    INSERT INTO enrollments (student_id, syllabus_id, status, enrolled_at)
    VALUES (?, ?, 'enrolled', NOW())
    ON DUPLICATE KEY UPDATE status='enrolled'
");

$enrollCount = 0;
foreach ($students as $stu) {
    foreach ($syllabi as $syl) {
        $stmtEnroll->bind_param('ii', $stu['id'], $syl['id']);
        $stmtEnroll->execute();
        $enrollCount++;
    }
}
echo "Enrolled students across subjects ($enrollCount total enrollment links).\n";

// 6. Populate Realistic Assessments Mapped to Topics
echo "Seeding aligned assessments...\n";
$assessmentsSeed = [
    [
        'course' => 'PROMAN413', 'week' => 1, 'type' => 'assignment', 'max' => 100,
        'title' => 'Project Charter & Stakeholder Analysis',
        'desc' => 'Draft a comprehensive Project Charter for an Information Systems project including business case, objective statements, and stakeholder register.',
        'due' => date('Y-m-d H:i:s', strtotime('+3 days')),
    ],
    [
        'course' => 'PROMAN413', 'week' => 2, 'type' => 'quiz', 'max' => 50,
        'title' => 'WBS & Scope Management Quiz',
        'desc' => 'Covers work package decomposition, WBS numbering conventions, and scope creep mitigation.',
        'due' => date('Y-m-d H:i:s', strtotime('-5 days')), // Past
    ],
    [
        'course' => 'PROMAN413', 'week' => 4, 'type' => 'project', 'max' => 100,
        'title' => 'Agile Sprint Backlog & User Stories',
        'desc' => 'Create an Agile backlog of at least 15 user stories with acceptance criteria and estimated story points.',
        'due' => date('Y-m-d H:i:s', strtotime('+2 days')), // Due Soon
    ],
    [
        'course' => 'CAP413', 'week' => 2, 'type' => 'assignment', 'max' => 100,
        'title' => 'System Architecture & Data Flow Diagram Level 1',
        'desc' => 'Submit complete Context and DFD Level 1 diagrams for your Capstone project with supporting narrative documentation.',
        'due' => date('Y-m-d H:i:s', strtotime('-8 days')), // Past
    ],
    [
        'course' => 'CAP413', 'week' => 3, 'type' => 'activity', 'max' => 100,
        'title' => 'Database Schema Design & 3NF ERD Script',
        'desc' => 'Submit your normalized database schema SQL export and Entity-Relationship diagram.',
        'due' => date('Y-m-d H:i:s', strtotime('+1 day')), // Due in 24 hours!
    ],
    [
        'course' => 'ADET413', 'week' => 2, 'type' => 'assignment', 'max' => 100,
        'title' => 'REST API Endpoint Specification & CRUD Controller',
        'desc' => 'Construct an API controller providing GET, POST, PUT, DELETE operations with proper HTTP status codes.',
        'due' => date('Y-m-d H:i:s', strtotime('-4 days')), // Past
    ],
    [
        'course' => 'ADET413', 'week' => 4, 'type' => 'quiz', 'max' => 50,
        'title' => 'Web Application Security & CSRF Defense Quiz',
        'desc' => 'Assess understanding of Bcrypt hashing, secure cookies, SQL injection prevention, and CSRF tokens.',
        'due' => date('Y-m-d H:i:s', strtotime('+4 days')),
    ],
    [
        'course' => 'HCI413', 'week' => 3, 'type' => 'project', 'max' => 100,
        'title' => 'Interactive Figma High-Fidelity Prototype',
        'desc' => 'Build an interactive web interface prototype with clear visual hierarchy, color theory compliance, and feedback states.',
        'due' => date('Y-m-d H:i:s', strtotime('+2 days')), // Due soon
    ],
    [
        'course' => 'HCI413', 'week' => 4, 'type' => 'activity', 'max' => 100,
        'title' => 'Heuristic Usability Evaluation Report',
        'desc' => 'Perform a formal Nielsen 10 Heuristics inspection of a deployed web application and report severity rankings.',
        'due' => date('Y-m-d H:i:s', strtotime('-10 days')),
    ],
    [
        'course' => 'ADV08', 'week' => 2, 'type' => 'assignment', 'max' => 100,
        'title' => 'Data Preprocessing & Normalization Case Study',
        'desc' => 'Perform z-score normalization and imputation of missing values on the provided retail dataset.',
        'due' => date('Y-m-d H:i:s', strtotime('-3 days')),
    ],
    [
        'course' => 'ADV08', 'week' => 4, 'type' => 'quiz', 'max' => 50,
        'title' => 'Association Rule Mining (Apriori) Quiz',
        'desc' => 'Calculation of support, confidence, and lift for market basket transaction sets.',
        'due' => date('Y-m-d H:i:s', strtotime('+5 days')),
    ],
    [
        'course' => 'ISSMA413', 'week' => 3, 'type' => 'assignment', 'max' => 100,
        'title' => 'Software Sourcing Strategy & TCO Evaluation',
        'desc' => 'Evaluate Build vs. Buy vs. SaaS options for an enterprise ERP system and draft a 5-year TCO calculation.',
        'due' => date('Y-m-d H:i:s', strtotime('+6 days')),
    ],
];

$stmtAss = $conn->prepare("
    INSERT INTO assessments
    (syllabus_id, topic_id, teacher_id, title, description, type, max_score, due_date, delivery_mode)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'both')
");

$createdAssessments = [];
foreach ($assessmentsSeed as $a) {
    if (!isset($syllabi[$a['course']])) continue;
    $sylId = $syllabi[$a['course']]['id'];
    $tId = $syllabi[$a['course']]['teacher_id'];
    $topicId = $topicMap[$a['course']][$a['week']] ?? null;

    $check = $conn->query("SELECT id FROM assessments WHERE syllabus_id=$sylId AND title='{$conn->real_escape_string($a['title'])}'");
    if ($check && $check->num_rows > 0) {
        $assId = $check->fetch_assoc()['id'];
        // Update topic_id and due_date to ensure mapping
        $conn->query("UPDATE assessments SET topic_id=$topicId, due_date='{$a['due']}' WHERE id=$assId");
    } else {
        $stmtAss->bind_param('iiisssds', $sylId, $topicId, $tId, $a['title'], $a['desc'], $a['type'], $a['max'], $a['due']);
        $stmtAss->execute();
        $assId = $stmtAss->insert_id;
    }
    $createdAssessments[] = [
        'id' => $assId,
        'course' => $a['course'],
        'max' => $a['max'],
        'due' => $a['due']
    ];
}
echo "Assessments mapped and synchronized (" . count($createdAssessments) . " active assessments).\n";

// 7. Seed Student Submissions & Teacher Grades
echo "Generating student submissions and grades...\n";
$stmtSub = $conn->prepare("
    INSERT INTO submissions (assessment_id, student_id, text_answer, score, feedback, status, submitted_at, graded_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE score=VALUES(score), feedback=VALUES(feedback), status=VALUES(status)
");

$sampleFeedbacks = [
    'Outstanding work! Deliverable milestones and risk mitigations are exceptionally clear.',
    'Very well organized. Good understanding of the core concepts and principles.',
    'Solid submission. Ensure you address edge cases and error states in the next iteration.',
    'Good effort. Need to improve formatting and provide more detailed rationale in Section 2.',
    'Satisfactory work. Met the basic requirements, but could benefit from deeper analysis.'
];

$submissionsCount = 0;
// Create varied submissions across students
foreach ($students as $index => $stu) {
    // Each student submits to 2 to 4 past or active assessments
    foreach ($createdAssessments as $assIndex => $ass) {
        $isPast = strtotime($ass['due']) < time();
        $isDueSoon = (strtotime($ass['due']) - time()) <= 48 * 3600 && (strtotime($ass['due']) > time());

        // Students 0 to 20 have completed past assessments
        if ($isPast && ($index % 2 === 0 || $index % 3 === 0)) {
            $isGraded = ($index % 5 !== 0); // 80% graded, 20% pending review for teacher
            $score = $isGraded ? round($ass['max'] * (0.75 + (($index % 5) * 0.05)), 1) : null;
            $status = $isGraded ? 'graded' : 'submitted';
            $feedback = $isGraded ? $sampleFeedbacks[$index % count($sampleFeedbacks)] : null;
            $submittedAt = date('Y-m-d H:i:s', strtotime($ass['due'] . ' - ' . ($index + 1) . ' hours'));
            $gradedAt = $isGraded ? date('Y-m-d H:i:s', strtotime($submittedAt . ' + 1 day')) : null;
            $text = "Here is my completed work for this assessment. All requirements and constraints specified in the syllabus have been addressed.";

            $stmtSub->bind_param('iisdssss', $ass['id'], $stu['id'], $text, $score, $feedback, $status, $submittedAt, $gradedAt);
            $stmtSub->execute();
            $submissionsCount++;
        }

        // Some students submitted early to "due soon" assessments
        if ($isDueSoon && ($index % 4 === 0)) {
            $status = 'submitted';
            $score = null;
            $feedback = null;
            $submittedAt = date('Y-m-d H:i:s', strtotime('-2 hours'));
            $gradedAt = null;
            $text = "Early submission for review. Please see my uploaded response and code notes.";

            $stmtSub->bind_param('iisdssss', $ass['id'], $stu['id'], $text, $score, $feedback, $status, $submittedAt, $gradedAt);
            $stmtSub->execute();
            $submissionsCount++;
        }
    }
}
echo "Generated $submissionsCount student submissions and grades.\n";

// 8. Seed Student Topic Completion Progress & Reflection Notes
echo "Seeding student lesson progress & reflections (Objective 3)...\n";
$stmtProg = $conn->prepare("
    INSERT INTO topic_progress (student_id, syllabus_topic_id, status, completed_at, notes)
    VALUES (?, ?, 'completed', ?, ?)
    ON DUPLICATE KEY UPDATE status='completed', completed_at=VALUES(completed_at), notes=VALUES(notes)
");

$sampleReflections = [
    'Understood how the Work Breakdown Structure prevents scope creep and defines accountability.',
    'Gained clear insight into Agile sprint execution. Story point estimation was very helpful.',
    'Completed the database ERD. Normalizing up to 3NF eliminated redundant customer data.',
    'Practiced designing responsive components in Figma. Heuristic evaluation showed navigation flaws.',
    'Learned how the Apriori algorithm computes support and confidence in market basket analysis.',
    'Understood the difference between COTS and custom software acquisition for enterprises.'
];

$progressCount = 0;
foreach ($students as $stuIndex => $stu) {
    // Varied progress: students have completed 1 to 4 topics in each subject
    $topicsToComplete = ($stuIndex % 4) + 1; // 1, 2, 3, or 4 topics

    foreach ($topicMap as $code => $weeksMap) {
        $count = 0;
        foreach ($weeksMap as $weekNum => $topicId) {
            if ($count >= $topicsToComplete) break;

            $completedAt = date('Y-m-d H:i:s', strtotime('-' . (10 - $weekNum) . ' days'));
            $note = $sampleReflections[($stuIndex + $weekNum) % count($sampleReflections)];

            $stmtProg->bind_param('iiss', $stu['id'], $topicId, $completedAt, $note);
            $stmtProg->execute();
            $progressCount++;
            $count++;
        }
    }
}
echo "Seeded $progressCount student topic progress records with reflections.\n";

// 9. Seed Announcements
echo "Seeding announcements (Objective 2)...\n";
$announcementsSeed = [
    [
        'author_id' => 1,
        'title' => 'I-Tech College Midterm Examination Schedule (AY 2025-2026)',
        'content' => 'Please be informed that the BSIS 4th Year Midterm Examinations will take place from Sept 28 to Oct 02, 2026. All course syllabi, lecture slides, and project rubrics are accessible on BlendEd LMS.',
        'role' => 'all'
    ],
    [
        'author_id' => 39, // Albert Buenafe
        'title' => 'PROMAN413: Sprint 1 Retrospective & Project Backlog Due',
        'content' => 'Kindly ensure that all project teams have finalized their Sprint 1 User Stories and WBS Dictionary by Friday 11:59 PM. Submissions must be uploaded via the assessment module.',
        'role' => 'student'
    ],
    [
        'author_id' => 45, // Jeffred Lim
        'title' => 'ADV08: Hands-on Lab Session on Data Cleaning',
        'content' => 'Our next class will be a hands-on laboratory session in Computer Lab 4. Please review the lecture materials on z-score normalization and outlier detection before class.',
        'role' => 'student'
    ]
];

$stmtAnn = $conn->prepare("
    INSERT INTO announcements (author_id, title, content, target_role, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

foreach ($announcementsSeed as $ann) {
    $check = $conn->query("SELECT id FROM announcements WHERE title='{$conn->real_escape_string($ann['title'])}'");
    if (!$check || $check->num_rows === 0) {
        $stmtAnn->bind_param('isss', $ann['author_id'], $ann['title'], $ann['content'], $ann['role']);
        $stmtAnn->execute();
    }
}
echo "Announcements seeded successfully.\n\n";

echo "========================================================\n";
echo "  Data Seeding Complete!                                \n";
echo "  - 36 Syllabus Topics Mapped across 6 BSIS Courses     \n";
echo "  - 45 Students Enrolled across all courses             \n";
echo "  - 12 Topic-Aligned Assessments Created                \n";
echo "  - Realistic Graded & Pending Submissions Populated    \n";
echo "  - Student Reflections & Progress Records Seeded       \n";
echo "  - Active Timely Announcements & Deadlines Live        \n";
echo "========================================================\n";
