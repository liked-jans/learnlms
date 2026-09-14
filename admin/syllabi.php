<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'All Syllabi';
ensureColumnExists('syllabi', 'syllabus_file', "varchar(255) DEFAULT NULL AFTER image_path");
ensureColumnExists('syllabus_topics', 'is_completed', "TINYINT(1) NOT NULL DEFAULT 0");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'status') {
        $id=(int)$_POST['id']; $status=sanitize($_POST['status']);
        $stmt=$conn->prepare("UPDATE syllabi SET status=? WHERE id=?"); $stmt->bind_param('si',$status,$id); $stmt->execute();
        setFlash('success','Status updated.');

    } elseif ($action === 'delete') {
        $id=(int)$_POST['id'];
        $conn->query("DELETE FROM syllabi WHERE id=$id");
        setFlash('success','Syllabus deleted.');

    } elseif ($action === 'add' || $action === 'edit') {
        $courseId      = (int)$_POST['course_id'];
        $teacherId     = (int)$_POST['teacher_id'];
        $academicYear  = sanitize($_POST['academic_year']);
        $semester      = sanitize($_POST['semester']);
        $description   = sanitize($_POST['course_description']);
        $outcomes      = sanitize($_POST['course_outcomes']);
        $status        = sanitize($_POST['status']);
        $externalUrl   = trim($_POST['external_url'] ?? '');

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

        // Keep the previously-uploaded syllabus document unless a new one is chosen
        $syllabusFile = $_POST['existing_syllabus_file'] ?? null;
        $syllabusFile = $syllabusFile !== '' ? $syllabusFile : null;

        if (!empty($_FILES['syllabus_file']['name'])) {
            $docExt = strtolower(pathinfo($_FILES['syllabus_file']['name'], PATHINFO_EXTENSION));
            $allowedDocExt = ['pdf','doc','docx'];
            if (in_array($docExt, $allowedDocExt) && $_FILES['syllabus_file']['error'] === UPLOAD_ERR_OK) {
                $docDestDir = '../uploads/syllabus_docs/';
                if (!is_dir($docDestDir)) mkdir($docDestDir, 0755, true);
                $docNewName = 'sylfile_' . uniqid() . '.' . $docExt;
                if (move_uploaded_file($_FILES['syllabus_file']['tmp_name'], $docDestDir . $docNewName)) {
                    $syllabusFile = $docNewName;
                } else {
                    setFlash('error', 'Syllabus document upload failed, but the rest of the syllabus was saved.');
                }
            } else {
                setFlash('error', 'Invalid syllabus document type. Allowed: pdf, doc, docx.');
            }
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO syllabi
                (course_id, teacher_id, academic_year, semester, course_description, course_outcomes, status, image_path, external_url, syllabus_file)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('iissssssss', $courseId, $teacherId, $academicYear, $semester, $description, $outcomes, $status, $imagePath, $externalUrl, $syllabusFile);
            $stmt->execute();
            setFlash('success', 'Syllabus created.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE syllabi SET
                course_id=?, teacher_id=?, academic_year=?, semester=?, course_description=?,
                course_outcomes=?, status=?, image_path=?, external_url=?, syllabus_file=?
                WHERE id=?");
            $stmt->bind_param('iissssssssi', $courseId, $teacherId, $academicYear, $semester, $description, $outcomes, $status, $imagePath, $externalUrl, $syllabusFile, $id);
            $stmt->execute();
            setFlash('success', 'Syllabus updated.');
        }
    }
    redirect(BASE_URL.'admin/syllabi.php');
}

