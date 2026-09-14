<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Assessments';
$tid = $_SESSION['user_id'];
ensureColumnExists('assessments', 'attachment_path', "varchar(255) DEFAULT NULL AFTER delivery_mode");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $sylId   = (int)($_POST['syllabus_id'] ?? 0);
        $topicId = $_POST['topic_id'] ?? '';
        $topicId = $topicId !== '' ? (int)$topicId : null;
        $title   = sanitize($_POST['title'] ?? '');
        $desc    = sanitize($_POST['description'] ?? '');
        $type    = sanitize($_POST['type'] ?? 'quiz');
        $max     = (float)($_POST['max_score'] ?? 0);
        $due     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $dm      = sanitize($_POST['delivery_mode'] ?? 'online');
        $shuffle = isset($_POST['shuffle_questions']) ? 1 : 0;
        $subType = 'quiz_builder';

        if (!$sylId || $title === '') {
            setFlash('error', 'Please select a syllabus and enter an assessment title.');
            redirect(BASE_URL.'teacher/assessments.php');
        }

        // Optional attachment
        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $attExt = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowedAttExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','jpg','jpeg','png'];

            if (in_array($attExt, $allowedAttExt)) {
                $attDestDir = '../uploads/assessments/';
                if (!is_dir($attDestDir)) mkdir($attDestDir, 0755, true);
                $attNewName = 'assess_' . uniqid() . '.' . $attExt;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $attDestDir . $attNewName)) {
                    $attachmentPath = $attNewName;
                }
            }
        }

        $stmt = $conn->prepare("INSERT INTO assessments (syllabus_id, topic_id, teacher_id, title, description, type, max_score, due_date, delivery_mode, attachment_path, submission_type, shuffle_questions) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iiisssdssssi', $sylId, $topicId, $tid, $title, $desc, $type, $max, $due, $dm, $attachmentPath, $subType, $shuffle);

        try {
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                setFlash('success', 'Assessment created! You can now add questions to your questionnaire.');
                redirect(BASE_URL.'teacher/assessment_questions.php?id='.$newId);
            } else {
                setFlash('error', 'Could not save the assessment: ' . $stmt->error);
                redirect(BASE_URL.'teacher/assessments.php');
            }
        } catch (\mysqli_sql_exception $e) {
            setFlash('error', 'Could not save the assessment: ' . $e->getMessage());
            redirect(BASE_URL.'teacher/assessments.php');
        }

    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM assessment_questions WHERE assessment_id=$id");
        $conn->query("DELETE FROM assessments WHERE id=$id AND teacher_id=$tid");
        setFlash('success', 'Assessment and its questions deleted.');
        redirect(BASE_URL.'teacher/assessments.php');
    }
}

$sylFilter = (int)($_GET['syl'] ?? 0);
$mySyllabi = $conn->query("SELECT s.*, c.course_code, c.course_name FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid");
$sylArr = []; while($s = $mySyllabi->fetch_assoc()) $sylArr[] = $s;

$selectedSyllabus = null;
if ($sylFilter) {
    foreach ($sylArr as $s) {
        if ((int)$s['id'] === $sylFilter) { $selectedSyllabus = $s; break; }
    }
}

$where = "a.teacher_id=$tid"; if($sylFilter) $where .= " AND a.syllabus_id=$sylFilter";
$assessments = $conn->query("
    SELECT a.*, c.course_code, s.syllabus_file,
           (SELECT COUNT(*) FROM assessment_questions aq WHERE aq.assessment_id = a.id) as q_count,
           (SELECT COUNT(*) FROM submissions sub WHERE sub.assessment_id = a.id) as subs
    FROM assessments a 
    LEFT JOIN syllabi s ON a.syllabus_id = s.id 
    LEFT JOIN courses c ON s.course_id = c.id 
    WHERE $where 
    ORDER BY a.created_at DESC
");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div class="page-header-left">
        <h2>Assessments & Quizzes</h2>
        <p style="color:var(--text3);font-size:13px;margin:2px 0 0">Create quizzes, exams, and activities with multiple choice, true/false, and essays.</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addAssModal')">
        <i class="fas fa-plus"></i> Create Assessment
    </button>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <select name="syl" class="form-control" style="width:300px" onchange="this.form.submit()">
                <option value="">All Syllabi / Courses</option>
                <?php foreach($sylArr as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>>
                        <?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selectedSyllabus && !empty($selectedSyllabus['syllabus_file'])): ?>
                <a href="<?= BASE_URL ?>uploads/syllabus_docs/<?= htmlspecialchars($selectedSyllabus['syllabus_file']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <i class="fas fa-file-pdf"></i> View Syllabus Document
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card"><div class="table-wrap"><table>
<thead>
    <tr>
        <th>Title & Topic</th>
        <th>Course</th>
        <th>Type</th>
        <th>Question Pool</th>
        <th>Points</th>
        <th>Due Date</th>
        <th>Delivery</th>
        <th>Submissions</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
<?php if ($assessments->num_rows === 0): ?>
    <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--text3)">No assessments created yet. Click <strong>Create Assessment</strong> to get started.</td></tr>
