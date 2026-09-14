<?php
require_once '../includes/config.php';
requireRole('teacher');
$sid = (int)($_GET['id'] ?? 0);
$tid = $_SESSION['user_id'];
$syl = $conn->query("SELECT s.*,c.course_name,c.course_code FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.id=$sid AND s.teacher_id=$tid")->fetch_assoc();
if (!$syl) { setFlash('error','Not found.'); redirect(BASE_URL.'teacher/syllabi.php'); }
$pageTitle = 'Edit Syllabus: '.$syl['course_code'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_syl') {
        $desc = sanitize($_POST['course_description']);
        $out  = sanitize($_POST['course_outcomes']);
        $externalUrl = trim($_POST['external_url'] ?? '');

        // Keep the previously-uploaded image unless a new one is chosen
        $imagePath = $_POST['existing_image'] ?? null;
        $imagePath = $imagePath !== '' ? $imagePath : null;

        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg','jpeg','png','gif','webp'];
            if (in_array($ext, $allowedExt) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $destDir = '../uploads/syllabi/';
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                $newName = 'syl_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destDir . $newName)) {
                    $imagePath = $newName;
                } else {
                    setFlash('error', 'Image upload failed, but the rest of the syllabus was saved.');
                }
            } else {
                setFlash('error', 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp.');
            }
        }

        $stmt=$conn->prepare("UPDATE syllabi SET course_description=?,course_outcomes=?,image_path=?,external_url=? WHERE id=? AND teacher_id=?");
        $stmt->bind_param('ssssii',$desc,$out,$imagePath,$externalUrl,$sid,$tid); $stmt->execute();
        setFlash('success','Syllabus info updated.');

    } elseif ($action === 'add_topic') {
        $wk=(int)$_POST['week_number']; $title=sanitize($_POST['topic_title']); $tdesc=sanitize($_POST['topic_description']);
        $lo=sanitize($_POST['learning_outcomes']); $dm=sanitize($_POST['delivery_mode']);
        $plat=sanitize($_POST['online_platform']); $res=sanitize($_POST['resources']); $ass=sanitize($_POST['assessment_type']);
        $stmt=$conn->prepare("INSERT INTO syllabus_topics (syllabus_id,week_number,topic_title,topic_description,learning_outcomes,delivery_mode,online_platform,resources,assessment_type) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iisssssss',$sid,$wk,$title,$tdesc,$lo,$dm,$plat,$res,$ass);
        $stmt->execute();
        setFlash('success','Topic added.');

    } elseif ($action === 'edit_topic') {
        $topicId=(int)$_POST['topic_id']; $wk=(int)$_POST['week_number']; $title=sanitize($_POST['topic_title']);
        $tdesc=sanitize($_POST['topic_description']); $lo=sanitize($_POST['learning_outcomes']); $dm=sanitize($_POST['delivery_mode']);
        $plat=sanitize($_POST['online_platform']); $res=sanitize($_POST['resources']); $ass=sanitize($_POST['assessment_type']);
        $stmt=$conn->prepare("UPDATE syllabus_topics SET week_number=?,topic_title=?,topic_description=?,learning_outcomes=?,delivery_mode=?,online_platform=?,resources=?,assessment_type=? WHERE id=? AND syllabus_id=?");
        $stmt->bind_param('isssssssii',$wk,$title,$tdesc,$lo,$dm,$plat,$res,$ass,$topicId,$sid); $stmt->execute();
        setFlash('success','Topic updated.');
    } elseif ($action === 'delete_topic') {
        $topicId=(int)$_POST['topic_id'];
        $conn->query("DELETE FROM syllabus_topics WHERE id=$topicId AND syllabus_id=$sid");
        setFlash('success','Topic deleted.');
    }
    redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
}

