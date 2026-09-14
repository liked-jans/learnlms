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
</head>
<body>
<?php if ($flash): ?>
<div class="flash-msg flash-<?= $flash['type'] ?>" id="flashMsg">
    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $flash['msg'] ?>
    <button onclick="document.getElementById('flashMsg').remove()"><i class="fas fa-times"></i></button>
</div>
<?php endif; ?>
