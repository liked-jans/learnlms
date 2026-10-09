<?php
require_once __DIR__ . '/../includes/config.php';
requireRole(['teacher', 'admin']);

$userRole = $_SESSION['role'] ?? '';
$tid = $_SESSION['user_id'];
$assFilter = $_GET['assessment'] ?? '';
$isAll = ($assFilter === 'all');
$assFilterInt = (int)$assFilter;

if (empty($assFilter)) {
    setFlash('error', 'Please select an assessment to print the grade sheet.');
    redirect(BASE_URL . 'teacher/grades.php');
}

$assessmentsData = [];

if ($isAll) {
    // Fetch all assessments for this teacher
    $stmtList = $conn->prepare("
        SELECT a.*, s.academic_year, s.semester, s.section_name,
               c.course_code, c.course_name, c.units,
               t.week_number, t.topic_title,
               u.full_name as teacher_name, u.email as teacher_email,
               (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as q_count
        FROM assessments a 
        LEFT JOIN syllabi s ON a.syllabus_id = s.id 
        LEFT JOIN courses c ON s.course_id = c.id 
        LEFT JOIN syllabus_topics t ON a.topic_id = t.id
        LEFT JOIN users u ON a.teacher_id = u.id
        WHERE " . ($userRole === 'admin' ? "1=1" : "a.teacher_id = ?") . "
        ORDER BY a.created_at DESC
    ");
    if ($userRole !== 'admin') {
        $stmtList->bind_param('i', $tid);
    }
    $stmtList->execute();
    $aListRes = $stmtList->get_result();

    while ($a = $aListRes->fetch_assoc()) {
        $stmt2 = $conn->prepare("
            SELECT e.student_id, u.full_name, u.email,
                   sub.id as submission_id, sub.score, sub.status as sub_status,
                   sub.submitted_at, sub.graded_at, sub.feedback, sub.is_auto_graded
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN submissions sub ON sub.assessment_id = ? AND sub.student_id = e.student_id
            WHERE e.syllabus_id = ? AND e.status = 'enrolled'
            ORDER BY u.full_name ASC
        ");
        $stmt2->bind_param('ii', $a['id'], $a['syllabus_id']);
        $stmt2->execute();
        $subRows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $assessmentsData[] = ['assessment' => $a, 'submissions' => $subRows];
    }
} else {
    // Single assessment
    $stmt = $conn->prepare("
        SELECT a.*, s.academic_year, s.semester, s.section_name,
               c.course_code, c.course_name, c.units,
               t.week_number, t.topic_title,
               u.full_name as teacher_name, u.email as teacher_email,
               (SELECT COUNT(*) FROM assessment_questions WHERE assessment_id = a.id) as q_count
        FROM assessments a 
        LEFT JOIN syllabi s ON a.syllabus_id = s.id 
        LEFT JOIN courses c ON s.course_id = c.id 
        LEFT JOIN syllabus_topics t ON a.topic_id = t.id
        LEFT JOIN users u ON a.teacher_id = u.id
        WHERE a.id = ? " . ($userRole === 'admin' ? "" : "AND (a.teacher_id = ? OR s.teacher_id = ?)") . "
    ");
    if ($userRole === 'admin') {
        $stmt->bind_param('i', $assFilterInt);
    } else {
        $stmt->bind_param('iii', $assFilterInt, $tid, $tid);
    }
    $stmt->execute();
    $assessment = $stmt->get_result()->fetch_assoc();

    if (!$assessment) {
        setFlash('error', 'Assessment not found or you do not have permission to view it.');
        redirect(BASE_URL . 'teacher/grades.php');
    }

    $stmt2 = $conn->prepare("
        SELECT e.student_id, u.full_name, u.email,
               sub.id as submission_id, sub.score, sub.status as sub_status,
               sub.submitted_at, sub.graded_at, sub.feedback, sub.is_auto_graded
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN submissions sub ON sub.assessment_id = ? AND sub.student_id = e.student_id
            WHERE e.syllabus_id = ? AND e.status = 'enrolled'
            ORDER BY u.full_name ASC
    ");
    $stmt2->bind_param('ii', $assFilterInt, $assessment['syllabus_id']);
    $stmt2->execute();
    $subRows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
    $assessmentsData[] = ['assessment' => $assessment, 'submissions' => $subRows];
}

function getRatingScale($percentage) {
    if ($percentage >= 97) return ['equiv' => '1.00', 'desc' => 'Excellent', 'color' => '#15803d'];
    if ($percentage >= 94) return ['equiv' => '1.25', 'desc' => 'Superior', 'color' => '#15803d'];
    if ($percentage >= 91) return ['equiv' => '1.50', 'desc' => 'Very Good', 'color' => '#16a34a'];
    if ($percentage >= 88) return ['equiv' => '1.75', 'desc' => 'Good', 'color' => '#16a34a'];
    if ($percentage >= 85) return ['equiv' => '2.00', 'desc' => 'Very Satisfactory', 'color' => '#2563eb'];
    if ($percentage >= 80) return ['equiv' => '2.25', 'desc' => 'Satisfactory', 'color' => '#2563eb'];
    if ($percentage >= 75) return ['equiv' => '3.00', 'desc' => 'Passing', 'color' => '#d97706'];
    return ['equiv' => '5.00', 'desc' => 'Failed', 'color' => '#dc2626'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Official Grade Sheet - <?= htmlspecialchars($assessmentsData[0]['assessment']['course_code'] ?? 'Course') ?> (Print)</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        background: #f8fafc;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #0f172a;
        line-height: 1.5;
        padding-bottom: 60px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Screen Action Bar */
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
        box-shadow: 0 4px 14px rgba(0,0,0,0.18);
        gap: 16px;
        flex-wrap: wrap;
    }
    .screen-actions-bar .bar-left, .screen-actions-bar .bar-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .screen-actions-bar a, .screen-actions-bar button {
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        border: none;
        transition: all 0.15s ease;
    }
    .btn-print {
        background: #10b981;
        color: white;
    }
    .btn-print:hover { background: #059669; }
    .btn-back {
        background: #334155;
        color: #f1f5f9;
    }
    .btn-back:hover { background: #475569; }

    /* Printable Page Container */
    .print-sheet {
        max-width: 960px;
        margin: 28px auto;
        background: #ffffff;
        padding: 40px 48px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        page-break-after: always;
    }
    .print-sheet:last-child {
        page-break-after: auto;
    }

    /* Institutional Header */
    .inst-header {
        text-align: center;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 16px;
        margin-bottom: 22px;
        position: relative;
    }
    .inst-title {
        font-family: 'Cinzel', serif;
        font-size: 19px;
        font-weight: 700;
        letter-spacing: 1.2px;
        color: #0f172a;
        text-transform: uppercase;
    }
    .inst-subtitle {
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-top: 2px;
    }
    .inst-doc-title {
        font-size: 15px;
        font-weight: 800;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-top: 10px;
        background: #eff6ff;
        display: inline-block;
        padding: 4px 16px;
        border-radius: 4px;
        border: 1px solid #bfdbfe;
    }
    .inst-term {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
        font-weight: 500;
    }

    /* Metadata Table */
    .meta-box {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px 24px;
        font-size: 12.5px;
    }
    .meta-item {
        display: flex;
        align-items: baseline;
    }
    .meta-label {
        width: 140px;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.3px;
        flex-shrink: 0;
    }
    .meta-value {
        color: #0f172a;
        font-weight: 600;
    }

    /* KPI Summary Stats */
    .kpi-row {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 10px;
        margin-bottom: 22px;
    }
    .kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 12px;
        text-align: center;
    }
    .kpi-val {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
    }
    .kpi-lbl {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        margin-top: 4px;
        letter-spacing: 0.3px;
    }

    /* Main Grades Table */
    .grades-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        margin-bottom: 26px;
    }
    .grades-table th {
        background: #1e293b;
        color: #ffffff;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 10.5px;
        letter-spacing: 0.5px;
        padding: 9px 10px;
        border: 1px solid #1e293b;
        text-align: left;
    }
    .grades-table th.center, .grades-table td.center {
        text-align: center;
    }
    .grades-table th.right, .grades-table td.right {
        text-align: right;
    }
    .grades-table td {
        padding: 8px 10px;
        border: 1px solid #cbd5e1;
        color: #1e293b;
        vertical-align: middle;
    }
    .grades-table tr:nth-child(even) {
        background: #f8fafc;
    }
    .grades-table tr.highlight-row {
        background: #f0fdf4;
    }

    /* Status Badges */
    .badge-status {
        display: inline-block;
        padding: 2px 7px;
        border-radius: 4px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .status-graded { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-submitted { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .status-missing { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Sign-off & Certification Footer */
    .cert-footer {
        margin-top: 36px;
        padding-top: 18px;
        page-break-inside: avoid;
    }
    .cert-notice {
        font-size: 10.5px;
        color: #64748b;
        text-align: justify;
        line-height: 1.5;
        border-top: 1px dashed #cbd5e1;
        padding-top: 12px;
        margin-bottom: 28px;
    }
    .sig-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        margin-top: 10px;
    }
    .sig-box {
        text-align: center;
    }
    .sig-line {
        height: 1px;
        background: #334155;
        margin: 44px 10px 8px 10px;
    }
    .sig-name {
        font-weight: 700;
        font-size: 12px;
        color: #0f172a;
        text-transform: uppercase;
    }
    .sig-role {
        font-size: 10.5px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Print Specific Media Styles */
    @media print {
        body {
            background: #ffffff;
            padding: 0;
            font-size: 11.5px;
        }
        .screen-actions-bar {
            display: none !important;
        }
        .print-sheet {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 10mm 12mm !important;
            box-shadow: none !important;
            border: none !important;
        }
        @page {
            size: letter portrait;
            margin: 10mm 10mm 12mm 10mm;
        }
        .grades-table tr {
            page-break-inside: avoid;
        }
    }
</style>
</head>
<body>

<!-- Top Action Bar for Screen Viewing -->
<div class="screen-actions-bar">
    <div class="bar-left">
        <a href="grades.php<?= $isAll ? '?assessment=all' : ('?assessment=' . (int)$assFilterInt) ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Grades
        </a>
        <span style="font-size:13px;font-weight:600;color:#94a3b8">
            <i class="fas fa-file-invoice" style="margin-right:5px;color:#38bdf8"></i> Official Grade Sheet
        </span>
    </div>
    <div class="bar-right">
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print Grade Sheet
        </button>
    </div>
</div>

<?php foreach ($assessmentsData as $block):
    $a = $block['assessment'];
    $subs = $block['submissions'];
    $maxScore = (float)$a['max_score'];
    $qCount = (int)$a['q_count'];

    $totalEnrolled = count($subs);
    $subCount = 0;
    $gradedCount = 0;
    $scores = [];

    foreach ($subs as $s) {
        if (!empty($s['submission_id'])) {
            $subCount++;
            if ($s['sub_status'] === 'graded') $gradedCount++;
            $scores[] = (float)$s['score'];
        }
    }

    $avgScore = !empty($scores) ? (array_sum($scores) / count($scores)) : 0;
    $highScore = !empty($scores) ? max($scores) : 0;
    $lowScore = !empty($scores) ? min($scores) : 0;
    $passingCount = count(array_filter($scores, fn($sc) => ($sc / ($maxScore ?: 1)) >= 0.75));
    $passingRate = $subCount > 0 ? (($passingCount / $subCount) * 100) : 0;
    $submissionRate = $totalEnrolled > 0 ? (($subCount / $totalEnrolled) * 100) : 0;
?>

<div class="print-sheet">
    <!-- Institutional Header -->
    <div class="inst-header">
        <div class="inst-title">BlendEd Learning Management System</div>
        <div class="inst-subtitle">College of Computer Studies & Information Systems</div>
        <div class="inst-doc-title">Official Class Grade Sheet & Evaluation Record</div>
        <div class="inst-term">
            Academic Year <?= htmlspecialchars($a['academic_year'] ?? '2025-2026') ?> &bull; 
            <?= htmlspecialchars($a['semester'] ?? '2nd') ?> Semester &bull; 
            Published: <?= date('F d, Y') ?>
        </div>
    </div>

    <!-- Course & Assessment Metadata -->
    <div class="meta-box">
        <div class="meta-item">
            <span class="meta-label">Course:</span>
            <span class="meta-value"><?= htmlspecialchars($a['course_code']) ?> &mdash; <?= htmlspecialchars($a['course_name']) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Instructor:</span>
            <span class="meta-value"><?= htmlspecialchars($a['teacher_name'] ?? 'Instructor') ?> (<?= htmlspecialchars($a['teacher_email'] ?? '') ?>)</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Assessment:</span>
            <span class="meta-value"><?= safeHtml($a['title']) ?> (<?= ucfirst($a['type']) ?>)</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Module / Week:</span>
            <span class="meta-value">Week <?= (int)$a['week_number'] ?> &bull; <?= safeHtml($a['topic_title'] ?? 'Curriculum Topic') ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Declared Max:</span>
            <span class="meta-value"><?= number_format($maxScore, 1) ?> pts (<?= $qCount ?> Question<?= $qCount != 1 ? 's' : '' ?>)</span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Passing Mark:</span>
            <span class="meta-value"><?= number_format($maxScore * 0.75, 1) ?> pts (75.0% Standard)</span>
        </div>
    </div>

    <!-- Statistical Performance Highlights -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-val"><?= $totalEnrolled ?></div>
            <div class="kpi-lbl">Total Enrolled</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-val" style="color:#2563eb"><?= $subCount ?> (<?= round($submissionRate) ?>%)</div>
            <div class="kpi-lbl">Turnout</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-val" style="color:#15803d"><?= number_format($highScore, 1) ?></div>
            <div class="kpi-lbl">Highest Score</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-val" style="color:#d97706"><?= number_format($lowScore, 1) ?></div>
            <div class="kpi-lbl">Lowest Score</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-val"><?= number_format($avgScore, 1) ?> (<?= round(($avgScore/($maxScore?:1))*100) ?>%)</div>
            <div class="kpi-lbl">Class Mean</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-val" style="color:#15803d"><?= round($passingRate) ?>%</div>
            <div class="kpi-lbl">Passing Rate</div>
        </div>
    </div>

    <!-- Student Grades Roster Table -->
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width:36px" class="center">#</th>
                <th style="width:110px">Student ID</th>
                <th>Student Full Name</th>
                <th style="width:145px">Submission Date</th>
                <th style="width:75px" class="right">Score</th>
                <th style="width:65px" class="right">Pct.</th>
                <th style="width:75px" class="center">Rating</th>
                <th style="width:85px" class="center">Status</th>
                <th style="width:170px">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($subs)): ?>
                <tr>
                    <td colspan="9" class="center" style="padding:24px;color:#64748b">No student enrollments found for this class roster.</td>
                </tr>
            <?php else: 
                $itemNum = 1;
                foreach ($subs as $s):
                    $hasSubmitted = !empty($s['submission_id']);
                    $scoreVal = $hasSubmitted ? (float)$s['score'] : 0.0;
                    $pct = $maxScore > 0 ? (($scoreVal / $maxScore) * 100) : 0;
                    $rating = $hasSubmitted ? getRatingScale($pct) : null;
                    $isPassed = $hasSubmitted && ($pct >= 75.0);
            ?>
            <tr class="<?= $hasSubmitted ? '' : 'missing-row' ?>">
                <td class="center" style="font-weight:700;color:#64748b"><?= $itemNum++ ?></td>
                <td style="font-weight:600;font-family:monospace;font-size:11.5px">
                    STU-<?= str_pad($s['student_id'], 5, '0', STR_PAD_LEFT) ?>
                </td>
                <td>
                    <strong style="color:#0f172a"><?= htmlspecialchars($s['full_name']) ?></strong>
                    <div style="font-size:10.5px;color:#64748b"><?= htmlspecialchars($s['email']) ?></div>
                </td>
                <td style="font-size:11px;color:#334155">
                    <?php if ($hasSubmitted && !empty($s['submitted_at'])): ?>
                        <?= date('M d, Y h:i A', strtotime($s['submitted_at'])) ?>
                    <?php else: ?>
                        <span style="color:#94a3b8;font-style:italic">&mdash; Not Taken &mdash;</span>
                    <?php endif; ?>
                </td>
                <td class="right" style="font-weight:700;font-size:12.5px;color:<?= $hasSubmitted ? '#0f172a' : '#94a3b8' ?>">
                    <?php if ($hasSubmitted): ?>
                        <?= number_format($scoreVal, 1) ?> <span style="font-size:10px;color:#64748b;font-weight:500">/ <?= number_format($maxScore, 1) ?></span>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td class="right" style="font-weight:700;font-size:12px;color:<?= $hasSubmitted ? ($isPassed ? '#15803d' : '#dc2626') : '#94a3b8' ?>">
                    <?php if ($hasSubmitted): ?>
                        <?= number_format($pct, 1) ?>%
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td class="center">
                    <?php if ($hasSubmitted && $rating): ?>
                        <strong style="color:<?= $rating['color'] ?>"><?= $rating['equiv'] ?></strong>
                        <div style="font-size:9.5px;color:#64748b;line-height:1"><?= $rating['desc'] ?></div>
                    <?php else: ?>
                        <span style="color:#94a3b8;font-size:10.5px">INC</span>
                    <?php endif; ?>
                </td>
                <td class="center">
                    <?php if ($hasSubmitted): ?>
                        <span class="badge-status <?= $s['sub_status'] === 'graded' ? 'status-graded' : 'status-submitted' ?>">
                            <?= htmlspecialchars(ucfirst($s['sub_status'])) ?>
                        </span>
                    <?php else: ?>
                        <span class="badge-status status-missing">Missing</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:11px;color:#475569">
                    <?php if ($hasSubmitted && !empty($s['feedback'])): ?>
                        <?= safeHtml($s['feedback']) ?>
                    <?php elseif ($hasSubmitted): ?>
                        <span style="color:#64748b;font-style:italic">Evaluated & Recorded</span>
                    <?php else: ?>
                        <span style="color:#991b1b;font-style:italic">Assessment not yet submitted</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Academic Certification & Sign-off Footer -->
    <div class="cert-footer">
        <div class="cert-notice">
            <strong>CERTIFICATION:</strong> I hereby certify that the student marks, raw scores, and equivalent ratings recorded herein are true, accurate, and computed in accordance with the prescribed institutional curriculum grading system and blend-ed standards.
        </div>
        <div class="sig-grid">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name"><?= htmlspecialchars($a['teacher_name'] ?? 'Course Instructor') ?></div>
                <div class="sig-role">Faculty Member / Instructor</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name">Engr. Alan M. Turing, Ph.D.</div>
                <div class="sig-role">Department Chairperson</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-name">Dr. Grace Brewster Hopper</div>
                <div class="sig-role">Dean, Academic Affairs</div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>