$topics = $conn->query("SELECT * FROM syllabus_topics WHERE syllabus_id=$sid ORDER BY week_number,sort_order");
$topicsArr = []; while($t=$topics->fetch_assoc()) $topicsArr[] = $t;
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
        <p>Edit syllabus details and map weekly topics</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" onclick="openModal('addTopicModal')"><i class="fas fa-plus"></i> Add Topic</button>
    </div>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('info',this)">Syllabus Info</button>
    <button class="tab-btn" onclick="showTab('mapping',this)">Topic Mapping (<?= count($topicsArr) ?> topics)</button>
</div>

<div class="tab-pane active" id="info">
<div class="card"><div class="card-body">
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="update_syl">
    <input type="hidden" name="existing_image" value="<?= htmlspecialchars($syl['image_path'] ?? '') ?>">
    <div class="form-group"><label>Course Description</label>
    <textarea name="course_description" class="form-control" rows="4"><?= htmlspecialchars($syl['course_description']) ?></textarea></div>
    <div class="form-group"><label>Course Learning Outcomes</label>
    <textarea name="course_outcomes" class="form-control" rows="5" placeholder="1. Identify...\n2. Apply...\n3. Analyze..."><?= htmlspecialchars($syl['course_outcomes']) ?></textarea></div>
    <div class="form-group">
        <label>Cover Image <span style="color:var(--text3);font-weight:400">(optional — shown to your students)</span></label>
        <?php if (!empty($syl['image_path'])): ?>
        <div style="margin-bottom:8px">
            <img src="<?= BASE_URL ?>uploads/syllabi/<?= htmlspecialchars($syl['image_path']) ?>" style="max-height:100px;border-radius:8px;border:1px solid var(--border)">
        </div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <div class="form-group">
        <label>External URL <span style="color:var(--text3);font-weight:400">(optional — e.g. Google Classroom, Moodle link)</span></label>
        <input type="url" name="external_url" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($syl['external_url'] ?? '') ?>">
    </div>

    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
</form>
</div></div>
</div>

<div class="tab-pane" id="mapping">
<?php if (empty($topicsArr)): ?>
<div class="empty-state card"><div class="card-body">
    <i class="fas fa-map"></i>
    <h3>No topics yet</h3>
    <p>Start mapping your syllabus by adding weekly topics</p>
    <button class="btn btn-primary" onclick="showTab('mapping',document.querySelector('.tab-btn:nth-child(2)'));openModal('addTopicModal')" style="margin-top:16px"><i class="fas fa-plus"></i> Add First Topic</button>
</div></div>
<?php else: ?>

<!-- Mode legend -->
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <span class="mode-pill mode-face"><i class="fas fa-users"></i> Face-to-Face</span>
    <span class="mode-pill mode-online"><i class="fas fa-laptop"></i> Online</span>
    <span class="mode-pill mode-blended"><i class="fas fa-layer-group"></i> Blended</span>
    <span class="mode-pill mode-async"><i class="fas fa-clock"></i> Asynchronous</span>
    <span class="mode-pill mode-sync"><i class="fas fa-video"></i> Synchronous</span>
</div>