$syllabi = $conn->query("SELECT s.*, c.course_name, c.course_code, u.full_name as teacher_name,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) as topic_count,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id AND st.is_completed=1) as completed_count,
    (SELECT COUNT(*) FROM enrollments e WHERE e.syllabus_id=s.id) as student_count
    FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id ORDER BY s.created_at DESC");

$courses = $conn->query("SELECT id, course_code, course_name FROM courses WHERE status='active' ORDER BY course_code");
$coursesArr = []; while($c = $courses->fetch_assoc()) $coursesArr[] = $c;

$teachers = $conn->query("SELECT id, full_name FROM users WHERE role='teacher' AND status='active' ORDER BY full_name");
$teachersArr = []; while($t = $teachers->fetch_assoc()) $teachersArr[] = $t;
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>All Syllabi</h2><p>View and manage all course syllabi</p></div>
    <div class="page-header-right">
        <button class="btn btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Add Syllabus</button>
    </div>
</div>
<div class="card">
<div class="table-wrap"><table>
<thead><tr><th>Image</th><th>Course</th><th>Teacher</th><th>Year/Semester</th><th>Topics</th><th>Progress</th><th>Students</th><th>Document</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php while($s=$syllabi->fetch_assoc()):
    $topicCount = (int)$s['topic_count'];
    $completedCount = (int)$s['completed_count'];
    $pct = $topicCount > 0 ? round($completedCount / $topicCount * 100) : 0;
?>
<tr>
    <td>
        <?php if (!empty($s['image_path'] ?? null)): ?>
            <img src="<?= BASE_URL ?>uploads/syllabi/<?= htmlspecialchars($s['image_path']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
        <?php else: ?>
            <div style="width:48px;height:48px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;color:var(--text3)"><i class="fas fa-file-alt"></i></div>
        <?php endif; ?>
    </td>
    <td>
        <strong><?= htmlspecialchars($s['course_code']) ?></strong>
        <?php if (!empty($s['external_url'] ?? null)): ?>
            <a href="<?= htmlspecialchars($s['external_url']) ?>" target="_blank" title="External Link" style="margin-left:4px"><i class="fas fa-link" style="font-size:11px;color:var(--primary)"></i></a>
        <?php endif; ?>
        <br><small class="text-muted"><?= htmlspecialchars($s['course_name']) ?></small>
    </td>
    <td><?= htmlspecialchars($s['teacher_name']) ?></td>
    <td><?= $s['academic_year'] ?> / <?= $s['semester'] ?> Sem</td>
    <td><span class="badge badge-blue"><?= $topicCount ?> topics</span></td>
    <td style="min-width:120px">
        <?php if ($topicCount > 0): ?>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text3);margin-bottom:4px">
            <span><?= $completedCount ?>/<?= $topicCount ?> done</span>
            <span><?= $pct ?>%</span>
        </div>
        <div class="progress-bar" style="height:8px"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
        <?php else: ?>
            <span class="text-muted"><small>No topics yet</small></span>
        <?php endif; ?>
    </td>
    <td><span class="badge badge-green"><?= $s['student_count'] ?> students</span></td>
    <td>
        <?php if (!empty($s['syllabus_file'] ?? null)): ?>
            <a href="<?= BASE_URL ?>uploads/syllabus_docs/<?= htmlspecialchars($s['syllabus_file']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="View syllabus document"><i class="fas fa-file-pdf"></i> View</a>
        <?php else: ?>
            <span class="text-muted"><small>No file</small></span>
        <?php endif; ?>
    </td>
    <td><?php $sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange'];
        echo '<span class="badge '.$sc[$s['status']].'">'.ucfirst($s['status']).'</span>'; ?></td>
    <td><div class="action-btns">
        <a href="syllabi_view.php?id=<?= $s['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
        <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'><i class="fas fa-edit"></i></button>
        <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <select name="status" class="form-control" style="width:110px;display:inline;padding:6px 8px;font-size:12px" onchange="this.form.submit()">
                <option <?= $s['status']==='draft'?'selected':'' ?>>draft</option>
                <option <?= $s['status']==='published'?'selected':'' ?>>published</option>
                <option <?= $s['status']==='archived'?'selected':'' ?>>archived</option>
            </select>
        </form>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')">
            <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
            <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
        </form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div>
</div>

