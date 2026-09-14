<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'Assessments';
$tid = $_SESSION['user_id'];
ensureColumnExists('assessments', 'attachment_path', "varchar(255) DEFAULT NULL AFTER delivery_mode");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Guard: if the uploaded file exceeded post_max_size, PHP wipes $_POST and $_FILES
        // entirely, so we can detect that case and give a clear message instead of a broken insert.
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            setFlash('error', 'The file you tried to attach is too large for this server to accept. Please use a smaller file (try under 8MB) or ask your administrator to raise the upload limit.');
            redirect(BASE_URL.'teacher/assessments.php');
        }

        $sylId   = (int)($_POST['syllabus_id'] ?? 0);
        $topicId = $_POST['topic_id'] ?? '';
        $topicId = $topicId !== '' ? (int)$topicId : null;
        $title   = sanitize($_POST['title'] ?? '');
        $desc    = sanitize($_POST['description'] ?? '');
        $type    = sanitize($_POST['type'] ?? '');
        $max     = (float)($_POST['max_score'] ?? 0);
        $due     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $dm      = sanitize($_POST['delivery_mode'] ?? '');

        if (!$sylId || $title === '') {
            setFlash('error', 'Please select a syllabus and enter a title.');
            redirect(BASE_URL.'teacher/assessments.php');
        }

        // Optional attachment (instructions / materials) for this assessment
        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name'])) {
            $fileErr = $_FILES['attachment']['error'];

            if ($fileErr !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE   => 'The attachment is larger than this server allows (upload_max_filesize). Please choose a smaller file.',
                    UPLOAD_ERR_FORM_SIZE  => 'The attachment is larger than the form allows. Please choose a smaller file.',
                    UPLOAD_ERR_PARTIAL    => 'The attachment was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder for uploads. Please contact the administrator.',
                    UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file to disk. Please contact the administrator.',
                    UPLOAD_ERR_EXTENSION  => 'A server extension blocked the file upload.',
                ];
                setFlash('error', ($uploadErrors[$fileErr] ?? 'The attachment failed to upload (error code '.$fileErr.').') . ' The assessment itself was NOT saved yet — please resubmit without the file, or with a smaller file.');
                redirect(BASE_URL.'teacher/assessments.php');
            }

            $attExt = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowedAttExt = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','jpg','jpeg','png'];

            if (!in_array($attExt, $allowedAttExt)) {
                setFlash('error', 'Invalid attachment type. Allowed: pdf, doc, docx, ppt, pptx, xls, xlsx, zip, jpg, jpeg, png.');
                redirect(BASE_URL.'teacher/assessments.php');
            }

            $attDestDir = '../uploads/assessments/';
            if (!is_dir($attDestDir)) mkdir($attDestDir, 0755, true);
            $attNewName = 'assess_' . uniqid() . '.' . $attExt;

            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $attDestDir . $attNewName)) {
                $attachmentPath = $attNewName;
            } else {
                setFlash('error', 'Attachment upload failed (could not save the file on the server). Please check that uploads/assessments/ is writable, then try again.');
                redirect(BASE_URL.'teacher/assessments.php');
            }
        }

        $subType = sanitize($_POST['submission_type'] ?? 'google_docs_sheets');
        if (!in_array($subType, ['google_docs_sheets', 'file', 'text'])) {
            $subType = 'google_docs_sheets';
        }
        $templateUrl = filter_var(trim($_POST['google_template_url'] ?? ''), FILTER_VALIDATE_URL) ? trim($_POST['google_template_url']) : null;
        $autoGradePercent = isset($_POST['auto_grade_percent']) ? (float)$_POST['auto_grade_percent'] : 100.00;
        if ($autoGradePercent < 0) $autoGradePercent = 0;
        if ($autoGradePercent > 100) $autoGradePercent = 100;

        $stmt = $conn->prepare("INSERT INTO assessments (syllabus_id,topic_id,teacher_id,title,description,type,max_score,due_date,delivery_mode,attachment_path,submission_type,google_template_url,auto_grade_percent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iiisssdsssssd', $sylId, $topicId, $tid, $title, $desc, $type, $max, $due, $dm, $attachmentPath, $subType, $templateUrl, $autoGradePercent);

        try {
            if ($stmt->execute()) {
                setFlash('success', 'Assessment created successfully with ' . ($subType === 'google_docs_sheets' ? 'Google Docs/Sheets auto-grading' : 'standard submission') . '.');
            } else {
                setFlash('error', 'Could not save the assessment: ' . $stmt->error);
            }
        } catch (\mysqli_sql_exception $e) {
            setFlash('error', 'Could not save the assessment: ' . $e->getMessage());
        }

    } elseif ($action === 'grade') {
        $subId=(int)$_POST['submission_id']; $score=(float)$_POST['score']; $feedback=sanitize($_POST['feedback']);
        $stmt=$conn->prepare("UPDATE submissions SET score=?,feedback=?,status='graded',is_auto_graded=0,graded_at=NOW() WHERE id=?");
        $stmt->bind_param('dsi',$score,$feedback,$subId); $stmt->execute();
        setFlash('success','Grade updated successfully.');
    } elseif ($action === 'delete') {
        $id=(int)$_POST['id'];
        $conn->query("DELETE FROM assessments WHERE id=$id AND teacher_id=$tid");
        setFlash('success','Deleted.');
    }
    redirect(BASE_URL.'teacher/assessments.php');
}

