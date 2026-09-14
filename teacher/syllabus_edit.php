<?php
require_once '../includes/config.php';
requireRole('teacher');
$sid = (int)($_GET['id'] ?? 0);
$tid = $_SESSION['user_id'];
ensureColumnExists('syllabus_topics', 'is_completed', "TINYINT(1) NOT NULL DEFAULT 0");
ensureColumnExists('syllabus_topics', 'completion_notes', "TEXT DEFAULT NULL");
$syl = $conn->query("SELECT s.*,c.course_name,c.course_code FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.id=$sid AND s.teacher_id=$tid")->fetch_assoc();
if (!$syl) { setFlash('error','Not found.'); redirect(BASE_URL.'teacher/syllabi.php'); }

// Access is gated on admin approval: the syllabus stays visible in the teacher's
// list, but the editor itself only opens once the admin sets status to 'published'.
if ($syl['status'] !== 'published') {
    setFlash('error','This syllabus is pending admin approval ('.htmlspecialchars($syl['status']).'). You can edit it once the admin publishes it.');
    redirect(BASE_URL.'teacher/syllabi.php');
}

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
    } elseif ($action === 'toggle_complete') {
        // AJAX endpoint (mirrors the student progress tracker) — no redirect, returns JSON.
        $topicId = (int)$_POST['topic_id'];
        $status  = sanitize($_POST['status'] ?? '');
        $notes   = sanitize($_POST['notes'] ?? '');
        $isCompleted = $status === 'completed' ? 1 : 0;
        $stmt = $conn->prepare("UPDATE syllabus_topics SET is_completed=?, completion_notes=? WHERE id=? AND syllabus_id=?");
        $stmt->bind_param('isii', $isCompleted, $notes, $topicId, $sid);
        $stmt->execute();
        echo json_encode(['success' => true]);
        exit;
    } elseif ($action === 'upload_lesson') {
        $topicId=(int)$_POST['topic_id'];
        $title=sanitize($_POST['lesson_title'] ?? '');
        $desc=sanitize($_POST['lesson_description'] ?? '');
        $topic = $conn->query("SELECT id, delivery_mode FROM syllabus_topics WHERE id=$topicId AND syllabus_id=$sid")->fetch_assoc();
        if (!$topic) {
            setFlash('error','Topic not found.');
        } elseif (empty($_FILES['lesson_file']['name']) && empty($_POST['external_url'])) {
            setFlash('error','Please upload a file or add a lesson link.');
        } else {
            $uploadDir = '../uploads/materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowedExt = ['pdf','doc','docx','ppt','pptx','jpg','jpeg','png','zip'];
            $filePath = null;
            $type = 'document';
            if (!empty($_FILES['lesson_file']['name'])) {
                $ext = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt) || $_FILES['lesson_file']['error'] !== UPLOAD_ERR_OK) {
                    setFlash('error','Invalid lesson file. Allowed: PDF, DOC/DOCX, PPT/PPTX, images, ZIP.');
                    redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
                }
                $type = in_array($ext, ['ppt','pptx']) ? 'presentation' : 'document';
                $filePath = uniqid('mat_') . '.' . $ext;
                if (!move_uploaded_file($_FILES['lesson_file']['tmp_name'], $uploadDir . $filePath)) {
                    setFlash('error','Lesson file upload failed.');
                    redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
                }
            } elseif (!empty($_POST['external_url'])) {
                $type = 'link';
            }
            $url = trim($_POST['external_url'] ?? '');
            $dm = in_array($topic['delivery_mode'], ['online','asynchronous','synchronous']) ? 'online' : ($topic['delivery_mode'] === 'face-to-face' ? 'offline' : 'both');
            $stmt=$conn->prepare("INSERT INTO learning_materials (syllabus_topic_id,syllabus_id,teacher_id,title,description,type,file_path,external_url,delivery_mode) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('iiissssss',$topicId,$sid,$tid,$title,$desc,$type,$filePath,$url,$dm);
            $stmt->execute();
            setFlash('success','Weekly lesson file uploaded.');
        }
    }
    redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$sid);
}

