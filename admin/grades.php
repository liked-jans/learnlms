<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Master Gradebook';

// Filters
$sylFilter     = (int)($_GET['syllabus_id'] ?? 0);
$teacherFilter = (int)($_GET['teacher_id'] ?? 0);
$typeFilter    = sanitize($_GET['type'] ?? '');
$statusFilter  = sanitize($_GET['status'] ?? '');

// Base Query
$where = "1=1";
$params = [];
$types = "";

if ($sylFilter) {
    $where .= " AND s.id = ?";
    $params[] = $sylFilter;
    $types .= "i";
}
if ($teacherFilter) {
    $where .= " AND s.teacher_id = ?";
    $params[] = $teacherFilter;
    $types .= "i";
}
if ($typeFilter) {
    $where .= " AND a.type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}
if ($statusFilter) {
    if ($statusFilter === 'graded') {
        $where .= " AND sub.status = 'graded'";
    } elseif ($statusFilter === 'submitted') {
        $where .= " AND sub.status = 'submitted'";
    } elseif ($statusFilter === 'pending') {
        $where .= " AND sub.id IS NULL";
    }
}

// Check CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=BlendEd_Gradebook_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Student Email', 'Course Code', 'Course Name', 'Teacher', 'Assessment', 'Type', 'Max Score', 'Score', 'Percentage', 'Status', 'Submitted At', 'Graded At', 'Feedback']);

    $exportSql = "
        SELECT 
            u.full_name as student_name, u.email as student_email,
            c.course_code, c.course_name,
            t.full_name as teacher_name,
            a.title as assessment_title, a.type as assessment_type, a.max_score,
            sub.score, sub.status as submission_status, sub.submitted_at, sub.graded_at, sub.feedback
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        JOIN syllabi s ON e.syllabus_id = s.id
        JOIN courses c ON s.course_id = c.id
        JOIN users t ON s.teacher_id = t.id
        JOIN assessments a ON a.syllabus_id = s.id
        LEFT JOIN submissions sub ON sub.assessment_id = a.id AND sub.student_id = u.id
        WHERE $where AND e.status = 'enrolled'
        ORDER BY c.course_code, u.full_name, a.title
    ";

    $stmtExp = $conn->prepare($exportSql);
    if (!empty($params)) {
        $stmtExp->bind_param($types, ...$params);
    }
    $stmtExp->execute();
    $expRes = $stmtExp->get_result();

    while ($row = $expRes->fetch_assoc()) {
        $pct = ($row['score'] !== null && $row['max_score'] > 0) ? round(($row['score'] / $row['max_score']) * 100, 1) . '%' : 'N/A';
        fputcsv($output, [
            $row['student_name'],
            $row['student_email'],
            $row['course_code'],
            $row['course_name'],
            $row['teacher_name'],
            $row['assessment_title'],
            ucfirst($row['assessment_type']),
            $row['max_score'],
            $row['score'] ?? 'N/A',
            $pct,
            ucfirst($row['submission_status'] ?? 'Pending'),
            $row['submitted_at'] ?? 'Not Submitted',
            $row['graded_at'] ?? 'N/A',
            $row['feedback'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// Fetch filter option lists
$syllabiList = $conn->query("
    SELECT s.id, c.course_code, c.course_name, s.section_name, s.academic_year, s.semester, t.full_name as teacher_name
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN users t ON s.teacher_id = t.id
    WHERE s.status = 'published'
    ORDER BY c.course_code ASC
");

$teachersList = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY full_name ASC");

// Main Data Query
$querySql = "
    SELECT 
        u.id as student_id, u.full_name as student_name, u.email as student_email,
        c.course_code, c.course_name,
        t.full_name as teacher_name,
        a.id as assessment_id, a.title as assessment_title, a.type as assessment_type, a.max_score,
        sub.id as submission_id, sub.score, sub.status as submission_status, sub.submitted_at, sub.graded_at, sub.feedback
    FROM enrollments e
    JOIN users u ON e.student_id = u.id
    JOIN syllabi s ON e.syllabus_id = s.id
    JOIN courses c ON s.course_id = c.id
    JOIN users t ON s.teacher_id = t.id
    JOIN assessments a ON a.syllabus_id = s.id
    LEFT JOIN submissions sub ON sub.assessment_id = a.id AND sub.student_id = u.id
    WHERE $where AND e.status = 'enrolled'
    ORDER BY a.created_at DESC, u.full_name ASC
";

$stmtMain = $conn->prepare($querySql);
if (!empty($params)) {
    $stmtMain->bind_param($types, ...$params);
}
$stmtMain->execute();
$gradesData = $stmtMain->get_result();

// Summary Metrics
$totalRows = 0;
$totalGraded = 0;
$scoreSum = 0;
$maxSum = 0;
$passingCount = 0;

$rows = [];
while ($r = $gradesData->fetch_assoc()) {
    $totalRows++;
    if ($r['submission_status'] === 'graded' && $r['score'] !== null) {
        $totalGraded++;
        $scoreSum += $r['score'];
        $maxSum += $r['max_score'];
        if ($r['max_score'] > 0 && ($r['score'] / $r['max_score']) >= 0.75) {
            $passingCount++;
        }
    }
    $rows[] = $r;
}

$avgPct = ($maxSum > 0) ? round(($scoreSum / $maxSum) * 100, 1) : 0;
$passRate = ($totalGraded > 0) ? round(($passingCount / $totalGraded) * 100, 1) : 0;
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center">
    <div class="page-header-left">
        <h2>Institutional Master Gradebook</h2>
        <p>Comprehensive oversight of student performance and syllabus assessments across BSIS curriculum</p>
    </div>
    <div>
        <?php
        $exportParams = $_GET;
        $exportParams['export'] = 'csv';
        $exportUrl = 'grades.php?' . http_build_query($exportParams);
        ?>
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-primary">
            <i class="fas fa-file-export" style="margin-right:6px"></i> Export to CSV
        </a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-list-check"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalRows ?></div>
            <div class="stat-label">Total Course Tasks</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalGraded ?></div>
            <div class="stat-label">Graded Submissions</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $avgPct ?>%</div>
            <div class="stat-label">Average Score</div>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon purple"><i class="fas fa-award"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $passRate ?>%</div>
            <div class="stat-label">Passing Rate (&ge;75%)</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <select name="syllabus_id" class="form-control" style="width:260px" onchange="this.form.submit()">
                <option value="">All Courses / Syllabi</option>
                <?php while($s = $syllabiList->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $sylFilter == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['course_code'] . ' - ' . $s['course_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="teacher_id" class="form-control" style="width:200px" onchange="this.form.submit()">
                <option value="">All Instructors</option>
                <?php while($t = $teachersList->fetch_assoc()): ?>
                <option value="<?= $t['id'] ?>" <?= $teacherFilter == $t['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['full_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>

            <select name="type" class="form-control" style="width:160px" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="quiz" <?= $typeFilter === 'quiz' ? 'selected' : '' ?>>Quiz</option>
                <option value="assignment" <?= $typeFilter === 'assignment' ? 'selected' : '' ?>>Assignment</option>
                <option value="exam" <?= $typeFilter === 'exam' ? 'selected' : '' ?>>Exam</option>
                <option value="project" <?= $typeFilter === 'project' ? 'selected' : '' ?>>Project</option>
                <option value="activity" <?= $typeFilter === 'activity' ? 'selected' : '' ?>>Activity</option>
            </select>

            <select name="status" class="form-control" style="width:160px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="graded" <?= $statusFilter === 'graded' ? 'selected' : '' ?>>Graded</option>
                <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted (Pending)</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Unsubmitted</option>
            </select>

            <?php if ($sylFilter || $teacherFilter || $typeFilter || $statusFilter): ?>
            <a href="grades.php" class="btn btn-secondary btn-sm" title="Clear all filters">
                <i class="fas fa-times"></i> Reset
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Main Gradebook Table -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Teacher</th>
                    <th>Assessment</th>
                    <th>Type</th>
                    <th>Score / Max</th>
                    <th>Percentage</th>
                    <th>Status</th>
                    <th>Date Graded</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--text3)">
                        <i class="fas fa-inbox" style="font-size:32px;opacity:0.4;display:block;margin-bottom:10px"></i>
                        No student grades or submissions found matching the selected filters.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): 
                    $pct = ($r['score'] !== null && $r['max_score'] > 0) ? round(($r['score'] / $r['max_score']) * 100, 1) : null;
                    $typeColors = ['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($r['student_name']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($r['student_email']) ?></small>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($r['course_code']) ?></strong>
                        <br><small class="text-muted"><?= htmlspecialchars($r['course_name']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($r['teacher_name']) ?></td>
                    <td>
                        <?= htmlspecialchars($r['assessment_title']) ?>
                        <?php if (!empty($r['feedback'])): ?>
                            <br><small class="text-muted" title="<?= htmlspecialchars($r['feedback']) ?>"><i class="fas fa-comment-dots"></i> <?= htmlspecialchars(substr($r['feedback'], 0, 45)) ?>...</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $typeColors[$r['assessment_type']] ?? 'badge-gray' ?>"><?= ucfirst($r['assessment_type']) ?></span></td>
                    <td>
                        <?php if ($r['score'] !== null): ?>
                            <strong><?= $r['score'] ?></strong> / <?= $r['max_score'] ?>
                        <?php else: ?>
                            <span class="text-muted">- / <?= $r['max_score'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($pct !== null): ?>
                            <span style="font-weight:700;color:<?= $pct >= 75 ? 'var(--primary)' : ($pct >= 60 ? 'var(--warning)' : 'var(--danger)') ?>">
                                <?= $pct ?>%
                            </span>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['submission_status'] === 'graded'): ?>
                            <span class="badge badge-green"><i class="fas fa-check"></i> Graded</span>
                        <?php elseif ($r['submission_status'] === 'submitted'): ?>
                            <span class="badge badge-orange"><i class="fas fa-clock"></i> Pending Review</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Not Submitted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($r['graded_at'])): ?>
                            <small><?= date('M d, Y', strtotime($r['graded_at'])) ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div></div></div>
</body></html>
