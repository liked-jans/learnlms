<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Progress Monitor';

// Get selected tab and syllabus filter
$activeTab = sanitize($_GET['tab'] ?? 'syllabi');
$selectedSylId = (int)($_GET['syl'] ?? 0);

// Global Executive Statistics
$totalSyllabiCount = (int)($conn->query("SELECT COUNT(*) c FROM syllabi")->fetch_assoc()['c'] ?? 0);
$publishedSyllabiCount = (int)($conn->query("SELECT COUNT(*) c FROM syllabi WHERE status = 'published'")->fetch_assoc()['c'] ?? 0);
$activeTeachersCount = (int)($conn->query("SELECT COUNT(DISTINCT teacher_id) c FROM syllabi")->fetch_assoc()['c'] ?? 0);
$totalEnrollmentsCount = (int)($conn->query("SELECT COUNT(*) c FROM enrollments WHERE status = 'enrolled'")->fetch_assoc()['c'] ?? 0);
$totalStudentsCount = (int)($conn->query("SELECT COUNT(DISTINCT student_id) c FROM enrollments WHERE status = 'enrolled'")->fetch_assoc()['c'] ?? 0);

// 1. Fetch all syllabi with teacher and mapping metrics
$syllabiMonitorQuery = $conn->query("
    SELECT s.*, 
           c.course_code, c.course_name, c.units,
           u.id as teacher_id, u.full_name as teacher_name, u.email as teacher_email,
           d.name as dept_name,
           (SELECT COUNT(*) FROM syllabus_topics WHERE syllabus_id = s.id) as topic_count,
           (SELECT COUNT(DISTINCT ilo_code) FROM syllabus_topics WHERE syllabus_id = s.id AND ilo_code IS NOT NULL AND ilo_code != '') as ilo_count,
           (SELECT COUNT(*) FROM learning_materials WHERE syllabus_id = s.id) as material_count,
           (SELECT COUNT(*) FROM assessments WHERE syllabus_id = s.id) as assessment_count,
           (SELECT COUNT(*) FROM enrollments WHERE syllabus_id = s.id AND status = 'enrolled') as student_count
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN users u ON s.teacher_id = u.id
    LEFT JOIN departments d ON c.department_id = d.id
    ORDER BY c.course_code ASC
");
$allSyllabi = $syllabiMonitorQuery ? $syllabiMonitorQuery->fetch_all(MYSQLI_ASSOC) : [];

// If no syllabus selected, default to the first syllabus with enrollments or topics
if (!$selectedSylId && !empty($allSyllabi)) {
    // Prefer syllabus 67 if present, else first syllabus
    $found67 = array_filter($allSyllabi, fn($s) => (int)$s['id'] === 67);
    if (!empty($found67)) {
        $selectedSylId = 67;
    } else {
        $selectedSylId = (int)$allSyllabi[0]['id'];
    }
}

// 2. Fetch data for selected syllabus student monitoring
$currentSyllabus = null;
$enrolledStudents = [];
$totalTopicsCount = 0;
$totalAssessmentsCount = 0;
$cohortAvgOverallPct = 0;
$completedCohortCount = 0;
$inProgressCohortCount = 0;
$notStartedCohortCount = 0;
$activeReadersCount = 0;

if ($selectedSylId > 0) {
    foreach ($allSyllabi as $sRow) {
        if ((int)$sRow['id'] === $selectedSylId) {
            $currentSyllabus = $sRow;
            break;
        }
    }
    
    if ($currentSyllabus) {
        $totalTopicsCount = (int)$currentSyllabus['topic_count'];
        $totalAssessmentsCount = (int)$currentSyllabus['assessment_count'];

        $enrolledStudentsStmt = $conn->prepare("
            SELECT e.id as enrollment_id, e.enrolled_at, e.status as enrollment_status,
                   u.id as student_id, u.full_name, u.email, u.username,
                   
                   -- Reading Stats
                   COALESCE(tp_stats.finished_topics, 0) as finished_topics,
                   COALESCE(tp_stats.reading_topics, 0) as reading_topics,
                   COALESCE(tp_stats.total_read_pct_sum, 0) as total_read_pct_sum,
                   tp_stats.latest_read_at,
                   
                   -- Assessment Stats
                   COALESCE(sub_stats.submitted_assessments, 0) as submitted_assessments,
                   COALESCE(sub_stats.graded_submissions, 0) as graded_submissions,
                   sub_stats.latest_submission_at
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN (
                SELECT tp.student_id,
                       COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage >= 90 OR tp.status = 'completed') THEN st.id END) as finished_topics,
                       COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage > 0 OR tp.status = 'in_progress') AND tp.read_percentage < 90 AND tp.status != 'completed' THEN st.id END) as reading_topics,
                       SUM(CASE WHEN tp.status = 'not_started' THEN 0 ELSE COALESCE(tp.read_percentage, 0) END) as total_read_pct_sum,
                       MAX(tp.last_read_at) as latest_read_at
                FROM syllabus_topics st
                JOIN topic_progress tp ON tp.syllabus_topic_id = st.id
                WHERE st.syllabus_id = ?
                GROUP BY tp.student_id
            ) tp_stats ON tp_stats.student_id = e.student_id
            LEFT JOIN (
                SELECT s.student_id,
                       COUNT(DISTINCT s.assessment_id) as submitted_assessments,
                       COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded_submissions,
                       MAX(s.submitted_at) as latest_submission_at
                FROM assessments a
                JOIN submissions s ON s.assessment_id = a.id
                WHERE a.syllabus_id = ?
                GROUP BY s.student_id
            ) sub_stats ON sub_stats.student_id = e.student_id
            WHERE e.syllabus_id = ? AND e.status = 'enrolled'
            ORDER BY u.full_name ASC
        ");
        $enrolledStudentsStmt->bind_param('iii', $selectedSylId, $selectedSylId, $selectedSylId);
        $enrolledStudentsStmt->execute();
        $enrolledStudents = $enrolledStudentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $enrolledStudentsStmt->close();

        $enrolledCount = count($enrolledStudents);
        $cohortSumOverallPct = 0;

        foreach ($enrolledStudents as &$es) {
            $tot = $totalTopicsCount;
            $fin = (int)$es['finished_topics'];
            $read = (int)$es['reading_topics'];
            $notStarted = max(0, $tot - $fin - $read);
            
            $readAvgPct = $tot > 0 ? round((float)$es['total_read_pct_sum'] / $tot, 1) : 0.0;
            $readAvgPct = min(100.0, max(0.0, $readAvgPct));
            
            $totAssess = $totalAssessmentsCount;
            $subAssess = (int)$es['submitted_assessments'];
            $gradedAssess = (int)$es['graded_submissions'];
            $assessAvgPct = $totAssess > 0 ? round(($subAssess / $totAssess) * 100, 1) : 0.0;

            if ($totAssess > 0 && $tot > 0) {
                $overallPct = round(((float)$es['total_read_pct_sum'] + ($subAssess * 100)) / ($tot + $totAssess), 1);
            } elseif ($tot > 0) {
                $overallPct = $readAvgPct;
            } else {
                $overallPct = $assessAvgPct;
            }
            $overallPct = min(100.0, max(0.0, $overallPct));
            
            $isFullyCompleted = ($overallPct >= 100.0) || ($fin === $tot && ($totAssess === 0 || $subAssess === $totAssess) && $tot > 0);
            
            if ($isFullyCompleted) {
                $statusKey = 'completed';
                $statusLabel = 'Completed';
                $statusBadge = 'badge-green';
                $barColor = '#10b981';
                $completedCohortCount++;
            } elseif ($overallPct > 0 || $fin > 0 || $read > 0 || $subAssess > 0) {
                $statusKey = 'in_progress';
                $statusLabel = 'In Progress';
                $statusBadge = 'badge-blue';
                $barColor = '#3b82f6';
                $inProgressCohortCount++;
            } else {
                $statusKey = 'not_started';
                $statusLabel = 'Not Started';
                $statusBadge = 'badge-gray';
                $barColor = '#cbd5e1';
                $notStartedCohortCount++;
            }
            
            $tRead = !empty($es['latest_read_at']) ? strtotime($es['latest_read_at']) : 0;
            $tSub = !empty($es['latest_submission_at']) ? strtotime($es['latest_submission_at']) : 0;
            $maxTime = max($tRead, $tSub);
            $lastActivity = $maxTime > 0 ? date('M d, Y g:i A', $maxTime) : 'No activity recorded';

            $es['total_topics_count'] = $tot;
            $es['finished_topics_count'] = $fin;
            $es['reading_topics_count'] = $read;
            $es['not_started_topics_count'] = $notStarted;
            $es['read_avg_pct'] = $readAvgPct;
            $es['total_assessments_count'] = $totAssess;
            $es['submitted_assessments_count'] = $subAssess;
            $es['graded_assessments_count'] = $gradedAssess;
            $es['assess_avg_pct'] = $assessAvgPct;
            $es['overall_pct'] = $overallPct;
            $es['status_key'] = $statusKey;
            $es['status_label'] = $statusLabel;
            $es['status_badge'] = $statusBadge;
            $es['bar_color'] = $barColor;
            $es['last_activity_formatted'] = $lastActivity;
            
            $cohortSumOverallPct += $overallPct;
            if ($readAvgPct > 0 || $fin > 0 || $read > 0) $activeReadersCount++;
        }
        unset($es);

        $cohortAvgOverallPct = $enrolledCount > 0 ? round($cohortSumOverallPct / $enrolledCount, 1) : 0;
    }
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<!-- Page Header -->
<div class="page-header" style="margin-bottom:20px">
    <div class="page-header-left">
        <h2 style="font-size:22px;font-weight:800;display:flex;align-items:center;gap:10px">
            <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:rgba(59,130,246,0.12);color:var(--primary)">
                <i class="fas fa-chart-line"></i>
            </span>
            Curriculum & Student Progress Monitor
        </h2>
        <p style="color:var(--text3);margin-top:4px">
            Administrative oversight to monitor teachers' syllabi mapping alignment and real-time student learning progression.
        </p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <a href="syllabi.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-file-alt" style="margin-right:4px"></i> Manage Syllabi
        </a>
        <a href="reports.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-chart-pie" style="margin-right:4px"></i> System Reports
        </a>
    </div>
