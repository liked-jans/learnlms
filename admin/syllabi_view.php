<?php
require_once '../includes/config.php';
requireRole('admin');
$id = (int)($_GET['id'] ?? 0);
ensureColumnExists('syllabus_topics', 'is_completed', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'completion_notes', "TEXT DEFAULT NULL");
ensureColumnExists('syllabus_topics', 'deletion_requested', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'deletion_reason', "TEXT DEFAULT NULL");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'admin_delete_topic') {
        $topicId = (int)$_POST['topic_id'];
        deleteTopicCascade($topicId, $id);
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], "Admin permanently deleted topic ID {$topicId} and associated student progress from syllabus ID {$id}", 'Syllabus');
        }
        setFlash('success', 'Topic and all associated student progress deleted.');
        redirect(BASE_URL . 'admin/syllabi_view.php?id=' . $id);
    } elseif ($action === 'admin_dismiss_delete_request') {
        $topicId = (int)$_POST['topic_id'];
        $conn->query("UPDATE syllabus_topics SET deletion_requested = 0, deletion_reason = NULL WHERE id=$topicId AND syllabus_id=$id");
        if (function_exists('logActivity')) {
            logActivity($_SESSION['user_id'], "Admin dismissed deletion request for topic ID {$topicId} in syllabus ID {$id}", 'Syllabus');
        }
        setFlash('success', 'Deletion request dismissed. Topic retained.');
        redirect(BASE_URL . 'admin/syllabi_view.php?id=' . $id);
    }
}

$syl = $conn->query("SELECT s.*,c.course_name,c.course_code,c.units,u.full_name as teacher_name,u.email as teacher_email,d.name as dept_name FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id JOIN departments d ON c.department_id=d.id WHERE s.id=$id")->fetch_assoc();
if (!$syl) redirect(BASE_URL.'admin/syllabi.php');
$pageTitle = 'Syllabus: '.$syl['course_code'];
$topics = $conn->query("SELECT * FROM syllabus_topics WHERE syllabus_id=$id ORDER BY week_number,sort_order");
$topicsArr = []; while($t=$topics->fetch_assoc()) $topicsArr[] = $t;
$completedCount = count(array_filter($topicsArr, fn($t) => !empty($t['is_completed'])));
$totalTopics = count($topicsArr);
$topicsPct = $totalTopics > 0 ? round($completedCount / $totalTopics * 100) : 0;
$totalAssessments = (int)$conn->query("SELECT COUNT(*) FROM assessments WHERE syllabus_id=$id")->fetch_row()[0];

