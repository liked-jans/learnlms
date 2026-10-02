<?php
require_once '../includes/config.php';
requireRole('teacher');
$pageTitle = 'My Syllabi';
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $cid=(int)$_POST['course_id']; $ay=sanitize($_POST['academic_year']); $sem=sanitize($_POST['semester']);
        $desc=sanitize($_POST['course_description']); $outcomes=sanitize($_POST['course_outcomes']);
        $stmt=$conn->prepare("INSERT INTO syllabi (course_id,teacher_id,academic_year,semester,course_description,course_outcomes) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iissss',$cid,$tid,$ay,$sem,$desc,$outcomes); $stmt->execute();
        $newId = $conn->insert_id;
        setFlash('success','Syllabus created! Now add topics.');
        redirect(BASE_URL.'teacher/syllabus_edit.php?id='.$newId);
    } elseif ($action === 'status') {
        $id=(int)$_POST['id']; $status=sanitize($_POST['status']);
        $stmt=$conn->prepare("UPDATE syllabi SET status=? WHERE id=? AND teacher_id=?");
        $stmt->bind_param('sii',$status,$id,$tid); $stmt->execute();
        setFlash('success','Status updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $chk = $conn->query("SELECT id FROM syllabi WHERE id=$id AND teacher_id=$tid")->fetch_assoc();
        if ($chk) {
            deleteSyllabusCascade($id);
            if (function_exists('logActivity')) {
                logActivity($tid, "Teacher deleted syllabus ID {$id} and all associated topics, materials, and student progress", 'Syllabus');
            }
            setFlash('success','Syllabus and all associated student progress deleted.');
        } else {
            setFlash('error','Unauthorized to delete this syllabus.');
        }
    }
    redirect(BASE_URL.'teacher/syllabi.php');
}

$syllabi = $conn->query("SELECT s.*,c.course_name,c.course_code,
    (SELECT COUNT(*) FROM syllabus_topics st WHERE st.syllabus_id=s.id) topics,
    (SELECT COUNT(*) FROM enrollments e WHERE e.syllabus_id=s.id) students
    FROM syllabi s JOIN courses c ON s.course_id=c.id WHERE s.teacher_id=$tid ORDER BY s.created_at DESC");
// Group courses by department for optgroup display
$deptCoursesRaw = $conn->query("SELECT c.*, d.name as dept_name, d.code as dept_code FROM courses c JOIN departments d ON c.department_id=d.id WHERE c.status='active' ORDER BY d.name, c.year_level, c.course_code");
$groupedCourses = [];
while($row = $deptCoursesRaw->fetch_assoc()) {
    $groupedCourses[$row['dept_name']][] = $row;
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>My Syllabi</h2><p>Create and manage your course syllabi</p></div>
    <button class="btn btn-primary" onclick="openModal('addSylModal')"><i class="fas fa-plus"></i> Create Syllabus</button>
</div>

<?php if ($syllabi->num_rows === 0): ?>
<div class="empty-state card">
    <i class="fas fa-file-alt"></i>
    <h3>No syllabi yet</h3>
    <p>Click <strong>Create Syllabus</strong> to build your first course map.</p>
</div>
<?php else: ?>
<div class="syllabus-grid">
<?php while($s=$syllabi->fetch_assoc()):
$sc=['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange']; ?>
<div class="card">
    <div class="card-body">
        <div class="syllabus-card-top">
            <div class="syllabus-card-info">
                <?php if (!empty($s['image_path'])): ?>
                <img src="<?= BASE_URL ?>uploads/syllabi/<?= htmlspecialchars($s['image_path']) ?>" class="syllabus-thumb" alt="">
                <?php endif; ?>
                <div style="min-width:0">
                    <span class="syllabus-code"><?= htmlspecialchars($s['course_code']) ?></span>
                    <h3 class="syllabus-title"><?= htmlspecialchars($s['course_name']) ?></h3>
                    <p class="syllabus-period"><?= htmlspecialchars($s['academic_year']) ?> &bull; <?= htmlspecialchars($s['semester']) ?> Sem</p>
                </div>
            </div>
            <span class="badge <?= $sc[$s['status']] ?>"><?= htmlspecialchars($s['status']) ?></span>
        </div>

        <?php if (!empty($s['external_url'])): ?>
        <a href="<?= htmlspecialchars($s['external_url']) ?>" target="_blank" class="btn btn-secondary btn-sm syllabus-resource-link">
            <i class="fas fa-external-link-alt"></i> Open Resource
        </a>
        <?php endif; ?>

        <div class="syllabus-stats">
            <div class="syllabus-stat">
                <div class="syllabus-stat-num"><?= $s['topics'] ?></div>
                <div class="syllabus-stat-label">Topics</div>
            </div>
            <div class="syllabus-stat info">
                <div class="syllabus-stat-num"><?= $s['students'] ?></div>
                <div class="syllabus-stat-label">Students</div>
            </div>
        </div>
        <div class="syllabus-card-actions">
            <a href="syllabus_edit.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;justify-content:center"><i class="fas fa-edit"></i> Edit & Map</a>
            <form method="POST" style="display:contents">
                <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <select name="status" class="form-control syllabus-status-select" onchange="this.form.submit()">
                    <option <?= $s['status']==='draft'?'selected':'' ?>>draft</option>
                    <option <?= $s['status']==='published'?'selected':'' ?>>published</option>
                    <option <?= $s['status']==='archived'?'selected':'' ?>>archived</option>
                </select>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this syllabus?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </div>
</div>
<?php endwhile; ?>
</div>
<?php endif; ?>

<!-- Create Syllabus Modal -->
<div class="modal-overlay" id="addSylModal">
<div class="modal" style="max-width:680px">
<div class="modal-header"><span class="modal-title">Create New Syllabus</span><button class="modal-close" onclick="closeModal('addSylModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Course</label>
        <select name="course_id" class="form-control" required>
            <option value="">-- Select a course --</option>
            <?php foreach($groupedCourses as $deptName => $deptCourses): ?>
            <optgroup label="<?= htmlspecialchars($deptName) ?>">
                <?php foreach($deptCourses as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['course_code'].' - '.$c['course_name']) ?> (Yr <?= $c['year_level'] ?>, <?= $c['semester'] ?> Sem)</option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Academic Year</label><input type="text" name="academic_year" class="form-control" placeholder="e.g. 2024-2025" required></div>
    </div>
    <div class="form-group"><label>Semester</label>
    <select name="semester" class="form-control" required><option>1st</option><option>2nd</option><option>Summer</option></select></div>
    <div class="form-group"><label>Course Description</label><textarea name="course_description" class="form-control" rows="3"></textarea></div>
    <div class="form-group"><label>Course Outcomes/Objectives</label><textarea name="course_outcomes" class="form-control" rows="3" placeholder="What students will learn..."></textarea></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addSylModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Create & Add Topics</button>
</div>
</form></div></div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>