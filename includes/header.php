<?php
$flash = getFlash();
$currentUser = null;
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $r = $conn->query("SELECT * FROM users WHERE id=$uid");
    $currentUser = $r->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? $pageTitle . ' | ' : '' ?><?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
window.showWeeklyLockAlert = function(msg) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Syllabus Sequence Required',
            html: '<div style="font-size:14.5px;color:#334155;margin-top:8px;line-height:1.55;font-weight:500">' + msg + '</div>',
            confirmButtonColor: '#2563eb',
            confirmButtonText: '<i class="fas fa-check" style="margin-right:6px"></i> Understood',
            customClass: {
                popup: 'swal2-modern-modal'
            }
        });
    } else {
        alert(msg);
    }
};
</script>
</head>
<body>
<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $flash['msg'] ?>
    <button onclick="document.getElementById('flashMsg').remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>