$studentsQuery = $conn->query("
    SELECT u.id as student_id, u.full_name, u.email, e.enrolled_at, e.status,
           COALESCE(COUNT(DISTINCT CASE WHEN tp.read_percentage >= 90 OR tp.status = 'completed' THEN tp.syllabus_topic_id END), 0) as completed_topics,
           COALESCE(SUM(tp.read_percentage), 0) as total_read_pct_sum,
           COALESCE(COUNT(DISTINCT sub.assessment_id), 0) as submitted_assessments,
           COALESCE(AVG(sub.score), 0) as avg_score
    FROM enrollments e
    JOIN users u ON e.student_id=u.id
    LEFT JOIN topic_progress tp ON tp.student_id=u.id AND tp.syllabus_topic_id IN (SELECT id FROM syllabus_topics WHERE syllabus_id=$id)
    LEFT JOIN submissions sub ON sub.student_id=u.id AND sub.assessment_id IN (SELECT id FROM assessments WHERE syllabus_id=$id)
    WHERE e.syllabus_id=$id
    GROUP BY u.id, u.full_name, u.email, e.enrolled_at, e.status
    ORDER BY u.full_name ASC
");
$studentsList = [];
while ($st = $studentsQuery->fetch_assoc()) {
    $denom = ($totalTopics + $totalAssessments);
    $st['overall_pct'] = $denom > 0 ? min(100, max(0, round(($st['total_read_pct_sum'] + ($st['submitted_assessments'] * 100)) / $denom, 1))) : 0;
    $studentsList[] = $st;
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="breadcrumb"><a href="syllabi.php">Syllabi</a><span>›</span> <?= htmlspecialchars($syl['course_code']) ?></div>
<div class="page-header">
    <div class="page-header-left">
        <h2><?= htmlspecialchars($syl['course_code']) ?>: <?= htmlspecialchars($syl['course_name']) ?></h2>
        <p><?= htmlspecialchars($syl['dept_name']) ?> | <?= $syl['academic_year'] ?> - <?= $syl['semester'] ?> Semester</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <a href="<?= BASE_URL ?>teacher/export_syllabus.php?id=<?= $id ?>" target="_blank" class="btn btn-secondary" style="display:inline-flex;align-items:center;gap:6px">
            <i class="fas fa-print"></i> Print / Export Syllabus
        </a>
        <a href="monitoring.php?syl=<?= $id ?>" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px">
            <i class="fas fa-chart-line"></i> Monitor Cohort
        </a>
        <?php $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
        <span class="badge <?= $sc[$syl['status']] ?>" style="font-size:14px;padding:8px 16px"><?= ucfirst($syl['status']) ?></span>
    </div>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('overview',this)">Overview</button>
    <button class="tab-btn" onclick="showTab('topics',this)">Topics & Mapping (<?= $totalTopics ?> topics<?= $totalTopics ? ', '.$completedCount.'/'.$totalTopics.' done' : '' ?>)</button>
    <button class="tab-btn" onclick="showTab('students',this)">Enrolled Students & Progress (<?= count($studentsList) ?>)</button>
</div>

<div class="tab-pane active" id="overview">
<div class="two-col">
<div class="card"><div class="card-body">
    <dl class="info-block">
        <dt>Teacher</dt><dd><?= htmlspecialchars($syl['teacher_name']) ?> <small class="text-muted"><?= $syl['teacher_email'] ?></small></dd>
        <dt>Section</dt><dd><?= htmlspecialchars($syl['section_name'] ?? 'Not set') ?></dd>
        <dt>Course Description</dt><dd><?= nl2br(htmlspecialchars($syl['course_description'] ?? 'Not set')) ?></dd>
        <dt>Course Outcomes</dt><dd><?= nl2br(htmlspecialchars($syl['course_outcomes'] ?? 'Not set')) ?></dd>
    </dl>
</div></div>
<div>
<div class="card"><div class="card-body">
    <dl class="info-block">
        <dt>Course Code</dt><dd><?= htmlspecialchars($syl['course_code']) ?></dd>
        <dt>Units</dt><dd><?= $syl['units'] ?> units</dd>
        <dt>Academic Year</dt><dd><?= $syl['academic_year'] ?></dd>
        <dt>Semester</dt><dd><?= $syl['semester'] ?></dd>
        <dt>Created</dt><dd><?= date('M d, Y', strtotime($syl['created_at'])) ?></dd>
    </dl>
</div></div>
</div>
</div>
</div>

<div class="tab-pane" id="topics">

<?php if ($totalTopics > 0): ?>
<div class="card" style="margin-bottom:20px"><div class="card-body">
    <div style="display:flex;align-items:center;gap:24px">
        <div style="flex:1">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <span style="font-weight:600">Topics Completed</span>
                <span style="font-weight:800;color:var(--primary);font-size:18px"><?= $topicsPct ?>%</span>
            </div>
            <div class="progress-bar" style="height:12px"><div class="progress-fill" style="width:<?= $topicsPct ?>%"></div></div>
            <p style="font-size:12px;color:var(--text3);margin-top:6px"><?= $completedCount ?> of <?= $totalTopics ?> topics marked done by the teacher</p>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:36px;font-weight:800;color:var(--primary)"><?= $completedCount ?>/<?= $totalTopics ?></div>
            <div style="font-size:12px;color:var(--text3)">Topics</div>
        </div>
    </div>
</div></div>
<?php endif; ?>

<?php 
$pendingDeletionCount = count(array_filter($topicsArr, fn($t) => !empty($t['deletion_requested'])));
if ($pendingDeletionCount > 0): 
?>
<div class="card" style="margin-bottom:20px;border-left:4px solid #f59e0b;background:#fffbeb">
    <div class="card-body" style="padding:16px 20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:40px;height:40px;border-radius:50%;background:#fef3c7;color:#d97706;display:flex;align-items:center;justify-content:center;font-size:18px">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <strong style="color:#92400e;font-size:14px"><?= $pendingDeletionCount ?> Topic Deletion Request<?= $pendingDeletionCount > 1 ? 's' : '' ?> Pending Review</strong>
                <div style="color:#b45309;font-size:12px;margin-top:2px">The instructor has requested to delete weekly curriculum topics. Review the stated reasons and approve or dismiss below.</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="week-timeline">
<?php foreach($topicsArr as $t):
$modeClass = ['face-to-face'=>'face','online'=>'online','blended'=>'blended','asynchronous'=>'async','synchronous'=>'sync'][$t['delivery_mode']] ?? 'blended';
$isDone = !empty($t['is_completed']);
?>
<div class="week-item">
    <div class="week-dot <?= $isDone ? 'completed' : $modeClass ?>"></div>
    <div class="week-card <?= $modeClass ?>" style="<?= $isDone ? 'opacity:.75' : '' ?>">
        <div class="week-header">
            <span class="week-num">Week <?= $t['week_number'] ?></span>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="mode-pill mode-<?= $modeClass ?>"><?= ucfirst($t['delivery_mode']) ?></span>
                <?php if($isDone): ?>
                <span class="badge badge-green"><i class="fas fa-check"></i> Done</span>
                <?php else: ?>
                <span class="badge badge-gray">Not done</span>
                <?php endif; ?>
                <?php if (!empty($t['deletion_requested'])): ?>
                <span class="badge badge-orange" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a"><i class="fas fa-clock"></i> Deletion Requested</span>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Admin: Permanently delete this topic? This cannot be undone.');">
                    <input type="hidden" name="action" value="admin_delete_topic">
                    <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" style="padding:2px 6px;font-size:11px;color:#dc2626" title="Delete Topic">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="week-title" style="<?= $isDone ? 'text-decoration:line-through;color:var(--text3)' : '' ?>"><?= htmlspecialchars($t['topic_title']) ?></div>
        <?php if($t['topic_description']): ?><p style="font-size:13px;color:var(--text3);margin-top:6px"><?= htmlspecialchars($t['topic_description']) ?></p><?php endif; ?>

        <?php if (!empty($t['deletion_requested'])): ?>
        <div style="margin-top:10px;padding:12px 14px;background:#fffbeb;border:1px solid #fef3c7;border-radius:8px">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div>
                    <strong style="font-size:12px;color:#92400e;display:flex;align-items:center;gap:6px">
                        <i class="fas fa-exclamation-circle"></i> Teacher Deletion Request
                    </strong>
                    <p style="font-size:13px;color:#78350f;margin:4px 0 0">
                        <strong>Stated Reason:</strong> <?= htmlspecialchars($t['deletion_reason'] ?? 'No reason provided') ?>
                    </p>
                </div>
                <div style="display:flex;gap:6px">
                    <form method="POST" onsubmit="return confirm('Approve deletion of this topic?');">
                        <input type="hidden" name="action" value="admin_delete_topic">
                        <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" style="font-size:11px;padding:4px 10px">
                            <i class="fas fa-check"></i> Approve & Delete
                        </button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="admin_dismiss_delete_request">
                        <input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="font-size:11px;padding:4px 10px">
                            <i class="fas fa-times"></i> Dismiss Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if($isDone && !empty($t['completion_notes'])): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:8px;border-left:3px solid var(--primary)">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)"><i class="fas fa-pen"></i> Teacher's Notes</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['completion_notes'])) ?></p>
        </div>
        <?php endif; ?>
        <div class="week-meta">
            <?php if($t['learning_outcomes']): ?><span><i class="fas fa-bullseye"></i> Has Learning Outcomes</span><?php endif; ?>
            <?php if($t['online_platform']): ?><span><i class="fas fa-laptop"></i> <?= htmlspecialchars($t['online_platform']) ?></span><?php endif; ?>
            <?php if($t['assessment_type']): ?><span><i class="fas fa-tasks"></i> <?= htmlspecialchars($t['assessment_type']) ?></span><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>

<div class="tab-pane" id="students">
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <span class="card-title"><i class="fas fa-user-graduate" style="color:var(--primary);margin-right:8px"></i>Enrolled Students & Progress</span>
            <span class="text-muted" style="font-size:12px;margin-left:8px">(Total: <?= count($studentsList) ?>)</span>
        </div>
        <a href="monitoring.php?tab=students&syl=<?= $id ?>" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px">
            <i class="fas fa-chart-line"></i> Full Progress Monitor
        </a>
    </div>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Enrolled Date</th>
                <th>Reading Depth</th>
                <th>Assessments</th>
                <th>Overall Progress</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($studentsList)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text3);padding:30px">No students enrolled in this syllabus yet.</td></tr>
        <?php else: ?>
            <?php foreach($studentsList as $s): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="avatar-sm"><?= strtoupper(substr($s['full_name'],0,2)) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($s['full_name']) ?></div>
                            <div style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($s['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td><?= date('M d, Y', strtotime($s['enrolled_at'])) ?></td>
                <td>
                    <span class="badge <?= $s['completed_topics'] >= $totalTopics && $totalTopics > 0 ? 'badge-green' : ($s['completed_topics'] > 0 ? 'badge-blue' : 'badge-gray') ?>">
                        <?= $s['completed_topics'] ?> / <?= $totalTopics ?> topics
                    </span>
                </td>
                <td>
                    <span class="badge <?= $s['submitted_assessments'] >= $totalAssessments && $totalAssessments > 0 ? 'badge-green' : ($s['submitted_assessments'] > 0 ? 'badge-orange' : 'badge-gray') ?>">
                        <?= $s['submitted_assessments'] ?> / <?= $totalAssessments ?> submitted
                    </span>
                    <?php if ($s['submitted_assessments'] > 0 && $s['avg_score'] > 0): ?>
                        <small class="text-muted" style="display:block;margin-top:2px">Avg: <?= round($s['avg_score'], 1) ?> pts</small>
                    <?php endif; ?>
                </td>
                <td style="min-width:140px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="progress-bar" style="flex:1;height:8px;background:var(--border);border-radius:4px;overflow:hidden">
                            <div style="height:100%;width:<?= $s['overall_pct'] ?>%;background:<?= $s['overall_pct'] >= 100 ? '#10b981' : ($s['overall_pct'] >= 50 ? 'var(--primary)' : '#f59e0b') ?>;border-radius:4px"></div>
                        </div>
                        <span style="font-weight:700;font-size:12px;width:38px;text-align:right"><?= $s['overall_pct'] ?>%</span>
                    </div>
                </td>
                <td>
                    <span class="badge <?= $s['status']==='enrolled'?'badge-green':'badge-gray' ?>"><?= ucfirst($s['status']) ?></span>
                </td>
                <td>
                    <a href="monitoring.php?tab=students&syl=<?= $id ?>&search=<?= urlencode($s['full_name']) ?>" class="btn btn-secondary btn-sm" title="Inspect Student Progress">
                        <i class="fas fa-search-plus"></i>
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

</div></div></div>
<script>
function showTab(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
</script>
</body></html>