<?php else: while($a = $assessments->fetch_assoc()): 
    $tc = ['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple'];
?>
<tr>
    <td>
        <strong><?= htmlspecialchars($a['title']) ?></strong>
        <?php if($a['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($a['description'],0,50)) ?></small><?php endif; ?>
    </td>
    <td><?= $a['course_code'] ? htmlspecialchars($a['course_code']) : '-' ?></td>
    <td><span class="badge <?= $tc[$a['type']] ?? 'badge-gray' ?>" style="text-transform:capitalize"><?= $a['type'] ?></span></td>
    <td>
        <a href="assessment_questions.php?id=<?= $a['id'] ?>" class="btn <?= $a['q_count'] > 0 ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="font-size:12px;display:inline-flex;align-items:center;gap:5px">
            <i class="fas fa-list-ol"></i> <?= $a['q_count'] ?> Question<?= $a['q_count'] != 1 ? 's' : '' ?>
        </a>
        <?php if (!empty($a['shuffle_questions'])): ?>
            <br><span class="badge badge-green" style="font-size:10px;margin-top:3px;display:inline-flex;align-items:center;gap:3px">
                <i class="fas fa-random"></i> Scrambled
            </span>
        <?php endif; ?>
    </td>
    <td><strong><?= number_format($a['max_score'], 1) ?></strong> pts</td>
    <td><?= $a['due_date'] ? date('M d, Y g:i A', strtotime($a['due_date'])) : '<span class="text-muted">No deadline</span>' ?></td>
    <td><span class="mode-pill mode-<?= $a['delivery_mode']==='online'?'online':($a['delivery_mode']==='offline'?'face':'blended') ?>"><?= $a['delivery_mode'] ?></span></td>
    <td><a href="grades.php?assessment=<?= $a['id'] ?>" class="badge badge-blue"><?= $a['subs'] ?> submissions</a></td>
    <td>
        <div class="action-btns">
            <a href="assessment_questions.php?id=<?= $a['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Questions"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this assessment and all questions?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                <button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
<?php endwhile; endif; ?>
</tbody>
</table></div></div>

<!-- Add Assessment Modal -->
<div class="modal-overlay" id="addAssModal">
<div class="modal" style="max-width:640px">
<div class="modal-header">
    <span class="modal-title"><i class="fas fa-clipboard-list" style="color:var(--primary);margin-right:6px"></i> Create New Assessment</span>
    <button class="modal-close" onclick="closeModal('addAssModal')">&times;</button>
</div>
<form method="POST" enctype="multipart/form-data" id="addAssForm">
<input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group">
            <label>Syllabus / Course *</label>
            <select name="syllabus_id" class="form-control" required onchange="loadTopics2(this.value)">
                <option value="">Select course...</option>
                <?php foreach($sylArr as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Link to Topic (optional)</label>
            <select name="topic_id" id="assTopic" class="form-control">
                <option value="">Not linked to specific topic</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Assessment Title *</label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Quiz 1: Software Requirements & Modeling" required>
    </div>

    <div class="form-group">
        <label>Instructions & Guidelines</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Explain examination guidelines, rules, or objectives..."></textarea>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Assessment Type</label>
            <select name="type" class="form-control">
                <option value="quiz" selected>Quiz</option>
                <option value="exam">Major Exam (Midterm / Final)</option>
                <option value="activity">Activity / Laboratory</option>
                <option value="assignment">Assignment</option>
                <option value="project">Project Milestone</option>
            </select>
        </div>
        <div class="form-group">
            <label>Delivery Mode</label>
            <select name="delivery_mode" class="form-control">
                <option value="online" selected>Online</option>
                <option value="offline">In-Person / Offline</option>
                <option value="both">Blended / Both</option>
            </select>
        </div>
    </div>

    <div class="form-group" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:12px 16px;margin-bottom:14px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0;font-size:13px;font-weight:600">
            <input type="checkbox" name="shuffle_questions" value="1" checked style="transform:scale(1.2)">
            <span><i class="fas fa-random" style="color:var(--primary);margin-right:4px"></i> Scramble / Randomize Question Order for Students</span>
        </label>
        <small style="color:var(--text3);display:block;margin-top:4px;margin-left:24px">
            Prevents cheating by presenting questions in different sequences for each student attempt.
        </small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Due Date & Time (optional)</label>
            <input type="datetime-local" name="due_date" class="form-control">
        </div>
        <div class="form-group">
            <label>Supplementary Attachment (optional)</label>
            <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.jpeg,.png">
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addAssModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Continue to Add Questions</button>
</div>
</form></div></div>

</div></div></div>

<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function loadTopics2(sylId){
    if(!sylId)return;
    fetch('get_topics.php?syl='+sylId).then(r=>r.json()).then(data=>{
        const sel=document.getElementById('assTopic');
        sel.innerHTML='<option value="">Not linked to specific topic</option>';
        data.forEach(t=>sel.innerHTML+=`<option value="${t.id}">Week ${t.week_number}: ${t.topic_title}</option>`);
    });
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
