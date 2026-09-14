<?php
require_once '../includes/config.php';
requireRole('teacher');
$tid = $_SESSION['user_id'];
$sylId = (int)($_GET['id'] ?? ($_GET['syl_id'] ?? 0));

// Fetch syllabus, course, and teacher
$stmt = $conn->prepare("
    SELECT s.*, c.course_code, c.course_name, c.units, c.description as course_desc, c.prerequisite, c.year_level,
           u.full_name as teacher_name, u.email as teacher_email,
           d.name as department_name
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN users u ON s.teacher_id = u.id
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE s.id = ? AND s.teacher_id = ?
");
$stmt->bind_param('ii', $sylId, $tid);
$stmt->execute();
$syllabus = $stmt->get_result()->fetch_assoc();

if (!$syllabus) {
    setFlash('error', 'Syllabus not found or access denied.');
    redirect(BASE_URL . 'teacher/topics.php');
}

// Fetch topics with materials and assessments
$stmtTopics = $conn->prepare("
    SELECT st.*,
           GROUP_CONCAT(DISTINCT lm.title SEPARATOR '||') as materials_list,
           GROUP_CONCAT(DISTINCT CONCAT(a.title, ' [', a.type, ', ', a.max_score, ' pts]') SEPARATOR '||') as assessments_list
    FROM syllabus_topics st
    LEFT JOIN learning_materials lm ON lm.syllabus_topic_id = st.id
    LEFT JOIN assessments a ON a.topic_id = st.id
    WHERE st.syllabus_id = ?
    GROUP BY st.id
    ORDER BY st.week_number ASC, st.sort_order ASC
");
$stmtTopics->bind_param('i', $sylId);
$stmtTopics->execute();
$topics = $stmtTopics->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Official OBE Syllabus - ' . $syllabus['course_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($syllabus['course_code']) ?> - Official OBE Syllabus | <?= SITE_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: #f1f5f9;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #1e293b;
        line-height: 1.5;
        padding-bottom: 60px;
    }

    /* Action bar for screen only */
    .screen-actions-bar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #0f172a;
        color: white;
        padding: 12px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .screen-actions-bar a, .screen-actions-bar button {
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-back { background: #334155; color: white; border: none; }
    .btn-back:hover { background: #475569; }
    .btn-print { background: #2563eb; color: white; border: none; }
    .btn-print:hover { background: #1d4ed8; }

    /* The Printable Document Container */
    .document-page {
        max-width: 900px;
        margin: 30px auto;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 50px 60px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.06);
    }

    /* University Header */
    .uni-header {
        text-align: center;
        border-bottom: 3px double #0f172a;
        padding-bottom: 20px;
        margin-bottom: 24px;
    }
    .uni-title {
        font-family: 'Cinzel', serif;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 1px;
        color: #0f172a;
        text-transform: uppercase;
    }
    .uni-sub {
        font-size: 12px;
        color: #475569;
        font-weight: 500;
        margin-top: 2px;
    }
    .doc-badge {
        display: inline-block;
        background: #0f172a;
        color: white;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 4px 14px;
        border-radius: 4px;
        margin-top: 10px;
    }

    /* Course Specs Table */
    .specs-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 24px;
        font-size: 12px;
    }
    .specs-table th, .specs-table td {
        border: 1px solid #94a3b8;
        padding: 8px 12px;
        vertical-align: top;
    }
    .specs-table th {
        background: #f8fafc;
        font-weight: 700;
        color: #334155;
        width: 22%;
    }

    /* Section Headings */
    .section-title {
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #0f172a;
        border-left: 4px solid #2563eb;
        padding-left: 10px;
        margin: 24px 0 12px;
    }

    .desc-text {
        font-family: 'Lora', serif;
        font-size: 13px;
        color: #334155;
        line-height: 1.7;
        margin-bottom: 16px;
        text-align: justify;
    }

    /* 6-Column Learning Plan Matrix */
    .matrix-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 12px;
        margin-bottom: 24px;
    }
    .matrix-table th, .matrix-table td {
        border: 1px solid #64748b;
        padding: 8px 10px;
        vertical-align: top;
        line-height: 1.4;
    }
    .matrix-table th {
        background: #0f172a;
        color: #ffffff;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        text-align: center;
    }
    .matrix-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }
    .cilo-tag {
        font-weight: 800;
        color: #1d4ed8;
        display: block;
        margin-bottom: 2px;
    }
    .bloom-pill {
        display: inline-block;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 2px 5px;
        border-radius: 3px;
        background: #e2e8f0;
        color: #334155;
    }
    .bloom-understanding { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .bloom-applying { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .bloom-analyzing { background: #f0fdfa; color: #115e59; border: 1px solid #99f6e4; }
    .bloom-evaluating { background: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
    .bloom-creating { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

    /* Grading and Signatories */
    .grading-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
        font-size: 12px;
    }
    .grading-box {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 14px 16px;
        background: #f8fafc;
    }
    .grading-box h4 {
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #0f172a;
        text-transform: uppercase;
    }

    .signatory-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    .signatory-box {
        text-align: center;
        font-size: 11px;
    }
    .signatory-line {
        border-bottom: 1px solid #0f172a;
        margin: 40px 10px 8px;
    }
    .signatory-name {
        font-weight: 700;
        color: #0f172a;
        text-transform: uppercase;
        font-size: 11px;
    }
    .signatory-title {
        color: #64748b;
        font-size: 10px;
    }

    /* Print media optimization */
    @media print {
        body { background: #ffffff; padding: 0; }
        .screen-actions-bar { display: none !important; }
        .document-page {
            max-width: 100%;
            margin: 0;
            border: none;
            padding: 20px 25px;
            box-shadow: none;
        }
        .matrix-table { page-break-inside: auto; }
        .matrix-table tr { page-break-inside: avoid; page-break-after: auto; }
        .signatory-row { page-break-inside: avoid; }
    }
</style>
</head>
<body>

<!-- Screen Top Control Bar -->
<div class="screen-actions-bar">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="topics.php?syl_id=<?= $syllabus['id'] ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Syllabus Mapping
        </a>
        <span style="font-size:13px;opacity:0.8">CHED CMO 25, s. 2015 Compliant OBE Syllabus</span>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print / Save as PDF
        </button>
    </div>
</div>

<!-- Official Syllabus Page -->
<div class="document-page">

    <!-- University Header -->
    <div class="uni-header">
        <div class="uni-title"><?= SITE_NAME ?> INSTITUTE OF TECHNOLOGY</div>
        <div class="uni-sub">COLLEGE OF COMPUTER STUDIES &bull; <?= htmlspecialchars(strtoupper($syllabus['department_name'] ?? 'Department of Information Systems')) ?></div>
        <div class="uni-sub">Accredited by the Commission on Higher Education (CHED) &bull; CMO No. 25, Series of 2015</div>
        <div class="doc-badge">Outcomes-Based Course Syllabus (OBE)</div>
    </div>

    <!-- Course Specifications -->
    <table class="specs-table">
        <tr>
            <th>Course Code & Title</th>
            <td colspan="3"><strong><?= htmlspecialchars($syllabus['course_code']) ?>: <?= htmlspecialchars($syllabus['course_name']) ?></strong></td>
        </tr>
        <tr>
            <th>Course Units</th>
            <td><?= $syllabus['units'] ?> Units (3 hrs Lecture / Lab)</td>
            <th>Academic Year & Term</th>
            <td>AY <?= htmlspecialchars($syllabus['academic_year']) ?> &bull; <?= htmlspecialchars($syllabus['semester']) ?> Semester</td>
        </tr>
        <tr>
            <th>Prerequisites</th>
            <td><?= htmlspecialchars($syllabus['prerequisite'] ?: 'None') ?></td>
            <th>Year Level</th>
            <td><?= $syllabus['year_level'] ? $syllabus['year_level'].'th Year' : '3rd/4th Year' ?> BS Information Systems</td>
        </tr>
        <tr>
            <th>Course Instructor</th>
            <td><strong><?= htmlspecialchars($syllabus['teacher_name']) ?></strong></td>
            <th>Contact & Consultation</th>
            <td><?= htmlspecialchars($syllabus['teacher_email']) ?></td>
        </tr>
    </table>

    <!-- Course Description -->
    <div class="section-title">I. Course Description</div>
    <p class="desc-text">
        <?= nl2br(htmlspecialchars($syllabus['course_desc'] ?: 'This course covers the theoretical principles, computational frameworks, and practical applications of knowledge discovery and data analytics. Students explore data preprocessing, association rule mining, predictive classification, and unsupervised clustering with real-world enterprise applications.')) ?>
    </p>

    <!-- Course Intended Learning Outcomes -->
    <div class="section-title">II. Course Intended Learning Outcomes (CILOs)</div>
    <p class="desc-text" style="margin-bottom:8px">Upon successful completion of this course, students are expected to demonstrate the following competencies:</p>
    <table class="specs-table" style="margin-bottom:24px">
        <thead>
            <tr style="background:#f1f5f9">
                <th style="width:12%;text-align:center">CILO ID</th>
                <th style="width:20%">Cognitive Domain</th>
                <th>Intended Learning Outcome Description</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $t): ?>
            <tr>
                <td style="text-align:center;font-weight:700;color:#1d4ed8"><?= htmlspecialchars($t['ilo_code'] ?: 'CILO '.$t['week_number']) ?></td>
                <td>
                    <span class="bloom-pill bloom-<?= htmlspecialchars($t['blooms_level'] ?? 'understanding') ?>">
                        <?= ucfirst(htmlspecialchars($t['blooms_level'] ?? 'Understanding')) ?>
                    </span>
                </td>
                <td><?= htmlspecialchars($t['learning_outcomes'] ?: 'Understand and apply core algorithmic concepts in ' . $t['topic_title']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- The 6-Column Learning Plan Matrix -->
    <div class="section-title">III. Outcomes-Based Constructive Alignment Matrix (Learning Plan)</div>
    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width:10%">Timeframe</th>
                <th style="width:14%">Outcome (CILO)</th>
                <th style="width:22%">Topic & Content Outline</th>
                <th style="width:18%">Teaching & Learning Activities (TLAs)</th>
                <th style="width:18%">Instructional Materials</th>
                <th style="width:18%">Assessment Tasks (ATs)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $row): 
                $mats = !empty($row['materials_list']) ? explode('||', $row['materials_list']) : [];
                $asses = !empty($row['assessments_list']) ? explode('||', $row['assessments_list']) : [];
            ?>
            <tr>
                <td style="text-align:center">
                    <strong>Week <?= $row['week_number'] ?></strong>
                    <div style="font-size:9px;color:#64748b;margin-top:2px">
                        <?= $row['week_number'] <= 6 ? 'Prelim Period' : ($row['week_number'] <= 12 ? 'Midterm Period' : 'Final Period') ?>
                    </div>
                </td>
                <td>
                    <span class="cilo-tag"><?= htmlspecialchars($row['ilo_code'] ?: 'CILO '.$row['week_number']) ?></span>
                    <span class="bloom-pill bloom-<?= htmlspecialchars($row['blooms_level'] ?? 'understanding') ?>">
                        <?= ucfirst(htmlspecialchars($row['blooms_level'] ?? 'Understanding')) ?>
                    </span>
                </td>
                <td>
                    <strong><?= htmlspecialchars($row['topic_title']) ?></strong>
                    <?php if (!empty($row['topic_description'])): ?>
                        <div style="font-size:10px;color:#475569;margin-top:3px">
                            <?= htmlspecialchars(substr($row['topic_description'], 0, 100)) ?><?= strlen($row['topic_description']) > 100 ? '...' : '' ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-top:4px">
                        <span style="font-size:9px;padding:2px 4px;border:1px solid #cbd5e1;border-radius:2px;color:#475569">
                            Mode: <?= ucfirst($row['delivery_mode'] ?: 'Blended') ?>
                        </span>
                    </div>
                </td>
                <td>
                    <?php if (!empty($row['activity_title'])): ?>
                        &bull; <?= htmlspecialchars($row['activity_title']) ?>
                    <?php else: ?>
                        &bull; Interactive Lecture & Practical Laboratory Case Study
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($mats)): ?>
                        <?php foreach ($mats as $m): ?>
                            <div>&bull; <?= htmlspecialchars($m) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>&bull; Lecture Slide Deck</div>
                        <div>&bull; Standard Textbook / IEEE References</div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($asses)): ?>
                        <?php foreach ($asses as $a): ?>
                            <div style="font-weight:600;color:#0f172a;margin-bottom:2px">
                                &bull; <?= htmlspecialchars($a) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#64748b;font-style:italic">&bull; Formative Quiz & Rubric Evaluation</div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Grading System -->
    <div class="section-title">IV. Assessment Criteria & Grading System</div>
    <div class="grading-grid">
        <div class="grading-box">
            <h4>Formative Assessments (50%)</h4>
            <ul style="padding-left:18px;line-height:1.6">
                <li>Quizzes & Objective Tests: <strong>20%</strong></li>
                <li>Laboratory Activities & Case Studies: <strong>20%</strong></li>
                <li>Class Participation & Exercises: <strong>10%</strong></li>
            </ul>
        </div>
        <div class="grading-box">
            <h4>Summative Assessments (50%)</h4>
            <ul style="padding-left:18px;line-height:1.6">
                <li>Midterm Examination: <strong>20%</strong></li>
                <li>Final Capstone / Term Project: <strong>25%</strong></li>
                <li>Ethics & Professionalism: <strong>5%</strong></li>
            </ul>
        </div>
    </div>

    <!-- Signatory Section -->
    <div class="signatory-row">
        <div class="signatory-box">
            <div>Prepared by:</div>
            <div class="signatory-line"></div>
            <div class="signatory-name"><?= htmlspecialchars($syllabus['teacher_name']) ?></div>
            <div class="signatory-title">Course Instructor / Faculty</div>
        </div>
        <div class="signatory-box">
            <div>Recommending Approval:</div>
            <div class="signatory-line"></div>
            <div class="signatory-name">Department Chair, IS</div>
            <div class="signatory-title">Curriculum Committee</div>
        </div>
        <div class="signatory-box">
            <div>Approved by:</div>
            <div class="signatory-line"></div>
            <div class="signatory-name">Dean, College of Computer Studies</div>
            <div class="signatory-title">Academic Affairs</div>
        </div>
    </div>

</div>

</body>
</html>