</div>

<!-- Executive Metric Cards -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalSyllabiCount ?></div>
            <div class="stat-label">Syllabi (<?= $publishedSyllabiCount ?> Published)</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $activeTeachersCount ?></div>
            <div class="stat-label">Teachers Monitored</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalStudentsCount ?></div>
            <div class="stat-label">Enrolled Learners</div>
        </div>
    </div>
    <div class="stat-card purple" style="background:#fff;border:1px solid var(--border);border-left:4px solid #8b5cf6">
        <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="fas fa-tasks"></i></div>
        <div class="stat-info">
            <div class="stat-num" style="color:#8b5cf6"><?= $totalEnrollmentsCount ?></div>
            <div class="stat-label">Course Enrollments</div>
        </div>
    </div>
</div>

<!-- Main Navigation Tabs -->
<div class="tab-nav" style="margin-bottom:20px">
    <button type="button" class="tab-btn <?= $activeTab === 'syllabi' ? 'active' : '' ?>" onclick="switchMonitoringTab('syllabi', this)">
        <i class="fas fa-sitemap" style="margin-right:6px"></i> Teachers' Syllabi OBE & Mapping Monitor (<?= count($allSyllabi) ?>)
    </button>
    <button type="button" class="tab-btn <?= $activeTab === 'students' ? 'active' : '' ?>" onclick="switchMonitoringTab('students', this)">
        <i class="fas fa-user-graduate" style="margin-right:6px"></i> Student Learning Progress Monitor
    </button>
