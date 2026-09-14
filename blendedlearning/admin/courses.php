<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Manage Courses';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $dept = (int)$_POST['department_id'];
        $code = sanitize($_POST['course_code']);
        $name = sanitize($_POST['course_name']);
        $desc = sanitize($_POST['description']);
        $units = (int)$_POST['units'];
        $yl = (int)$_POST['year_level'];
        $sem = sanitize($_POST['semester']);
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO courses (department_id,course_code,course_name,description,units,year_level,semester) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('isssiss',$dept,$code,$name,$desc,$units,$yl,$sem);
            $stmt->execute();
            setFlash('success','Course added.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE courses SET department_id=?,course_code=?,course_name=?,description=?,units=?,year_level=?,semester=? WHERE id=?");
            $stmt->bind_param('isssissi',$dept,$code,$name,$desc,$units,$yl,$sem,$id);
            $stmt->execute();
            setFlash('success','Course updated.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM courses WHERE id=$id");
        setFlash('success','Course deleted.');
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE courses SET status=IF(status='active','inactive','active') WHERE id=$id");
    }
    redirect(BASE_URL.'admin/courses.php');
}

$courses = $conn->query("SELECT c.*, d.name as dept_name FROM courses c JOIN departments d ON c.department_id=d.id ORDER BY d.name,c.course_code");
$depts = $conn->query("SELECT * FROM departments ORDER BY name");
$deptsArr = []; while($d=$depts->fetch_assoc()) $deptsArr[] = $d;
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Course Management</h2><p>Manage all courses</p></div>
    <button class="btn btn-primary" onclick="openModal('addCourseModal')"><i class="fas fa-plus"></i> Add Course</button>
</div>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>Code</th><th>Course Name</th><th>Department</th><th>Units</th><th>Year/Sem</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php while($c=$courses->fetch_assoc()): ?>
<tr>
    <td><code><?= htmlspecialchars($c['course_code']) ?></code></td>
    <td><?= htmlspecialchars($c['course_name']) ?></td>
    <td><?= htmlspecialchars($c['dept_name']) ?></td>
    <td><?= $c['units'] ?> units</td>
    <td>Year <?= $c['year_level'] ?> / <?= $c['semester'] ?></td>
    <td><span class="badge <?= $c['status']==='active'?'badge-green':'badge-gray' ?>"><?= $c['status'] ?></span></td>
    <td><div class="action-btns">
        <button class="btn btn-secondary btn-sm" onclick='editCourse(<?= json_encode($c) ?>)'><i class="fas fa-edit"></i></button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Add/Edit Course Modal -->
<div class="modal-overlay" id="addCourseModal">
<div class="modal">
<div class="modal-header"><span class="modal-title" id="courseModalTitle">Add Course</span><button class="modal-close" onclick="closeModal('addCourseModal')">&times;</button></div>
<form method="POST" id="courseForm">
<input type="hidden" name="action" value="add" id="courseAction">
<input type="hidden" name="id" id="courseId">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Course Code</label><input type="text" name="course_code" id="cCode" class="form-control" required></div>
        <div class="form-group"><label>Department</label>
        <select name="department_id" id="cDept" class="form-control" required>
            <?php foreach($deptsArr as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="form-group"><label>Course Name</label><input type="text" name="course_name" id="cName" class="form-control" required></div>
    <div class="form-group"><label>Description</label><textarea name="description" id="cDesc" class="form-control"></textarea></div>
    <div class="form-row three">
        <div class="form-group"><label>Units</label><input type="number" name="units" id="cUnits" class="form-control" value="3" min="1" max="9"></div>
        <div class="form-group"><label>Year Level</label><select name="year_level" id="cYear" class="form-control"><option>1</option><option>2</option><option>3</option><option>4</option></select></div>
        <div class="form-group"><label>Semester</label><select name="semester" id="cSem" class="form-control"><option>1st</option><option>2nd</option><option>Summer</option></select></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addCourseModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
</div>
</form>
</div>
</div>
</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function editCourse(c){
    document.getElementById('courseModalTitle').textContent='Edit Course';
    document.getElementById('courseAction').value='edit';
    document.getElementById('courseId').value=c.id;
    document.getElementById('cCode').value=c.course_code;
    document.getElementById('cName').value=c.course_name;
    document.getElementById('cDesc').value=c.description;
    document.getElementById('cUnits').value=c.units;
    document.getElementById('cDept').value=c.department_id;
    document.getElementById('cYear').value=c.year_level;
    document.getElementById('cSem').value=c.semester;
    openModal('addCourseModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
