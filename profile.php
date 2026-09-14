<?php
require_once 'includes/config.php';
requireLogin();
$pageTitle = 'My Profile';
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update') {
        $name=sanitize($_POST['full_name']); $email=sanitize($_POST['email']);
        $stmt=$conn->prepare("UPDATE users SET full_name=?,email=? WHERE id=?");
        $stmt->bind_param('ssi',$name,$email,$uid); $stmt->execute();
        $_SESSION['full_name'] = $name;
        setFlash('success','Profile updated.');
    } elseif ($action === 'password') {
        $old=$_POST['old_password']; $new=$_POST['new_password']; $conf=$_POST['confirm_password'];
        $user=$conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();
        if (!password_verify($old,$user['password'])) { setFlash('error','Current password is wrong.'); }
        elseif ($new !== $conf) { setFlash('error','Passwords do not match.'); }
        elseif (strlen($new) < 6) { setFlash('error','Password too short (min 6 chars).'); }
        else {
            $hash=password_hash($new,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param('si',$hash,$uid); $stmt->execute();
            setFlash('success','Password changed.');
        }
    }
    redirect(BASE_URL.'profile.php');
}
$user=$conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
?>
<?php require_once 'includes/header.php'; ?>
<div class="app-layout">
<?php require_once 'includes/sidebar.php'; ?>
<div class="main-content">
<?php require_once 'includes/topbar.php'; ?>
<div class="page-content">
<div class="page-header"><div class="page-header-left"><h2>My Profile</h2></div></div>
<div class="two-col">
<div class="card">
  <div class="card-header"><span class="card-title">Profile Information</span></div>
  <div class="card-body">
    <div style="text-align:center;margin-bottom:24px">
      <div style="width:80px;height:80px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:28px;font-weight:700;color:white"><?= strtoupper(substr($user['full_name'],0,2)) ?></div>
      <div style="margin-top:10px"><span class="badge <?= $user['role']==='admin'?'badge-red':($user['role']==='teacher'?'badge-blue':'badge-green') ?>"><?= ucfirst($user['role']) ?></span></div>
    </div>
    <form method="POST"><input type="hidden" name="action" value="update">
      <div class="form-group"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
      <div class="form-group"><label>Username</label><input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled></div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
  </div>
</div>
<div class="card">
  <div class="card-header"><span class="card-title">Change Password</span></div>
  <div class="card-body">
    <form method="POST"><input type="hidden" name="action" value="password">
      <div class="form-group"><label>Current Password</label><input type="password" name="old_password" class="form-control" required></div>
      <div class="form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
      <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
      <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Change Password</button>
    </form>
  </div>
</div>
</div>
</div></div></div>
</body></html>
