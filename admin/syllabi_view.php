<?php
require_once '../includes/config.php';
requireRole('admin');
$id = (int)($_GET['id'] ?? 0);
ensureColumnExists('syllabus_topics', 'is_completed', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'completion_notes', "TEXT DEFAULT NULL");
$syl = $conn->query("SELECT s.*,c.course_name,c.course_code,c.units,u.full_name as teacher_name,u.email as teacher_email,d.name as dept_name FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id JOIN departments d ON c.department_id=d.id WHERE s.id=$id")->fetch_assoc();
if (!$syl) redirect(BASE_URL.'admin/syllabi.php');
$pageTitle = 'Syllabus: '.$syl['course_code'];
$topics = $conn->query("SELECT * FROM syllabus_topics WHERE syllabus_id=$id ORDER BY week_number,sort_order");
$topicsArr = []; while($t=$topics->fetch_assoc()) $topicsArr[] = $t;
$completedCount = count(array_filter($topicsArr, fn($t) => !empty($t['is_completed'])));
$totalTopics = count($topicsArr);
$topicsPct = $totalTopics > 0 ? round($completedCount / $totalTopics * 100) : 0;
$students = $conn->query("SELECT u.full_name,u.email,e.enrolled_at,e.status FROM enrollments e JOIN users u ON e.student_id=u.id WHERE e.syllabus_id=$id");
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
    <?php $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
    <span class="badge <?= $sc[$syl['status']] ?>" style="font-size:14px;padding:8px 16px"><?= ucfirst($syl['status']) ?></span>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('overview',this)">Overview</button>
    <button class="tab-btn" onclick="showTab('topics',this)">Topics & Mapping (<?= $totalTopics ?> topics<?= $totalTopics ? ', '.$completedCount.'/'.$totalTopics.' done' : '' ?>)</button>
    <button class="tab-btn" onclick="showTab('students',this)">Enrolled Students</button>
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
            </div>
        </div>
        <div class="week-title" style="<?= $isDone ? 'text-decoration:line-through;color:var(--text3)' : '' ?>"><?= htmlspecialchars($t['topic_title']) ?></div>
        <?php if($t['topic_description']): ?><p style="font-size:13px;color:var(--text3);margin-top:6px"><?= htmlspecialchars($t['topic_description']) ?></p><?php endif; ?>
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
<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Student Name</th><th>Email</th><th>Enrolled Date</th><th>Status</th></tr></thead>
<tbody>
<?php while($s=$students->fetch_assoc()): ?>
<tr>
    <td><div style="display:flex;align-items:center;gap:8px"><div class="avatar-sm"><?= strtoupper(substr($s['full_name'],0,2)) ?></div><?= htmlspecialchars($s['full_name']) ?></div></td>
    <td><?= htmlspecialchars($s['email']) ?></td>
    <td><?= date('M d, Y', strtotime($s['enrolled_at'])) ?></td>
    <td><span class="badge <?= $s['status']==='enrolled'?'badge-green':'badge-gray' ?>"><?= $s['status'] ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>
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