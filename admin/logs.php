<?php
require_once '../includes/config.php';
requireRole('admin');
$pageTitle = 'Activity Logs';

// ---------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------
$category = $_GET['category'] ?? '';
$search   = trim($_GET['search'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$where  = [];
$params = [];
$types  = '';

if ($category !== '') {
    $where[] = "al.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR al.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Total count for pagination
$countSql = "SELECT COUNT(*) as c FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id $whereSql";
$countStmt = $conn->prepare($countSql);
if ($types !== '') { $countStmt->bind_param($types, ...$params); }
$countStmt->execute();
$totalLogs = (int)$countStmt->get_result()->fetch_assoc()['c'];
$totalPages = max(1, ceil($totalLogs / $perPage));

// Page of results
$sql = "
    SELECT al.*, u.full_name, u.username, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    $whereSql
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$bindTypes = $types . 'ii';
$bindParams = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($bindTypes, ...$bindParams);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Distinct categories for the filter dropdown
$categories = [];
$catRes = $conn->query("SELECT DISTINCT category FROM activity_logs ORDER BY category");
if ($catRes) { while ($row = $catRes->fetch_assoc()) { $categories[] = $row['category']; } }

// Color-code Authentication rows by what happened
function logBadge($category, $description) {
    if ($category === 'Authentication') {
        $desc = strtolower($description);
        if (strpos($desc, 'timed out') !== false || strpos($desc, 'timeout') !== false) {
            return ['Timeout', 'badge-amber'];
        }
        if (strpos($desc, 'logged out') !== false || strpos($desc, 'logout') !== false) {
            return ['Logout', 'badge-gray'];
        }
        if (strpos($desc, 'logged in') !== false || strpos($desc, 'login') !== false) {
            return ['Login', 'badge-green'];
        }
    }
    return [$category, 'badge-gray'];
}
?>
<?php require_once '../includes/header.php'; ?>
<div class="app-layout">
<?php require_once '../includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once '../includes/topbar.php'; ?>
<div class="page-content">

<div class="page-header">
    <div class="page-header-left"><h2>Activity Logs</h2><p>Login, logout, session timeout, and system activity across BlendEd LMS.</p></div>
</div>

<div class="card filter-card" style="margin-bottom:1.5rem; padding:1.5rem;">
    <form method="GET" class="filter-grid" style="display:grid; grid-template-columns:1fr 2fr; gap:1.5rem;">
        <div class="form-group">
            <label class="filter-label">Category</label>
            <select name="category" class="form-control filter-select" onchange="this.form.submit()">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $category===$c?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="filter-label">Search user or description</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="e.g. Juan Dela Cruz, logged in..." class="form-control filter-input">
        </div>
        <div class="form-group" style="grid-column:1 / -1; display:flex; gap:.75rem; margin-top:.5rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($category !== '' || $search !== ''): ?>
                <a href="logs.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>
.filter-label{
    display:block;
    font-size:.72rem;
    font-weight:700;
    letter-spacing:.06em;
    text-transform:uppercase;
    color:#6b7c73;
    margin-bottom:.5rem;
}
.filter-select, .filter-input{
    width:100%;
    border:1.5px solid #dfe6e2;
    border-radius:12px;
    padding:.85rem 1rem;
    font-size:.95rem;
    background:#fff;
}
.filter-select:focus, .filter-input:focus{
    outline:none;
    border-color:#1f5f4a;
}
</style>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" style="text-align:center; padding:3rem 1rem;">
                    <div style="font-size:2rem; margin-bottom:.5rem;">🗒️</div>
                    No activity found. Try adjusting your filters.
                </td></tr>
            <?php endif; ?>
            <?php foreach ($logs as $log): ?>
                <?php [$badgeLabel, $badgeClass] = logBadge($log['category'], $log['description']); ?>
                <tr>
                    <td style="white-space:nowrap;"><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                    <td style="white-space:nowrap;">
                        <strong><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown user'); ?></strong><br>
                        <small><?php echo htmlspecialchars($log['username'] ?? ''); ?></small>
                    </td>
                    <td style="white-space:nowrap;"><?php echo htmlspecialchars(ucfirst($log['role'] ?? '—')); ?></td>
                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeLabel); ?></span></td>
                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php if ($totalPages > 1): ?>
    <div class="pagination" style="display:flex; justify-content:center; gap:.5rem; margin-top:1.5rem;">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>"
               class="btn <?php echo $p === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                <?php echo $p; ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

</div></div></div>
</body>
</html>