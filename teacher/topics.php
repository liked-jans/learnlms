<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Syllabus Mapping';
$tid = $_SESSION['user_id'];

// Get all syllabi for this teacher
$syllabiQuery = $conn->prepare("
    SELECT s.*, c.course_code, c.course_name, c.units, c.prerequisite,
           (SELECT COUNT(*) FROM syllabus_topics WHERE syllabus_id = s.id) as topic_count,
           (SELECT COUNT(DISTINCT ilo_code) FROM syllabus_topics WHERE syllabus_id = s.id AND ilo_code IS NOT NULL AND ilo_code != '') as ilo_count,
           (SELECT COUNT(*) FROM assessments WHERE syllabus_id = s.id) as assessment_count,
           (SELECT COUNT(*) FROM enrollments WHERE syllabus_id = s.id AND status = 'enrolled') as student_count
    FROM syllabi s
    JOIN courses c ON s.course_id = c.id
    WHERE s.teacher_id = ?
    ORDER BY s.created_at DESC
");
$syllabiQuery->bind_param('i', $tid);
$syllabiQuery->execute();
$allSyllabi = $syllabiQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle active course selection
$selectedSylId = (int)($_GET['syl_id'] ?? ($allSyllabi[0]['id'] ?? 0));
$activeSyl = null;
foreach ($allSyllabi as $s) {
    if ($s['id'] == $selectedSylId) {
        $activeSyl = $s;
        break;
    }
}
if (!$activeSyl && !empty($allSyllabi)) {
    $activeSyl = $allSyllabi[0];
    $selectedSylId = $activeSyl['id'];
}

// Handle Form Submission: Add New Mapping Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . 'teacher/topics.php?syl_id=' . $selectedSylId);
    }

    $action = $_POST['action'];

    if ($action === 'add_mapping') {
        $sylId = (int)$_POST['syllabus_id'];
        $week = (int)$_POST['week_number'];
        $iloCode = sanitize($_POST['ilo_code']);
        $bloomsLevel = sanitize($_POST['blooms_level'] ?? 'understanding');
        $topicTitle = sanitize($_POST['topic_title']);
        $topicDesc = sanitize($_POST['topic_description']);
        $iloDesc = sanitize($_POST['learning_outcomes']);
        $actTitle = sanitize($_POST['activity_title']);
        $mode = sanitize($_POST['delivery_mode']);
        $platform = sanitize($_POST['online_platform']);

        $stmt = $conn->prepare("
            INSERT INTO syllabus_topics (syllabus_id, week_number, ilo_code, blooms_level, topic_title, topic_description, learning_outcomes, activity_title, delivery_mode, online_platform, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                ilo_code = VALUES(ilo_code),
                blooms_level = VALUES(blooms_level),
                topic_title = VALUES(topic_title),
                topic_description = VALUES(topic_description),
                learning_outcomes = VALUES(learning_outcomes),
                activity_title = VALUES(activity_title),
                delivery_mode = VALUES(delivery_mode),
                online_platform = VALUES(online_platform)
        ");
        $stmt->bind_param('iissssssssi', $sylId, $week, $iloCode, $bloomsLevel, $topicTitle, $topicDesc, $iloDesc, $actTitle, $mode, $platform, $week);
        $stmt->execute();
        $newTopicId = $stmt->insert_id ?: $conn->query("SELECT id FROM syllabus_topics WHERE syllabus_id=$sylId AND week_number=$week")->fetch_assoc()['id'];

        // Optional quick assessment link
        $assTitle = sanitize($_POST['assessment_title'] ?? '');
        if (!empty($assTitle)) {
            $assType = sanitize($_POST['assessment_type'] ?? 'quiz');
            $assMax = (float)($_POST['assessment_max'] ?? 10);
            $stmtAss = $conn->prepare("INSERT INTO assessments (syllabus_id, topic_id, teacher_id, title, type, max_score, submission_type) VALUES (?, ?, ?, ?, ?, ?, 'quiz_builder')");
            $stmtAss->bind_param('iiissd', $sylId, $newTopicId, $tid, $assTitle, $assType, $assMax);
            $stmtAss->execute();
        }

        logActivity($tid, "Added syllabus mapping for Week $week ($topicTitle)", 'Curriculum');
        setFlash('success', "Mapping for Week $week added successfully!");
        redirect(BASE_URL . 'teacher/topics.php?syl_id=' . $sylId);

    } elseif ($action === 'link_assessment') {
        $topicId = (int)$_POST['topic_id'];
        $assessmentId = (int)$_POST['assessment_id'];
        $sylId = (int)$_POST['syllabus_id'];

        if ($assessmentId > 0 && $topicId > 0) {
            $stmtLink = $conn->prepare("UPDATE assessments SET topic_id = ? WHERE id = ? AND teacher_id = ?");
            $stmtLink->bind_param('iii', $topicId, $assessmentId, $tid);
            $stmtLink->execute();
            setFlash('success', 'Assessment linked to topic successfully!');
        }
        redirect(BASE_URL . 'teacher/topics.php?syl_id=' . $sylId);
    }
}

// Fetch Mapping Data for Active Course
$mappingRows = [];
$ciloGroups = [];
$unlinkedAssessments = [];
$totalAssessmentsCount = 0;
$formativeCount = 0;
$summativeCount = 0;
$cilosWithAssessments = [];

if ($activeSyl) {
    $mapSql = "
        SELECT st.*, 
               GROUP_CONCAT(DISTINCT CONCAT(lm.id, '::', lm.title) SEPARATOR '||') as materials_list,
               GROUP_CONCAT(DISTINCT CONCAT(a.id, '::', a.title, '::', a.type, '::', a.max_score) SEPARATOR '||') as assessments_data
        FROM syllabus_topics st
        LEFT JOIN learning_materials lm ON lm.syllabus_topic_id = st.id
        LEFT JOIN assessments a ON a.topic_id = st.id
        WHERE st.syllabus_id = ?
        GROUP BY st.id
        ORDER BY st.week_number ASC, st.sort_order ASC
    ";
    $stmtMap = $conn->prepare($mapSql);
    $stmtMap->bind_param('i', $selectedSylId);
    $stmtMap->execute();
    $mappingRows = $stmtMap->get_result()->fetch_all(MYSQLI_ASSOC);

    // Group by CILO for Outcome Alignment View & Gap Analysis
    foreach ($mappingRows as $row) {
        $ilo = trim($row['ilo_code'] ?: 'CILO ' . $row['week_number']);
        if (!isset($ciloGroups[$ilo])) {
            $ciloGroups[$ilo] = [
                'code' => $ilo,
                'description' => $row['learning_outcomes'],
                'blooms_level' => $row['blooms_level'] ?? 'understanding',
                'topics' => [],
                'materials' => [],
                'assessments' => []
            ];
        }

        $ciloGroups[$ilo]['topics'][] = [
            'id' => $row['id'],
            'week' => $row['week_number'],
            'title' => $row['topic_title'],
            'mode' => $row['delivery_mode'],
            'activity' => $row['activity_title']
        ];

        if (!empty($row['materials_list'])) {
            foreach (explode('||', $row['materials_list']) as $mRaw) {
                $mParts = explode('::', $mRaw);
                $ciloGroups[$ilo]['materials'][] = ['id' => $mParts[0] ?? 0, 'title' => $mParts[1] ?? 'Material'];
            }
        }

        if (!empty($row['assessments_data'])) {
            foreach (explode('||', $row['assessments_data']) as $aRaw) {
                $aParts = explode('::', $aRaw);
                $assObj = [
                    'id' => $aParts[0] ?? 0,
                    'title' => $aParts[1] ?? 'Assessment',
                    'type' => $aParts[2] ?? 'quiz',
                    'max_score' => $aParts[3] ?? 10
                ];
                $ciloGroups[$ilo]['assessments'][] = $assObj;
                $cilosWithAssessments[$ilo] = true;

                $totalAssessmentsCount++;
                if (in_array($assObj['type'], ['exam', 'project', 'midterm', 'final'])) {
                    $summativeCount++;
                } else {
                    $formativeCount++;
                }
            }
        }
    }

    // Fetch unlinked assessments for this syllabus that teacher can quick-map
    $stmtUnlinked = $conn->prepare("
        SELECT id, title, type, max_score 
        FROM assessments 
        WHERE syllabus_id = ? AND teacher_id = ? AND (topic_id IS NULL OR topic_id = 0)
        ORDER BY created_at DESC
    ");
    $stmtUnlinked->bind_param('ii', $selectedSylId, $tid);
    $stmtUnlinked->execute();
    $unlinkedAssessments = $stmtUnlinked->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Compute Mapping & Gap Progress
$totalExpectedWeeks = 16;
$mappedWeeksCount = count($mappingRows);
$mappingPercent = $totalExpectedWeeks > 0 ? min(100, round(($mappedWeeksCount / $totalExpectedWeeks) * 100)) : 0;

$totalCilosCount = count($ciloGroups);
$assessedCilosCount = count($cilosWithAssessments);
$outcomeCoveragePercent = $totalCilosCount > 0 ? round(($assessedCilosCount / $totalCilosCount) * 100) : 0;
$unassessedCilos = array_diff(array_keys($ciloGroups), array_keys($cilosWithAssessments));
?>
<?php require_once '../includes/header.php'; ?>
<style>
/* Modern OBE Syllabus Mapping Styles */
.mapping-course-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
}
.course-badge-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: #2563eb;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}
.course-tabs {
    display: flex;
    gap: 28px;
    margin-top: 24px;
    border-bottom: 1px solid var(--border);
}
.course-tab-item {
    font-size: 14px;
    font-weight: 600;
    color: var(--text3);
    padding-bottom: 12px;
    text-decoration: none;
    position: relative;
    transition: color .2s;
}
.course-tab-item:hover { color: var(--primary); }
.course-tab-item.active {
    color: #2563eb;
    border-bottom: 2px solid #2563eb;
}

/* Metric Cards */
.mapping-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.stat-box {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--shadow-sm);
}
.stat-box-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.stat-box-icon.blue { background: #eff6ff; color: #2563eb; }
.stat-box-icon.green { background: #ecfdf5; color: #059669; }
.stat-box-icon.purple { background: #f5f3ff; color: #7c3aed; }
.stat-box-icon.orange { background: #fffbeb; color: #d97706; }

/* Main Layout */
.mapping-main-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 24px;
    align-items: start;
}
@media (max-width: 1100px) {
    .mapping-main-layout { grid-template-columns: 1fr; }
    .mapping-stats-grid { grid-template-columns: 1fr 1fr; }
}

/* View Mode Switcher */
.view-mode-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 8px 14px;
}
.view-mode-tabs {
    display: flex;
    gap: 8px;
}
.view-btn {
    border: none;
    background: transparent;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    color: var(--text3);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.view-btn.active {
    background: #eff6ff;
    color: #1d4ed8;
}

/* Table Styling */
.mapping-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
}
.mapping-table td {
    padding: 16px 14px;
    border-bottom: 1px solid var(--border);
    vertical-align: top;
    font-size: 13px;
}

.ilo-badge {
    background: #eff6ff;
    color: #1d4ed8;
    font-weight: 800;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
    margin-bottom: 4px;
}

/* Bloom's Taxonomy Cognitive Badges */
.bloom-tag {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    display: inline-block;
    margin-left: 4px;
}
.bloom-remembering { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.bloom-understanding { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.bloom-applying { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.bloom-analyzing { background: #f0fdfa; color: #115e59; border: 1px solid #99f6e4; }
.bloom-evaluating { background: #f5f3ff; color: #5b21b6; border: 1px solid #ddd6fe; }
.bloom-creating { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

/* Interactive Assessment & Material Links */
.interactive-ass-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    color: #7e22ce;
    font-size: 12px;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 6px;
    text-decoration: none;
    margin-bottom: 4px;
    transition: all 0.2s;
}
.interactive-ass-link:hover {
    background: #7e22ce;
    color: white;
    border-color: #7e22ce;
}
.unassessed-gap-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fffbeb;
    color: #b45309;
    border: 1px dashed #f59e0b;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
}

/* Outcome Card (By CILO View) */
.cilo-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 18px;
    box-shadow: var(--shadow-sm);
    transition: border-color 0.2s;
}
.cilo-card.gap {
    border-left: 5px solid #f59e0b;
}
.cilo-card.aligned {
    border-left: 5px solid #10b981;
}

/* Flow Diagram */
.flow-diagram-container {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 24px;
    margin-top: 24px;
    box-shadow: var(--shadow-sm);
}
.flow-nodes-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin: 20px 0 16px;
    overflow-x: auto;
    padding-bottom: 8px;
}
.flow-node {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-align: center;
    min-width: 110px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.flow-node-arrow {
    color: #94a3b8;
    font-size: 14px;
    flex-shrink: 0;
}
.node-ilo { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.node-topic { background: #f5f3ff; color: #6b21a8; border: 1px solid #ddd6fe; }
.node-material { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.node-activity { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.node-assessment { background: #fdf2f8; color: #9d174d; border: 1px solid #fbcfe8; }
.node-progress { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

/* Progress Circles & Gap Sidebar */
.circle-progress-container {
    position: relative;
    width: 130px;
    height: 130px;
    margin: 0 auto 16px;
}
.circle-progress-svg {
    transform: rotate(-90deg);
}
.circle-progress-val {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}
</style>

<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<!-- Top Page Title & Course Switcher -->
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
    <div class="page-header-left">
        <h2>Syllabus Mapping</h2>
        <p>Constructive alignment of Intended Learning Outcomes (ILOs), weekly topics, learning resources, and native assessment tasks.</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <label style="font-size:13px;font-weight:600;color:var(--text3)">Switch Course:</label>
        <form method="GET" action="topics.php">
            <select name="syl_id" class="form-control" style="width:260px;font-weight:600" onchange="this.form.submit()">
                <?php foreach($allSyllabi as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $selectedSylId == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['course_code'] . ' - ' . $s['course_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if ($activeSyl): ?>
<!-- 1. Course Profile Card -->
<div class="mapping-course-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div style="display:flex;align-items:center;gap:16px">
            <div class="course-badge-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div>
                <h2 style="font-size:20px;font-weight:800;margin:0 0 4px">BSIS - <?= htmlspecialchars($activeSyl['course_name']) ?></h2>
                <p style="font-size:13px;color:var(--text3);margin:0">
                    <strong><?= htmlspecialchars($activeSyl['course_code']) ?></strong> &bull; 
                    <?= $activeSyl['units'] ?> Units &bull; 
                    <?= $activeSyl['semester'] ?> Semester, AY <?= htmlspecialchars($activeSyl['academic_year']) ?>
                </p>
                <div style="font-size:12px;color:var(--text3);margin-top:4px">
                    <i class="fas fa-link" style="margin-right:4px"></i> Prerequisite: <strong><?= htmlspecialchars($activeSyl['prerequisite'] ?? 'None') ?></strong>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center">
            <a href="export_syllabus.php?id=<?= $activeSyl['id'] ?>" target="_blank" class="btn btn-secondary btn-sm" style="border-radius:8px;font-weight:700">
                <i class="fas fa-print" style="margin-right:6px"></i> Export Official Syllabus
            </a>
            <a href="syllabus_edit.php?id=<?= $activeSyl['id'] ?>" class="btn btn-secondary btn-sm" style="border-radius:8px">
                <i class="fas fa-edit" style="margin-right:6px"></i> Edit Course
            </a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="course-tabs">
        <a href="syllabi.php" class="course-tab-item">Overview</a>
        <a href="syllabus_edit.php?id=<?= $activeSyl['id'] ?>" class="course-tab-item">Syllabus</a>
        <a href="topics.php?syl_id=<?= $activeSyl['id'] ?>" class="course-tab-item active">Syllabus Mapping</a>
        <a href="students.php" class="course-tab-item">Students</a>
        <a href="syllabus_edit.php?id=<?= $activeSyl['id'] ?>" class="course-tab-item">Settings</a>
    </div>
</div>

<!-- 2. Four Core Metric Cards -->
<div class="mapping-stats-grid">
    <div class="stat-box">
        <div class="stat-box-icon blue"><i class="fas fa-bookmark"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $totalCilosCount ?: 6 ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">ILOs Defined</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon green"><i class="fas fa-calendar-alt"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $mappedWeeksCount ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">Mapped Lessons</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon purple"><i class="fas fa-file-alt"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $totalAssessmentsCount ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">Aligned Assessments</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon orange"><i class="fas fa-users"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $activeSyl['student_count'] ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">Enrolled Students</div>
        </div>
    </div>
</div>

<!-- 3. Dual-View Mode Switcher Bar -->
<div class="view-mode-bar">
    <div class="view-mode-tabs">
        <button type="button" class="view-btn active" id="btnTimelineView" onclick="switchMappingView('timeline')">
            <i class="fas fa-stream"></i> Timeline Matrix View (Weeks 1 - 18)
        </button>
        <button type="button" class="view-btn" id="btnOutcomeView" onclick="switchMappingView('outcome')">
            <i class="fas fa-bullseye"></i> Outcome Alignment View (By CILO)
        </button>
    </div>
    <div>
        <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addMappingModal')" style="background:#2563eb;border-color:#2563eb">
            <i class="fas fa-plus" style="margin-right:6px"></i> Add Mapping
        </button>
    </div>
</div>

<!-- 4. Main Two-Column Layout -->
<div class="mapping-main-layout">
    
    <!-- LEFT COLUMN: Main View Containers -->
    <div>
        
        <!-- A. TIMELINE MATRIX VIEW -->
        <div id="timelineViewContainer">
            <div class="card" style="margin-bottom:24px">
                <div class="table-wrap" style="border:none">
                    <table class="mapping-table" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:18%">ILO & Cognitive Domain</th>
                                <th style="width:22%">Lesson / Topic</th>
                                <th style="width:18%">Learning Material</th>
                                <th style="width:18%">Activity (TLA)</th>
                                <th style="width:18%">Assessment Task (AT)</th>
                                <th style="width:6%;text-align:center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($mappingRows)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;padding:40px;color:var(--text3)">
                                    <i class="fas fa-map" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
                                    No syllabus mapping entries found for this course.<br>
                                    Click <strong>+ Add Mapping</strong> above to create your first mapped lesson.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($mappingRows as $idx => $row): 
                                $materials = !empty($row['materials_list']) ? explode('||', $row['materials_list']) : [];
                                $assessments = !empty($row['assessments_data']) ? explode('||', $row['assessments_data']) : [];
                                $iloCode = !empty($row['ilo_code']) ? $row['ilo_code'] : 'CILO ' . ($idx + 1);
                                $bloom = $row['blooms_level'] ?? 'understanding';
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <span class="ilo-badge"><?= htmlspecialchars($iloCode) ?></span>
                                        <span class="bloom-tag bloom-<?= htmlspecialchars($bloom) ?>"><?= ucfirst(htmlspecialchars($bloom)) ?></span>
                                    </div>
                                    <div style="font-size:12px;color:var(--text2);margin-top:4px">
                                        <?= htmlspecialchars($row['learning_outcomes'] ?: 'Achieve core competency for this module.') ?>
                                    </div>
                                </td>
                                <td>
                                    <strong style="color:var(--text);font-size:13px">Week <?= $row['week_number'] ?></strong>
                                    <div style="color:var(--text2);margin-top:2px;font-weight:600">
                                        <?= htmlspecialchars($row['topic_title']) ?>
                                    </div>
                                    <span class="badge <?= $row['delivery_mode']==='online'?'badge-blue':($row['delivery_mode']==='blended'?'badge-purple':'badge-orange') ?>" style="font-size:10px;margin-top:4px">
                                        <?= ucfirst($row['delivery_mode'] ?: 'Face-to-Face') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($materials)): ?>
                                        <ul style="padding-left:14px;margin:0;color:var(--text2);font-size:12px">
                                        <?php foreach(array_slice($materials, 0, 3) as $m): 
                                            $mParts = explode('::', $m);
                                        ?>
                                            <li style="margin-bottom:3px">
                                                <a href="materials.php" style="color:var(--primary);text-decoration:none">
                                                    <?= htmlspecialchars($mParts[1] ?? 'Document') ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <a href="materials.php" class="text-muted" style="font-size:12px;text-decoration:none">
                                            <i class="fas fa-plus-circle"></i> Attach Material
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['activity_title'])): ?>
                                        <div style="font-size:12px;color:var(--text2)">
                                            &bull; <?= htmlspecialchars($row['activity_title']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="font-size:12px;color:var(--text3)">&bull; In-class discussion & exercises</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($assessments)): ?>
                                        <?php foreach($assessments as $assRaw): 
                                            $aParts = explode('::', $assRaw);
                                            $aId = $aParts[0] ?? 0;
                                            $aTitle = $aParts[1] ?? 'Assessment';
                                            $aType = $aParts[2] ?? 'quiz';
                                            $aMax = $aParts[3] ?? 10;
                                        ?>
                                            <a href="assessment_questions.php?id=<?= $aId ?>" class="interactive-ass-link" title="Click to view/edit question bank">
                                                <i class="fas fa-tasks"></i> 
                                                <span><?= htmlspecialchars($aTitle) ?></span>
                                                <small style="opacity:0.8">(<?= number_format((float)$aMax, 1) ?> pts)</small>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="display:flex;flex-direction:column;gap:4px">
                                            <span class="unassessed-gap-badge">
                                                <i class="fas fa-exclamation-triangle"></i> Unassessed
                                            </span>
                                            <?php if (!empty($unlinkedAssessments)): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 6px" onclick="openLinkModal(<?= $row['id'] ?>, <?= $row['week_number'] ?>)">
                                                    <i class="fas fa-link"></i> Link Quiz
                                                </button>
                                            <?php else: ?>
                                                <a href="assessments.php" class="btn btn-secondary btn-sm" style="font-size:10px;padding:2px 6px">
                                                    <i class="fas fa-plus"></i> Create Quiz
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center">
                                    <a href="javascript:void(0)" onclick="viewTopicModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)" class="btn btn-secondary btn-sm" style="padding:4px 8px" title="Quick View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- B. OUTCOME ALIGNMENT VIEW (BY CILO) -->
        <div id="outcomeViewContainer" style="display:none">
            <?php if (empty($ciloGroups)): ?>
                <div class="card" style="padding:40px;text-align:center;color:var(--text3)">
                    <i class="fas fa-bullseye" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
                    No learning outcomes defined yet.
                </div>
            <?php else: ?>
                <?php foreach ($ciloGroups as $ciloKey => $grp): 
                    $hasAss = !empty($grp['assessments']);
                ?>
                <div class="cilo-card <?= $hasAss ? 'aligned' : 'gap' ?>">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px">
                        <div>
                            <span class="ilo-badge" style="font-size:13px;padding:4px 10px"><?= htmlspecialchars($grp['code']) ?></span>
                            <span class="bloom-tag bloom-<?= htmlspecialchars($grp['blooms_level']) ?>"><?= ucfirst(htmlspecialchars($grp['blooms_level'])) ?></span>
                            <div style="font-size:14px;color:var(--text);font-weight:600;margin-top:6px">
                                <?= htmlspecialchars($grp['description'] ?: 'Core curriculum competency.') ?>
                            </div>
                        </div>
                        <div>
                            <?php if ($hasAss): ?>
                                <span class="badge badge-green" style="font-size:12px;padding:6px 12px">
                                    <i class="fas fa-check-circle" style="margin-right:4px"></i> Aligned & Assessed (<?= count($grp['assessments']) ?>)
                                </span>
                            <?php else: ?>
                                <span class="badge badge-orange" style="font-size:12px;padding:6px 12px">
                                    <i class="fas fa-exclamation-circle" style="margin-right:4px"></i> Assessment Gap Detected
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Topics addressing this CILO -->
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:10px">
                        <div style="background:#f8fafc;padding:12px 14px;border-radius:8px;border:1px solid #e2e8f0">
                            <strong style="font-size:12px;text-transform:uppercase;color:#475569;display:block;margin-bottom:6px">
                                <i class="fas fa-book-open"></i> Mapped Topics (<?= count($grp['topics']) ?>)
                            </strong>
                            <?php foreach ($grp['topics'] as $t): ?>
                                <div style="font-size:12px;margin-bottom:4px">
                                    <strong>Week <?= $t['week'] ?>:</strong> <?= htmlspecialchars($t['title']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="background:#f8fafc;padding:12px 14px;border-radius:8px;border:1px solid #e2e8f0">
                            <strong style="font-size:12px;text-transform:uppercase;color:#475569;display:block;margin-bottom:6px">
                                <i class="fas fa-tasks"></i> Aligned Assessments (<?= count($grp['assessments']) ?>)
                            </strong>
                            <?php if ($hasAss): ?>
                                <?php foreach ($grp['assessments'] as $a): ?>
                                    <div style="margin-bottom:4px">
                                        <a href="assessment_questions.php?id=<?= $a['id'] ?>" class="interactive-ass-link">
                                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($a['title']) ?> (<?= $a['max_score'] ?> pts)
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="font-size:12px;color:#b45309">
                                    <i class="fas fa-exclamation-triangle"></i> No assessment measures this outcome yet.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 5. Syllabus Mapping Flow Banner -->
        <div class="flow-diagram-container">
            <h3 style="font-size:15px;font-weight:700;margin:0">Outcomes-Based Constructive Alignment Flow</h3>
            
            <div class="flow-nodes-wrapper">
                <div class="flow-node node-ilo">
                    CILO<br><small style="font-size:10px;font-weight:400">(Why to Learn)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>
                
                <div class="flow-node node-topic">
                    Lesson / Topic<br><small style="font-size:10px;font-weight:400">(What to Learn)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-material">
                    Learning Material<br><small style="font-size:10px;font-weight:400">(Instructional Tools)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-activity">
                    Activity (TLA)<br><small style="font-size:10px;font-weight:400">(How to Practice)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-assessment">
                    Assessment (AT)<br><small style="font-size:10px;font-weight:400">(How Well Learned)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-progress">
                    OBE Mastery<br><small style="font-size:10px;font-weight:400">(Attainment %)</small>
                </div>
            </div>

            <p style="font-size:12px;color:var(--text3);margin:0;line-height:1.5">
                The constructive alignment model ensures every intended learning outcome is taught through structured activities and evaluated via authentic assessment tasks.
            </p>
        </div>
    </div>

    <!-- RIGHT COLUMN: Curriculum Intelligence & OBE Gap Analysis -->
    <div>
        <!-- A. Curriculum Health & Gap Analysis Widget -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
                <span class="card-title" style="font-size:15px">Curriculum Intelligence</span>
                <?php if (empty($unassessedCilos)): ?>
                    <span class="badge badge-green" style="font-size:11px">CHED Ready</span>
                <?php else: ?>
                    <span class="badge badge-orange" style="font-size:11px">Gaps Found</span>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding:20px;text-align:center">
                <?php
                $dashoffset = 314 - (314 * ($outcomeCoveragePercent / 100));
                ?>
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="130" height="130">
                        <circle cx="65" cy="65" r="50" stroke="#e2e8f0" stroke-width="10" fill="none" />
                        <circle cx="65" cy="65" r="50" stroke="#2563eb" stroke-width="10" fill="none"
                                stroke-dasharray="314"
                                stroke-dashoffset="<?= $dashoffset ?>"
                                stroke-linecap="round" />
                    </svg>
                    <div class="circle-progress-val">
                        <div style="font-size:22px;font-weight:800;color:#0f172a"><?= $outcomeCoveragePercent ?>%</div>
                        <div style="font-size:11px;color:var(--text3);font-weight:600">OBE Aligned</div>
                    </div>
                </div>

                <div style="text-align:left;border-top:1px solid var(--border);padding-top:14px;font-size:12px">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="color:var(--text3)">Weeks Mapped:</span>
                        <strong><?= $mappedWeeksCount ?> / <?= $totalExpectedWeeks ?> (<?= $mappingPercent ?>%)</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="color:var(--text3)">Outcomes Assessed:</span>
                        <strong><?= $assessedCilosCount ?> / <?= $totalCilosCount ?> CILOs</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                        <span style="color:var(--text3)">Assessment Balance:</span>
                        <strong><?= $formativeCount ?> Formative &bull; <?= $summativeCount ?> Summative</strong>
                    </div>

                    <?php if (!empty($unassessedCilos)): ?>
                        <div style="margin-top:12px;padding:10px;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;color:#92400e">
                            <strong style="display:block;margin-bottom:2px"><i class="fas fa-exclamation-triangle"></i> Gaps to Resolve:</strong>
                            <?= implode(', ', $unassessedCilos) ?> have no assessment task mapped yet.
                        </div>
                    <?php else: ?>
                        <div style="margin-top:12px;padding:10px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;color:#065f46">
                            <i class="fas fa-check-double"></i> 100% of defined learning outcomes have aligned assessment tasks!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- B. Quick Action Links -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="padding:16px 20px"><span class="card-title" style="font-size:15px">Quick Actions</span></div>
            <div class="card-body" style="padding:16px 20px;display:flex;flex-direction:column;gap:10px">
                <a href="export_syllabus.php?id=<?= $activeSyl['id'] ?>" target="_blank" class="btn btn-primary" style="background:#0f172a;border-color:#0f172a;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-file-pdf" style="width:20px"></i> Print Official Syllabus
                </a>
                <button type="button" class="btn btn-primary" onclick="openModal('addMappingModal')" style="background:#2563eb;border-color:#2563eb;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-plus-circle" style="width:20px"></i> Add Topic Mapping
                </button>
                <a href="materials.php" class="btn btn-primary" style="background:#059669;border-color:#059669;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-cloud-upload-alt" style="width:20px"></i> Upload Material
                </a>
                <a href="assessments.php" class="btn btn-primary" style="background:#7c3aed;border-color:#7c3aed;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-tasks" style="width:20px"></i> Manage Questionnaires
                </a>
            </div>
        </div>

        <!-- C. Accreditation Compliance Note -->
        <div class="card">
            <div class="card-header" style="padding:16px 20px">
                <span class="card-title" style="font-size:15px">CHED Compliance</span>
            </div>
            <div class="card-body" style="padding:16px 20px;font-size:12px;color:var(--text2);line-height:1.6">
                <p style="margin-bottom:8px">
                    <strong>CHED CMO No. 25, s. 2015</strong> mandates constructive alignment across all BSIS professional courses.
                </p>
                <p style="margin:0">
                    Use the <strong>Export Official Syllabus</strong> tool to generate an accreditation-ready document with standard university signatories for Dean submission.
                </p>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add Mapping Entry -->
<div class="modal-overlay" id="addMappingModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <span class="modal-title">Add Syllabus Mapping Entry</span>
            <button class="modal-close" onclick="closeModal('addMappingModal')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_mapping">
            <input type="hidden" name="syllabus_id" value="<?= $selectedSylId ?>">
            
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Week Number *</label>
                        <input type="number" name="week_number" class="form-control" min="1" max="18" value="<?= $mappedWeeksCount + 1 ?>" required>
                    </div>
                    <div class="form-group">
                        <label>ILO Code *</label>
                        <input type="text" name="ilo_code" class="form-control" placeholder="e.g. CILO 1" value="CILO <?= $mappedWeeksCount + 1 ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Bloom's Domain *</label>
                        <select name="blooms_level" class="form-control" required>
                            <option value="remembering">Remembering</option>
                            <option value="understanding" selected>Understanding</option>
                            <option value="applying">Applying</option>
                            <option value="analyzing">Analyzing</option>
                            <option value="evaluating">Evaluating</option>
                            <option value="creating">Creating</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Lesson / Topic Title *</label>
                    <input type="text" name="topic_title" class="form-control" placeholder="e.g. Data Warehouse Architecture & ETL Pipelines" required>
                </div>

                <div class="form-group">
                    <label>Course Intended Learning Outcome (CILO Statement) *</label>
                    <textarea name="learning_outcomes" class="form-control" rows="2" placeholder="e.g. Design and evaluate multi-dimensional schema models and ETL workflows." required></textarea>
                </div>

                <div class="form-group">
                    <label>Teaching & Learning Activity (TLA) *</label>
                    <input type="text" name="activity_title" class="form-control" placeholder="e.g. Collaborative Schema Modeling Workshop & Lab" required>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label>Delivery Mode *</label>
                        <select name="delivery_mode" class="form-control" required>
                            <option value="face-to-face">Face-to-Face</option>
                            <option value="online">Online</option>
                            <option value="blended" selected>Blended (Hybrid)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Platform / Room</label>
                        <input type="text" name="online_platform" class="form-control" placeholder="e.g. Lab 402 / BlendEd LMS">
                    </div>
                </div>

                <div class="form-group" style="padding:12px;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e1">
                    <label style="font-weight:700;color:#1e40af">Optional: Link Assessment Task</label>
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;margin-top:6px">
                        <input type="text" name="assessment_title" class="form-control" placeholder="Assessment Title (e.g. ETL Lab Quiz)">
                        <select name="assessment_type" class="form-control">
                            <option value="quiz">Quiz</option>
                            <option value="activity">Lab Activity</option>
                            <option value="exam">Major Exam</option>
                            <option value="project">Project</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addMappingModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#2563eb;border-color:#2563eb">
                    <i class="fas fa-check" style="margin-right:6px"></i> Save Mapping
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Quick Link Existing Assessment -->
<?php if (!empty($unlinkedAssessments)): ?>
<div class="modal-overlay" id="linkAssModal">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <span class="modal-title" id="linkModalTitle">Link Assessment to Topic</span>
            <button class="modal-close" onclick="closeModal('linkAssModal')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="link_assessment">
            <input type="hidden" name="syllabus_id" value="<?= $selectedSylId ?>">
            <input type="hidden" name="topic_id" id="linkTopicId" value="">

            <div class="modal-body" style="font-size:13px;line-height:1.6">
                <p style="margin-bottom:12px">Select an existing assessment to link directly to this topic:</p>
                <div class="form-group">
                    <label>Assessment *</label>
                    <select name="assessment_id" class="form-control" required>
                        <?php foreach($unlinkedAssessments as $ua): ?>
                            <option value="<?= $ua['id'] ?>">
                                <?= htmlspecialchars($ua['title']) ?> (<?= ucfirst($ua['type']) ?> &bull; <?= $ua['max_score'] ?> pts)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('linkAssModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:#2563eb;border-color:#2563eb">
                    <i class="fas fa-link" style="margin-right:6px"></i> Link Assessment
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Quick View -->
<div class="modal-overlay" id="viewModal">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <span class="modal-title" id="viewTitle">Mapping Details</span>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewBody" style="font-size:13px;line-height:1.6"></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<script>
function switchMappingView(mode) {
    var timelineCont = document.getElementById('timelineViewContainer');
    var outcomeCont = document.getElementById('outcomeViewContainer');
    var btnT = document.getElementById('btnTimelineView');
    var btnO = document.getElementById('btnOutcomeView');

    if (mode === 'outcome') {
        timelineCont.style.display = 'none';
        outcomeCont.style.display = 'block';
        btnT.classList.remove('active');
        btnO.classList.add('active');
    } else {
        timelineCont.style.display = 'block';
        outcomeCont.style.display = 'none';
        btnT.classList.add('active');
        btnO.classList.remove('active');
    }
}

function openLinkModal(topicId, weekNum) {
    document.getElementById('linkTopicId').value = topicId;
    document.getElementById('linkModalTitle').textContent = `Link Assessment to Week ${weekNum}`;
    openModal('linkAssModal');
}

function viewTopicModal(t) {
    document.getElementById('viewTitle').textContent = `Week ${t.week_number}: ${t.topic_title}`;
    let html = `
        <div style="margin-bottom:12px">
            <span class="ilo-badge">${t.ilo_code || 'CILO'}</span>
            <span class="bloom-tag bloom-${t.blooms_level || 'understanding'}">${(t.blooms_level || 'understanding').toUpperCase()}</span>
        </div>
        <div style="margin-bottom:12px"><strong>Learning Outcome:</strong><br><span style="color:var(--text2)">${t.learning_outcomes || 'None'}</span></div>
        <div style="margin-bottom:12px"><strong>Activity (TLA):</strong><br><span style="color:var(--text2)">${t.activity_title || 'None'}</span></div>
        <div style="margin-bottom:12px"><strong>Delivery Mode:</strong> ${(t.delivery_mode || 'face-to-face').toUpperCase()} (${t.online_platform || 'Campus'})</div>
        <div><strong>Description:</strong><br><span style="color:var(--text3)">${t.topic_description || 'No detailed description.'}</span></div>
    `;
    document.getElementById('viewBody').innerHTML = html;
    openModal('viewModal');
}

if (new URLSearchParams(window.location.search).get('add') === '1') {
    document.addEventListener('DOMContentLoaded', function() {
        openModal('addMappingModal');
    });
}
</script>

<?php endif; ?>

</div></div></div>
</body></html>
