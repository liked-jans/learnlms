<?php
require_once '../includes/config.php';
requireRole('student');
$stid = $_SESSION['user_id'];
$sylId = (int)($_GET['syl'] ?? 0);

// Check enrollment + publish status before allowing access
if ($sylId) {
    $syl = $conn->query("SELECT s.*,c.course_name,c.course_code,u.full_name as teacher_name FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id WHERE s.id=$sylId")->fetch_assoc();
    if (!$syl) { setFlash('error','Syllabus not found.'); redirect(BASE_URL.'student/courses.php'); }

    $enroll = $conn->query("SELECT e.* FROM enrollments e WHERE e.student_id=$stid AND e.syllabus_id=$sylId")->fetch_assoc();
    if (!$enroll) { setFlash('error','Not enrolled in this course.'); redirect(BASE_URL.'student/courses.php'); }

    if ($syl['status'] !== 'published') {
        setFlash('error','This syllabus has not been published by the admin yet. Please check back later.');
        redirect(BASE_URL.'student/courses.php');
    }
}

// Handle progress update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $topicId = (int)$_POST['topic_id'];
    $status  = sanitize($_POST['status']);
    $notes   = sanitize($_POST['notes'] ?? '');

    // Re-verify the topic belongs to a published syllabus the student is enrolled in,
    // so progress can't be posted directly to an unpublished/draft syllabus.
    $check = $conn->query("SELECT st.id FROM syllabus_topics st
                            JOIN syllabi s ON s.id = st.syllabus_id
                            JOIN enrollments e ON e.syllabus_id = s.id AND e.student_id=$stid
                            WHERE st.id=$topicId AND s.status='published'")->fetch_assoc();
    if (!$check) {
        echo json_encode(['success'=>false, 'error'=>'Not allowed.']); exit;
    }

    $stmt = $conn->prepare("INSERT INTO topic_progress (student_id,syllabus_topic_id,status,completed_at,notes)
                             VALUES (?,?,?, IF(?='completed',NOW(),NULL), ?)
                             ON DUPLICATE KEY UPDATE
                                status=VALUES(status),
                                completed_at=IF(VALUES(status)='completed',NOW(),NULL),
                                notes=VALUES(notes)");
    $stmt->bind_param('iisss', $stid, $topicId, $status, $status, $notes);
    $stmt->execute();
    echo json_encode(['success'=>true]); exit;
}

if ($sylId) {
    $topics=$conn->query("SELECT st.*,COALESCE(tp.status,'not_started') as progress_status, tp.notes as progress_notes FROM syllabus_topics st LEFT JOIN topic_progress tp ON tp.syllabus_topic_id=st.id AND tp.student_id=$stid WHERE st.syllabus_id=$sylId ORDER BY st.week_number,st.sort_order");
    $topicsArr=[]; while($t=$topics->fetch_assoc()) $topicsArr[]=$t;
    $materials=$conn->query("SELECT * FROM learning_materials WHERE syllabus_id=$sylId ORDER BY created_at DESC");
    $materialsByTopic=[];
    while($m=$materials->fetch_assoc()) {
        $materialsByTopic[(int)$m['syllabus_topic_id']][] = $m;
    }
    $done = count(array_filter($topicsArr, fn($t)=>$t['progress_status']==='completed'));
    $total = count($topicsArr);
    $pct = $total > 0 ? round($done/$total*100) : 0;
    $pageTitle = 'Syllabus: '.$syl['course_code'];
} else {
    $pageTitle = 'My Syllabi';
    // Show every enrolled course (draft/published/archived) so the student can see it exists —
    // only the detail page itself is gated on the syllabus being published (see check above).
    $mySyllabi=$conn->query("SELECT e.*,s.id as syl_id,s.status as syl_status,s.semester,s.academic_year,s.section_name,c.course_name,c.course_code,u.full_name as teacher_name FROM enrollments e JOIN syllabi s ON e.syllabus_id=s.id JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id WHERE e.student_id=$stid AND e.status='enrolled'");
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<?php if ($sylId && isset($syl)): ?>
<div class="breadcrumb"><a href="syllabus.php">Syllabi</a><span>›</span> <?= htmlspecialchars($syl['course_code']) ?></div>
<div class="page-header">
    <div class="page-header-left">
        <h2><?= htmlspecialchars($syl['course_code']) ?>: <?= htmlspecialchars($syl['course_name']) ?></h2>
        <p>Teacher: <?= htmlspecialchars($syl['teacher_name']) ?> &bull; <?= $syl['academic_year'] ?> - <?= $syl['semester'] ?> Semester<?= !empty($syl['section_name']) ? ' &bull; Section '.htmlspecialchars($syl['section_name']) : '' ?></p>
    </div>
</div>

<!-- Progress Summary -->
<div class="card" style="margin-bottom:24px"><div class="card-body">
    <div style="display:flex;align-items:center;gap:24px">
        <div style="flex:1">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <span style="font-weight:600">Overall Progress</span>
                <span style="font-weight:800;color:var(--primary);font-size:18px"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar" style="height:12px"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <p style="font-size:12px;color:var(--text3);margin-top:6px"><?= $done ?> of <?= $total ?> topics completed</p>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:36px;font-weight:800;color:var(--primary)"><?= $done ?>/<?= $total ?></div>
            <div style="font-size:12px;color:var(--text3)">Topics</div>
        </div>
    </div>
</div></div>

<!-- Mode legend -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <span style="font-size:12px;font-weight:600;color:var(--text3)">Delivery Mode:</span>
    <span class="mode-pill mode-face"><i class="fas fa-users"></i> Face-to-Face</span>
    <span class="mode-pill mode-online"><i class="fas fa-laptop"></i> Online</span>
    <span class="mode-pill mode-blended"><i class="fas fa-layer-group"></i> Blended</span>
    <span class="mode-pill mode-async"><i class="fas fa-clock"></i> Async</span>
</div>

<div class="week-timeline">
<?php foreach($topicsArr as $t):
$modeClass = ['face-to-face'=>'face','online'=>'online','blended'=>'blended','asynchronous'=>'async','synchronous'=>'sync'][$t['delivery_mode']] ?? 'blended';
$isDone = $t['progress_status'] === 'completed';
$isInProgress = $t['progress_status'] === 'in_progress';
?>
<div class="week-item" id="topic-<?= $t['id'] ?>">
    <div class="week-dot <?= $isDone ? 'completed' : $modeClass ?>"></div>
    <div class="week-card <?= $modeClass ?>" style="<?= $isDone ? 'opacity:.75;' : '' ?>">
        <div class="week-header">
            <span class="week-num">Week <?= $t['week_number'] ?></span>
            <div style="display:flex;align-items:center;gap:8px">
                <span class="mode-pill mode-<?= $modeClass ?>"><?= ucfirst($t['delivery_mode']) ?></span>
                <?php if($isDone): ?>
                <span class="badge badge-green"><i class="fas fa-check"></i> Done</span>
                <?php elseif($isInProgress): ?>
                <span class="badge badge-orange">In Progress</span>
                <?php endif; ?>
                <?php
                    if ($isDone) {
                        $btnJs = "updateProgress(".$t['id'].", 'not_started')";
                    } else {
                        $btnJs = "openDoneModal(".$t['id'].", ".json_encode($t['topic_title']).", ".json_encode($t['progress_notes'] ?? '').")";
                    }
                ?>
                <button class="btn btn-sm <?= $isDone ? 'btn-secondary' : 'btn-success' ?>"
                    onclick="<?= htmlspecialchars($btnJs, ENT_QUOTES) ?>">
                    <?= $isDone ? '<i class="fas fa-undo"></i> Undo' : '<i class="fas fa-check"></i> Mark Done' ?>
                </button>
            </div>
        </div>
        <div class="week-title"><?= htmlspecialchars($t['topic_title']) ?></div>
        <?php if($t['topic_description']): ?><p style="font-size:13px;color:var(--text3);margin-top:6px"><?= nl2br(htmlspecialchars($t['topic_description'])) ?></p><?php endif; ?>
        <?php if($t['learning_outcomes']): ?>
        <div style="margin-top:10px;padding:10px;background:rgba(255,255,255,.5);border-radius:8px">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)"><i class="fas fa-bullseye"></i> Learning Outcomes</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['learning_outcomes'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if($isDone && !empty($t['progress_notes'])): ?>
        <div style="margin-top:10px;padding:10px;background:rgba(255,255,255,.5);border-radius:8px;border-left:3px solid var(--primary)">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)"><i class="fas fa-pen"></i> Your Reflection</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['progress_notes'])) ?></p>
        </div>
        <?php endif; ?>
        <div class="week-meta">
            <?php if($t['online_platform']): ?><span><i class="fas fa-laptop"></i> <?= htmlspecialchars($t['online_platform']) ?></span><?php endif; ?>
            <?php if($t['resources']): ?><span><i class="fas fa-book"></i> Resources available</span><?php endif; ?>
            <?php if($t['assessment_type']): ?><span><i class="fas fa-tasks"></i> <?= htmlspecialchars($t['assessment_type']) ?></span><?php endif; ?>
        </div>
        <?php if(!empty($materialsByTopic[(int)$t['id']])): ?>
        <div style="margin-top:10px;padding:10px;background:rgba(255,255,255,.5);border-radius:8px">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)"><i class="fas fa-paperclip"></i> Lesson Files</strong>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
                <?php foreach($materialsByTopic[(int)$t['id']] as $m):
                    $fileUrl = $m['file_path'] ? BASE_URL.'uploads/materials/'.rawurlencode($m['file_path']) : $m['external_url'];
                ?>
                <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($fileUrl) ?>" target="_blank">
                    <i class="fas <?= $m['type']==='presentation'?'fa-file-powerpoint':($m['type']==='link'?'fa-link':'fa-file-alt') ?>"></i>
                    <?= htmlspecialchars($m['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Mark as Done modal -->
<div class="modal-overlay" id="doneModal">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title"><i class="fas fa-check-circle"></i> Mark Topic as Done</span>
        <button class="modal-close" onclick="closeModal('doneModal')">&times;</button>
    </div>
    <div class="modal-body">
        <p style="font-size:14px;margin-bottom:14px">
            You're about to mark <strong id="doneTopicTitle"></strong> as completed. Nice work! 🎉
        </p>
        <div class="form-group">
            <label>Reflection / Notes <span style="color:var(--text3);font-weight:400">(optional)</span></label>
            <textarea id="doneNotes" class="form-control" rows="3"
                placeholder="What did you learn from this topic? Any questions or takeaways?"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('doneModal')">Cancel</button>
        <button type="button" class="btn btn-success" onclick="confirmMarkDone()"><i class="fas fa-check"></i> Mark as Complete</button>
    </div>
</div>
</div>

<?php else: ?>
<div class="page-header"><div class="page-header-left"><h2>My Syllabi</h2></div></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px">
<?php while($s=$mySyllabi->fetch_assoc()):
    $isPublished = $s['syl_status'] === 'published';
?>
<div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
        <span style="font-size:11px;font-weight:700;color:var(--primary)"><?= htmlspecialchars($s['course_code']) ?></span>
        <?php if (!$isPublished): ?>
        <span class="badge badge-gray"><?= ucfirst($s['syl_status']) ?></span>
        <?php endif; ?>
    </div>
    <h3 style="font-size:16px;font-weight:700;margin:4px 0"><?= htmlspecialchars($s['course_name']) ?></h3>
    <p style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($s['teacher_name']) ?> &bull; <?= $s['semester'] ?> Sem <?= $s['academic_year'] ?><?= !empty($s['section_name']) ? ' &bull; Section '.htmlspecialchars($s['section_name']) : '' ?></p>
    <?php if ($isPublished): ?>
    <a href="syllabus.php?syl=<?= $s['syl_id'] ?>" class="btn btn-primary btn-sm" style="margin-top:12px"><i class="fas fa-map"></i> View Syllabus Map</a>
    <?php else: ?>
    <button class="btn btn-secondary btn-sm" style="margin-top:12px;opacity:.6;cursor:not-allowed" disabled title="Waiting for admin to publish this syllabus"><i class="fas fa-lock"></i> Pending admin approval</button>
    <?php endif; ?>
</div></div>
<?php endwhile; ?>
</div>
<?php endif; ?>

</div></div></div>
<script>
var pendingDoneTopicId = null;

function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(function(m){
    m.addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
});

// Opens the confirmation modal before marking a topic as done.
// existingNotes lets a student re-open and edit their reflection if they undo + redo.
function openDoneModal(topicId, topicTitle, existingNotes) {
    pendingDoneTopicId = topicId;
    document.getElementById('doneTopicTitle').textContent = topicTitle;
    document.getElementById('doneNotes').value = existingNotes || '';
    openModal('doneModal');
}

function confirmMarkDone() {
    var notes = document.getElementById('doneNotes').value;
    submitProgress(pendingDoneTopicId, 'completed', notes);
    closeModal('doneModal');
}

// Used directly for "Undo" — no modal needed to un-mark a topic.
function updateProgress(topicId, status) {
    submitProgress(topicId, status, '');
}

function submitProgress(topicId, status, notes) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'topic_id=' + encodeURIComponent(topicId)
            + '&status=' + encodeURIComponent(status)
            + '&notes=' + encodeURIComponent(notes || '')
    })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); });
}
</script>
</body></html>