<!-- Add / Edit Syllabus Modal -->
<div class="modal-overlay" id="syllabusModal">
<div class="modal" style="max-width:560px">
    <div class="modal-header">
        <span class="modal-title" id="syllabusModalTitle"><i class="fas fa-file-alt"></i> Add Syllabus</span>
        <button class="modal-close" onclick="closeModal('syllabusModal')">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">
        <input type="hidden" name="existing_image" id="formExistingImage" value="">
        <input type="hidden" name="existing_syllabus_file" id="formExistingSyllabusFile" value="">
        <div class="modal-body">
            <div class="form-group">
                <label>Course</label>
                <select name="course_id" id="formCourse" class="form-control" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($coursesArr as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'].' - '.$c['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Teacher</label>
                <select name="teacher_id" id="formTeacher" class="form-control" required>
                    <option value="">-- Select Teacher --</option>
                    <?php foreach ($teachersArr as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:12px">
                <div class="form-group" style="flex:1">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" id="formYear" class="form-control" placeholder="2025-2026" required>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Semester</label>
                    <select name="semester" id="formSemester" class="form-control" required>
                        <option value="1st">1st</option>
                        <option value="2nd">2nd</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Course Description</label>
                <textarea name="course_description" id="formDescription" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Course Outcomes</label>
                <textarea name="course_outcomes" id="formOutcomes" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="formStatus" class="form-control">
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="archived">archived</option>
                </select>
            </div>
            <div class="form-group">
                <label>Cover Image <span style="color:var(--text3);font-weight:400">(optional)</span></label>
                <input type="file" name="image" id="formImage" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                <div id="formImagePreviewWrap" style="margin-top:8px;display:none">
                    <img id="formImagePreview" src="" style="max-height:80px;border-radius:8px;border:1px solid var(--border)">
                </div>
            </div>
            <div class="form-group">
                <label>Syllabus Document <span style="color:var(--text3);font-weight:400">(optional, PDF/DOC/DOCX)</span></label>
                <input type="file" name="syllabus_file" id="formSyllabusFile" class="form-control" accept=".pdf,.doc,.docx">
                <div id="formSyllabusFilePreviewWrap" style="margin-top:8px;display:none">
                    <a href="#" id="formSyllabusFilePreview" target="_blank" class="btn btn-secondary btn-sm"><i class="fas fa-file-pdf"></i> View current file</a>
                </div>
            </div>
            <div class="form-group">
                <label>External URL <span style="color:var(--text3);font-weight:400">(optional)</span></label>
                <input type="url" name="external_url" id="formUrl" class="form-control" placeholder="https://...">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('syllabusModal')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Syllabus</button>
        </div>
    </form>
</div>
</div>

</div></div></div>
<script>
var BASE_URL = <?= json_encode(BASE_URL) ?>;

function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(function(m){
    m.addEventListener('click', function(e){ if (e.target === this) this.classList.remove('open'); });
});

function resetSyllabusForm(){
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('formExistingImage').value = '';
    document.getElementById('formExistingSyllabusFile').value = '';
    document.getElementById('formCourse').value = '';
    document.getElementById('formTeacher').value = '';
    document.getElementById('formYear').value = '';
    document.getElementById('formSemester').value = '1st';
    document.getElementById('formDescription').value = '';
    document.getElementById('formOutcomes').value = '';
    document.getElementById('formStatus').value = 'draft';
    document.getElementById('formUrl').value = '';
    document.getElementById('formImage').value = '';
    document.getElementById('formImagePreviewWrap').style.display = 'none';
    document.getElementById('formSyllabusFile').value = '';
    document.getElementById('formSyllabusFilePreviewWrap').style.display = 'none';
}

function openAddModal(){
    resetSyllabusForm();
    document.getElementById('syllabusModalTitle').innerHTML = '<i class="fas fa-file-alt"></i> Add Syllabus';
    openModal('syllabusModal');
}

function openEditModal(s){
    resetSyllabusForm();
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = s.id;
    document.getElementById('formExistingImage').value = s.image_path || '';
    document.getElementById('formExistingSyllabusFile').value = s.syllabus_file || '';
    document.getElementById('formCourse').value = s.course_id;
    document.getElementById('formTeacher').value = s.teacher_id;
    document.getElementById('formYear').value = s.academic_year;
    document.getElementById('formSemester').value = s.semester;
    document.getElementById('formDescription').value = s.course_description || '';
    document.getElementById('formOutcomes').value = s.course_outcomes || '';
    document.getElementById('formStatus').value = s.status;
    document.getElementById('formUrl').value = s.external_url || '';

    if (s.image_path) {
        document.getElementById('formImagePreview').src = BASE_URL + 'uploads/syllabi/' + s.image_path;
        document.getElementById('formImagePreviewWrap').style.display = 'block';
    }

    if (s.syllabus_file) {
        document.getElementById('formSyllabusFilePreview').href = BASE_URL + 'uploads/syllabus_docs/' + s.syllabus_file;
        document.getElementById('formSyllabusFilePreviewWrap').style.display = 'block';
    }

    document.getElementById('syllabusModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Syllabus';
    openModal('syllabusModal');
}
</script>
</body></html>