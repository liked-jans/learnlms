<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Progress Monitor';

$focusSylId = (int)($_GET['syl'] ?? 0);

// Ensure optional columns exist or fall back gracefully
ensureColumnExists('syllabus_topics', 'deletion_requested', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'deletion_reason', "TEXT DEFAULT NULL");

$hasDelCol = false;
$colCheck = $conn->query("SHOW COLUMNS FROM syllabus_topics LIKE 'deletion_requested'");
if ($colCheck && $colCheck->num_rows > 0) {
    $hasDelCol = true;
}

// Global Executive Statistics
$totalSyllabiCount = (int)($conn->query("SELECT COUNT(*) c FROM syllabi")->fetch_assoc()['c'] ?? 0);
$publishedSyllabiCount = (int)($conn->query("SELECT COUNT(*) c FROM syllabi WHERE status = 'published'")->fetch_assoc()['c'] ?? 0);
$activeTeachersCount = (int)($conn->query("SELECT COUNT(DISTINCT teacher_id) c FROM syllabi")->fetch_assoc()['c'] ?? 0);
$totalEnrollmentsCount = (int)($conn->query("SELECT COUNT(*) c FROM enrollments WHERE status = 'enrolled'")->fetch_assoc()['c'] ?? 0);
$totalStudentsCount = (int)($conn->query("SELECT COUNT(DISTINCT student_id) c FROM enrollments WHERE status = 'enrolled'")->fetch_assoc()['c'] ?? 0);
$totalCoursesCount = (int)($conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'] ?? 0);

// 1. Fetch all syllabi with course, teacher, dept, topics, assessments, materials, student count
$syllabiMonitorQuery = $conn->query("
    SELECT s.*, 
           c.course_code, c.course_name, c.units, c.department_id,
           u.id as teacher_id, u.full_name as teacher_name, u.email as teacher_email,
           d.id as dept_id, d.name as dept_name, d.code as dept_code,
           (SELECT COUNT(*) FROM syllabus_topics WHERE syllabus_id = s.id) as topic_count,
           (SELECT COUNT(DISTINCT ilo_code) FROM syllabus_topics WHERE syllabus_id = s.id AND ilo_code IS NOT NULL AND ilo_code != '') as ilo_count,
           (SELECT COUNT(*) FROM learning_materials WHERE syllabus_id = s.id) as material_count,
           (SELECT COUNT(*) FROM assessments WHERE syllabus_id = s.id) as assessment_count,
           (SELECT COUNT(*) FROM enrollments WHERE syllabus_id = s.id AND status = 'enrolled') as student_count
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    JOIN users u ON s.teacher_id = u.id
    LEFT JOIN departments d ON c.department_id = d.id
    ORDER BY c.course_code ASC, s.id ASC
");
$allSyllabi = $syllabiMonitorQuery ? $syllabiMonitorQuery->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all active departments for the filter dropdown
$allDepartmentsQuery = $conn->query("SELECT id, name, code FROM departments ORDER BY name ASC");
$allDepartments = $allDepartmentsQuery ? $allDepartmentsQuery->fetch_all(MYSQLI_ASSOC) : [];

$sylIds = array_column($allSyllabi, 'id');
$enrollmentsBySyllabus = [];
$topicsBySyllabus = [];
$materialsByTopic = [];
$assessmentsByTopic = [];

if (!empty($sylIds)) {
    $sylIdsList = implode(',', array_map('intval', $sylIds));

    // 2. Fetch all enrolled student details across all syllabi
    $enrollmentQuery = $conn->query("
        SELECT e.id as enrollment_id, e.syllabus_id, e.enrolled_at, e.status as enrollment_status,
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
            SELECT tp.student_id, st.syllabus_id,
                   COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage >= 90 OR tp.status = 'completed') THEN st.id END) as finished_topics,
                   COUNT(DISTINCT CASE WHEN tp.status != 'not_started' AND (tp.read_percentage > 0 OR tp.status = 'in_progress') AND tp.read_percentage < 90 AND tp.status != 'completed' THEN st.id END) as reading_topics,
                   SUM(CASE WHEN tp.status = 'not_started' THEN 0 ELSE COALESCE(tp.read_percentage, 0) END) as total_read_pct_sum,
                   MAX(tp.last_read_at) as latest_read_at
            FROM syllabus_topics st
            JOIN topic_progress tp ON tp.syllabus_topic_id = st.id
            WHERE st.syllabus_id IN ($sylIdsList)
            GROUP BY tp.student_id, st.syllabus_id
        ) tp_stats ON tp_stats.student_id = e.student_id AND tp_stats.syllabus_id = e.syllabus_id
        LEFT JOIN (
            SELECT s.student_id, a.syllabus_id,
                   COUNT(DISTINCT s.assessment_id) as submitted_assessments,
                   COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded_submissions,
                   MAX(s.submitted_at) as latest_submission_at
            FROM assessments a
            JOIN submissions s ON s.assessment_id = a.id
            WHERE a.syllabus_id IN ($sylIdsList)
            GROUP BY s.student_id, a.syllabus_id
        ) sub_stats ON sub_stats.student_id = e.student_id AND sub_stats.syllabus_id = e.syllabus_id
        WHERE e.syllabus_id IN ($sylIdsList) AND e.status = 'enrolled'
        ORDER BY e.syllabus_id ASC, u.full_name ASC
    ");
    
    if ($enrollmentQuery) {
        while ($row = $enrollmentQuery->fetch_assoc()) {
            $enrollmentsBySyllabus[$row['syllabus_id']][] = $row;
        }
    }

    // 3. Fetch all weekly topics with strictly enrolled student readers
    $delCols = $hasDelCol ? "st.deletion_requested, st.deletion_reason," : "0 as deletion_requested, '' as deletion_reason,";
    $topicsRes = $conn->query("
        SELECT st.id, st.syllabus_id, st.week_number, st.topic_title, st.topic_description,
               st.learning_outcomes, st.ilo_code, st.blooms_level, st.delivery_mode,
               $delCols
               (SELECT COUNT(*) FROM learning_materials WHERE syllabus_topic_id = st.id) as materials_count,
               (SELECT COUNT(*) FROM assessments WHERE topic_id = st.id) as assessments_count,
               (SELECT COUNT(DISTINCT tp.student_id) FROM topic_progress tp 
                JOIN enrollments e ON e.student_id = tp.student_id AND e.syllabus_id = st.syllabus_id AND e.status = 'enrolled'
                WHERE tp.syllabus_topic_id = st.id AND (tp.status = 'completed' OR tp.read_percentage >= 90)) as finished_readers,
               (SELECT COUNT(DISTINCT tp.student_id) FROM topic_progress tp 
                JOIN enrollments e ON e.student_id = tp.student_id AND e.syllabus_id = st.syllabus_id AND e.status = 'enrolled'
                WHERE tp.syllabus_topic_id = st.id AND tp.status != 'not_started' AND tp.read_percentage < 90) as active_readers
        FROM syllabus_topics st
        WHERE st.syllabus_id IN ($sylIdsList)
        ORDER BY st.syllabus_id ASC, st.week_number ASC, st.id ASC
    ");
    if ($topicsRes) {
        while ($tRow = $topicsRes->fetch_assoc()) {
            $topicsBySyllabus[$tRow['syllabus_id']][] = $tRow;
        }
    }

    // 4. Fetch learning materials grouped by topic
    $mRes = $conn->query("
        SELECT id, syllabus_id, syllabus_topic_id, title, type, file_path, external_url, delivery_mode, estimated_read_time
        FROM learning_materials
        WHERE syllabus_id IN ($sylIdsList) AND syllabus_topic_id IS NOT NULL AND syllabus_topic_id > 0
        ORDER BY id ASC
    ");
    if ($mRes) {
        while ($mRow = $mRes->fetch_assoc()) {
            $materialsByTopic[$mRow['syllabus_topic_id']][] = $mRow;
        }
    }

    // 5. Fetch assessments grouped by topic
    $aRes = $conn->query("
        SELECT id, syllabus_id, topic_id, title, type, max_score, due_date, submission_type, delivery_mode
        FROM assessments
        WHERE syllabus_id IN ($sylIdsList) AND topic_id IS NOT NULL AND topic_id > 0
        ORDER BY id ASC
    ");
    if ($aRes) {
        while ($aRow = $aRes->fetch_assoc()) {
            $assessmentsByTopic[$aRow['topic_id']][] = $aRow;
        }
    }
}

// 6. Compute metrics for each syllabus and its student cohort
$totalInstProgressSum = 0;
$totalActiveCohorts = 0;

foreach ($allSyllabi as &$syl) {
    $sId = (int)$syl['id'];
    $students = $enrollmentsBySyllabus[$sId] ?? [];
    $totTopics = (int)$syl['topic_count'];
    $totAssess = (int)$syl['assessment_count'];
    
    $completedCount = 0;
    $inProgressCount = 0;
    $notStartedCount = 0;
    $cohortSumPct = 0;
    $processedStudents = [];
    
    foreach ($students as $es) {
        $fin = (int)$es['finished_topics'];
        $reading = (int)$es['reading_topics'];
        $notStarted = max(0, $totTopics - $fin - $reading);
        
        $readAvgPct = $totTopics > 0 ? round((float)$es['total_read_pct_sum'] / $totTopics, 1) : 0.0;
        $readAvgPct = min(100.0, max(0.0, $readAvgPct));
        
        $subAssess = (int)$es['submitted_assessments'];
        $gradedAssess = (int)$es['graded_submissions'];
        $assessAvgPct = $totAssess > 0 ? round(($subAssess / $totAssess) * 100, 1) : 0.0;
        
        if ($totAssess > 0 && $totTopics > 0) {
            $overallPct = round(((float)$es['total_read_pct_sum'] + ($subAssess * 100)) / ($totTopics + $totAssess), 1);
        } elseif ($totTopics > 0) {
            $overallPct = $readAvgPct;
        } else {
            $overallPct = $assessAvgPct;
        }
        $overallPct = min(100.0, max(0.0, $overallPct));
        
        $isDone = ($overallPct >= 100.0) || ($fin === $totTopics && ($totAssess === 0 || $subAssess === $totAssess) && $totTopics > 0);
        
        if ($isDone) {
            $statusKey = 'completed';
            $statusLabel = 'Completed';
            $statusBadge = 'badge-green';
            $barColor = '#10b981';
            $completedCount++;
        } elseif ($overallPct > 0 || $fin > 0 || $reading > 0 || $subAssess > 0) {
            $statusKey = 'in_progress';
            $statusLabel = 'In Progress';
            $statusBadge = 'badge-blue';
            $barColor = '#3b82f6';
            $inProgressCount++;
        } else {
            $statusKey = 'not_started';
            $statusLabel = 'Not Started';
            $statusBadge = 'badge-gray';
            $barColor = '#cbd5e1';
            $notStartedCount++;
        }
        
        $tRead = !empty($es['latest_read_at']) ? strtotime($es['latest_read_at']) : 0;
        $tSub = !empty($es['latest_submission_at']) ? strtotime($es['latest_submission_at']) : 0;
        $maxTime = max($tRead, $tSub);
        $lastActivity = $maxTime > 0 ? date('M d, Y g:i A', $maxTime) : 'No activity';
        
        $es['finished_topics_count'] = $fin;
        $es['reading_topics_count'] = $reading;
        $es['not_started_topics_count'] = $notStarted;
        $es['read_avg_pct'] = $readAvgPct;
        $es['submitted_assessments_count'] = $subAssess;
        $es['graded_assessments_count'] = $gradedAssess;
        $es['assess_avg_pct'] = $assessAvgPct;
        $es['overall_pct'] = $overallPct;
        $es['status_key'] = $statusKey;
        $es['status_label'] = $statusLabel;
        $es['status_badge'] = $statusBadge;
        $es['bar_color'] = $barColor;
        $es['last_activity_formatted'] = $lastActivity;
        
        $cohortSumPct += $overallPct;
        $processedStudents[] = $es;
    }
    
    $studentCount = count($processedStudents);
    $avgCohortPct = $studentCount > 0 ? round($cohortSumPct / $studentCount, 1) : 0.0;
    
    if ($studentCount > 0) {
        $totalInstProgressSum += $avgCohortPct;
        $totalActiveCohorts++;
    }
    
    $syl['students'] = $processedStudents;
    $syl['weekly_topics'] = $topicsBySyllabus[$sId] ?? [];
    $syl['cohort_avg_pct'] = $avgCohortPct;
    $syl['completed_count'] = $completedCount;
    $syl['in_progress_count'] = $inProgressCount;
    $syl['not_started_count'] = $notStartedCount;
}
unset($syl);

$overallInstitutionalAvg = $totalActiveCohorts > 0 ? round($totalInstProgressSum / $totalActiveCohorts, 1) : 0.0;

// 7. Group data by Teacher: Teacher -> Courses -> (Students & Weekly Topics)
$teachersData = [];
foreach ($allSyllabi as $syl) {
    $tId = (int)$syl['teacher_id'];
    if (!isset($teachersData[$tId])) {
        $teachersData[$tId] = [
            'teacher_id' => $tId,
            'teacher_name' => $syl['teacher_name'],
            'teacher_email' => $syl['teacher_email'],
            'dept_name' => $syl['dept_name'] ?? 'College of Computing',
            'department_ids' => [],
            'courses' => [],
            'total_students_enrolled' => 0,
            'total_courses_count' => 0,
            'sum_cohort_pct' => 0,
            'completed_students_count' => 0,
            'in_progress_students_count' => 0,
            'not_started_students_count' => 0,
        ];
    }
    
    if (!empty($syl['department_id'])) {
        $teachersData[$tId]['department_ids'][] = (int)$syl['department_id'];
    }

    $teachersData[$tId]['courses'][] = $syl;
    $teachersData[$tId]['total_courses_count']++;
    $teachersData[$tId]['total_students_enrolled'] += count($syl['students']);
    $teachersData[$tId]['sum_cohort_pct'] += (float)$syl['cohort_avg_pct'];
    $teachersData[$tId]['completed_students_count'] += (int)$syl['completed_count'];
    $teachersData[$tId]['in_progress_students_count'] += (int)$syl['in_progress_count'];
    $teachersData[$tId]['not_started_students_count'] += (int)$syl['not_started_count'];
}

foreach ($teachersData as &$t) {
    $cCount = count($t['courses']);
    $t['avg_progress_pct'] = $cCount > 0 ? round($t['sum_cohort_pct'] / $cCount, 1) : 0.0;
}
unset($t);

uasort($teachersData, fn($a, $b) => strcmp($a['teacher_name'], $b['teacher_name']));
?>
<?php require_once '../includes/header.php'; ?>
<style>
.course-subtab-btn {
    transition: all 0.15s ease;
    cursor: pointer;
}
.course-subtab-btn.active {
    background: var(--primary) !important;
    color: #fff !important;
    border: none !important;
}
.course-subtab-btn.active i,
.course-subtab-btn.active .fa-calendar-alt,
.course-subtab-btn.active .fa-user-graduate {
    color: #fff !important;
}
.course-subtab-btn:not(.active) {
    background: #fff !important;
    color: var(--text) !important;
    border: 1px solid var(--border) !important;
}
.course-subtab-btn:not(.active) i.fa-calendar-alt {
    color: var(--primary) !important;
}
.course-subtab-btn:not(.active) i.fa-user-graduate {
    color: var(--text3) !important;
}
</style>
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
            Faculty-centric curriculum & progress monitoring: <strong>Teacher &rarr; Assigned Courses &rarr; Student Cohorts & Weekly Syllabi</strong>.
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
<div class="stats-grid" style="margin-bottom:24px;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr))">
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= count($teachersData) ?></div>
            <div class="stat-label">Faculty Monitored</div>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-book"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= count($allSyllabi) ?></div>
            <div class="stat-label">Active Courses (<?= $publishedSyllabiCount ?> Published)</div>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <div class="stat-num"><?= $totalStudentsCount ?></div>
            <div class="stat-label">Unique Learners (<?= $totalEnrollmentsCount ?> Enrolled)</div>
        </div>
    </div>
    <div class="stat-card purple" style="background:#fff;border:1px solid var(--border);border-left:4px solid #8b5cf6">
        <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6"><i class="fas fa-percentage"></i></div>
        <div class="stat-info">
            <div class="stat-num" style="color:#8b5cf6"><?= $overallInstitutionalAvg ?>%</div>
            <div class="stat-label">Institutional Avg Progress</div>
        </div>
    </div>
</div>

<!-- Filter & Control Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
        <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:300px;flex-wrap:wrap">
            <div style="position:relative;flex:1;min-width:220px;max-width:360px">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:13px"></i>
                <input type="text" id="teacherSearchInput" class="form-control" placeholder="Search teacher, course, topic, or student..." style="padding-left:34px;height:38px;font-size:13px" onkeyup="filterTeacherHierarchy()">
            </div>
            <div style="min-width:180px;max-width:240px">
                <select id="departmentFilterSelect" class="form-control" style="height:38px;font-size:12px;cursor:pointer" onchange="filterTeacherHierarchy()">
                    <option value="all">All Departments</option>
                    <?php foreach ($allDepartments as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['name']) ?><?= !empty($d['code']) ? ' (' . htmlspecialchars($d['code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="status-filters" style="display:flex;gap:4px;flex-wrap:wrap">
                <button type="button" class="btn btn-secondary btn-sm teacher-filter-btn active" onclick="filterByTeacherStatus('all', this)" style="font-size:11px;padding:5px 10px">All Teachers</button>
                <button type="button" class="btn btn-secondary btn-sm teacher-filter-btn" onclick="filterByTeacherStatus('high', this)" style="font-size:11px;padding:5px 10px">&ge; 60% Avg</button>
                <button type="button" class="btn btn-secondary btn-sm teacher-filter-btn" onclick="filterByTeacherStatus('has_completed', this)" style="font-size:11px;padding:5px 10px">Has Completed</button>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <button type="button" class="btn btn-secondary btn-sm" onclick="expandAllTeachersAndCourses(true)">
                <i class="fas fa-expand-arrows-alt" style="margin-right:4px"></i> Expand All
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="expandAllTeachersAndCourses(false)">
                <i class="fas fa-compress-arrows-alt" style="margin-right:4px"></i> Collapse All
            </button>
        </div>
    </div>
</div>

<!-- TEACHERS HIERARCHICAL PROGRESS MONITOR -->
<div id="teachersHierarchyContainer" style="display:flex;flex-direction:column;gap:18px">
    <?php if (empty($teachersData)): ?>
        <div class="card"><div class="card-body" style="text-align:center;padding:48px;color:var(--text3)">
            <i class="fas fa-chalkboard-teacher" style="font-size:36px;margin-bottom:12px;display:block;opacity:0.5"></i>
            No faculty or courses available for monitoring.
        </div></div>
    <?php else: foreach ($teachersData as $t): 
        $tId = $t['teacher_id'];
        $tName = $t['teacher_name'];
        $tEmail = $t['teacher_email'];
        $tCourses = $t['courses'];
        $tAvg = (float)$t['avg_progress_pct'];
        $tComp = (int)$t['completed_students_count'];
        $tInProg = (int)$t['in_progress_students_count'];
        $tNotStart = (int)$t['not_started_students_count'];
        $tInitials = strtoupper(substr($tName, 0, 2));
        
        $tColor = $tAvg >= 75 ? '#10b981' : ($tAvg >= 50 ? '#3b82f6' : ($tAvg > 0 ? '#f59e0b' : '#94a3b8'));
        $isTeacherAutoExpand = false;
        foreach ($tCourses as $tc) {
            if ((int)$tc['id'] === $focusSylId) {
                $isTeacherAutoExpand = true;
                break;
            }
        }
    ?>
    <div class="card teacher-group-card"
         id="teacher-card-<?= $tId ?>"
         data-teacher-id="<?= $tId ?>"
         data-teacher-name="<?= strtolower(htmlspecialchars($tName)) ?>"
         data-teacher-email="<?= strtolower(htmlspecialchars($tEmail)) ?>"
         data-dept-ids="<?= implode(',', array_unique(array_filter($t['department_ids'] ?? []))) ?>"
         data-avg-pct="<?= $tAvg ?>"
         data-completed-count="<?= $tComp ?>"
         style="border:1px solid var(--border);border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);overflow:hidden">
        
        <!-- LEVEL 1: TEACHER HEADER BAR -->
        <div class="card-header teacher-header-bar"
             onclick="toggleTeacherSection(<?= $tId ?>)"
             style="cursor:pointer;background:#fff;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;user-select:none;border-bottom:1px solid transparent;transition:background 0.15s">
            
            <!-- Left: Teacher Info & Dept -->
            <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:320px">
                <div class="avatar-lg" style="width:48px;height:48px;border-radius:12px;background:rgba(37,99,235,0.12);color:var(--primary);font-weight:800;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <?= $tInitials ?>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                        <span class="badge badge-purple" style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px">Instructor</span>
                        <span class="badge badge-gray" style="font-size:11px"><?= count($tCourses) ?> Course<?= count($tCourses) > 1 ? 's' : '' ?></span>
                    </div>
                    <h3 class="teacher-display-name" style="font-size:17px;font-weight:700;color:var(--text);margin:0 0 3px 0;line-height:1.2">
                        <?= htmlspecialchars($tName) ?>
                    </h3>
                    <div style="font-size:12px;color:var(--text3);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span class="teacher-display-email"><?= htmlspecialchars($tEmail) ?></span>
                        <span>&bull;</span>
                        <span><?= htmlspecialchars($t['dept_name']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Center: Teacher Cohort Overall Progress & Badges -->
            <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                <div style="min-width:180px;text-align:right">
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px">
                        <span style="font-size:11px;font-weight:600;color:var(--text3);text-transform:uppercase">Faculty Cohort Avg</span>
                        <span style="font-size:18px;font-weight:800;color:<?= $tColor ?>"><?= $tAvg ?>%</span>
                    </div>
                    <div style="height:8px;background:#e2e8f0;border-radius:99px;overflow:hidden;width:180px">
                        <div style="height:100%;width:<?= $tAvg ?>%;background:<?= $tColor ?>;border-radius:99px"></div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:4px;min-width:130px">
                    <div style="display:flex;gap:4px">
                        <span class="badge badge-green" style="font-size:10px;padding:3px 7px" title="Learners who completed all modules & assessments">
                            <i class="fas fa-check" style="margin-right:3px"></i> <?= $tComp ?> Done
                        </span>
                        <span class="badge badge-blue" style="font-size:10px;padding:3px 7px" title="Learners actively reading or submitting">
                            <i class="fas fa-spinner fa-spin" style="margin-right:3px;font-size:9px"></i> <?= $tInProg ?> Active
                        </span>
                    </div>
                    <?php if ($tNotStart > 0): ?>
                    <div>
                        <span class="badge badge-gray" style="font-size:10px;padding:2px 7px">
                            <i class="fas fa-minus" style="margin-right:3px"></i> <?= $tNotStart ?> Not Started
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Expand Teacher Button -->
            <div style="display:flex;align-items:center;gap:8px" onclick="event.stopPropagation()">
                <button type="button" class="btn btn-secondary btn-sm teacher-toggle-btn"
                        id="btn-teacher-<?= $tId ?>"
                        onclick="toggleTeacherSection(<?= $tId ?>)"
                        style="font-size:12px;padding:5px 12px;display:inline-flex;align-items:center;gap:6px">
                    <i class="fas fa-book-open"></i>
                    <span><?= count($tCourses) ?> Course<?= count($tCourses) > 1 ? 's' : '' ?></span>
                    <i class="fas fa-chevron-down teacher-chevron" id="chevron-teacher-<?= $tId ?>" style="transition:transform 0.2s"></i>
                </button>
            </div>
        </div>

        <!-- LEVEL 2: COURSES LIST UNDER THIS TEACHER -->
        <div class="teacher-courses-body" 
             id="teacher-courses-<?= $tId ?>"
             style="display:<?= $isTeacherAutoExpand ? 'block' : 'none' ?>;background:#f8fafc;padding:16px 20px;border-top:1px solid var(--border)">
            
            <div style="display:flex;flex-direction:column;gap:14px">
                <?php foreach ($tCourses as $syl): 
                    $sId = (int)$syl['id'];
                    $stus = $syl['students'];
                    $weeklyTopics = $syl['weekly_topics'];
                    $stuCount = count($stus);
                    $topicCount = count($weeklyTopics);
                    $cAvg = (float)$syl['cohort_avg_pct'];
                    $cComp = (int)$syl['completed_count'];
                    $cInProg = (int)$syl['in_progress_count'];
                    $cNotStart = (int)$syl['not_started_count'];
                    $courseColor = $cAvg >= 75 ? '#10b981' : ($cAvg >= 50 ? '#3b82f6' : ($cAvg > 0 ? '#f59e0b' : '#94a3b8'));
                    $isCourseAutoExpand = ($focusSylId === $sId);
                ?>
                <div class="course-subcard"
                     id="course-subcard-<?= $sId ?>"
                     data-syllabus-id="<?= $sId ?>"
                     data-course-code="<?= strtolower(htmlspecialchars($syl['course_code'])) ?>"
                     data-course-name="<?= strtolower(htmlspecialchars($syl['course_name'])) ?>"
                     data-dept-id="<?= (int)($syl['department_id'] ?? 0) ?>"
                     data-avg-pct="<?= $cAvg ?>"
                     data-completed-count="<?= $cComp ?>"
                     style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 1px 2px rgba(0,0,0,0.03);overflow:hidden">
                    
                    <!-- COURSE HEADER ROW -->
                    <div class="course-subcard-header"
                         onclick="toggleCourseDrawer(<?= $sId ?>, 'students')"
                         style="cursor:pointer;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;user-select:none;transition:background 0.15s">
                        
                        <!-- Course Basic Info with 16-week mapping badge -->
                        <div style="display:flex;align-items:center;gap:12px;flex:1;min-width:280px">
                            <span class="badge badge-blue" style="font-size:11px;font-weight:700;letter-spacing:0.5px"><?= htmlspecialchars($syl['course_code']) ?></span>
                            <div>
                                <h4 class="course-sub-title" style="font-size:15px;font-weight:700;color:var(--text);margin:0 0 3px 0;line-height:1.2">
                                    <?= htmlspecialchars($syl['course_name']) ?>
                                </h4>
                                <div style="font-size:11px;color:var(--text3);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span><?= $syl['units'] ?? 3 ?> Units</span>
                                    <span>&bull;</span>
                                    <span class="badge badge-gray" style="font-size:10px;padding:1px 6px" title="OBE 16-Week Target Mapping">
                                        <i class="fas fa-calendar-alt" style="margin-right:2px"></i> <?= $topicCount ?>/16 Weeks Mapped
                                    </span>
                                    <span>&bull;</span>
                                    <span><i class="fas fa-tasks" style="margin-right:2px"></i> <?= $syl['assessment_count'] ?> Assessments</span>
                                    <span>&bull;</span>
                                    <span class="badge <?= $syl['status'] === 'published' ? 'badge-green' : 'badge-orange' ?>" style="font-size:9px;padding:1px 5px">
                                        <?= $syl['status'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Course Cohort Progress Summary -->
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                            <div style="min-width:150px;text-align:right">
                                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:3px">
                                    <span style="font-size:10px;font-weight:600;color:var(--text3);text-transform:uppercase">Course Avg</span>
                                    <span style="font-size:16px;font-weight:800;color:<?= $courseColor ?>"><?= $cAvg ?>%</span>
                                </div>
                                <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;width:150px">
                                    <div style="height:100%;width:<?= $cAvg ?>%;background:<?= $courseColor ?>;border-radius:99px"></div>
                                </div>
                            </div>

                            <div style="display:flex;gap:4px">
                                <span class="badge badge-green" style="font-size:10px;padding:2px 6px">
                                    <i class="fas fa-check" style="font-size:8px"></i> <?= $cComp ?> Done
                                </span>
                                <span class="badge badge-blue" style="font-size:10px;padding:2px 6px">
                                    <i class="fas fa-spinner" style="font-size:8px"></i> <?= $cInProg ?> Active
                                </span>
                                <?php if ($cNotStart > 0): ?>
                                <span class="badge badge-gray" style="font-size:10px;padding:2px 6px">
                                    <?= $cNotStart ?> Left
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Actions & Toggle Buttons -->
                        <div style="display:flex;align-items:center;gap:6px" onclick="event.stopPropagation()">
                            <button type="button" class="btn btn-secondary btn-sm"
                                    id="btn-toggle-topics-<?= $sId ?>"
                                    onclick="toggleCourseDrawer(<?= $sId ?>, 'topics')"
                                    title="View Weekly Syllabi Topics & ILOs"
                                    style="font-size:11px;padding:4px 9px;display:inline-flex;align-items:center;gap:5px">
                                <i class="fas fa-calendar-alt" style="color:var(--primary)"></i>
                                <span><?= $topicCount ?> Weeks</span>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm"
                                    id="btn-toggle-students-<?= $sId ?>"
                                    onclick="toggleCourseDrawer(<?= $sId ?>, 'students')"
                                    style="font-size:11px;padding:4px 10px;display:inline-flex;align-items:center;gap:5px">
                                <i class="fas fa-user-graduate"></i>
                                <span><?= $stuCount ?> Students</span>
                                <i class="fas fa-chevron-down course-chevron" id="chevron-course-<?= $sId ?>" style="transition:transform 0.2s"></i>
                            </button>
                            <a href="syllabi_view.php?id=<?= $sId ?>" class="btn btn-secondary btn-sm" title="View Full Syllabus Detail" style="font-size:11px;padding:4px 8px">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>

                    <!-- LEVEL 3: DUAL-TAB DRAWER (ENROLLED STUDENTS OR WEEKLY SYLLABI) -->
                    <div class="course-students-drawer"
                         id="drawer-course-<?= $sId ?>"
                         style="display:<?= $isCourseAutoExpand ? 'block' : 'none' ?>;border-top:1px solid #edf2f7;background:#fafbfc">
                        
                        <!-- Sub-Nav Pill Tabs Inside Course Drawer -->
                        <div style="padding:10px 16px;background:#f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;border-bottom:1px solid #e2e8f0">
                            <div style="display:flex;gap:6px">
                                <button type="button" 
                                        class="btn btn-sm course-subtab-btn active" 
                                        id="subtab-btn-students-<?= $sId ?>"
                                        onclick="switchCourseInnerTab(<?= $sId ?>, 'students')"
                                        style="font-size:11px;padding:3px 10px;border-radius:6px">
                                    <i class="fas fa-user-graduate" style="margin-right:4px"></i> Enrolled Students (<?= $stuCount ?>)
                                </button>
                                <button type="button" 
                                        class="btn btn-sm course-subtab-btn" 
                                        id="subtab-btn-topics-<?= $sId ?>"
                                        onclick="switchCourseInnerTab(<?= $sId ?>, 'topics')"
                                        style="font-size:11px;padding:3px 10px;border-radius:6px">
                                    <i class="fas fa-calendar-alt" style="margin-right:4px"></i> Weekly Syllabi & ILOs (<?= $topicCount ?> Weeks)
                                </button>
                            </div>
                            <div style="font-size:11px;color:var(--text3)">
                                Course: <strong style="color:var(--text)"><?= htmlspecialchars($syl['course_code']) ?></strong> &bull; Teacher: <strong style="color:var(--text)"><?= htmlspecialchars($syl['teacher_name']) ?></strong>
                            </div>
                        </div>

                        <!-- PANE 1: ENROLLED STUDENTS TABLE -->
                        <div class="course-inner-pane" id="pane-students-<?= $sId ?>" style="display:block">
                            <div class="table-wrap" style="margin:0;border:none;border-radius:0">
                                <table style="margin:0;border-collapse:collapse;background:#fff;width:100%">
                                    <thead>
                                        <tr style="background:#f8fafc">
                                            <th style="min-width:210px;font-size:11px;text-transform:uppercase;color:var(--text3);padding:10px 16px">Student</th>
                                            <th style="min-width:190px;font-size:11px;text-transform:uppercase;color:var(--text3);padding:10px 16px">Reading Materials</th>
                                            <th style="min-width:190px;font-size:11px;text-transform:uppercase;color:var(--text3);padding:10px 16px">Assessment Deliverables</th>
                                            <th style="min-width:170px;font-size:11px;text-transform:uppercase;color:var(--text3);padding:10px 16px">Overall Progress</th>
                                            <th style="min-width:150px;font-size:11px;text-transform:uppercase;color:var(--text3);padding:10px 16px">Latest Activity</th>
                                            <th style="min-width:90px;font-size:11px;text-transform:uppercase;color:var(--text3);text-align:center;padding:10px 16px">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($stus)): ?>
                                            <tr>
                                                <td colspan="6" style="text-align:center;padding:20px;color:var(--text3);font-size:12px">
                                                    No students enrolled in this course syllabus.
                                                </td>
                                            </tr>
                                        <?php else: foreach ($stus as $stu): 
                                            $stuInitials = strtoupper(substr($stu['full_name'], 0, 2));
                                            $totT = (int)$syl['topic_count'];
                                            $totA = (int)$syl['assessment_count'];
                                        ?>
                                        <tr class="student-progress-row"
                                            data-student-name="<?= strtolower(htmlspecialchars($stu['full_name'])) ?>"
                                            data-student-email="<?= strtolower(htmlspecialchars($stu['email'])) ?>"
                                            data-student-status="<?= $stu['status_key'] ?>"
                                            style="border-bottom:1px solid #f1f5f9">
                                            
                                            <!-- Student Info -->
                                            <td style="padding:10px 16px">
                                                <div style="display:flex;align-items:center;gap:10px">
                                                    <div class="avatar-sm" style="background:#e0e7ff;color:#4338ca;font-weight:700;font-size:11px;width:32px;height:32px">
                                                        <?= $stuInitials ?>
                                                    </div>
                                                    <div>
                                                        <strong class="stu-full-name" style="font-size:12px;color:var(--text);display:block;line-height:1.2">
                                                            <?= htmlspecialchars($stu['full_name']) ?>
                                                        </strong>
                                                        <small class="stu-email-text" style="color:var(--text3);font-size:11px"><?= htmlspecialchars($stu['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Reading Materials Progress -->
                                            <td style="padding:10px 16px">
                                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;font-size:11px">
                                                    <span class="badge <?= $stu['finished_topics_count'] === $totT && $totT > 0 ? 'badge-green' : ($stu['finished_topics_count'] > 0 ? 'badge-blue' : 'badge-gray') ?>" style="font-size:9px;padding:2px 6px">
                                                        <i class="fas <?= $stu['finished_topics_count'] === $totT && $totT > 0 ? 'fa-check-double' : 'fa-book-open' ?>"></i>
                                                        <?= $stu['finished_topics_count'] ?> / <?= $totT ?> Topics
                                                    </span>
                                                    <span style="font-weight:700;color:var(--text);font-size:11px"><?= $stu['read_avg_pct'] ?>%</span>
                                                </div>
                                                <div style="height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:5px">
                                                    <div style="height:100%;width:<?= min(100, max(0, $stu['read_avg_pct'])) ?>%;background:<?= $stu['read_avg_pct'] >= 90 ? '#10b981' : ($stu['read_avg_pct'] > 0 ? '#3b82f6' : '#cbd5e1') ?>;border-radius:99px"></div>
                                                </div>
                                                <div style="display:flex;gap:3px;flex-wrap:wrap;font-size:9px">
                                                    <span style="background:#ecfdf5;color:#065f46;padding:1px 5px;border-radius:3px;font-weight:600">
                                                        <?= $stu['finished_topics_count'] ?> Done
                                                    </span>
                                                    <?php if ($stu['reading_topics_count'] > 0): ?>
                                                    <span style="background:#eff6ff;color:#1e40af;padding:1px 5px;border-radius:3px;font-weight:600">
                                                        <?= $stu['reading_topics_count'] ?> Reading
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- Assessment Deliverables Progress -->
                                            <td style="padding:10px 16px">
                                                <?php if ($totA > 0): ?>
                                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;font-size:11px">
                                                    <span class="badge <?= $stu['submitted_assessments_count'] === $totA ? 'badge-green' : ($stu['submitted_assessments_count'] > 0 ? 'badge-purple' : 'badge-gray') ?>" style="font-size:9px;padding:2px 6px">
                                                        <i class="fas fa-tasks"></i> <?= $stu['submitted_assessments_count'] ?> / <?= $totA ?> Deliverables
                                                    </span>
                                                    <span style="font-weight:700;color:var(--text);font-size:11px"><?= $stu['assess_avg_pct'] ?>%</span>
                                                </div>
                                                <div style="height:5px;background:#e2e8f0;border-radius:99px;overflow:hidden;margin-bottom:5px">
                                                    <div style="height:100%;width:<?= min(100, max(0, $stu['assess_avg_pct'])) ?>%;background:<?= $stu['assess_avg_pct'] >= 100 ? '#10b981' : ($stu['assess_avg_pct'] > 0 ? '#8b5cf6' : '#cbd5e1') ?>;border-radius:99px"></div>
                                                </div>
                                                <div style="display:flex;gap:3px;flex-wrap:wrap;font-size:9px">
                                                    <span style="background:#f5f3ff;color:#6b21a8;padding:1px 5px;border-radius:3px;font-weight:600">
                                                        <?= $stu['submitted_assessments_count'] ?> Submitted
                                                    </span>
                                                    <span style="background:#ecfdf5;color:#065f46;padding:1px 5px;border-radius:3px;font-weight:600">
                                                        <?= $stu['graded_assessments_count'] ?> Graded
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                            <span class="text-muted" style="font-size:11px"><i class="fas fa-info-circle"></i> No assessments</span>
                                            <?php endif; ?>
                                            </td>

                                            <!-- Overall Course Progress -->
                                            <td style="padding:10px 16px">
                                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;font-size:11px">
                                                    <strong style="color:var(--text);font-size:12px"><?= $stu['overall_pct'] ?>%</strong>
                                                    <span class="badge <?= $stu['status_badge'] ?>" style="font-size:9px;padding:1px 5px"><?= $stu['status_label'] ?></span>
                                                </div>
                                                <div style="height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                                                    <div style="height:100%;width:<?= min(100, max(0, $stu['overall_pct'])) ?>%;background:<?= $stu['bar_color'] ?>;border-radius:99px"></div>
                                                </div>
                                            </td>

                                            <!-- Latest Activity -->
                                            <td style="padding:10px 16px">
                                                <div style="font-size:11px;color:var(--text2);display:flex;align-items:center;gap:4px">
                                                    <i class="far fa-clock" style="color:var(--text3);font-size:10px"></i>
                                                    <?= $stu['last_activity_formatted'] ?>
                                                </div>
                                            </td>

                                            <!-- Status Badge -->
                                            <td style="text-align:center;padding:10px 16px">
                                                <span class="badge <?= $stu['status_badge'] ?>" style="font-size:10px;font-weight:700">
                                                    <?= $stu['status_label'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PANE 2: WEEKLY SYLLABI ROADMAP & AUDIT WITH EXPANDABLE MATERIALS & ASSESSMENTS -->
                        <div class="course-inner-pane" id="pane-topics-<?= $sId ?>" style="display:none;padding:16px 20px;background:#fff">
                            <?php if (empty($weeklyTopics)): ?>
                                <div style="text-align:center;padding:32px;color:var(--text3)">
                                    <i class="fas fa-calendar-times" style="font-size:28px;margin-bottom:8px;display:block;opacity:0.5"></i>
                                    No weekly topics mapped for this course syllabus yet.
                                </div>
                            <?php else: ?>
                                <div style="display:flex;flex-direction:column;gap:12px">
                                    <?php foreach ($weeklyTopics as $wt): 
                                        $wtId = (int)$wt['id'];
                                        $finReaders = (int)$wt['finished_readers'];
                                        $actReaders = (int)$wt['active_readers'];
                                        $readCohortPct = $stuCount > 0 ? min(100.0, round(($finReaders / $stuCount) * 100, 1)) : 0.0;
                                        $hasDelReq = !empty($wt['deletion_requested']);
                                        
                                        $wtMaterials = $materialsByTopic[$wtId] ?? [];
                                        $wtAssessments = $assessmentsByTopic[$wtId] ?? [];
                                        $mCount = count($wtMaterials);
                                        $aCount = count($wtAssessments);
                                    ?>
                                    <div class="weekly-topic-card" 
                                         id="weekly-topic-card-<?= $wtId ?>"
                                         data-topic-title="<?= strtolower(htmlspecialchars($wt['topic_title'])) ?>"
                                         style="border:1px solid <?= $hasDelReq ? '#fcd34d' : '#e2e8f0' ?>;border-radius:8px;padding:14px 16px;background:<?= $hasDelReq ? '#fffbeb' : '#fff' ?>">
                                        
                                        <!-- Top Row: Week Number, Title, ILO, Delivery & Deletion Warning -->
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:6px">
                                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                                <span class="badge badge-purple" style="font-size:11px;font-weight:700">
                                                    Week <?= (int)$wt['week_number'] ?>
                                                </span>
                                                <strong style="font-size:14px;color:var(--text)">
                                                    <?= htmlspecialchars($wt['topic_title']) ?>
                                                </strong>
                                                <?php if (!empty($wt['ilo_code'])): ?>
                                                <span class="badge badge-blue" style="font-size:10px" title="Intended Learning Outcome Code">
                                                    <i class="fas fa-bullseye" style="margin-right:2px"></i> <?= htmlspecialchars($wt['ilo_code']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if (!empty($wt['blooms_level'])): ?>
                                                <span class="badge badge-gray" style="font-size:10px;text-transform:capitalize">
                                                    <?= htmlspecialchars($wt['blooms_level']) ?>
                                                </span>
                                                <?php endif; ?>
                                                <?php if (!empty($wt['delivery_mode'])): ?>
                                                <span class="badge badge-gray" style="font-size:10px;text-transform:capitalize">
                                                    <i class="fas fa-chalkboard" style="margin-right:2px"></i> <?= htmlspecialchars($wt['delivery_mode']) ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Student Reading Depth For This Week (Accurately Capped at 100%) -->
                                            <div style="display:flex;align-items:center;gap:10px;font-size:11px">
                                                <span style="color:var(--text3)">Student Completion:</span>
                                                <div style="width:90px;height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                                                    <div style="height:100%;width:<?= $readCohortPct ?>%;background:#10b981;border-radius:99px"></div>
                                                </div>
                                                <strong style="color:<?= $readCohortPct >= 80 ? '#10b981' : ($readCohortPct >= 40 ? '#3b82f6' : '#f59e0b') ?>">
                                                    <?= $finReaders ?>/<?= $stuCount ?> (<?= $readCohortPct ?>%)
                                                </strong>
                                            </div>
                                        </div>

                                        <!-- Description / Learning Outcomes -->
                                        <?php if (!empty($wt['topic_description']) || !empty($wt['learning_outcomes'])): ?>
                                        <div style="font-size:12px;color:var(--text2);margin-bottom:8px;line-height:1.4">
                                            <?= htmlspecialchars($wt['topic_description'] ?: $wt['learning_outcomes']) ?>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Materials & Assessments Expandable Trigger Buttons -->
                                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding-top:8px;border-top:1px dashed #edf2f7;font-size:11px">
                                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                                                <!-- Materials Button -->
                                                <?php if ($mCount > 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm week-pill-btn" 
                                                        id="btn-mat-<?= $wtId ?>"
                                                        onclick="toggleWeekDetail(<?= $wtId ?>, 'materials')"
                                                        style="font-size:11px;padding:3px 10px;border-radius:20px;border:1px solid #a7f3d0;background:#ecfdf5;color:#065f46;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s">
                                                    <i class="fas fa-file-alt" style="color:#059669"></i>
                                                    <strong><?= $mCount ?></strong> Lesson Material<?= $mCount != 1 ? 's' : '' ?>
                                                    <i class="fas fa-chevron-down week-chevron-mat-<?= $wtId ?>" style="font-size:9px;margin-left:2px;transition:transform 0.2s"></i>
                                                </button>
                                                <?php else: ?>
                                                <span style="color:var(--text3);font-size:11px;display:inline-flex;align-items:center;gap:4px">
                                                    <i class="fas fa-file-alt" style="color:#94a3b8"></i> 0 Lesson Materials
                                                </span>
                                                <?php endif; ?>

                                                <!-- Assessments Button -->
                                                <?php if ($aCount > 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm week-pill-btn" 
                                                        id="btn-ass-<?= $wtId ?>"
                                                        onclick="toggleWeekDetail(<?= $wtId ?>, 'assessments')"
                                                        style="font-size:11px;padding:3px 10px;border-radius:20px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s">
                                                    <i class="fas fa-tasks" style="color:#d97706"></i>
                                                    <strong><?= $aCount ?></strong> Assessment Task<?= $aCount != 1 ? 's' : '' ?>
                                                    <i class="fas fa-chevron-down week-chevron-ass-<?= $wtId ?>" style="font-size:9px;margin-left:2px;transition:transform 0.2s"></i>
                                                </button>
                                                <?php else: ?>
                                                <span style="color:var(--text3);font-size:11px;display:inline-flex;align-items:center;gap:4px">
                                                    <i class="fas fa-tasks" style="color:#94a3b8"></i> 0 Assessment Tasks
                                                </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Deletion Request Notice -->
                                            <?php if ($hasDelReq): ?>
                                            <div style="display:inline-flex;align-items:center;gap:8px;background:#fef3c7;padding:3px 10px;border-radius:6px;border:1px solid #fde68a">
                                                <span style="color:#b45309;font-weight:700;font-size:11px">
                                                    <i class="fas fa-exclamation-triangle" style="margin-right:3px"></i> Deletion Requested: "<?= htmlspecialchars($wt['deletion_reason'] ?: 'No reason specified') ?>"
                                                </span>
                                                <a href="syllabi_view.php?id=<?= $sId ?>" class="btn btn-danger btn-sm" style="font-size:10px;padding:2px 6px">
                                                    Review in Syllabus
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- EXPANDABLE DRAWER FOR MATERIALS AND ASSESSMENTS -->
                                        <div class="week-detail-panel" 
                                             id="week-detail-panel-<?= $wtId ?>" 
                                             style="display:none;margin-top:10px;padding:12px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0">
                                            
                                            <!-- MATERIALS CONTAINER -->
                                            <div class="week-mat-box" id="week-mat-box-<?= $wtId ?>" style="display:none">
                                                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:8px;display:flex;align-items:center;gap:6px">
                                                    <i class="fas fa-file-alt" style="color:#059669"></i> Lesson Materials Uploaded for Week <?= (int)$wt['week_number'] ?> (<?= $mCount ?>)
                                                </div>
                                                <div style="display:flex;flex-direction:column;gap:6px">
                                                    <?php foreach ($wtMaterials as $m): 
                                                        $typeIcon = match($m['type'] ?? '') {
                                                            'presentation' => 'fa-file-powerpoint',
                                                            'document' => 'fa-file-word',
                                                            'link' => 'fa-link',
                                                            'video' => 'fa-video',
                                                            'module' => 'fa-book-open',
                                                            default => 'fa-file-alt'
                                                        };
                                                        $typeColor = match($m['type'] ?? '') {
                                                            'presentation' => '#ea580c',
                                                            'document' => '#2563eb',
                                                            'link' => '#0891b2',
                                                            'video' => '#dc2626',
                                                            'module' => '#059669',
                                                            default => '#475569'
                                                        };
                                                    ?>
                                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;gap:10px">
                                                        <div style="display:flex;align-items:center;gap:10px">
                                                            <span style="color:<?= $typeColor ?>;font-size:16px"><i class="fas <?= $typeIcon ?>"></i></span>
                                                            <div>
                                                                <strong style="font-size:12px;color:var(--text);display:block;line-height:1.3">
                                                                    <?= htmlspecialchars($m['title']) ?>
                                                                </strong>
                                                                <div style="font-size:10px;color:var(--text3);display:flex;align-items:center;gap:6px;margin-top:2px">
                                                                    <span class="badge badge-gray" style="text-transform:uppercase;font-size:9px;padding:1px 5px"><?= htmlspecialchars($m['type']) ?></span>
                                                                    <?php if (!empty($m['estimated_read_time'])): ?>
                                                                    <span>&bull; <?= (int)$m['estimated_read_time'] ?> mins read</span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($m['delivery_mode'])): ?>
                                                                    <span>&bull; Mode: <?= htmlspecialchars($m['delivery_mode']) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div style="display:flex;gap:6px;flex-shrink:0;align-items:center">
                                                            <?php if (!empty($m['external_url'])): ?>
                                                            <a href="<?= htmlspecialchars($m['external_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="font-size:10px;padding:3px 8px" title="Open external link in new tab">
                                                                <i class="fas fa-external-link-alt" style="margin-right:3px"></i> Open Link
                                                            </a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($m['file_path'])): ?>
                                                            <a href="<?= BASE_URL ?>uploads/materials/<?= htmlspecialchars($m['file_path']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="font-size:10px;padding:3px 8px" title="Download or view raw file in new tab">
                                                                <i class="fas fa-download" style="margin-right:3px"></i> View File
                                                            </a>
                                                            <?php endif; ?>
                                                            <?php if ($m['type'] === 'module' || (empty($m['external_url']) && empty($m['file_path']))): ?>
                                                            <a href="<?= BASE_URL ?>student/read_material.php?id=<?= (int)$m['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-sm" style="font-size:10px;padding:3px 8px;background:#059669;color:#fff;border-color:#059669;display:inline-flex;align-items:center;gap:4px" title="Read interactive module in new tab">
                                                                <i class="fas fa-book-open"></i> View Module
                                                            </a>
                                                            <?php else: ?>
                                                            <a href="<?= BASE_URL ?>student/read_material.php?id=<?= (int)$m['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="font-size:10px;padding:3px 8px;display:inline-flex;align-items:center;gap:4px" title="Preview in reader in new tab">
                                                                <i class="fas fa-eye"></i> Preview
                                                            </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <!-- ASSESSMENTS CONTAINER -->
                                            <div class="week-ass-box" id="week-ass-box-<?= $wtId ?>" style="display:none">
                                                <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:8px;display:flex;align-items:center;gap:6px">
                                                    <i class="fas fa-tasks" style="color:#d97706"></i> Assessment Tasks Assigned for Week <?= (int)$wt['week_number'] ?> (<?= $aCount ?>)
                                                </div>
                                                <div style="display:flex;flex-direction:column;gap:6px">
                                                    <?php foreach ($wtAssessments as $a): 
                                                        $assTypeIcon = match($a['type'] ?? '') {
                                                            'quiz' => 'fa-question-circle',
                                                            'exam' => 'fa-graduation-cap',
                                                            'activity' => 'fa-pencil-ruler',
                                                            'project' => 'fa-project-diagram',
                                                            default => 'fa-tasks'
                                                        };
                                                        $assBadgeClass = match($a['type'] ?? '') {
                                                            'quiz' => 'badge-blue',
                                                            'exam' => 'badge-purple',
                                                            'activity' => 'badge-green',
                                                            'project' => 'badge-orange',
                                                            default => 'badge-gray'
                                                        };
                                                    ?>
                                                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:6px;gap:10px">
                                                        <div style="display:flex;align-items:center;gap:10px">
                                                            <span style="color:var(--primary);font-size:16px"><i class="fas <?= $assTypeIcon ?>"></i></span>
                                                            <div>
                                                                <strong style="font-size:12px;color:var(--text);display:block;line-height:1.3">
                                                                    <?= htmlspecialchars($a['title']) ?>
                                                                </strong>
                                                                <div style="font-size:10px;color:var(--text3);display:flex;align-items:center;gap:6px;margin-top:2px">
                                                                    <span class="badge <?= $assBadgeClass ?>" style="text-transform:uppercase;font-size:9px;padding:1px 5px"><?= htmlspecialchars($a['type']) ?></span>
                                                                    <span>&bull; Max Score: <strong><?= rtrim(rtrim($a['max_score'], '0'), '.') ?> pts</strong></span>
                                                                    <?php if (!empty($a['submission_type'])): ?>
                                                                    <span>&bull; Type: <?= htmlspecialchars(str_replace('_', ' ', $a['submission_type'])) ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($a['due_date'])): ?>
                                                                    <span>&bull; Due: <?= date('M d, Y', strtotime($a['due_date'])) ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div style="flex-shrink:0">
                                                            <a href="<?= BASE_URL ?>teacher/assessment_questions.php?id=<?= (int)$a['id'] ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="font-size:10px;padding:3px 8px;display:inline-flex;align-items:center;gap:4px" title="View assessment questions and details in new tab">
                                                                <i class="fas fa-tasks"></i> View Assessment
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

</div></div></div>

<script>
function toggleTeacherSection(teacherId) {
    var body = document.getElementById('teacher-courses-' + teacherId);
    var chevron = document.getElementById('chevron-teacher-' + teacherId);
    if (!body) return;

    if (body.style.display === 'none' || body.style.display === '') {
        body.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    } else {
        body.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }
}

function toggleCourseDrawer(sylId, defaultTab) {
    var drawer = document.getElementById('drawer-course-' + sylId);
    var chevron = document.getElementById('chevron-course-' + sylId);
    if (!drawer) return;

    var isCurrentlyOpen = (drawer.style.display === 'block');
    
    // If opening or switching tab while already open:
    if (!isCurrentlyOpen) {
        drawer.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        switchCourseInnerTab(sylId, defaultTab || 'students');
    } else {
        // If clicking the other tab button while open, switch to that tab without closing
        var currentPane = document.getElementById('pane-' + defaultTab + '-' + sylId);
        if (currentPane && currentPane.style.display === 'none') {
            switchCourseInnerTab(sylId, defaultTab);
        } else {
            // Toggle closed
            drawer.style.display = 'none';
            if (chevron) chevron.style.transform = 'rotate(0deg)';
        }
    }
}

function switchCourseInnerTab(sylId, tabType) {
    var paneStudents = document.getElementById('pane-students-' + sylId);
    var paneTopics = document.getElementById('pane-topics-' + sylId);
    var btnStudents = document.getElementById('subtab-btn-students-' + sylId);
    var btnTopics = document.getElementById('subtab-btn-topics-' + sylId);

    if (tabType === 'students') {
        if (paneStudents) paneStudents.style.display = 'block';
        if (paneTopics) paneTopics.style.display = 'none';
        if (btnStudents) {
            btnStudents.classList.add('active');
            btnStudents.style.background = 'var(--primary)';
            btnStudents.style.color = '#fff';
            btnStudents.style.border = 'none';
            var icS = btnStudents.querySelector('i');
            if (icS) icS.style.color = '#fff';
        }
        if (btnTopics) {
            btnTopics.classList.remove('active');
            btnTopics.style.background = '#fff';
            btnTopics.style.color = 'var(--text)';
            btnTopics.style.border = '1px solid var(--border)';
            var icT = btnTopics.querySelector('i');
            if (icT) icT.style.color = 'var(--primary)';
        }
    } else {
        if (paneStudents) paneStudents.style.display = 'none';
        if (paneTopics) paneTopics.style.display = 'block';
        if (btnTopics) {
            btnTopics.classList.add('active');
            btnTopics.style.background = 'var(--primary)';
            btnTopics.style.color = '#fff';
            btnTopics.style.border = 'none';
            var icT = btnTopics.querySelector('i');
            if (icT) icT.style.color = '#fff';
        }
        if (btnStudents) {
            btnStudents.classList.remove('active');
            btnStudents.style.background = '#fff';
            btnStudents.style.color = 'var(--text)';
            btnStudents.style.border = '1px solid var(--border)';
            var icS = btnStudents.querySelector('i');
            if (icS) icS.style.color = 'var(--text3)';
        }
    }
}

function toggleWeekDetail(topicId, type) {
    var panel = document.getElementById('week-detail-panel-' + topicId);
    var matBox = document.getElementById('week-mat-box-' + topicId);
    var assBox = document.getElementById('week-ass-box-' + topicId);
    var chevronMat = document.querySelector('.week-chevron-mat-' + topicId);
    var chevronAss = document.querySelector('.week-chevron-ass-' + topicId);
    if (!panel) return;

    var isPanelOpen = (panel.style.display === 'block');
    var targetBox = (type === 'materials') ? matBox : assBox;
    var otherBox = (type === 'materials') ? assBox : matBox;
    var isTargetAlreadyVisible = targetBox && (targetBox.style.display === 'block');

    if (isPanelOpen && isTargetAlreadyVisible) {
        // Toggle closed
        panel.style.display = 'none';
        if (matBox) matBox.style.display = 'none';
        if (assBox) assBox.style.display = 'none';
        if (chevronMat) chevronMat.style.transform = 'rotate(0deg)';
        if (chevronAss) chevronAss.style.transform = 'rotate(0deg)';
    } else {
        // Open panel and switch to requested box
        panel.style.display = 'block';
        if (targetBox) targetBox.style.display = 'block';
        if (otherBox) otherBox.style.display = 'none';

        if (type === 'materials') {
            if (chevronMat) chevronMat.style.transform = 'rotate(180deg)';
            if (chevronAss) chevronAss.style.transform = 'rotate(0deg)';
        } else {
            if (chevronAss) chevronAss.style.transform = 'rotate(180deg)';
            if (chevronMat) chevronMat.style.transform = 'rotate(0deg)';
        }
    }
}

function expandAllTeachersAndCourses(open) {
    document.querySelectorAll('.teacher-courses-body').forEach(function(b) {
        b.style.display = open ? 'block' : 'none';
    });
    document.querySelectorAll('.teacher-chevron').forEach(function(c) {
        c.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
    });

    document.querySelectorAll('.course-students-drawer').forEach(function(d) {
        d.style.display = open ? 'block' : 'none';
    });
    document.querySelectorAll('.course-chevron').forEach(function(c) {
        c.style.transform = open ? 'rotate(180deg)' : 'rotate(0deg)';
    });
}

var currentTeacherFilter = 'all';

function filterByTeacherStatus(filter, btn) {
    currentTeacherFilter = filter;
    document.querySelectorAll('.teacher-filter-btn').forEach(b => {
        b.classList.remove('active');
        b.style.background = '';
        b.style.color = '';
    });
    if (btn) {
        btn.classList.add('active');
        btn.style.background = 'var(--primary, #2563eb)';
        btn.style.color = '#fff';
    }
    filterTeacherHierarchy();
}

function filterTeacherHierarchy() {
    var query = (document.getElementById('teacherSearchInput').value || '').toLowerCase().trim();
    var deptSelect = document.getElementById('departmentFilterSelect');
    var selectedDept = deptSelect ? deptSelect.value : 'all';
    var teacherCards = document.querySelectorAll('.teacher-group-card');

    teacherCards.forEach(function(tCard) {
        var tName = (tCard.getAttribute('data-teacher-name') || '').toLowerCase();
        var tEmail = (tCard.getAttribute('data-teacher-email') || '').toLowerCase();
        var tAvg = parseFloat(tCard.getAttribute('data-avg-pct') || 0);
        var tComp = parseInt(tCard.getAttribute('data-completed-count') || 0);

        var teacherMatches = query.length > 0 && (tName.includes(query) || tEmail.includes(query));

        var courseSubcards = tCard.querySelectorAll('.course-subcard');
        var matchingCoursesCount = 0;

        courseSubcards.forEach(function(cCard) {
            var cCode = (cCard.getAttribute('data-course-code') || '').toLowerCase();
            var cName = (cCard.getAttribute('data-course-name') || '').toLowerCase();
            var cDeptId = cCard.getAttribute('data-dept-id') || '0';

            var courseMatchesDept = (selectedDept === 'all' || cDeptId === selectedDept);
            var courseMatches = query.length > 0 && (cCode.includes(query) || cName.includes(query));

            var studentRows = cCard.querySelectorAll('.student-progress-row');
            var matchingStudentFound = false;

            studentRows.forEach(function(stuRow) {
                var stuName = (stuRow.getAttribute('data-student-name') || '').toLowerCase();
                var stuEmail = (stuRow.getAttribute('data-student-email') || '').toLowerCase();
                if (query.length > 0 && (stuName.includes(query) || stuEmail.includes(query))) {
                    matchingStudentFound = true;
                }
            });

            var topicCards = cCard.querySelectorAll('.weekly-topic-card');
            var matchingTopicFound = false;

            topicCards.forEach(function(topCard) {
                var topTitle = (topCard.getAttribute('data-topic-title') || '').toLowerCase();
                if (query.length > 0 && topTitle.includes(query)) {
                    matchingTopicFound = true;
                }
            });

            // Match course if: matches selected department AND (matches query or query is empty)
            var matchesQuery = !query || teacherMatches || courseMatches || matchingStudentFound || matchingTopicFound;
            var isCourseMatched = courseMatchesDept && matchesQuery;

            if (isCourseMatched) {
                cCard.style.display = '';
                matchingCoursesCount++;

                // If teacher or course was searched, keep ALL students visible!
                // Only filter individual students if the query wasn't matching the teacher/course
                studentRows.forEach(function(stuRow) {
                    var stuName = (stuRow.getAttribute('data-student-name') || '').toLowerCase();
                    var stuEmail = (stuRow.getAttribute('data-student-email') || '').toLowerCase();
                    if (!query || teacherMatches || courseMatches) {
                        stuRow.style.display = '';
                    } else {
                        var stuMatches = stuName.includes(query) || stuEmail.includes(query);
                        stuRow.style.display = stuMatches ? '' : 'none';
                    }
                });

                // If teacher or course was searched, keep ALL topics visible!
                // Only filter individual topics if the query wasn't matching the teacher/course
                topicCards.forEach(function(topCard) {
                    var topTitle = (topCard.getAttribute('data-topic-title') || '').toLowerCase();
                    if (!query || teacherMatches || courseMatches) {
                        topCard.style.display = '';
                    } else {
                        var topMatches = topTitle.includes(query);
                        topCard.style.display = topMatches ? '' : 'none';
                    }
                });

                // Auto-expand and switch tabs appropriately when searching
                var sId = cCard.getAttribute('data-syllabus-id');
                var drawer = document.getElementById('drawer-course-' + sId);
                var chevron = document.getElementById('chevron-course-' + sId);

                if (query) {
                    if (matchingTopicFound && !matchingStudentFound && !teacherMatches && !courseMatches) {
                        if (drawer) drawer.style.display = 'block';
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                        switchCourseInnerTab(sId, 'topics');
                    } else if (matchingStudentFound && !teacherMatches && !courseMatches) {
                        if (drawer) drawer.style.display = 'block';
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                        switchCourseInnerTab(sId, 'students');
                    } else if (teacherMatches || courseMatches) {
                        if (drawer) drawer.style.display = 'block';
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                    }
                }
            } else {
                cCard.style.display = 'none';
            }
        });

        var matchesFilter = true;
        if (currentTeacherFilter === 'high') {
            matchesFilter = (tAvg >= 60);
        } else if (currentTeacherFilter === 'has_completed') {
            matchesFilter = (tComp > 0);
        }

        var hasMatch = (matchingCoursesCount > 0) && matchesFilter;

        if (hasMatch) {
            tCard.style.display = '';
            if ((query || selectedDept !== 'all') && matchingCoursesCount > 0) {
                var tId = tCard.getAttribute('data-teacher-id');
                var tBody = document.getElementById('teacher-courses-' + tId);
                var tChevron = document.getElementById('chevron-teacher-' + tId);
                if (tBody) tBody.style.display = 'block';
                if (tChevron) tChevron.style.transform = 'rotate(180deg)';
            }
        } else {
            tCard.style.display = 'none';
        }
    });
}
</script>
</body></html>