$sylFilter=(int)($_GET['syl']??0);
$mySyllabi=$conn->query("SELECT s.*,c.course_code,c.course_name FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid");
$sylArr=[]; while($s=$mySyllabi->fetch_assoc()) $sylArr[]=$s;

// Find the currently-filtered syllabus, if any, so we can surface the admin-uploaded syllabus document
$selectedSyllabus = null;
if ($sylFilter) {
    foreach ($sylArr as $s) {
        if ((int)$s['id'] === $sylFilter) { $selectedSyllabus = $s; break; }
    }
}

$where = "a.teacher_id=$tid"; if($sylFilter) $where.=" AND a.syllabus_id=$sylFilter";
$assessments=$conn->query("SELECT a.*,c.course_code, s.syllabus_file, (SELECT COUNT(*) FROM submissions sub WHERE sub.assessment_id=a.id) subs FROM assessments a LEFT JOIN syllabi s ON a.syllabus_id=s.id LEFT JOIN courses c ON s.course_id=c.id WHERE $where ORDER BY a.created_at DESC");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Assessments</h2></div>
    <button class="btn btn-primary" onclick="openModal('addAssModal')"><i class="fas fa-plus"></i> Create Assessment</button>
</div>

<div class="card" style="margin-bottom:20px"><div class="card-body" style="padding:14px 20px">
<form method="GET" style="display:flex;gap:12px;align-items:center">
<select name="syl" class="form-control" style="width:300px" onchange="this.form.submit()">
<option value="">All Syllabi</option>
<?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>" <?= $sylFilter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
</select>
<?php if ($selectedSyllabus): ?>
    <?php if (!empty($selectedSyllabus['syllabus_file'] ?? null)): ?>
        <a href="<?= BASE_URL ?>uploads/syllabus_docs/<?= htmlspecialchars($selectedSyllabus['syllabus_file']) ?>" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-file-pdf"></i> View Syllabus Document</a>
    <?php else: ?>
        <span class="text-muted"><small>Admin hasn't uploaded a syllabus document for this course yet.</small></span>
    <?php endif; ?>
<?php endif; ?>
</form></div></div>