<div class="week-timeline">
<?php foreach($topicsArr as $t):
$modeClass = ['face-to-face'=>'face','online'=>'online','blended'=>'blended','asynchronous'=>'async','synchronous'=>'sync'][$t['delivery_mode']] ?? 'blended';
?>
<div class="week-item">
    <div class="week-dot <?= $modeClass ?>"></div>
    <div class="week-card <?= $modeClass ?>">
        <div class="week-header">
            <span class="week-num">Week <?= $t['week_number'] ?></span>
            <div style="display:flex;gap:8px;align-items:center">
                <span class="mode-pill mode-<?= $modeClass ?>"><?= ucfirst($t['delivery_mode']) ?></span>
                <button class="btn btn-secondary btn-sm" onclick='editTopic(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete topic?')">
                    <input type="hidden" name="action" value="delete_topic"><input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <div class="week-title"><?= htmlspecialchars($t['topic_title']) ?></div>
        <?php if($t['topic_description']): ?><p style="font-size:13px;color:var(--text3);margin-top:6px;line-height:1.5"><?= nl2br(htmlspecialchars($t['topic_description'])) ?></p><?php endif; ?>
        <?php if($t['learning_outcomes']): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:8px">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Learning Outcomes</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['learning_outcomes'])) ?></p>
        </div>
        <?php endif; ?>
        <div class="week-meta">
            <?php if($t['online_platform']): ?><span><i class="fas fa-laptop"></i> <?= htmlspecialchars($t['online_platform']) ?></span><?php endif; ?>
            <?php if($t['resources']): ?><span><i class="fas fa-book"></i> Resources listed</span><?php endif; ?>
            <?php if($t['assessment_type']): ?><span><i class="fas fa-tasks"></i> <?= htmlspecialchars($t['assessment_type']) ?></span><?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- Add Topic Modal -->
<div class="modal-overlay" id="addTopicModal">
<div class="modal" style="max-width:700px">
<div class="modal-header"><span class="modal-title" id="topicModalTitle">Add Topic</span><button class="modal-close" onclick="closeModal('addTopicModal')">&times;</button></div>
<form method="POST" id="topicForm">
<input type="hidden" name="action" value="add_topic" id="topicAction">
<input type="hidden" name="topic_id" id="topicId">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Week Number</label><input type="number" name="week_number" id="tWeek" class="form-control" min="1" max="18" value="<?= count($topicsArr)+1 ?>" required></div>
        <div class="form-group"><label>Delivery Mode</label>
        <select name="delivery_mode" id="tMode" class="form-control" required>
            <option value="blended">Blended</option>
            <option value="face-to-face">Face-to-Face</option>
            <option value="online">Online</option>
            <option value="asynchronous">Asynchronous</option>
            <option value="synchronous">Synchronous</option>
        </select></div>
    </div>
    <div class="form-group"><label>Topic Title</label><input type="text" name="topic_title" id="tTitle" class="form-control" required placeholder="e.g. Introduction to Algorithms"></div>
    <div class="form-group"><label>Topic Description</label><textarea name="topic_description" id="tDesc" class="form-control" rows="3" placeholder="Brief description of the topic content..."></textarea></div>
    <div class="form-group"><label>Learning Outcomes</label><textarea name="learning_outcomes" id="tLO" class="form-control" rows="3" placeholder="At the end of this lesson, students should be able to..."></textarea></div>
    <div class="form-row">
        <div class="form-group"><label>Online Platform (if applicable)</label><input type="text" name="online_platform" id="tPlat" class="form-control" placeholder="Zoom, Google Meet, Moodle..."></div>
        <div class="form-group"><label>Assessment Type</label><input type="text" name="assessment_type" id="tAss" class="form-control" placeholder="Quiz, Activity, Recitation..."></div>
    </div>
    <div class="form-group"><label>Resources / References</label><textarea name="resources" id="tRes" class="form-control" rows="2" placeholder="Textbooks, links, materials..."></textarea></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addTopicModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Topic</button>
</div>
</form></div></div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function showTab(id,btn){
    document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
}
function editTopic(t){
    document.getElementById('topicModalTitle').textContent='Edit Topic';
    document.getElementById('topicAction').value='edit_topic';
    document.getElementById('topicId').value=t.id;
    document.getElementById('tWeek').value=t.week_number;
    document.getElementById('tTitle').value=t.topic_title;
    document.getElementById('tDesc').value=t.topic_description;
    document.getElementById('tLO').value=t.learning_outcomes;
    document.getElementById('tMode').value=t.delivery_mode;
    document.getElementById('tPlat').value=t.online_platform;
    document.getElementById('tRes').value=t.resources;
    document.getElementById('tAss').value=t.assessment_type;
    openModal('addTopicModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