$topics = $conn->query("SELECT * FROM syllabus_topics WHERE syllabus_id=$sid ORDER BY week_number,sort_order");
$topicsArr = []; while($t=$topics->fetch_assoc()) $topicsArr[] = $t;
$completedCount = count(array_filter($topicsArr, fn($t) => !empty($t['is_completed'])));
$materials = $conn->query("SELECT * FROM learning_materials WHERE syllabus_id=$sid AND teacher_id=$tid ORDER BY created_at DESC");
$materialsByTopic = [];
while($m=$materials->fetch_assoc()) {
    $materialsByTopic[(int)$m['syllabus_topic_id']][] = $m;
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
        <p>Edit syllabus details, weekly topics, and lesson files</p>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary" onclick="openModal('addTopicModal')"><i class="fas fa-plus"></i> Add Topic</button>
    </div>
</div>

<div class="tab-nav">
    <button class="tab-btn active" onclick="showTab('info',this)">Syllabus Info</button>
    <button class="tab-btn" onclick="showTab('mapping',this)">Topic Mapping (<?= count($topicsArr) ?> topics<?= count($topicsArr) ? ', '.$completedCount.'/'.count($topicsArr).' done' : '' ?>)</button>
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

<!-- Progress Summary -->
<?php $pct = count($topicsArr) ? round($completedCount / count($topicsArr) * 100) : 0; ?>
<div class="card" style="margin-bottom:24px"><div class="card-body">
    <div style="display:flex;align-items:center;gap:24px">
        <div style="flex:1">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <span style="font-weight:600">Topics Completed</span>
                <span style="font-weight:800;color:var(--primary);font-size:18px"><?= $pct ?>%</span>
            </div>
            <div class="progress-bar" style="height:12px"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
            <p style="font-size:12px;color:var(--text3);margin-top:6px"><?= $completedCount ?> of <?= count($topicsArr) ?> topics marked done</p>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:36px;font-weight:800;color:var(--primary)"><?= $completedCount ?>/<?= count($topicsArr) ?></div>
            <div style="font-size:12px;color:var(--text3)">Topics</div>
        </div>
    </div>
</div></div>

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
$isDone = !empty($t['is_completed']);
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
                <?php endif; ?>
                <?php
                    if ($isDone) {
                        $btnJs = "updateStatus(".$t['id'].", 'not_started')";
                    } else {
                        $btnJs = "openDoneModal(".$t['id'].", ".json_encode('Week '.$t['week_number'].' - '.$t['topic_title']).", ".json_encode($t['completion_notes'] ?? '').")";
                    }
                ?>
                <button class="btn btn-sm <?= $isDone ? 'btn-secondary' : 'btn-success' ?>" onclick="<?= htmlspecialchars($btnJs, ENT_QUOTES) ?>">
                    <?= $isDone ? '<i class="fas fa-undo"></i> Undo' : '<i class="fas fa-check"></i> Mark Done' ?>
                </button>
                <button class="btn btn-secondary btn-sm" onclick='openLessonModal(<?= (int)$t['id'] ?>, <?= json_encode('Week '.$t['week_number'].' - '.$t['topic_title']) ?>)'><i class="fas fa-upload"></i></button>
                <button class="btn btn-secondary btn-sm" onclick='editTopic(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete topic?')">
                    <input type="hidden" name="action" value="delete_topic"><input type="hidden" name="topic_id" value="<?= $t['id'] ?>">
                    <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
        <div class="week-title" style="<?= $isDone ? 'text-decoration:line-through;color:var(--text3)' : '' ?>"><?= htmlspecialchars($t['topic_title']) ?></div>
        <?php if($t['topic_description']): ?><p style="font-size:13px;color:var(--text3);margin-top:6px;line-height:1.5"><?= nl2br(htmlspecialchars($t['topic_description'])) ?></p><?php endif; ?>
        <?php if($t['learning_outcomes']): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:8px">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Learning Outcomes</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['learning_outcomes'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if($isDone && !empty($t['completion_notes'])): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:8px;border-left:3px solid var(--primary)">
            <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)"><i class="fas fa-pen"></i> Notes</strong>
            <p style="font-size:13px;margin-top:4px"><?= nl2br(htmlspecialchars($t['completion_notes'])) ?></p>
        </div>
        <?php endif; ?>
        <div class="week-meta">
            <?php if($t['online_platform']): ?><span><i class="fas fa-laptop"></i> <?= htmlspecialchars($t['online_platform']) ?></span><?php endif; ?>
            <?php if($t['resources']): ?><span><i class="fas fa-book"></i> Resources listed</span><?php endif; ?>
            <?php if($t['assessment_type']): ?><span><i class="fas fa-tasks"></i> <?= htmlspecialchars($t['assessment_type']) ?></span><?php endif; ?>
        </div>
        <?php if(!empty($materialsByTopic[(int)$t['id']])): ?>
        <div style="margin-top:10px;padding:10px;background:var(--bg);border-radius:8px">
            <strong style="font-size:11px;text-transform:uppercase;color:var(--text2)">Lesson Files</strong>
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