<div class="card"><div class="table-wrap"><table>
<thead><tr><th>Title</th><th>Course</th><th>Type</th><th>Submission Format</th><th>Max Score</th><th>Due Date</th><th>Mode</th><th>Attachment / Template</th><th>Submissions</th><th>Actions</th></tr></thead>
<tbody>
<?php while($a=$assessments->fetch_assoc()): 
$tc=['quiz'=>'badge-green','assignment'=>'badge-blue','exam'=>'badge-red','project'=>'badge-orange','activity'=>'badge-purple']; ?>
<tr>
    <td><strong><?= htmlspecialchars($a['title']) ?></strong><?php if($a['description']): ?><br><small class="text-muted"><?= htmlspecialchars(substr($a['description'],0,50)) ?></small><?php endif; ?></td>
    <td>
        <?= $a['course_code'] ? htmlspecialchars($a['course_code']) : '-' ?>
        <?php if (!empty($a['syllabus_file'] ?? null)): ?>
            <a href="<?= BASE_URL ?>uploads/syllabus_docs/<?= htmlspecialchars($a['syllabus_file']) ?>" target="_blank" title="View syllabus document" style="margin-left:4px"><i class="fas fa-paperclip" style="font-size:11px;color:var(--primary)"></i></a>
        <?php endif; ?>
    </td>
    <td><span class="badge <?= $tc[$a['type']] ?? 'badge-gray' ?>"><?= $a['type'] ?></span></td>
    <td>
        <?php if (($a['submission_type'] ?? 'google_docs_sheets') === 'google_docs_sheets'): ?>
            <span class="badge badge-green" style="display:inline-flex;align-items:center;gap:4px">
                <i class="fab fa-google-drive"></i> Google Docs/Sheets
            </span>
            <div style="font-size:11px;color:var(--text3);margin-top:2px"><i class="fas fa-robot"></i> Auto-grades <?= (int)($a['auto_grade_percent'] ?? 100) ?>%</div>
        <?php elseif ($a['submission_type'] === 'file'): ?>
            <span class="badge badge-blue"><i class="fas fa-file-upload"></i> File Upload</span>
        <?php else: ?>
            <span class="badge badge-gray"><i class="fas fa-font"></i> Text Response</span>
        <?php endif; ?>
    </td>
    <td><?= $a['max_score'] ?></td>
    <td><?= $a['due_date'] ? date('M d, Y g:i A', strtotime($a['due_date'])) : '<span class="text-muted">No deadline</span>' ?></td>
    <td><span class="mode-pill mode-<?= $a['delivery_mode']==='online'?'online':($a['delivery_mode']==='offline'?'face':'blended') ?>"><?= $a['delivery_mode'] ?></span></td>
    <td>
        <?php if (!empty($a['google_template_url'])): ?>
            <a href="<?= htmlspecialchars($a['google_template_url']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Open Google Template" style="color:#0f9d58;margin-bottom:3px;display:inline-flex;align-items:center;gap:4px">
                <i class="fab fa-google-drive"></i> Template ↗
            </a><br>
        <?php endif; ?>
        <?php if (!empty($a['attachment_path'] ?? null)): ?>
            <a href="<?= BASE_URL ?>uploads/assessments/<?= htmlspecialchars($a['attachment_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View attachment"><i class="fas fa-paperclip"></i> File</a>
        <?php endif; ?>
        <?php if (empty($a['google_template_url']) && empty($a['attachment_path'])): ?>
            <span class="text-muted"><small>None</small></span>
        <?php endif; ?>
    </td>
    <td><a href="grades.php?assessment=<?= $a['id'] ?>" class="badge badge-blue"><?= $a['subs'] ?> submissions</a></td>
    <td><div class="action-btns">
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $a['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div></div>

<!-- Add Assessment Modal -->
<div class="modal-overlay" id="addAssModal">
<div class="modal" style="max-width:680px">
<div class="modal-header"><span class="modal-title">Create Assessment</span><button class="modal-close" onclick="closeModal('addAssModal')">&times;</button></div>
<form method="POST" enctype="multipart/form-data" id="addAssForm" onsubmit="return validateAssForm()"><input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Syllabus</label>
        <select name="syllabus_id" class="form-control" required onchange="loadTopics2(this.value)">
            <option value="">Select...</option>
            <?php foreach($sylArr as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['course_code'].' - '.$s['course_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Link to Topic</label><select name="topic_id" id="assTopic" class="form-control"><option value="">Not linked</option></select></div>
    </div>
    <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" placeholder="e.g. Activity 1: Software Requirements Specification" required></div>
    <div class="form-group"><label>Description / Instructions</label><textarea name="description" class="form-control" rows="3" placeholder="Explain the assignment guidelines or prompt..."></textarea></div>

    <!-- Submission & Auto-Grading Configuration -->
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-bottom:16px">
        <div style="font-weight:700;font-size:13px;color:#166534;margin-bottom:8px;display:flex;align-items:center;gap:6px">
            <i class="fab fa-google-drive"></i> Submission Format & Automated Grading
        </div>
        <div class="form-row">
            <div class="form-group" style="margin-bottom:8px">
                <label style="font-size:12px;font-weight:600">Student Submission Method</label>
                <select name="submission_type" class="form-control" id="submissionTypeSelect" onchange="toggleTemplateFields(this.value)">
                    <option value="google_docs_sheets" selected>Google Docs / Sheets Link (Automated Instant Grading)</option>
                    <option value="file">File Upload (PDF, Word, Excel, etc.)</option>
                    <option value="text">Text Response</option>
                </select>
            </div>
            <div class="form-group" id="autoGradePercentGroup" style="margin-bottom:8px">
                <label style="font-size:12px;font-weight:600">Auto-Grade Benchmark Score (%)</label>
                <input type="number" name="auto_grade_percent" class="form-control" value="100" min="0" max="100" step="1">
            </div>
        </div>
        <div class="form-group" id="templateUrlGroup" style="margin-bottom:0">
            <label style="font-size:12px;font-weight:600">Google Docs / Sheets Template Link <span style="color:var(--text3);font-weight:400">(optional)</span></label>
            <input type="url" name="google_template_url" class="form-control" placeholder="https://docs.google.com/document/d/... or /spreadsheets/d/...">
            <small style="color:#166534;font-size:11px">Optional: Provide a template document for students to "Make a copy". When submitted, the system immediately awards the benchmark score so students have no waiting time. You can override or change the grade in the Gradebook anytime.</small>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group"><label>Type</label><select name="type" class="form-control"><option>quiz</option><option selected>assignment</option><option>exam</option><option>project</option><option>activity</option></select></div>
        <div class="form-group"><label>Max Score</label><input type="number" name="max_score" class="form-control" value="100" min="1"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Due Date (optional)</label><input type="datetime-local" name="due_date" class="form-control"></div>
        <div class="form-group"><label>Delivery Mode</label><select name="delivery_mode" class="form-control"><option>both</option><option selected>online</option><option>offline</option></select></div>
    </div>
    <div class="form-group">
        <label>Attachment <span style="color:var(--text3);font-weight:400">(optional supplementary material, max 8MB)</span></label>
        <input type="file" name="attachment" id="assAttachment" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.jpg,.jpeg,.png" onchange="checkAssFileSize(this)">
        <div id="assFileSizeWarning" style="color:var(--danger);font-size:12px;margin-top:6px;display:none">This file is bigger than 8MB and may fail to upload on this server. Try a smaller file.</div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addAssModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Assessment</button>
</div>
</form></div></div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function toggleTemplateFields(val){
    var isGoogle = (val === 'google_docs_sheets');
    document.getElementById('templateUrlGroup').style.display = isGoogle ? 'block' : 'none';
    document.getElementById('autoGradePercentGroup').style.display = isGoogle ? 'block' : 'none';
}
function loadTopics2(sylId){
    if(!sylId)return;
    fetch('get_topics.php?syl='+sylId).then(r=>r.json()).then(data=>{
        const sel=document.getElementById('assTopic');
        sel.innerHTML='<option value="">Not linked</option>';
        data.forEach(t=>sel.innerHTML+=`<option value="${t.id}">Week ${t.week_number}: ${t.topic_title}</option>`);
    });
}
function checkAssFileSize(input){
    const warn=document.getElementById('assFileSizeWarning');
    if(input.files && input.files[0] && input.files[0].size > 8*1024*1024){
        warn.style.display='block';
    } else {
        warn.style.display='none';
    }
}
function validateAssForm(){
    const f=document.getElementById('assAttachment');
    if(f.files && f.files[0] && f.files[0].size > 25*1024*1024){
        alert('This file is way too large (over 25MB) and will not upload. Please choose a smaller file.');
        return false;
    }
    return true;
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
