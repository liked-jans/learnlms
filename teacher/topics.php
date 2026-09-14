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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_mapping') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . 'teacher/topics.php?syl_id=' . $selectedSylId);
    }

    $sylId = (int)$_POST['syllabus_id'];
    $week = (int)$_POST['week_number'];
    $iloCode = sanitize($_POST['ilo_code']);
    $topicTitle = sanitize($_POST['topic_title']);
    $topicDesc = sanitize($_POST['topic_description']);
    $iloDesc = sanitize($_POST['learning_outcomes']);
    $actTitle = sanitize($_POST['activity_title']);
    $mode = sanitize($_POST['delivery_mode']);
    $platform = sanitize($_POST['online_platform']);

    // Check if topic exists or insert new
    $stmt = $conn->prepare("
        INSERT INTO syllabus_topics (syllabus_id, week_number, ilo_code, topic_title, topic_description, learning_outcomes, activity_title, delivery_mode, online_platform, sort_order)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            ilo_code = VALUES(ilo_code),
            topic_title = VALUES(topic_title),
            topic_description = VALUES(topic_description),
            learning_outcomes = VALUES(learning_outcomes),
            activity_title = VALUES(activity_title),
            delivery_mode = VALUES(delivery_mode),
            online_platform = VALUES(online_platform)
    ");
    $stmt->bind_param('iisssssssi', $sylId, $week, $iloCode, $topicTitle, $topicDesc, $iloDesc, $actTitle, $mode, $platform, $week);
    $stmt->execute();
    $newTopicId = $stmt->insert_id ?: $conn->query("SELECT id FROM syllabus_topics WHERE syllabus_id=$sylId AND week_number=$week")->fetch_assoc()['id'];

    // Optional quick assessment link
    $assTitle = sanitize($_POST['assessment_title'] ?? '');
    if (!empty($assTitle)) {
        $assType = sanitize($_POST['assessment_type'] ?? 'assignment');
        $assMax = (float)($_POST['assessment_max'] ?? 100);
        $stmtAss = $conn->prepare("INSERT INTO assessments (syllabus_id, topic_id, teacher_id, title, type, max_score, submission_type) VALUES (?, ?, ?, ?, ?, ?, 'google_docs_sheets')");
        $stmtAss->bind_param('iiissd', $sylId, $newTopicId, $tid, $assTitle, $assType, $assMax);
        $stmtAss->execute();
    }

    logActivity($tid, "Added syllabus mapping for Week $week ($topicTitle)", 'Curriculum');
    setFlash('success', "Mapping for Week $week added successfully!");
    redirect(BASE_URL . 'teacher/topics.php?syl_id=' . $sylId);
}

// Fetch Mapping Data for Active Course
$mappingRows = [];
if ($activeSyl) {
    $mapSql = "
        SELECT st.*, 
               GROUP_CONCAT(DISTINCT lm.title SEPARATOR '||') as materials_list,
               GROUP_CONCAT(DISTINCT CONCAT(a.title, ' (', a.type, ')') SEPARATOR '||') as assessments_list
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
}

// Compute Mapping Progress
$totalExpectedWeeks = 16;
$mappedWeeksCount = count($mappingRows);
$mappingPercent = $totalExpectedWeeks > 0 ? min(100, round(($mappedWeeksCount / $totalExpectedWeeks) * 100)) : 0;
?>
<?php require_once '../includes/header.php'; ?>
<style>
/* Syllabus Mapping Custom Styling matching user mockup */
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
    font-weight: 700;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-block;
    margin-bottom: 4px;
}
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

