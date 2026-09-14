<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Admin Dashboard';

// Handle maintenance mode toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_maintenance') {
    $current = isMaintenanceMode() ? 1 : 0;
    $newState = $current ? 0 : 1;
    $conn->query("UPDATE system_settings SET maintenance_mode = $newState WHERE id = 1");
    setFlash('success', $newState ? 'Maintenance mode is now ON. Only admins can access the system.' : 'Maintenance mode is now OFF. The system is live for everyone.');
    redirect(BASE_URL . 'admin/dashboard.php');
}

$maintenanceOn = isMaintenanceMode();

$stats = [];
$stats['users'] = $conn->query("SELECT COUNT(*) c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$stats['teachers'] = $conn->query("SELECT COUNT(*) c FROM users WHERE role='teacher'")->fetch_assoc()['c'];
$stats['students'] = $conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'];
$stats['courses'] = $conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'];
$stats['syllabi'] = $conn->query("SELECT COUNT(*) c FROM syllabi")->fetch_assoc()['c'];
$stats['published'] = $conn->query("SELECT COUNT(*) c FROM syllabi WHERE status='published'")->fetch_assoc()['c'];

$recentSyllabi = $conn->query("SELECT s.*, c.course_name, c.course_code, u.full_name as teacher_name FROM syllabi s JOIN courses c ON s.course_id=c.id JOIN users u ON s.teacher_id=u.id ORDER BY s.created_at DESC LIMIT 8");

$recentUsers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 6");
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<?php if ($flash = getFlash()): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?>" style="margin-bottom:16px">
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<!-- Maintenance Mode Control -->
<div class="card" style="margin-bottom:20px;border:1px solid <?= $maintenanceOn ? '#f59e0b' : 'var(--border)' ?>">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="font-size:24px;color:<?= $maintenanceOn ? '#f59e0b' : 'var(--text3)' ?>">
                <i class="fas fa-tools"></i>
            </div>
            <div>
                <div style="font-weight:600;font-size:15px">
                    Maintenance Mode:
                    <span class="badge <?= $maintenanceOn ? 'badge-orange' : 'badge-green' ?>"><?= $maintenanceOn ? 'ON' : 'OFF' ?></span>
                </div>
                <div style="font-size:12px;color:var(--text3);margin-top:2px">
                    <?php if ($maintenanceOn): ?>
                        Teachers and students are currently locked out. Only admins can use the system.
                    <?php else: ?>
                        The system is live and accessible to everyone.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <form method="POST" onsubmit="return confirm('<?= $maintenanceOn ? 'Turn OFF maintenance mode? The system will be live for everyone again.' : 'Turn ON maintenance mode? Teachers and students will be locked out immediately.' ?>')">
            <input type="hidden" name="action" value="toggle_maintenance">
            <button type="submit" class="btn <?= $maintenanceOn ? 'btn-success' : 'btn-danger' ?> btn-sm">
                <?php if ($maintenanceOn): ?>
                    <i class="fas fa-play"></i> Turn Off Maintenance Mode
                <?php else: ?>
                    <i class="fas fa-power-off"></i> Turn On Maintenance Mode
                <?php endif; ?>
            </button>
        </form>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-users"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['users'] ?></div><div class="stat-label">Total Users</div></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['teachers'] ?></div><div class="stat-label">Teachers</div></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['students'] ?></div><div class="stat-label">Students</div></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-book"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['courses'] ?></div><div class="stat-label">Courses</div></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['syllabi'] ?></div><div class="stat-label">Syllabi</div></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon orange"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info"><div class="stat-num"><?= $stats['published'] ?></div><div class="stat-label">Published</div></div>
    </div>
</div>

<div class="dash-grid">
<div>
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-file-alt" style="color:var(--primary);margin-right:8px"></i>Recent Syllabi</span>
        <a href="syllabi.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Course</th><th>Teacher</th><th>Year/Sem</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php while($row = $recentSyllabi->fetch_assoc()): ?>
        <tr>
            <td><strong><?= htmlspecialchars($row['course_code']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($row['course_name']) ?></small></td>
            <td><?= htmlspecialchars($row['teacher_name']) ?></td>
            <td><?= $row['academic_year'] ?> / <?= $row['semester'] ?></td>
            <td><?php
                $sc = ['draft'=>'badge-gray','published'=>'badge-green','archived'=>'badge-orange'];
                echo '<span class="badge '.$sc[$row['status']].'">'.ucfirst($row['status']).'</span>';
            ?></td>
            <td><a href="syllabi_view.php?id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>
</div>
<div>
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fas fa-users" style="color:var(--info);margin-right:8px"></i>Recent Users</span>
        <a href="users.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="card-body" style="padding:0">
    <?php while($u = $recentUsers->fetch_assoc()): 
        $ini = substr(strtoupper($u['full_name']),0,2); ?>
    <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border)">
        <div class="avatar-sm"><?= $ini ?></div>
        <div style="flex:1">
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($u['full_name']) ?></div>
            <div style="font-size:12px;color:var(--text3)"><?= $u['email'] ?></div>
        </div>
        <?php $rc = ['teacher'=>'badge-blue','student'=>'badge-green']; ?>
        <span class="badge <?= $rc[$u['role']] ?>"><?= ucfirst($u['role']) ?></span>
    </div>
    <?php endwhile; ?>
    </div>
</div>
</div>
</div>
</div>
</div>
</div>
</body></html>