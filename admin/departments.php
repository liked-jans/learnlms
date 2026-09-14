<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Departments';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $desc = sanitize($_POST['description']);
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO departments (name,code,description) VALUES (?,?,?)");
            $stmt->bind_param('sss',$name,$code,$desc); $stmt->execute();
            setFlash('success','Department added.');
        } else {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE departments SET name=?,code=?,description=? WHERE id=?");
            $stmt->bind_param('sssi',$name,$code,$desc,$id); $stmt->execute();
            setFlash('success','Department updated.');
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM departments WHERE id=$id");
        setFlash('success','Department deleted.');
    }
    redirect(BASE_URL.'admin/departments.php');
}
$depts = $conn->query("SELECT d.*, COUNT(c.id) as course_count FROM departments d LEFT JOIN courses c ON d.id=c.department_id GROUP BY d.id ORDER BY d.name");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header">
    <div class="page-header-left"><h2>Departments</h2><p>Manage academic departments</p></div>
    <button class="btn btn-primary" onclick="openModal('deptModal')"><i class="fas fa-plus"></i> Add Department</button>
</div>
<div class="card">
<div class="table-wrap"><table>
<thead><tr><th>Code</th><th>Department Name</th><th>Description</th><th>Courses</th><th>Actions</th></tr></thead>
<tbody>
<?php while($d=$depts->fetch_assoc()): ?>
<tr>
    <td><span class="badge badge-green"><?= htmlspecialchars($d['code']) ?></span></td>
    <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
    <td><?= htmlspecialchars($d['description']) ?></td>
    <td><?= $d['course_count'] ?> courses</td>
    <td><div class="action-btns">
        <button class="btn btn-secondary btn-sm" onclick='editDept(<?= json_encode($d) ?>)'><i class="fas fa-edit"></i></button>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete? This will delete all associated courses.')">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $d['id'] ?>">
        <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button></form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table></div>
</div>

<div class="modal-overlay" id="deptModal">
<div class="modal">
<div class="modal-header"><span class="modal-title" id="deptModalTitle">Add Department</span><button class="modal-close" onclick="closeModal('deptModal')">&times;</button></div>
<form method="POST"><input type="hidden" name="action" value="add" id="deptAction"><input type="hidden" name="id" id="deptId">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Department Name</label><input type="text" name="name" id="dName" class="form-control" required></div>
        <div class="form-group"><label>Code</label><input type="text" name="code" id="dCode" class="form-control" required maxlength="20"></div>
    </div>
    <div class="form-group"><label>Description</label><textarea name="description" id="dDesc" class="form-control"></textarea></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('deptModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
</div>
</form></div></div>
</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function editDept(d){
    document.getElementById('deptModalTitle').textContent='Edit Department';
    document.getElementById('deptAction').value='edit';
    document.getElementById('deptId').value=d.id;
    document.getElementById('dName').value=d.name;
    document.getElementById('dCode').value=d.code;
    document.getElementById('dDesc').value=d.description;
    openModal('deptModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