</div>

<!-- TAB 1: TEACHERS' SYLLABI MONITOR -->
<div class="tab-pane <?= $activeTab === 'syllabi' ? 'active' : '' ?>" id="tab-syllabi">
    <div class="card">
        <div class="card-header" style="background:#fff;border-bottom:1px solid var(--border);padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
                <strong style="font-size:15px;color:var(--text)">Teacher Curriculum Alignment & Delivery Audit</strong>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">
                    Monitor course syllabus completeness: intended learning outcomes (ILOs), weekly topics, materials, and aligned assessment tasks.
                </div>
            </div>
            <input type="text" id="teacherSyllabiSearch" class="form-control" placeholder="Search course, teacher, or code..." style="max-width:280px;font-size:12px;height:34px" onkeyup="filterTeacherSyllabiTable()">
        </div>
        <div class="table-wrap">
            <table id="teacherSyllabiTable">
                <thead>
                    <tr>
                        <th style="min-width:170px">Course</th>
                        <th style="min-width:180px">Assigned Teacher</th>
                        <th style="min-width:130px">Department</th>
                        <th style="min-width:230px">OBE Mapping Status</th>
                        <th style="min-width:100px;text-align:center">Students</th>
                        <th style="min-width:90px;text-align:center">Status</th>
                        <th style="min-width:150px;text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allSyllabi)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:32px;color:var(--text3)">No syllabi available for monitoring.</td></tr>
                    <?php else: foreach ($allSyllabi as $syl): 
                        $targetWeeks = 16;
                        $tCount = (int)$syl['topic_count'];
                        $mapPct = min(100, round(($tCount / $targetWeeks) * 100));
                        $gaugeColor = $mapPct >= 80 ? '#10b981' : ($mapPct >= 40 ? '#3b82f6' : '#f59e0b');
                        $sc = ['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange'];
                    ?>
                    <tr class="syl-monitor-row">
                        <td>
                            <div>
                                <span class="badge badge-blue" style="font-size:10px;font-weight:700"><?= htmlspecialchars($syl['course_code']) ?></span>
                                <strong class="syl-course-title" style="display:block;font-size:13px;color:var(--text);margin-top:3px"><?= htmlspecialchars($syl['course_name']) ?></strong>
                                <small style="color:var(--text3)"><?= $syl['units'] ?? 3 ?> Units &bull; <?= htmlspecialchars($syl['academic_year'] ?? '') ?> (<?= htmlspecialchars($syl['semester'] ?? '') ?> Sem)</small>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="avatar-sm" style="background:#eff6ff;color:#2563eb;font-weight:700"><?= strtoupper(substr($syl['teacher_name'], 0, 2)) ?></div>
                                <div>
                                    <strong class="syl-teacher-name" style="font-size:13px;color:var(--text)"><?= htmlspecialchars($syl['teacher_name']) ?></strong>
                                    <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars($syl['teacher_email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($syl['dept_name'] ?? 'College') ?></span>
                        </td>
                        <td>
                            <div style="margin-bottom:6px">
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;margin-bottom:3px">
                                    <span style="color:var(--text3)">Mapping: <strong><?= $tCount ?>/<?= $targetWeeks ?> Weeks</strong></span>
                                    <strong style="color:<?= $gaugeColor ?>"><?= $mapPct ?>%</strong>
                                </div>
                                <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                                    <div style="width:<?= $mapPct ?>%;height:100%;background:<?= $gaugeColor ?>;border-radius:99px"></div>
                                </div>
                            </div>
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                <span class="badge badge-gray" style="font-size:10px;padding:2px 6px" title="Intended Learning Outcomes">
                                    <i class="fas fa-bullseye" style="color:var(--primary);margin-right:2px"></i> <?= $syl['ilo_count'] ?> ILOs
                                </span>
                                <span class="badge badge-gray" style="font-size:10px;padding:2px 6px" title="Lesson Materials">
                                    <i class="fas fa-file-alt" style="color:var(--success);margin-right:2px"></i> <?= $syl['material_count'] ?> Materials
                                </span>
                                <span class="badge badge-gray" style="font-size:10px;padding:2px 6px" title="Assessment Tasks">
                                    <i class="fas fa-tasks" style="color:var(--warning);margin-right:2px"></i> <?= $syl['assessment_count'] ?> Assessments
                                </span>
                            </div>
                        </td>
                        <td style="text-align:center">
                            <span class="badge badge-purple" style="font-size:11px;padding:3px 8px;font-weight:700">
                                <i class="fas fa-users" style="margin-right:3px"></i> <?= $syl['student_count'] ?>
                            </span>
                        </td>
                        <td style="text-align:center">
                            <span class="badge <?= $sc[$syl['status']] ?? 'badge-gray' ?>" style="text-transform:capitalize;font-size:10px;font-weight:700">
                                <?= $syl['status'] ?>
                            </span>
                        </td>
                        <td style="text-align:right">
                            <div style="display:inline-flex;gap:6px">
                                <a href="syllabi_view.php?id=<?= $syl['id'] ?>" class="btn btn-secondary btn-sm" title="View Syllabus Content">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="monitoring.php?tab=students&syl=<?= $syl['id'] ?>" class="btn btn-primary btn-sm" style="font-size:11px;padding:3px 8px" title="Inspect Student Progress">
                                    <i class="fas fa-chart-bar" style="margin-right:3px"></i> Progress
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: STUDENT LEARNING PROGRESS MONITOR -->
<div class="tab-pane <?= $activeTab === 'students' ? 'active' : '' ?>" id="tab-students">
    <!-- Syllabus Selector Card -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
            <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:280px">
                <label style="font-size:13px;font-weight:700;color:var(--text);margin:0;white-space:nowrap">
                    <i class="fas fa-filter" style="color:var(--primary);margin-right:4px"></i> Select Course / Syllabus:
                </label>
                <form method="GET" style="flex:1;max-width:480px;margin:0">
                    <input type="hidden" name="tab" value="students">
                    <select name="syl" class="form-control" style="font-size:13px;height:38px" onchange="this.form.submit()">
                        <?php foreach ($allSyllabi as $sOpt): ?>
                            <option value="<?= $sOpt['id'] ?>" <?= $selectedSylId == $sOpt['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sOpt['course_code']) ?> &bull; <?= htmlspecialchars($sOpt['course_name']) ?> (Teacher: <?= htmlspecialchars($sOpt['teacher_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if ($currentSyllabus): ?>
                <div style="display:flex;gap:8px;align-items:center">
                    <a href="syllabi_view.php?id=<?= $currentSyllabus['id'] ?>" class="btn btn-secondary btn-sm">
                        <i class="fas fa-external-link-alt" style="margin-right:4px"></i> View Syllabus
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($currentSyllabus): ?>
        <!-- Cohort Summary Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:14px;margin-bottom:20px">
            <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
                <div style="font-size:26px;font-weight:800;color:var(--text);line-height:1.2"><?= count($enrolledStudents) ?></div>
                <div style="font-size:11px;color:var(--text3);font-weight:600;text-transform:uppercase;margin-top:4px">Enrolled Learners</div>
            </div></div>

            <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
                <div style="font-size:26px;font-weight:800;color:#2563eb;line-height:1.2"><?= $cohortAvgOverallPct ?>%</div>
                <div style="font-size:11px;color:#2563eb;font-weight:600;text-transform:uppercase;margin-top:4px">Cohort Avg Progress</div>
            </div></div>

            <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
                <div style="font-size:26px;font-weight:800;color:#059669;line-height:1.2"><?= $completedCohortCount ?></div>
                <div style="font-size:11px;color:#059669;font-weight:600;text-transform:uppercase;margin-top:4px">Completed Learners</div>
            </div></div>

            <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
                <div style="font-size:26px;font-weight:800;color:#f59e0b;line-height:1.2"><?= $inProgressCohortCount ?></div>
                <div style="font-size:11px;color:#f59e0b;font-weight:600;text-transform:uppercase;margin-top:4px">In Progress</div>
            </div></div>

            <div class="card"><div class="card-body" style="padding:16px 20px;text-align:center">
                <div style="font-size:26px;font-weight:800;color:#7c3aed;line-height:1.2"><?= $totalTopicsCount ?> Wks &bull; <?= $totalAssessmentsCount ?> Ass</div>
                <div style="font-size:11px;color:#7c3aed;font-weight:600;text-transform:uppercase;margin-top:4px">Course Scope</div>
            </div></div>
        </div>

        <!-- Student Progress Table Card -->
        <div class="card">
            <div class="card-body" style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
                <div>
                    <strong style="font-size:14px">Student Cohort Learning Progression Breakdown</strong>
                    <div style="font-size:12px;color:var(--text3);margin-top:2px">
                        Tracking module readings, assessment submissions, and combined syllabus completion under Teacher <?= htmlspecialchars($currentSyllabus['teacher_name']) ?>.
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <div class="status-filters" style="display:flex;gap:4px">
                        <button type="button" class="btn btn-secondary btn-sm admin-stu-filter active" onclick="filterAdminStudents('all', this)" style="font-size:11px;padding:3px 9px">All (<?= count($enrolledStudents) ?>)</button>
                        <button type="button" class="btn btn-secondary btn-sm admin-stu-filter" onclick="filterAdminStudents('completed', this)" style="font-size:11px;padding:3px 9px">Completed (<?= $completedCohortCount ?>)</button>
                        <button type="button" class="btn btn-secondary btn-sm admin-stu-filter" onclick="filterAdminStudents('in_progress', this)" style="font-size:11px;padding:3px 9px">In Progress (<?= $inProgressCohortCount ?>)</button>
                        <?php if ($notStartedCohortCount > 0): ?>
                        <button type="button" class="btn btn-secondary btn-sm admin-stu-filter" onclick="filterAdminStudents('not_started', this)" style="font-size:11px;padding:3px 9px">Not Started (<?= $notStartedCohortCount ?>)</button>
                        <?php endif; ?>
                    </div>
                    <input type="text" id="adminStudentSearch" class="form-control" placeholder="Search student name or email..." style="max-width:240px;font-size:12px;height:34px" onkeyup="searchAdminStudents()">
                </div>
            </div>

            <div class="table-wrap">
                <table id="adminStudentsTable">
                    <thead>
                        <tr>
                            <th style="min-width:200px">Student</th>
                            <th style="min-width:190px">Reading Materials</th>
                            <th style="min-width:190px">Assessments</th>
                            <th style="min-width:180px">Overall Progress</th>
                            <th style="min-width:150px">Latest Activity</th>
                            <th style="min-width:90px;text-align:center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($enrolledStudents)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--text3)">No students currently enrolled in this syllabus.</td></tr>
                        <?php else: foreach ($enrolledStudents as $es): 
                            $initials = strtoupper(substr($es['full_name'], 0, 2));
                        ?>
                        <tr class="admin-student-row" data-status="<?= $es['status_key'] ?>">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div class="avatar-sm" style="background:#e0e7ff;color:#4338ca;font-weight:700"><?= $initials ?></div>
                                    <div>
                                        <strong class="stu-name" style="font-size:13px;color:var(--text);display:block"><?= htmlspecialchars($es['full_name']) ?></strong>
                                        <small class="stu-email text-muted"><?= htmlspecialchars($es['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                    <span class="badge <?= $es['finished_topics_count'] === $es['total_topics_count'] && $es['total_topics_count'] > 0 ? 'badge-green' : ($es['finished_topics_count'] > 0 ? 'badge-blue' : 'badge-gray') ?>" style="font-size:10px;padding:2px 7px">
                                        <i class="fas <?= $es['finished_topics_count'] === $es['total_topics_count'] && $es['total_topics_count'] > 0 ? 'fa-check-double' : 'fa-book-open' ?>"></i>
                                        <?= $es['finished_topics_count'] ?> / <?= $es['total_topics_count'] ?> Topics
                                    </span>
                                    <span style="font-weight:700;color:var(--text);font-size:11px"><?= $es['read_avg_pct'] ?>%</span>
                                </div>
                                <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
                                    <div style="height:100%;width:<?= min(100, max(0, $es['read_avg_pct'])) ?>%;background:<?= $es['read_avg_pct'] >= 90 ? '#10b981' : ($es['read_avg_pct'] > 0 ? '#3b82f6' : '#cbd5e1') ?>;border-radius:99px"></div>
                                </div>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px">
                                    <span style="background:#ecfdf5;color:#065f46;padding:1px 6px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600">
                                        <i class="fas fa-check" style="font-size:8px"></i> <?= $es['finished_topics_count'] ?> Done
                                    </span>
                                    <?php if ($es['reading_topics_count'] > 0): ?>
                                    <span style="background:#eff6ff;color:#1e40af;padding:1px 6px;border-radius:4px;border:1px solid #bfdbfe;font-weight:600">
                                        <i class="fas fa-book-open" style="font-size:8px"></i> <?= $es['reading_topics_count'] ?> Reading
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($es['not_started_topics_count'] > 0): ?>
                                    <span style="background:#f1f5f9;color:#475569;padding:1px 6px;border-radius:4px;border:1px solid #cbd5e1;font-weight:600">
                                        <i class="fas fa-minus" style="font-size:8px"></i> <?= $es['not_started_topics_count'] ?> Not Started
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($es['total_assessments_count'] > 0): ?>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:11px">
                                    <span class="badge <?= $es['submitted_assessments_count'] === $es['total_assessments_count'] ? 'badge-green' : ($es['submitted_assessments_count'] > 0 ? 'badge-purple' : 'badge-gray') ?>" style="font-size:10px;padding:2px 7px">
                                        <i class="fas fa-tasks"></i> <?= $es['submitted_assessments_count'] ?> / <?= $es['total_assessments_count'] ?> Deliverables
                                    </span>
                                    <span style="font-weight:700;color:var(--text);font-size:11px"><?= $es['assess_avg_pct'] ?>%</span>
                                </div>
                                <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:6px">
                                    <div style="height:100%;width:<?= min(100, max(0, $es['assess_avg_pct'])) ?>%;background:<?= $es['assess_avg_pct'] >= 100 ? '#10b981' : ($es['assess_avg_pct'] > 0 ? '#8b5cf6' : '#cbd5e1') ?>;border-radius:99px"></div>
                                </div>
                                <div style="display:flex;gap:4px;flex-wrap:wrap;font-size:10px">
                                    <span style="background:#f5f3ff;color:#6b21a8;padding:1px 6px;border-radius:4px;border:1px solid #ddd6fe;font-weight:600">
                                        <i class="fas fa-check" style="font-size:8px"></i> <?= $es['submitted_assessments_count'] ?> Submitted
                                    </span>
                                    <span style="background:#ecfdf5;color:#065f46;padding:1px 6px;border-radius:4px;border:1px solid #a7f3d0;font-weight:600">
                                        <i class="fas fa-star" style="font-size:8px"></i> <?= $es['graded_assessments_count'] ?> Graded
                                    </span>
                                </div>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:11px"><i class="fas fa-info-circle"></i> No assessments mapped</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;font-size:12px">
                                    <strong style="color:var(--text)"><?= $es['overall_pct'] ?>%</strong>
                                    <span class="badge <?= $es['status_badge'] ?>" style="font-size:10px;padding:2px 7px"><?= $es['status_label'] ?></span>
                                </div>
                                <div style="height:7px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                                    <div style="height:100%;width:<?= min(100, max(0, $es['overall_pct'])) ?>%;background:<?= $es['bar_color'] ?>;border-radius:99px"></div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:12px;color:var(--text2);display:flex;align-items:center;gap:5px">
                                    <i class="far fa-clock" style="color:var(--text3);font-size:11px"></i>
                                    <?= $es['last_activity_formatted'] ?>
                                </div>
                            </td>
                            <td style="text-align:center">
                                <span class="badge <?= $es['status_badge'] ?>" style="font-size:10px;font-weight:700">
                                    <?= $es['status_label'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

</div></div></div>

<script>
function switchMonitoringTab(tabName, btn) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    
    var targetPane = document.getElementById('tab-' + tabName);
    if (targetPane) targetPane.classList.add('active');
    if (btn) btn.classList.add('active');

    // Update query string without full reload
    var url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

function filterTeacherSyllabiTable() {
    var query = (document.getElementById('teacherSyllabiSearch').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#teacherSyllabiTable tbody tr.syl-monitor-row');
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
}

var currentAdminFilter = 'all';

function filterAdminStudents(status, btn) {
    currentAdminFilter = status;
    document.querySelectorAll('.admin-stu-filter').forEach(function(b) {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
    });
    if (btn) {
        btn.classList.add('active');
        btn.style.background = 'var(--primary, #2563eb)';
        btn.style.color = '#fff';
    }
    searchAdminStudents();
}

function searchAdminStudents() {
    var query = (document.getElementById('adminStudentSearch').value || '').toLowerCase().trim();
    var rows = document.querySelectorAll('#adminStudentsTable tbody tr.admin-student-row');
    rows.forEach(function(row) {
        var name = (row.querySelector('.stu-name') ? row.querySelector('.stu-name').textContent : '').toLowerCase();
        var email = (row.querySelector('.stu-email') ? row.querySelector('.stu-email').textContent : '').toLowerCase();
        var rowStatus = row.getAttribute('data-status') || '';
        
        var matchesQuery = !query || name.includes(query) || email.includes(query);
        var matchesStatus = (currentAdminFilter === 'all') || (rowStatus === currentAdminFilter);
        
        row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
    });
}
</script>
</body></html>