.circle-progress-container {
    position: relative;
    width: 140px;
    height: 140px;
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
        <p>View and manage the mapping of Intended Learning Outcomes (ILOs), lessons, activities, and assessments for your course.</p>
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
<!-- 1. Course Header Card -->
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
        <div>
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

<!-- 2. Four Metric Cards -->
<div class="mapping-stats-grid">
    <div class="stat-box">
        <div class="stat-box-icon blue"><i class="fas fa-bookmark"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $activeSyl['ilo_count'] ?: 6 ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">ILOs Defined</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon green"><i class="fas fa-calendar-alt"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $activeSyl['topic_count'] ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">Lessons / Topics</div>
        </div>
    </div>
    <div class="stat-box">
        <div class="stat-box-icon purple"><i class="fas fa-file-alt"></i></div>
        <div>
            <div style="font-size:24px;font-weight:800"><?= $activeSyl['assessment_count'] ?></div>
            <div style="font-size:12px;color:var(--text3);font-weight:600">Assessments</div>
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

<!-- 3. Main Two-Column Layout -->
<div class="mapping-main-layout">
    
    <!-- LEFT: Main Syllabus Mapping Table & Flow Diagram -->
    <div>
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:18px 24px">
                <span class="card-title" style="font-size:16px;font-weight:700">Syllabus Mapping</span>
                <button type="button" class="btn btn-primary btn-sm" onclick="openModal('addMappingModal')" style="background:#2563eb;border-color:#2563eb">
                    <i class="fas fa-plus" style="margin-right:6px"></i> Add Mapping
                </button>
            </div>
            <div class="table-wrap" style="border:none">
                <table class="mapping-table" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:18%">ILO</th>
                            <th style="width:20%">Lesson / Topic</th>
                            <th style="width:18%">Learning Material</th>
                            <th style="width:20%">Activity</th>
                            <th style="width:16%">Assessment</th>
                            <th style="width:8%;text-align:center">Status</th>
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
                            $assessments = !empty($row['assessments_list']) ? explode('||', $row['assessments_list']) : [];
                            $iloCode = !empty($row['ilo_code']) ? $row['ilo_code'] : 'CILO ' . ($idx + 1);
                        ?>
                        <tr>
                            <td>
                                <span class="ilo-badge"><?= htmlspecialchars($iloCode) ?></span>
                                <div style="font-size:12px;color:var(--text2);margin-top:2px">
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
                                    <?php foreach(array_slice($materials, 0, 3) as $m): ?>
                                        <li style="margin-bottom:3px"><?= htmlspecialchars($m) ?></li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">&bull; Syllabus Guide</span>
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
                                    <?php foreach(array_slice($assessments, 0, 2) as $ass): ?>
                                        <div style="font-weight:600;color:var(--primary);font-size:12px;margin-bottom:2px">
                                            <?= htmlspecialchars($ass) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px">Participation & Rubric</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center">
                                <span class="badge badge-green" style="margin-bottom:4px">Mapped</span>
                                <a href="javascript:void(0)" onclick="viewTopicModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)" style="color:var(--text3);font-size:14px" title="Quick View">
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

        <!-- 4. Syllabus Mapping Flow Banner (Directly from Mockup) -->
        <div class="flow-diagram-container">
            <h3 style="font-size:15px;font-weight:700;margin:0">Syllabus Mapping Flow</h3>
            
            <div class="flow-nodes-wrapper">
                <div class="flow-node node-ilo">
                    ILO<br><small style="font-size:10px;font-weight:400">(Intended Learning Outcome)</small>
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>
                
                <div class="flow-node node-topic">
                    Lesson / Topic
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-material">
                    Learning Material
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-activity">
                    Activity
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-assessment">
                    Assessment
                </div>
                <div class="flow-node-arrow"><i class="fas fa-arrow-right"></i></div>

                <div class="flow-node node-progress">
                    Student Progress
                </div>
            </div>

            <p style="font-size:12px;color:var(--text3);margin:0;line-height:1.5">
                The syllabus mapping connects the intended learning outcomes to the actual learning activities and assessments, ensuring alignment throughout the course.
            </p>
        </div>
    </div>

    <!-- RIGHT: Course Progress, Quick Actions, and Recent Activity -->
    <div>
        <!-- Course Progress Card (Gauge) -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="padding:16px 20px"><span class="card-title" style="font-size:15px">Course Progress</span></div>
            <div class="card-body" style="padding:20px;text-align:center">
                <?php
                $dashoffset = 314 - (314 * ($mappingPercent / 100));
                ?>
                <div class="circle-progress-container">
                    <svg class="circle-progress-svg" width="140" height="140" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#e2e8f0" stroke-width="10"></circle>
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#0284c7" stroke-width="10" stroke-dasharray="314" stroke-dashoffset="<?= $dashoffset ?>" stroke-linecap="round" style="transition: stroke-dashoffset 0.8s ease"></circle>
                    </svg>
                    <div class="circle-progress-val">
                        <div style="font-size:26px;font-weight:800;color:#0284c7"><?= $mappingPercent ?>%</div>
                        <div style="font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase">Syllabus Mapping<br>Completed</div>
                    </div>
                </div>

                <div style="font-size:13px;font-weight:600;color:var(--text2);margin-bottom:6px">
                    <?= $mappedWeeksCount ?> of <?= $totalExpectedWeeks ?> topics mapped
                </div>
                <div class="progress-bar" style="height:8px;border-radius:4px;margin-bottom:0">
                    <div class="progress-fill" style="width:<?= $mappingPercent ?>%;background:#0284c7"></div>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card" style="margin-bottom:24px">
            <div class="card-header" style="padding:16px 20px"><span class="card-title" style="font-size:15px">Quick Actions</span></div>
            <div class="card-body" style="padding:16px 20px;display:flex;flex-direction:column;gap:10px">
                <button type="button" class="btn btn-primary" onclick="openModal('addMappingModal')" style="background:#2563eb;border-color:#2563eb;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-plus" style="width:20px"></i> Add Lesson
                </button>
                <button type="button" class="btn btn-primary" onclick="openModal('addMappingModal')" style="background:#0284c7;border-color:#0284c7;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-bullseye" style="width:20px"></i> Add ILO
                </button>
                <a href="materials.php" class="btn btn-primary" style="background:#059669;border-color:#059669;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-cloud-upload-alt" style="width:20px"></i> Upload Material
                </a>
                <a href="assessments.php" class="btn btn-primary" style="background:#7c3aed;border-color:#7c3aed;justify-content:flex-start;padding:10px 16px">
                    <i class="fas fa-file-signature" style="width:20px"></i> Create Assessment
                </a>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="card">
            <div class="card-header" style="padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
                <span class="card-title" style="font-size:15px">Recent Activity</span>
                <a href="javascript:void(0)" style="font-size:12px;color:#2563eb;font-weight:600">View All</a>
            </div>
            <div class="card-body" style="padding:0">
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:#e0e7ff;color:#4338ca;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">MS</div>
                    <div>
                        <div style="font-size:13px"><strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong></div>
                        <div style="font-size:12px;color:var(--text2)">added a new activity for Week 6</div>
                        <small style="color:var(--text3);font-size:11px">2 hours ago</small>
                    </div>
                </div>
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">JC</div>
                    <div>
                        <div style="font-size:13px"><strong>Juan Dela Cruz</strong></div>
                        <div style="font-size:12px;color:var(--text2)">updated assessment for Week 4</div>
                        <small style="color:var(--text3);font-size:11px">5 hours ago</small>
                    </div>
                </div>
                <div style="padding:14px 18px;display:flex;align-items:flex-start;gap:12px">
                    <div style="width:32px;height:32px;border-radius:50%;background:#ecfdf5;color:#047857;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700">AR</div>
                    <div>
                        <div style="font-size:13px"><strong>Ana Reyes</strong></div>
                        <div style="font-size:12px;color:var(--text2)">mapped CILO 5 to Week 5</div>
                        <small style="color:var(--text3);font-size:11px">1 day ago</small>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add Mapping Entry -->
<div class="modal-overlay" id="addMappingModal">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <span class="modal-title">Add Syllabus Mapping Entry</span>
            <button class="modal-close" onclick="closeModal('addMappingModal')">&times;</button>
        </div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_mapping">
            <input type="hidden" name="syllabus_id" value="<?= $selectedSylId ?>">
            
            <div class="modal-body" style="display:flex;flex-direction:column;gap:14px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label>Week Number *</label>
                        <input type="number" name="week_number" class="form-control" min="1" max="18" value="<?= $mappedWeeksCount + 1 ?>" required>
                    </div>
                    <div class="form-group">
                        <label>ILO Code *</label>
                        <input type="text" name="ilo_code" class="form-control" placeholder="e.g. CILO 1" value="CILO <?= $mappedWeeksCount + 1 ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Lesson / Topic Title *</label>
                    <input type="text" name="topic_title" class="form-control" placeholder="e.g. System Integration & API Testing" required>
                </div>

                <div class="form-group">
                    <label>Intended Learning Outcome (ILO Description) *</label>
                    <textarea name="learning_outcomes" class="form-control" rows="2" placeholder="e.g. Integrate database, user interface, and system functionalities into a working system." required></textarea>
                </div>

                <div class="form-group">
                    <label>Learning Activity Description *</label>
                    <input type="text" name="activity_title" class="form-control" placeholder="e.g. In-class API connection lab and module integration" required>
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
                        <input type="text" name="online_platform" class="form-control" placeholder="e.g. Room 302 / BlendEd LMS">
                    </div>
                </div>

                <div class="form-group" style="padding:12px;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e1">
                    <label style="font-weight:700;color:#1e40af">Optional: Link Assessment Directly</label>
                    <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;margin-top:6px">
                        <input type="text" name="assessment_title" class="form-control" placeholder="Assessment Title (e.g. Integration Demo)">
                        <select name="assessment_type" class="form-control">
                            <option value="assignment">Assignment</option>
                            <option value="quiz">Quiz</option>
                            <option value="activity">Lab Activity</option>
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
function viewTopicModal(t) {
    document.getElementById('viewTitle').textContent = `Week ${t.week_number}: ${t.topic_title}`;
    let html = `
        <div style="margin-bottom:12px"><span class="ilo-badge">${t.ilo_code || 'CILO'}</span></div>
        <div style="margin-bottom:12px"><strong>Learning Outcome:</strong><br><span style="color:var(--text2)">${t.learning_outcomes || 'None'}</span></div>
        <div style="margin-bottom:12px"><strong>Activity:</strong><br><span style="color:var(--text2)">${t.activity_title || 'None'}</span></div>
        <div style="margin-bottom:12px"><strong>Delivery Mode:</strong> ${t.delivery_mode.toUpperCase()} (${t.online_platform || 'Campus'})</div>
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
