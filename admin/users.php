<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Manage Users';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $full_name = sanitize($_POST['full_name']);
        $role = sanitize($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $chk = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $chk->bind_param('ss',$username,$email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            setFlash('error','Username or email already exists.');
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username,password,full_name,email,role) VALUES (?,?,?,?,?)");
            $stmt->bind_param('sssss',$username,$password,$full_name,$email,$role);
            $stmt->execute();
            setFlash('success','User added successfully.');
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE users SET status=IF(status='active','inactive','active') WHERE id=$id AND role!='admin'");
        setFlash('success','User status updated.');
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role!='admin'");
        setFlash('success','User deleted.');
    } elseif ($action === 'reset_pass') {
        $id = (int)$_POST['id'];
        $newpass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $conn->prepare("UPDATE users SET password=? WHERE id=?")->bind_param('si',$newpass,$id) && true;
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si',$newpass,$id);
        $stmt->execute();
        setFlash('success','Password reset successfully.');
    }
    redirect(BASE_URL.'admin/users.php');
}

$filter = sanitize($_GET['role'] ?? 'all');
$search = sanitize($_GET['q'] ?? '');
$where = "role != 'admin'";
if ($filter !== 'all') $where .= " AND role='$filter'";
if ($search) $where .= " AND (full_name LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%')";
$users = $conn->query("SELECT * FROM users WHERE $where ORDER BY created_at DESC");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div class="page-header-left">
        <h2>User Management</h2>
        <p>Manage teachers and students</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('addUserModal')">
        <i class="fas fa-plus"></i> Add User
    </button>
</div>

<div class="card" style="margin-bottom:20px">
<div class="card-body" style="padding:16px 20px">
<form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
    <div class="search-bar" style="flex:1;min-width:200px">
        <i class="fas fa-search"></i>
        <input type="text" name="q" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <select name="role" class="form-control" style="width:150px" onchange="this.form.submit()">
        <option value="all" <?= $filter==='all'?'selected':'' ?>>All Roles</option>
        <option value="teacher" <?= $filter==='teacher'?'selected':'' ?>>Teachers</option>
        <option value="student" <?= $filter==='student'?'selected':'' ?>>Students</option>
    </select>
    <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
</form>
</div>
</div>
<div class="card">
<div class="table-wrap">
<table>
<thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
<tbody>
<?php $i=1; while($u=$users->fetch_assoc()): ?>
<tr>
    <td><?= $i++ ?></td>
    <td><div style="display:flex;align-items:center;gap:8px">
        <div class="avatar-sm"><?= strtoupper(substr($u['full_name'],0,2)) ?></div>
        <?= htmlspecialchars($u['full_name']) ?>
    </div></td>
    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?php $rc=['teacher'=>'badge-blue','student'=>'badge-green']; echo '<span class="badge '.$rc[$u['role']].'">'.ucfirst($u['role']).'</span>'; ?></td>
    <td><span class="badge <?= $u['status']==='active'?'badge-green':'badge-red' ?>"><?= $u['status'] ?></span></td>
    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
    <td><div class="action-btns">
        <button class="btn btn-secondary btn-sm" onclick='editUser(<?= json_encode($u) ?>)' title="Edit"><i class="fas fa-edit"></i></button>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button class="btn btn-warning btn-sm" title="Toggle Status"><i class="fas fa-toggle-on"></i></button></form>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $u['id'] ?>">
        <button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button></form>
    </div></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
<div class="modal">
<div class="modal-header"><span class="modal-title">Add New User</span><button class="modal-close" onclick="closeModal('addUserModal')">&times;</button></div>
<form method="POST">
<input type="hidden" name="action" value="add">
<div class="modal-body">
    <div class="form-row">
        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" required></div>
        <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" required></div>
    </div>
    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="form-row">
        <div class="form-group"><label>Role</label><select name="role" class="form-control" required>
            <option value="teacher">Teacher</option><option value="student">Student</option></select></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
</div>
</form>
</div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetPassModal">
<div class="modal">
<div class="modal-header"><span class="modal-title">Reset Password</span><button class="modal-close" onclick="closeModal('resetPassModal')">&times;</button></div>
<form method="POST">
<input type="hidden" name="action" value="reset_pass">
<input type="hidden" name="id" id="reset_user_id">
<div class="modal-body">
    <div class="form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeModal('resetPassModal')">Cancel</button>
    <button type="submit" class="btn btn-primary">Reset Password</button>
</div>
</form>
</div>
</div>

</div></div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
function editUser(u){
    document.getElementById('reset_user_id').value=u.id;
    openModal('resetPassModal');
}
document.querySelectorAll('.modal-overlay').forEach(m=>m.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open');}));
</script>
</body></html>