<!-- Upload Lesson Modal -->
<div class="modal-overlay" id="lessonModal">
<div class="modal" style="max-width:560px">
<div class="modal-header"><span class="modal-title" id="lessonModalTitle">Upload Lesson File</span><button class="modal-close" onclick="closeModal('lessonModal')">&times;</button></div>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload_lesson">
<input type="hidden" name="topic_id" id="lessonTopicId">
<div class="modal-body">
    <div class="form-group"><label>Lesson Title</label><input type="text" name="lesson_title" id="lessonTitle" class="form-control" required></div>
    <div class="form-group"><label>Description</label><textarea name="lesson_description" class="form-control" rows="2" placeholder="Optional note for students"></textarea></div>
    <div class="form-group"><label>Upload PDF / PPT / File</label><input type="file" name="lesson_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.jpeg,.png,.zip"></div>
    <div class="form-group"><label>Or External Link</label><input type="url" name="external_url" class="form-control" placeholder="https://..."></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('lessonModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Lesson</button>
</div>
</form></div></div>

<!-- Mark as Done modal -->
<div class="modal-overlay" id="doneModal">
<div class="modal">
    <div class="modal-header">
        <span class="modal-title"><i class="fas fa-check-circle"></i> Mark Topic as Done</span>
        <button class="modal-close" onclick="closeModal('doneModal')">&times;</button>
    </div>
    <div class="modal-body">
        <p style="font-size:14px;margin-bottom:14px">
            You're about to mark <strong id="doneTopicTitle"></strong> as completed.
        </p>
        <div class="form-group">
            <label>Notes <span style="color:var(--text3);font-weight:400">(optional — e.g. what was covered, follow-ups)</span></label>
            <textarea id="doneNotes" class="form-control" rows="3" placeholder="Anything worth remembering about how this week went..."></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('doneModal')">Cancel</button>
        <button type="button" class="btn btn-success" onclick="confirmMarkDone()"><i class="fas fa-check"></i> Mark as Complete</button>
    </div>
</div>
</div>

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
function openLessonModal(topicId, title){
    document.getElementById('lessonTopicId').value = topicId;
    document.getElementById('lessonModalTitle').textContent = 'Upload Lesson File';
    document.getElementById('lessonTitle').value = title;
    openModal('lessonModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));

var pendingDoneTopicId = null;

// Opens the confirmation modal before marking a topic as done.
// existingNotes lets you re-open and edit notes if you undo + redo.
function openDoneModal(topicId, topicTitle, existingNotes) {
    pendingDoneTopicId = topicId;
    document.getElementById('doneTopicTitle').textContent = topicTitle;
    document.getElementById('doneNotes').value = existingNotes || '';
    openModal('doneModal');
}

function confirmMarkDone() {
    var notes = document.getElementById('doneNotes').value;
    submitStatus(pendingDoneTopicId, 'completed', notes);
    closeModal('doneModal');
}

// Used directly for "Undo" — no modal needed to un-mark a topic.
function updateStatus(topicId, status) {
    submitStatus(topicId, status, '');
}

function submitStatus(topicId, status, notes) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=toggle_complete'
            + '&topic_id=' + encodeURIComponent(topicId)
            + '&status=' + encodeURIComponent(status)
            + '&notes=' + encodeURIComponent(notes || '')
    })
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.success) location.reload(); });
}
</script>
</body></html>